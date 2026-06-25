<?php
/**
 * Ruft Seiten ab und erkennt externe Anfragen im gerenderten HTML.
 *
 * @package Kipphard\Dsgvo_Webfonts
 */

namespace Kipphard\Dsgvo_Webfonts;

defined( 'ABSPATH' ) || exit;

/**
 * HTTP-Fetcher und HTML-Parser, der externe Ressourcen-Anfragen aggregiert.
 */
class Scanner {

	/**
	 * Scannt eine Liste von URLs und gibt einen Bericht zurück.
	 *
	 * @param string[] $urls Zu scannende URLs.
	 * @return array<string,mixed>
	 */
	public function scan( array $urls ) {
		$scanned_urls = array();
		$findings     = array();
		$home_host    = wp_parse_url( home_url(), PHP_URL_HOST );

		foreach ( $urls as $url ) {
			// SSRF-Schutz: nur URLs des eigenen Hosts scannen.
			$url_host = wp_parse_url( $url, PHP_URL_HOST );
			if ( empty( $url_host ) || $url_host !== $home_host ) {
				continue;
			}

			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 15,
					'redirection' => 3,
					'sslverify'   => true,
					'user-agent'  => 'dsgvo-webfonts/' . DWF_VERSION,
				)
			);

			if ( is_wp_error( $response ) ) {
				// Fehler protokollieren, Scan aber fortsetzen.
				error_log( 'DWF Scanner: Fehler beim Abrufen von ' . $url . ': ' . $response->get_error_message() );
				continue;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( 200 !== $code ) {
				error_log( 'DWF Scanner: HTTP ' . $code . ' für ' . $url );
				continue;
			}

			$body = wp_remote_retrieve_body( $response );
			if ( empty( $body ) ) {
				continue;
			}

			$external_urls = $this->extract_external_urls( $body, $home_host );

			foreach ( $external_urls as $ext_url ) {
				$service_key = Helpers::classify_url( $ext_url );
				$ext_host    = (string) wp_parse_url( $ext_url, PHP_URL_HOST );

				if ( ! isset( $findings[ $service_key ] ) ) {
					if ( 'other' === $service_key ) {
						$findings[ $service_key ] = array(
							'service'  => 'other',
							'label'    => $ext_host,
							'category' => 'other',
							'risk'     => 'medium',
							'hint'     => __( 'Externe Anfrage prüfen und – falls personenbezogene Daten übertragen werden – lokal einbinden oder entfernen.', 'dsgvo-webfonts' ),
							'count'    => 0,
							'hosts'    => array(),
							'samples'  => array(),
						);
					} else {
						$services                 = Helpers::known_services();
						$def                      = $services[ $service_key ];
						$findings[ $service_key ] = array(
							'service'  => $service_key,
							'label'    => $def['label'],
							'category' => $def['category'],
							'risk'     => $def['risk'],
							'hint'     => $def['hint'],
							'count'    => 0,
							'hosts'    => array(),
							'samples'  => array(),
						);
					}
				}

				$findings[ $service_key ]['count']++;

				if ( ! in_array( $ext_host, $findings[ $service_key ]['hosts'], true ) ) {
					$findings[ $service_key ]['hosts'][] = $ext_host;
				}

				if ( count( $findings[ $service_key ]['samples'] ) < 5 ) {
					if ( ! in_array( $ext_url, $findings[ $service_key ]['samples'], true ) ) {
						$findings[ $service_key ]['samples'][] = $ext_url;
					}
				}
			}

			$scanned_urls[] = $url;
		}

		// Sortierung: risk_weight absteigend, dann count absteigend.
		$sorted = array_values( $findings );
		usort(
			$sorted,
			static function ( $a, $b ) {
				$wa = Helpers::risk_weight( $a['risk'] );
				$wb = Helpers::risk_weight( $b['risk'] );
				if ( $wa !== $wb ) {
					return $wb - $wa;
				}
				return $b['count'] - $a['count'];
			}
		);

		// Zählungen nach Risiko-Level.
		$counts = array( 'high' => 0, 'medium' => 0, 'low' => 0 );
		foreach ( $sorted as $finding ) {
			$risk = $finding['risk'];
			if ( isset( $counts[ $risk ] ) ) {
				$counts[ $risk ]++;
			}
		}

		$total_requests = 0;
		foreach ( $sorted as $finding ) {
			$total_requests += (int) $finding['count'];
		}

		return array(
			'scanned_at'      => time(),
			'urls'            => $scanned_urls,
			'findings'        => $sorted,
			'counts'          => $counts,
			'total_services'  => count( $sorted ),
			'total_requests'  => $total_requests,
		);
	}

	/**
	 * Extrahiert alle externen Ressourcen-URLs aus dem HTML-Body.
	 *
	 * @param string $body      HTML-Quelltext.
	 * @param string $home_host Eigener Hostname (SSRF-Filter).
	 * @return string[]
	 */
	private function extract_external_urls( $body, $home_host ) {
		$found = array();
		$dom   = self::parse_html( $body );
		$xpath = new \DOMXPath( $dom );

		// Ressourcen-Attribute aus bekannten Knoten sammeln.
		$expressions = array(
			'//link[@rel="preconnect" or @rel="dns-prefetch"]/@href',
			'//link[not(@rel="preconnect") and not(@rel="dns-prefetch")]/@href',
			'//script/@src',
			'//img/@src',
			'//iframe/@src',
			'//source/@src',
			'//embed/@src',
		);

		foreach ( $expressions as $expr ) {
			$nodes = $xpath->query( $expr );
			if ( false === $nodes ) {
				continue;
			}
			foreach ( $nodes as $node ) {
				$raw = trim( $node->nodeValue );
				if ( '' === $raw ) {
					continue;
				}
				// Protokoll-relative URLs normalisieren.
				if ( 0 === strpos( $raw, '//' ) ) {
					$raw = 'https:' . $raw;
				}
				if ( 0 !== strpos( $raw, 'http' ) ) {
					continue;
				}
				$host = (string) wp_parse_url( $raw, PHP_URL_HOST );
				if ( '' === $host || $host === $home_host ) {
					continue;
				}
				$found[] = $raw;
			}
		}

		// Inline-<style>-Blöcke und style-Attribute auf @import / url() prüfen.
		$inline_css = '';

		$style_nodes = $xpath->query( '//style' );
		if ( false !== $style_nodes ) {
			foreach ( $style_nodes as $node ) {
				$inline_css .= ' ' . $node->textContent;
			}
		}

		$style_attrs = $xpath->query( '//*/@style' );
		if ( false !== $style_attrs ) {
			foreach ( $style_attrs as $attr ) {
				$inline_css .= ' ' . $attr->nodeValue;
			}
		}

		if ( '' !== $inline_css ) {
			preg_match_all( '#https?://[^\s\'")\\\]+#', $inline_css, $matches );
			foreach ( $matches[0] as $css_url ) {
				$host = (string) wp_parse_url( $css_url, PHP_URL_HOST );
				if ( '' === $host || $host === $home_host ) {
					continue;
				}
				$found[] = $css_url;
			}
		}

		return $found;
	}

	/**
	 * Standard-URL-Liste: Startseite + bis zu $limit-1 zuletzt veröffentlichte Seiten/Beiträge.
	 *
	 * @param int $limit Maximale Anzahl URLs (inkl. Startseite).
	 * @return string[]
	 */
	public static function default_urls( $limit = 5 ) {
		$urls = array( home_url( '/' ) );

		$posts = get_posts(
			array(
				'post_type'   => array( 'page', 'post' ),
				'post_status' => 'publish',
				'numberposts' => $limit - 1,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'fields'      => 'ids',
			)
		);

		foreach ( $posts as $post_id ) {
			$permalink = get_permalink( $post_id );
			if ( $permalink && ! in_array( $permalink, $urls, true ) ) {
				$urls[] = $permalink;
			}
		}

		return $urls;
	}

	/**
	 * Vollständige URL-Liste: Startseite + alle veröffentlichten Seiten/Beiträge bis $limit.
	 * Nur URLs des eigenen Hosts (SSRF-Schutz).
	 *
	 * @param int $limit Maximale Anzahl URLs (inkl. Startseite).
	 * @return string[]
	 */
	public static function all_urls( $limit = 200 ) {
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$urls      = array( home_url( '/' ) );

		$posts = get_posts(
			array(
				'post_type'   => array( 'page', 'post' ),
				'post_status' => 'publish',
				'numberposts' => $limit - 1,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'fields'      => 'ids',
			)
		);

		foreach ( $posts as $post_id ) {
			$permalink = get_permalink( $post_id );
			if ( ! $permalink ) {
				continue;
			}
			$url_host = wp_parse_url( $permalink, PHP_URL_HOST );
			if ( empty( $url_host ) || $url_host !== $home_host ) {
				continue;
			}
			if ( ! in_array( $permalink, $urls, true ) ) {
				$urls[] = $permalink;
			}
		}

		return $urls;
	}

	/**
	 * Parst einen HTML-String in ein DOMDocument.
	 *
	 * @param string $html Roher HTML-Body.
	 * @return \DOMDocument
	 */
	private static function parse_html( $html ) {
		libxml_use_internal_errors( true );
		$dom = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		libxml_clear_errors();
		return $dom;
	}
}
