<?php
/**
 * Front-End-DSGVO-Fixes: Google Fonts entfernen, Emojis deaktivieren, Gravatar lokal ersetzen.
 *
 * @package Kipphard\Dsgvo_Webfonts
 */

namespace Kipphard\Dsgvo_Webfonts;

defined( 'ABSPATH' ) || exit;

/**
 * Registriert Front-End-Hooks zum Entfernen datenschutzrelevanter externer Anfragen.
 */
class Fixes {

	/**
	 * Aktuelle Plugin-Einstellungen.
	 *
	 * @var array<string,bool>
	 */
	private $settings = array();

	/**
	 * Hooks anhand der gespeicherten Einstellungen registrieren.
	 */
	public function hooks() {
		$this->settings = Helpers::get_settings();

		if ( $this->settings['remove_google_fonts'] ) {
			add_filter( 'wp_resource_hints', array( $this, 'remove_google_font_hints' ), 10, 2 );
			add_filter( 'style_loader_tag', array( $this, 'suppress_google_font_tag' ), 10, 2 );
			add_action( 'template_redirect', array( $this, 'start_font_buffer' ) );
		}

		if ( $this->settings['disable_emojis'] ) {
			$this->disable_emoji_hooks();
		}

		if ( $this->settings['disable_gravatar'] ) {
			add_filter( 'pre_get_avatar_data', array( $this, 'replace_gravatar' ), 10, 1 );
		}
	}

	// -------------------------------------------------------------------------
	// Google-Fonts entfernen
	// -------------------------------------------------------------------------

	/**
	 * Entfernt Google-Fonts-Preconnect- und DNS-Prefetch-Hinweise aus dem <head>.
	 *
	 * @param array<int,mixed> $hints   Bestehende Ressource-Hints.
	 * @param string           $relation_type Typ des Hints (preconnect, dns-prefetch, …).
	 * @return array<int,mixed>
	 */
	public function remove_google_font_hints( $hints, $relation_type ) {
		if ( ! in_array( $relation_type, array( 'preconnect', 'dns-prefetch' ), true ) ) {
			return $hints;
		}
		$blocked = array( 'fonts.googleapis.com', 'fonts.gstatic.com' );
		foreach ( $hints as $key => $hint ) {
			$href = is_array( $hint ) ? ( isset( $hint['href'] ) ? $hint['href'] : '' ) : $hint;
			foreach ( $blocked as $pattern ) {
				if ( false !== strpos( $href, $pattern ) ) {
					unset( $hints[ $key ] );
					break;
				}
			}
		}
		return array_values( $hints );
	}

	/**
	 * Unterdrückt <link>-Tags für Google-Fonts-Stylesheets aus der WP-Stylesheet-Queue.
	 *
	 * @param string $tag    Generierter HTML-Tag.
	 * @param string $handle Handle des Stylesheets.
	 * @return string Leerer String wenn Google Fonts, sonst unveränderter Tag.
	 */
	public function suppress_google_font_tag( $tag, $handle ) {
		if ( false !== strpos( $tag, 'fonts.googleapis.com' ) ) {
			return '';
		}
		return $tag;
	}

	/**
	 * Startet Output-Buffering für die Google-Fonts-Filterung (nur Front-End).
	 */
	public function start_font_buffer() {
		if ( is_admin() ) {
			return;
		}
		ob_start( array( $this, 'filter_buffer' ) );
	}

	/**
	 * Entfernt Google-Fonts-Referenzen aus dem gerenderten HTML-Buffer.
	 *
	 * @param string $html Gerenderte HTML-Seite.
	 * @return string Bereinigtes HTML.
	 */
	public function filter_buffer( $html ) {
		// <link ... href="...fonts.googleapis.com..."> entfernen.
		$html = preg_replace(
			'#<link[^>]+href=["\'][^"\']*fonts\.googleapis\.com[^"\']*["\'][^>]*/?\s*>#i',
			'',
			$html
		);

		// @import url(...googleapis...) aus <style>-Blöcken entfernen.
		$html = preg_replace(
			'#@import\s+url\(["\']?[^"\'()]*fonts\.googleapis\.com[^"\'()]*["\']?\)[^;]*;#i',
			'',
			$html
		);

		return $html;
	}

	// -------------------------------------------------------------------------
	// WordPress-Emojis deaktivieren
	// -------------------------------------------------------------------------

	/**
	 * Entfernt alle Emoji-Skripte, -Stile und -Hooks von WordPress.
	 */
	private function disable_emoji_hooks() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', array( $this, 'remove_tinymce_emoji' ) );
		add_filter( 'wp_resource_hints', array( $this, 'remove_emoji_dns_prefetch' ), 10, 2 );
	}

	/**
	 * Entfernt das wpemoji-Plugin aus TinyMCE.
	 *
	 * @param array<int,string> $plugins Liste der TinyMCE-Plugins.
	 * @return array<int,string>
	 */
	public function remove_tinymce_emoji( $plugins ) {
		return array_values( array_diff( $plugins, array( 'wpemoji' ) ) );
	}

	/**
	 * Entfernt den s.w.org DNS-Prefetch-Eintrag.
	 *
	 * @param array<int,mixed> $hints         Bestehende Ressource-Hints.
	 * @param string           $relation_type Typ des Hints.
	 * @return array<int,mixed>
	 */
	public function remove_emoji_dns_prefetch( $hints, $relation_type ) {
		if ( 'dns-prefetch' !== $relation_type ) {
			return $hints;
		}
		foreach ( $hints as $key => $hint ) {
			$href = is_array( $hint ) ? ( isset( $hint['href'] ) ? $hint['href'] : '' ) : $hint;
			if ( false !== strpos( $href, 's.w.org' ) ) {
				unset( $hints[ $key ] );
			}
		}
		return array_values( $hints );
	}

	// -------------------------------------------------------------------------
	// Gravatar lokal ersetzen
	// -------------------------------------------------------------------------

	/**
	 * Ersetzt Gravatar-URLs durch einen lokalen SVG-Platzhalter (keine externe Anfrage).
	 *
	 * @param array<string,mixed> $args Avatar-Argumente.
	 * @return array<string,mixed>
	 */
	public function replace_gravatar( $args ) {
		$args['url']          = self::local_avatar_url();
		$args['found_avatar'] = true;
		return $args;
	}

	/**
	 * Gibt einen Data-URI-SVG-Avatar zurück (neutraler grauer Kreis mit Silhouette).
	 * Keine externe Anfrage, keine gebündelte Datei erforderlich.
	 *
	 * @return string Data-URI-URL des SVG-Avatars.
	 */
	private static function local_avatar_url() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
			. '<circle cx="50" cy="50" r="50" fill="#c8cbcf"/>'
			. '<circle cx="50" cy="38" r="18" fill="#fff"/>'
			. '<ellipse cx="50" cy="90" rx="30" ry="24" fill="#fff"/>'
			. '</svg>';
		return 'data:image/svg+xml;charset=utf-8,' . rawurlencode( $svg );
	}
}
