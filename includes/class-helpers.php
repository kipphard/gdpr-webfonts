<?php
/**
 * Gemeinsame Hilfsmethoden: Capability-Prüfung, Dienst-Klassifikation, Einstellungen.
 *
 * @package Kipphard\Dsgvo_Webfonts
 */

namespace Kipphard\Dsgvo_Webfonts;

defined( 'ABSPATH' ) || exit;

/**
 * Statische Hilfsmethoden, die im gesamten Plugin genutzt werden.
 */
class Helpers {

	/** Berechtigung für alle Admin-Aktionen. */
	const CAP = 'manage_options';

	/** Option-Key für die Plugin-Einstellungen. */
	const OPT_SETTINGS = 'dwf_settings';

	/** Option-Key für den letzten Scan-Bericht. */
	const OPT_LAST_REPORT = 'dwf_last_report';

	/** Maximale Anzahl gescannter URLs in der Free-Version. */
	const MAX_FREE_URLS = 5;

	/**
	 * Prüft ob die Pro-Lizenz aktiv ist.
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return (bool) apply_filters( 'dwf_is_pro', defined( 'DWF_PRO' ) && DWF_PRO );
	}

	/**
	 * Sichert einen Admin-POST-Request ab: Berechtigung + Nonce. Bricht bei Fehler ab.
	 *
	 * @param string $action Nonce-Aktion.
	 * @param string $field  Name des Nonce-Feldes.
	 */
	public static function guard_post( $action, $field = '_wpnonce' ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'dsgvo-webfonts' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action, $field );
	}

	/**
	 * Standard-Einstellungen des Plugins.
	 *
	 * @return array<string,bool>
	 */
	public static function default_settings() {
		return array(
			'remove_google_fonts' => false,
			'disable_emojis'      => false,
			'disable_gravatar'    => false,
		);
	}

	/**
	 * Aktuelle Einstellungen aus der Datenbank, mit Defaults zusammengeführt.
	 *
	 * @return array<string,bool>
	 */
	public static function get_settings() {
		$saved = wp_parse_args(
			(array) get_option( self::OPT_SETTINGS, array() ),
			self::default_settings()
		);
		return array(
			'remove_google_fonts' => (bool) $saved['remove_google_fonts'],
			'disable_emojis'      => (bool) $saved['disable_emojis'],
			'disable_gravatar'    => (bool) $saved['disable_gravatar'],
		);
	}

	/**
	 * Bereinigt die Einstellungen aus dem POST-Array (Checkboxen).
	 *
	 * @param array<string,mixed> $raw Rohe $_POST-Daten.
	 * @return array<string,bool>
	 */
	public static function sanitize_settings( array $raw ) {
		return array(
			'remove_google_fonts' => ! empty( $raw['remove_google_fonts'] ),
			'disable_emojis'      => ! empty( $raw['disable_emojis'] ),
			'disable_gravatar'    => ! empty( $raw['disable_gravatar'] ),
		);
	}

	/**
	 * Bekannte externe Dienste mit Klassifikations-Metadaten.
	 * Jeder Eintrag enthält: label, category, risk, patterns (Teilstring-Matching), hint.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function known_services() {
		return array(
			'google_fonts'      => array(
				'label'    => __( 'Google Fonts', 'dsgvo-webfonts' ),
				'category' => 'fonts',
				'risk'     => 'high',
				'patterns' => array( 'fonts.googleapis.com', 'fonts.gstatic.com' ),
				'hint'     => __( 'Schriften lokal hosten oder entfernen. (LG München I, Az. 3 O 17493/20)', 'dsgvo-webfonts' ),
			),
			'google_analytics'  => array(
				'label'    => __( 'Google Analytics / GA4', 'dsgvo-webfonts' ),
				'category' => 'analytics',
				'risk'     => 'high',
				'patterns' => array( 'google-analytics.com', 'analytics.google.com', 'googletagmanager.com/gtag' ),
				'hint'     => __( 'Erst nach Einwilligung laden oder cookielose Alternative (z. B. Matomo, Plausible)', 'dsgvo-webfonts' ),
			),
			'google_tag_manager' => array(
				'label'    => __( 'Google Tag Manager', 'dsgvo-webfonts' ),
				'category' => 'analytics',
				'risk'     => 'high',
				'patterns' => array( 'googletagmanager.com' ),
				'hint'     => __( 'Erst nach Einwilligung laden', 'dsgvo-webfonts' ),
			),
			'google_ads'        => array(
				'label'    => __( 'Google Ads / DoubleClick', 'dsgvo-webfonts' ),
				'category' => 'analytics',
				'risk'     => 'high',
				'patterns' => array( 'googlesyndication.com', 'doubleclick.net', 'googleadservices.com' ),
				'hint'     => __( 'Erst nach Einwilligung laden', 'dsgvo-webfonts' ),
			),
			'youtube'           => array(
				'label'    => __( 'YouTube', 'dsgvo-webfonts' ),
				'category' => 'embed',
				'risk'     => 'high',
				'patterns' => array( 'youtube.com/embed', 'youtu.be', 'i.ytimg.com', 'youtube.com' ),
				'hint'     => __( 'youtube-nocookie.com nutzen oder Klick-zum-Laden-Platzhalter', 'dsgvo-webfonts' ),
			),
			'vimeo'             => array(
				'label'    => __( 'Vimeo', 'dsgvo-webfonts' ),
				'category' => 'embed',
				'risk'     => 'medium',
				'patterns' => array( 'player.vimeo.com', 'vimeo.com' ),
				'hint'     => __( 'dnt=1 setzen oder Platzhalter', 'dsgvo-webfonts' ),
			),
			'google_maps'       => array(
				'label'    => __( 'Google Maps', 'dsgvo-webfonts' ),
				'category' => 'maps',
				'risk'     => 'high',
				'patterns' => array( 'maps.googleapis.com', 'maps.google.com', 'google.com/maps' ),
				'hint'     => __( 'Statische Karte oder Platzhalter mit Einwilligung', 'dsgvo-webfonts' ),
			),
			'gravatar'          => array(
				'label'    => __( 'Gravatar', 'dsgvo-webfonts' ),
				'category' => 'avatar',
				'risk'     => 'medium',
				'patterns' => array( 'gravatar.com' ),
				'hint'     => __( 'Lokale Standard-Avatare verwenden', 'dsgvo-webfonts' ),
			),
			'recaptcha'         => array(
				'label'    => __( 'Google reCAPTCHA', 'dsgvo-webfonts' ),
				'category' => 'captcha',
				'risk'     => 'high',
				'patterns' => array( 'google.com/recaptcha', 'recaptcha.net', 'gstatic.com/recaptcha' ),
				'hint'     => __( 'hCaptcha / Friendly Captcha oder erst nach Einwilligung', 'dsgvo-webfonts' ),
			),
			'facebook'          => array(
				'label'    => __( 'Facebook / Meta Pixel', 'dsgvo-webfonts' ),
				'category' => 'social',
				'risk'     => 'high',
				'patterns' => array( 'connect.facebook.net', 'facebook.com/tr' ),
				'hint'     => __( 'Erst nach Einwilligung laden', 'dsgvo-webfonts' ),
			),
			'hotjar'            => array(
				'label'    => __( 'Hotjar', 'dsgvo-webfonts' ),
				'category' => 'analytics',
				'risk'     => 'high',
				'patterns' => array( 'hotjar.com', 'hotjar.io' ),
				'hint'     => __( 'Erst nach Einwilligung laden', 'dsgvo-webfonts' ),
			),
			'fontawesome'       => array(
				'label'    => __( 'Font Awesome (Icon-CDN)', 'dsgvo-webfonts' ),
				'category' => 'fonts',
				'risk'     => 'medium',
				'patterns' => array( 'use.fontawesome.com', 'kit.fontawesome.com' ),
				'hint'     => __( 'Icons lokal hosten', 'dsgvo-webfonts' ),
			),
			'js_cdn'            => array(
				'label'    => __( 'JavaScript-CDN (jQuery, cdnjs, jsDelivr, unpkg)', 'dsgvo-webfonts' ),
				'category' => 'cdn',
				'risk'     => 'medium',
				'patterns' => array( 'code.jquery.com', 'cdnjs.cloudflare.com', 'cdn.jsdelivr.net', 'unpkg.com' ),
				'hint'     => __( 'Bibliotheken lokal ausliefern', 'dsgvo-webfonts' ),
			),
			'wp_emoji'          => array(
				'label'    => __( 'WordPress-Emojis (s.w.org)', 'dsgvo-webfonts' ),
				'category' => 'cdn',
				'risk'     => 'low',
				'patterns' => array( 's.w.org/images/core/emoji' ),
				'hint'     => __( 'WordPress-Emoji-Skript deaktivieren', 'dsgvo-webfonts' ),
			),
		);
	}

	/**
	 * Klassifiziert eine externe URL anhand der bekannten Dienste.
	 *
	 * @param string $url Zu prüfende URL.
	 * @return string Service-Key oder 'other'.
	 */
	public static function classify_url( $url ) {
		$lower    = strtolower( $url );
		$services = self::known_services();
		foreach ( $services as $key => $service ) {
			foreach ( $service['patterns'] as $pattern ) {
				if ( false !== strpos( $lower, $pattern ) ) {
					return $key;
				}
			}
		}
		return 'other';
	}

	/**
	 * Gewichtung eines Risiko-Levels für die Sortierung.
	 *
	 * @param string $risk 'high'|'medium'|'low'.
	 * @return int
	 */
	public static function risk_weight( $risk ) {
		$weights = array(
			'high'   => 3,
			'medium' => 2,
			'low'    => 1,
		);
		return isset( $weights[ $risk ] ) ? $weights[ $risk ] : 1;
	}

	/**
	 * Übersetzte Bezeichnung eines Risiko-Levels.
	 *
	 * @param string $risk 'high'|'medium'|'low'.
	 * @return string
	 */
	public static function risk_label( $risk ) {
		$labels = array(
			'high'   => __( 'Hoch', 'dsgvo-webfonts' ),
			'medium' => __( 'Mittel', 'dsgvo-webfonts' ),
			'low'    => __( 'Niedrig', 'dsgvo-webfonts' ),
		);
		return isset( $labels[ $risk ] ) ? $labels[ $risk ] : $risk;
	}

	/**
	 * Übersetzte Bezeichnung einer Kategorie.
	 *
	 * @param string $cat Kategorie-Key.
	 * @return string
	 */
	public static function category_label( $cat ) {
		$labels = array(
			'fonts'     => __( 'Schriften', 'dsgvo-webfonts' ),
			'analytics' => __( 'Analyse / Tracking', 'dsgvo-webfonts' ),
			'embed'     => __( 'Einbettung', 'dsgvo-webfonts' ),
			'maps'      => __( 'Karten', 'dsgvo-webfonts' ),
			'avatar'    => __( 'Avatar', 'dsgvo-webfonts' ),
			'captcha'   => __( 'CAPTCHA', 'dsgvo-webfonts' ),
			'social'    => __( 'Social Media', 'dsgvo-webfonts' ),
			'cdn'       => __( 'CDN / Skripte', 'dsgvo-webfonts' ),
			'other'     => __( 'Sonstiges', 'dsgvo-webfonts' ),
		);
		return isset( $labels[ $cat ] ) ? $labels[ $cat ] : $cat;
	}
}
