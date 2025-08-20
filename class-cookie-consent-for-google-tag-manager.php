<?php
/**
 * Cookie Consent for Google Tag Manager
 *
 * @author            LifterLMS Expert
 * @copyright         2025 LifterLMS Expert
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Cookie Consent for Google Tag Manager
 * Plugin URI:        https://lifterlmsexpert.com/
 * Description:       Cookie Consent for Google Tag Manager helps you stay compliant with privacy regulations like GDPR by managing how and when tracking scripts are loaded. The plugin blocks Google Tag Manager (GTM), Google Analytics (GA4), and other tracking codes until the user gives consent.
 * Version:           1.0.0
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            LifterLMS Expert
 * Author URI:        https://profiles.wordpress.org/lifterlmsexpert/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cookie-consent-for-google-tag-manager
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants
define( 'Cookie_Consent_for_Google_Tag_Manager_VERSION', '1.0.0' );
define( 'Cookie_Consent_for_Google_Tag_Manager_DIR', plugin_dir_path( __FILE__ ) );
define( 'Cookie_Consent_for_Google_Tag_Manager_URL', plugin_dir_url( __FILE__ ) );

// Required Files
require_once Cookie_Consent_for_Google_Tag_Manager_DIR . 'admin/admin-page.php';
require_once Cookie_Consent_for_Google_Tag_Manager_DIR . 'includes/class-cookie-consent-for-google-tag-manager-loader.php';
require_once Cookie_Consent_for_Google_Tag_Manager_DIR . 'includes/class-cookie-consent-for-google-tag-manager.php';
require_once Cookie_Consent_for_Google_Tag_Manager_DIR . 'includes/class-cookie-consent-for-google-tag-manager-database.php';
require_once Cookie_Consent_for_Google_Tag_Manager_DIR . 'includes/class-initial-traffic-source.php'; 

// Run plugin
function run_cookie_consent_for_google_tag_manager() {
    $plugin = new Cookie_Consent_for_Google_Tag_Manager();
    $plugin->run();
    if ( get_option('cookie_consent_for_google_tag_manager_enable_initial_traffic_source', false) ) {
        new GTM_Initial_Traffic_Source();
    }
}
run_cookie_consent_for_google_tag_manager();

// Activation Hook
register_activation_hook(__FILE__, 'cookie_consent_for_google_tag_manager_activate');
function cookie_consent_for_google_tag_manager_activate() {
    Cookie_Consent_for_Google_Tag_Manager_Database::create_tables();
}