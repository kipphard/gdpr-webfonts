<?php
/**
 * Plugin-Bootstrap: Hooks, Admin-UI und Fixes registrieren.
 *
 * @package Kipphard\Dsgvo_Webfonts
 */

namespace Kipphard\Dsgvo_Webfonts;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton-Einstiegspunkt.
 */
final class Plugin {

	/** @var Plugin|null */
	private static $instance = null;

	/**
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private Konstruktor (Singleton).
	 */
	private function __construct() {}

	/**
	 * Aktivierung: Standard-Einstellungen anlegen, falls noch nicht vorhanden.
	 */
	public static function activate() {
		if ( false === get_option( Helpers::OPT_SETTINGS, false ) ) {
			add_option( Helpers::OPT_SETTINGS, Helpers::default_settings() );
		}
	}

	/**
	 * Deaktivierung: Cron-Zeitplan entfernen (nur wenn Pro-Klasse vorhanden).
	 */
	public static function deactivate() {
		if ( class_exists( __NAMESPACE__ . '\\Pro' ) ) {
			Pro::clear_schedule();
		}
	}

	/**
	 * Laufzeit-Hooks registrieren.
	 */
	public function boot() {
		load_plugin_textdomain(
			'dsgvo-webfonts',
			false,
			dirname( plugin_basename( DWF_FILE ) ) . '/languages'
		);

		// Front-End-Fixes immer registrieren; die Klasse prüft selbst die Einstellungen.
		( new Fixes() )->hooks();

		// Pro-only: nur laden wenn die (Premium-)Klasse im Build vorhanden ist.
		// Der freie Build (öffentliches Repo / WP.org) enthält class-pro.php nicht.
		if ( class_exists( __NAMESPACE__ . '\\Pro' ) ) {
			( new Pro() )->hooks();
		}

		if ( is_admin() ) {
			( new Admin() )->hooks();
		}
	}
}
