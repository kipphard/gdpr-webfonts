<?php
/**
 * Plugin-Deinstallationsroutine: entfernt alle Plugin-Optionen aus der Datenbank.
 *
 * @package Kipphard\Dsgvo_Webfonts
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'dwf_settings' );
delete_option( 'dwf_last_report' );
wp_clear_scheduled_hook( 'dwf_scheduled_scan' );
