<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Cookie_Consent_for_Google_Tag_Manager
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'cookie_consent_for_google_tag_manager_settings' );
delete_option( 'cookie_consent_for_google_tag_manager_enable_initial_traffic_source' );

// Drop custom tables
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cookie_consent_logs" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cookie_settings" );
