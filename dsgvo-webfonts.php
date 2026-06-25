<?php
/**
 * Plugin Name:       DSGVO Webfonts & externe Anfragen
 * Plugin URI:        https://products.kipphard.com/dsgvo-webfonts
 * Description:       Findet externe Anfragen (Google Fonts, YouTube, Google Analytics, Maps, Gravatar, reCAPTCHA …), die personenbezogene Daten an Dritte übertragen, und hilft sie DSGVO-konform zu entfernen oder lokal einzubinden. Ehrlicher Scan – kein Cookie-Banner-Overlay.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            André Kipphard
 * Author URI:        https://kipphard.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dsgvo-webfonts
 * Domain Path:       /languages
 *
 * @package Kipphard\Dsgvo_Webfonts
 */

defined( 'ABSPATH' ) || exit;

define( 'DWF_VERSION', '0.1.0' );
define( 'DWF_FILE', __FILE__ );
define( 'DWF_DIR', plugin_dir_path( __FILE__ ) );
define( 'DWF_URL', plugin_dir_url( __FILE__ ) );
define( 'DWF_SLUG', 'dsgvo-webfonts' );

/**
 * Minimaler PSR-4-Autoloader für den Namespace Kipphard\Dsgvo_Webfonts\.
 * Kipphard\Dsgvo_Webfonts\Foo_Bar -> includes/class-foo-bar.php
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Kipphard\\Dsgvo_Webfonts\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
		$path     = DWF_DIR . 'includes/' . $file;
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, array( '\Kipphard\Dsgvo_Webfonts\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\Kipphard\Dsgvo_Webfonts\Plugin', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\Kipphard\Dsgvo_Webfonts\Plugin::instance()->boot();
	}
);
