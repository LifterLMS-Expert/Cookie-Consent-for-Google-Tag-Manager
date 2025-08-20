<?php
/**
 * Admin page for Cookie Consent for Google Tag Manager.
 *
 * @package Cookie_Consent_for_Google_Tag_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Admin page class
 */
class Cookie_Consent_for_Google_Tag_Manager_Admin {
    private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Add action to enqueue admin styles
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
	}

	/**
	 * Enqueue admin-specific styles and scripts
	 */
	public function enqueue_admin_styles($hook) {
		// Only load on plugin pages
		if (strpos($hook, 'cookie-consent-for-google-tag-manager') === false) {
			return;
		}

		// Enqueue WordPress built-in styles
		wp_enqueue_style('wp-admin');
		wp_enqueue_style('dashicons');

		// Enqueue our custom admin styles
		wp_enqueue_style(
			$this->plugin_name . '-admin',
			plugin_dir_url(__FILE__) . 'css/admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Verify nonce for admin actions
	 *
	 * @param string $nonce_name Nonce name.
	 * @return bool
	 */
	private function verify_nonce($nonce_name) {
		$nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
		return wp_verify_nonce($nonce, $nonce_name);
	}

	/**
	 * Add options page to the admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_plugin_admin_menu() {
		// Add main menu item
		add_menu_page(
			__( 'Cookie Consent for Google Tag Manager', 'cookie-consent-for-google-tag-manager' ),
			__( 'Cookie Consent for Google Tag Manager', 'cookie-consent-for-google-tag-manager' ),
			'manage_options',
			'cookie-consent-for-google-tag-manager',
			array( $this, 'display_user_data_page' ),
			'dashicons-shield',
			30
		);

		// Add sub menu items
		add_submenu_page(
			'cookie-consent-for-google-tag-manager',
			__( 'User Data', 'cookie-consent-for-google-tag-manager' ),
			__( 'User Data', 'cookie-consent-for-google-tag-manager' ),
			'manage_options',
			'cookie-consent-for-google-tag-manager',
			array( $this, 'display_user_data_page' )
		);

		add_submenu_page(
			'cookie-consent-for-google-tag-manager',
			__( 'Settings', 'cookie-consent-for-google-tag-manager' ),
			__( 'Settings', 'cookie-consent-for-google-tag-manager' ),
			'manage_options',
			'cookie-consent-for-google-tag-manager-settings',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Display user data page with statistics and consent logs
	 */
	public function display_user_data_page() {
        // Check if required class exists
        if (!class_exists('Cookie_Consent_for_Google_Tag_Manager_Database')) {
            echo '<div class="error"><p>' . esc_html__('Database class not found. Please check plugin installation.', 'cookie-consent-for-google-tag-manager') . '</p></div>';
            return;
        }

        // Include WP_List_Table
        if (!class_exists('Consent_Logs_List_Table')) {
            $list_table_path = plugin_dir_path(__FILE__) . 'class-consent-logs-list-table.php';
            if (file_exists($list_table_path)) {
                require_once $list_table_path;
            } else {
                echo '<div class="error"><p>' . esc_html__('List table class file not found.', 'cookie-consent-for-google-tag-manager') . '</p></div>';
                return;
            }
        }

        // Get statistics
        $stats = Cookie_Consent_for_Google_Tag_Manager_Database::get_consent_statistics();

        // Create an instance of our table class
        $consent_table = new Consent_Logs_List_Table();
        $consent_table->prepare_items();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Cookie Consent Data', 'cookie-consent-for-google-tag-manager'); ?></h1>
            
            <!-- Statistics Dashboard -->
            <div class="consent-stats-wrapper">
                <div class="consent-stat-box">
                    <span class="dashicons dashicons-groups"></span>
                    <h3><?php esc_html_e('Total Visitors', 'cookie-consent-for-google-tag-manager'); ?></h3>
                    <span class="stat-number"><?php echo esc_html($stats['total_visitors']); ?></span>
                </div>
                
                <div class="consent-stat-box">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <h3><?php esc_html_e('Accepted', 'cookie-consent-for-google-tag-manager'); ?></h3>
                    <span class="stat-number"><?php echo esc_html($stats['accepted']); ?></span>
                </div>
                
                <div class="consent-stat-box">
                    <span class="dashicons dashicons-no-alt"></span>
                    <h3><?php esc_html_e('Declined', 'cookie-consent-for-google-tag-manager'); ?></h3>
                    <span class="stat-number"><?php echo esc_html($stats['declined']); ?></span>
                </div>
                
                <div class="consent-stat-box">
                    <span class="dashicons dashicons-clock"></span>
                    <h3><?php esc_html_e('No Action', 'cookie-consent-for-google-tag-manager'); ?></h3>
                    <span class="stat-number"><?php echo esc_html($stats['no_action']); ?></span>
                </div>
            </div>

            <!-- Search Form -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get">
                        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                        <?php
                        $consent_table->search_box(__('Search Logs', 'cookie-consent-for-google-tag-manager'), 'consent_search');
                        ?>
                    </form>
                </div>
            </div>

            <!-- The Table -->
            <form method="post">
                <?php
                $consent_table->display();
                ?>
            </form>
        </div>

        <style>
            .consent-stats-wrapper {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .consent-stat-box {
                background: #fff;
                padding: 20px;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                text-align: center;
            }
            .consent-stat-box .dashicons {
                font-size: 30px;
                width: 30px;
                height: 30px;
                margin-bottom: 10px;
            }
            .consent-stat-box h3 {
                margin: 0 0 10px;
                color: #23282d;
                font-size: 14px;
            }
            .consent-stat-box .stat-number {
                font-size: 24px;
                font-weight: 600;
                color: #0073aa;
            }
            .wp-list-table .column-consent_status {
                width: 10%;
            }
            .wp-list-table .column-created_at {
                width: 15%;
            }
            .tablenav.top {
                margin-bottom: 1em;
            }
        </style>
        <?php
    }

    /**
     * Display settings page with working tabs and grouped fields
     */
    public function display_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if required class exists
        if (!class_exists('Cookie_Consent_for_Google_Tag_Manager_Database')) {
            echo '<div class="error"><p>' . esc_html__('Database class not found. Please check plugin installation.', 'cookie-consent-for-google-tag-manager') . '</p></div>';
            return;
        }

        // Get statistics for the dashboard
        $stats = Cookie_Consent_for_Google_Tag_Manager_Database::get_consent_statistics();
        ?>
        <style>
        .gtm-tabs { margin-bottom: 20px; border-bottom: 1px solid #ccc; display: flex; gap: 0; }
        .gtm-tab { background: #f1f1f1; border: 1px solid #ccc; border-bottom: none; padding: 10px 20px; cursor: pointer; margin-right: 2px; border-radius: 8px 8px 0 0; font-weight: 500; }
        .gtm-tab.active { background: #fff; border-bottom: 1px solid #fff; }
        .gtm-tab-content { display: none; padding: 20px 0 0 0; }
        .gtm-tab-content.active { display: block; }
        .form-table th { width: 220px; }
        .gtm-stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .gtm-card {
            background: #fff;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        .gtm-card h2 {
            margin: 0 0 10px;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .gtm-card .stat-number {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        .gtm-card.accepted-card .stat-number { color: #46b450; }
        .gtm-card.declined-card .stat-number { color: #dc3232; }
        .gtm-card.no-action-card .stat-number { color: #ffb900; }
        .gtm-card.total-card .stat-number { color: #0073aa; }
        </style>
        
        <div class="wrap">
            <h1><?php 
                $version = defined('Cookie_Consent_for_Google_Tag_Manager_VERSION') ? Cookie_Consent_for_Google_Tag_Manager_VERSION : '1.0.0';
                echo esc_html__( 'Cookie Consent for Google Tag Manager Settings ' . $version, 'cookie-consent-for-google-tag-manager' ); 
            ?></h1>
            
            <?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) : ?>
                <div id="message" class="updated notice is-dismissible">
                    <p><?php esc_html_e( 'Settings saved.', 'cookie-consent-for-google-tag-manager' ); ?></p>
                </div>
            <?php endif; ?>

            <div class="gtm-tabs">
                <div class="gtm-tab active" data-tab="general"><?php esc_html_e('General', 'cookie-consent-for-google-tag-manager'); ?></div>
                <div class="gtm-tab" data-tab="notice"><?php esc_html_e('Consent Notice', 'cookie-consent-for-google-tag-manager'); ?></div>
                <div class="gtm-tab" data-tab="buttons"><?php esc_html_e('Button Settings', 'cookie-consent-for-google-tag-manager'); ?></div>
                <div class="gtm-tab" data-tab="links"><?php esc_html_e('Links & Labels', 'cookie-consent-for-google-tag-manager'); ?></div>
                <div class="gtm-tab" data-tab="advanced"><?php esc_html_e('Advanced', 'cookie-consent-for-google-tag-manager'); ?></div>
            </div>
            
            <form action="options.php" method="post">
                <?php settings_fields( 'cookie_consent_for_google_tag_manager_options' ); ?>
                
                <div class="gtm-tab-content active" id="tab-general">
                    <table class="form-table">
                        <tr><th><?php _e('Google Tag ID', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->gtag_id_callback(); ?></td></tr>
                        <tr><th><?php _e('Cookie Expiry (Days)', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->cookie_expiry_callback(); ?></td></tr>
                        <tr><th><?php _e('Cookie Notice Position', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->notice_position_callback(); ?></td></tr>
                        <tr><th><?php _e('Cookie Icon Position', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->icon_position_callback(); ?></td></tr>
                        <tr><th><?php _e('Cookie Notice Auto Open', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->auto_open_callback(); ?></td></tr>
                        <tr><th><?php _e('Auto Accept Cookie Consent', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->auto_accept_callback(); ?></td></tr>
                        <tr><th><?php _e('Enable Initial Traffic Source Tracking', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->enable_initial_traffic_source_callback(); ?></td></tr>
                    </table>
                </div>
                
                <div class="gtm-tab-content" id="tab-notice">
                    <table class="form-table">
                        <tr><th><?php _e('Consent Notice Title', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->consent_title_callback(); ?></td></tr>
                        <tr><th><?php _e('Consent Notice Text', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->consent_text_callback(); ?></td></tr>
                        <tr><th><?php _e('Accept Message', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->accept_message_callback(); ?></td></tr>
                        <tr><th><?php _e('Decline Message', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->decline_message_callback(); ?></td></tr>
                    </table>
                </div>
                
                <div class="gtm-tab-content" id="tab-buttons">
                    <table class="form-table">
                        <tr><th><?php _e('Accept Button Class', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->accept_btn_class_callback(); ?></td></tr>
                        <tr><th><?php _e('Decline Button Class', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->decline_btn_class_callback(); ?></td></tr>
                        <tr><th><?php _e('Accept Button Label', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->accept_btn_label_callback(); ?></td></tr>
                        <tr><th><?php _e('Decline Button Label', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->decline_btn_label_callback(); ?></td></tr>
                    </table>
                </div>
                
                <div class="gtm-tab-content" id="tab-links">
                    <table class="form-table">
                        <tr><th><?php _e('Terms of Use URL', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->terms_url_callback(); ?></td></tr>
                        <tr><th><?php _e('Privacy Policy URL', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->privacy_url_callback(); ?></td></tr>
                        <tr><th><?php _e('Terms of Use Link Label', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->terms_label_callback(); ?></td></tr>
                        <tr><th><?php _e('Privacy Policy Link Label', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->privacy_label_callback(); ?></td></tr>
                    </table>
                </div>
                
                <div class="gtm-tab-content" id="tab-advanced">
                    <table class="form-table">
                        <tr><th><?php _e('Custom CSS', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->custom_css_callback(); ?></td></tr>
                        <tr><th><?php _e('Custom JS', 'cookie-consent-for-google-tag-manager'); ?></th><td><?php $this->custom_js_callback(); ?></td></tr>
                    </table>
                </div>
                
                <?php submit_button( __( 'Save Settings', 'cookie-consent-for-google-tag-manager' ) ); ?>
            </form>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tabs = document.querySelectorAll('.gtm-tab');
            var contents = document.querySelectorAll('.gtm-tab-content');
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    tabs.forEach(function(t) { t.classList.remove('active'); });
                    contents.forEach(function(c) { c.classList.remove('active'); c.style.display = 'none'; });
                    tab.classList.add('active');
                    var tabId = 'tab-' + tab.getAttribute('data-tab');
                    var content = document.getElementById(tabId);
                    if(content) { content.classList.add('active'); content.style.display = 'block'; }
                });
            });
        });
        </script>
        <?php
    }

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		// GTAG ID setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_gtag_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_gtag_id' ),
				'default'           => '',
			)
		);

		// Consent Title setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_consent_title',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => __( 'This website uses cookies', 'cookie-consent-for-google-tag-manager' ),
			)
		);

		// Consent Text setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_consent_text',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post', // Allows basic HTML
				'default'           => __( 'We use cookies to improve your experience. By continuing to visit this site you agree to our use of cookies.', 'cookie-consent-for-google-tag-manager' ),
			)
		);

		// Cookie Expiry Date setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_cookie_expiry',
			array(
				'type'              => 'number', // Storing in days
				'sanitize_callback' => 'absint', // Ensure it's a positive integer
				'default'           => 365, // Default to 365 days (1 year)
			)
		);

		// Terms of Use URL setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_terms_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
		
		// Privacy Policy URL setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_privacy_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
		
		// Accept Message setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_accept_message',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => __( 'Thank you for accepting cookies. We will now be able to personalize your experience and analyze web traffic to improve our site.', 'cookie-consent-for-google-tag-manager' ),
			)
		);
		
		// Decline Message setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_decline_message',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => __( 'We understand your choice, but by declining non-essential cookies, you may miss out on personalized content and certain features designed to enhance your experience. If you change your mind, you can always enable cookies and enjoy a more tailored browsing experience.', 'cookie-consent-for-google-tag-manager' ),
			)
		);
		
		// Cookie Notice Position setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_notice_position',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'bottom_right',
			)
		);
		
		// Cookie Icon Position setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_icon_position',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'bottom_right',
			)
		);
		
		// Cookie Notice Auto Open setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_auto_open',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);
		
		// Auto Accept Cookie Consent setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_auto_accept',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);
		
		// Custom CSS setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_custom_css',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'wp_strip_all_tags',
				'default'           => '',
			)
		);
		
		// Custom JS setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_custom_js',
			array(
				'type'              => 'string',
				'sanitize_callback' => '', // Allow raw JS - be careful with this!
				'default'           => '',
			)
		);

		// Accept Button Class setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_accept_btn_class',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		
		// Decline Button Class setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_decline_btn_class',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		
		// Accept Button Label setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_accept_btn_label',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => __( 'Accept', 'cookie-consent-for-google-tag-manager' ),
			)
		);
		
		// Decline Button Label setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_decline_btn_label',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => __( 'Decline', 'cookie-consent-for-google-tag-manager' ),
			)
		);
		
		// Terms of Use Label setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_terms_label',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => __( 'Terms of Use', 'cookie-consent-for-google-tag-manager' ),
			)
		);
		
		// Privacy Policy Label setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_privacy_label',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => __( 'Privacy Policy', 'cookie-consent-for-google-tag-manager' ),
			)
		);
		
		// Enable Initial Traffic Source setting.
		register_setting(
			'cookie_consent_for_google_tag_manager_options',
			'cookie_consent_for_google_tag_manager_enable_initial_traffic_source',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);

		// Add settings sections and fields
		add_settings_section(
			'cookie_consent_for_google_tag_manager_section',
			__( 'Google Tag Manager and Consent Settings', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'print_section_info' ),
			'cookie-consent-for-google-tag-manager'
		);

		add_settings_field(
			'cookie_consent_for_google_tag_manager_gtag_id',
			__( 'Google Tag ID (G-XXXXXXXXX or GTM-XXXXXXX)', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'gtag_id_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);

		add_settings_field(
			'cookie_consent_for_google_tag_manager_consent_title',
			__( 'Consent Notice Title', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'consent_title_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);

		add_settings_field(
			'cookie_consent_for_google_tag_manager_consent_text',
			__( 'Consent Notice Text', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'consent_text_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);

		add_settings_field(
			'cookie_consent_for_google_tag_manager_cookie_expiry',
			__( 'Cookie Expiry (Days)', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'cookie_expiry_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_terms_url',
			__( 'Terms of Use URL', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'terms_url_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_privacy_url',
			__( 'Privacy Policy URL', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'privacy_url_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_accept_message',
			__( 'Accept Message', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'accept_message_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_decline_message',
			__( 'Decline Message', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'decline_message_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_notice_position',
			__( 'Cookie Notice Position', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'notice_position_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_icon_position',
			__( 'Cookie Icon Position', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'icon_position_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_auto_open',
			__( 'Cookie Notice Auto Open', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'auto_open_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_auto_accept',
			__( 'Auto Accept Cookie Consent', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'auto_accept_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_custom_css',
			__( 'Custom CSS', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'custom_css_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_custom_js',
			__( 'Custom JS', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'custom_js_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_accept_btn_class',
			__( 'Accept Button Class', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'accept_btn_class_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_decline_btn_class',
			__( 'Decline Button Class', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'decline_btn_class_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_accept_btn_label',
			__( 'Accept Button Label', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'accept_btn_label_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_decline_btn_label',
			__( 'Decline Button Label', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'decline_btn_label_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_terms_label',
			__( 'Terms of Use Link Label', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'terms_label_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
		
		add_settings_field(
			'cookie_consent_for_google_tag_manager_privacy_label',
			__( 'Privacy Policy Link Label', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'privacy_label_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);

		// Enable Initial Traffic Source Tracking field.
		add_settings_field(
			'cookie_consent_for_google_tag_manager_enable_initial_traffic_source_label',
			__( 'Enable Initial Traffic Source Tracking', 'cookie-consent-for-google-tag-manager' ),
			array( $this, 'enable_initial_traffic_source_callback' ),
			'cookie-consent-for-google-tag-manager',
			'cookie_consent_for_google_tag_manager_section'
		);
	}

	/**
	 * Print the Section text for the settings page.
	 *
	 * @since 1.0.0
	 */
	public function print_section_info() {
		_e( 'Configure your Google Tag Manager ID and the consent notice appearance.', 'cookie-consent-for-google-tag-manager' );
	}

	/**
	 * Get the settings option array and print one of its values.
	 *
	 * @since 1.0.0
	 */
	public function gtag_id_callback() {
		$gtm_id = get_option( 'cookie_consent_for_google_tag_manager_gtag_id' );
		printf(
			'<input type="text" id="cookie_consent_for_google_tag_manager_gtag_id" name="cookie_consent_for_google_tag_manager_gtag_id" value="%s" class="regular-text" />',
			esc_attr( $gtm_id )
		);
		echo '<p class="description">' . esc_html__( 'This ID will be used for Google Tag Manager/Analytics.', 'cookie-consent-for-google-tag-manager' ) . '</p>';
	}

	/**
	 * Get the consent title and print the input field.
	 *
	 * @since 1.1.0
	 */
	public function consent_title_callback() {
		$consent_title = get_option( 'cookie_consent_for_google_tag_manager_consent_title' );
		printf(
			'<input type="text" id="cookie_consent_for_google_tag_manager_consent_title" name="cookie_consent_for_google_tag_manager_consent_title" value="%s" class="regular-text" />',
			esc_attr( $consent_title )
		);
		echo '<p class="description">' . esc_html__( 'The title for your cookie consent notice (e.g., "Cookie Policy").', 'cookie-consent-for-google-tag-manager' ) . '</p>';
	}

	/**
	 * Get the consent text and print the textarea.
	 *
	 * @since 1.1.0
	 */
	public function consent_text_callback() {
		$consent_text = get_option( 'cookie_consent_for_google_tag_manager_consent_text' );
		printf(
			'<textarea id="cookie_consent_for_google_tag_manager_consent_text" name="cookie_consent_for_google_tag_manager_consent_text" rows="5" cols="50" class="large-text">%s</textarea>',
			esc_textarea( $consent_text )
		);
		echo '<p class="description">' . esc_html__( 'The main message for your cookie consent notice. Basic HTML is allowed.', 'cookie-consent-for-google-tag-manager' ) . '</p>';
	}

	/**
	 * Get the cookie expiry and print the input field.
	 *
	 * @since 1.1.0
	 */
	public function cookie_expiry_callback() {
		$cookie_expiry = get_option( 'cookie_consent_for_google_tag_manager_cookie_expiry' );
		printf(
			'<input type="number" id="cookie_consent_for_google_tag_manager_cookie_expiry" name="cookie_consent_for_google_tag_manager_cookie_expiry" value="%s" min="1" class="small-text" /> %s',
			esc_attr( $cookie_expiry ),
			esc_html__( 'days', 'cookie-consent-for-google-tag-manager' )
		);
		echo '<p class="description">' . esc_html__( 'How long (in days) the consent cookie should last. Default is 365 days (1 year).', 'cookie-consent-for-google-tag-manager' ) . '</p>';
	}

	/**
	 * Print the Terms of Use URL field.
	 */
	public function terms_url_callback() {
		$terms_url = get_option( 'cookie_consent_for_google_tag_manager_terms_url' );
		printf(
			'<input type="url" id="cookie_consent_for_google_tag_manager_terms_url" name="cookie_consent_for_google_tag_manager_terms_url" value="%s" class="regular-text" />',
			esc_attr( $terms_url )
		);
		echo '<p class="description">' . esc_html__( 'URL for your Terms of Use page.', 'cookie-consent-for-google-tag-manager' ) . '</p>';
	}

	/**
	 * Print the Privacy Policy URL field.
	 */
	public function privacy_url_callback() {
		$privacy_url = get_option( 'cookie_consent_for_google_tag_manager_privacy_url' );
		printf(
			'<input type="url" id="cookie_consent_for_google_tag_manager_privacy_url" name="cookie_consent_for_google_tag_manager_privacy_url" value="%s" class="regular-text" />',
			esc_attr( $privacy_url )
		);
		echo '<p class="description">' . esc_html__( 'URL for your Privacy Policy page.', 'cookie-consent-for-google-tag-manager' ) . '</p>';
	}

	/**
	 * Print the Accept Message field.
	 */
	public function accept_message_callback() {
		$accept_message = get_option( 'cookie_consent_for_google_tag_manager_accept_message' );
		printf(
			'<textarea id="cookie_consent_for_google_tag_manager_accept_message" name="cookie_consent_for_google_tag_manager_accept_message" rows="3" cols="50" class="large-text">%s</textarea>',
			esc_textarea( $accept_message )
		);
		echo '<p class="description">' . esc_html__( 'Message to show when user accepts cookies.', 'cookie-consent-for-google-tag-manager' ) . '</p>';
	}

	/**
	 * Print the Decline Message field.
	 */
	public function decline_message_callback() {
		$decline_message = get_option( 'cookie_consent_for_google_tag_manager_decline_message' );
		printf(
			'<textarea id="cookie_consent_for_google_tag_manager_decline_message" name="cookie_consent_for_google_tag_manager_decline_message" rows="3" cols="50" class="large-text">%s</textarea>',
			esc_textarea( $decline_message )
		);
		echo '<p class="description">' . esc_html__( 'Message to show when user declines cookies.', 'cookie-consent-for-google-tag-manager' ) . '</p>';
	}

	/**
	 * Print the Cookie Notice Position field.
	 */
	public function notice_position_callback() {
		$value = get_option( 'cookie_consent_for_google_tag_manager_notice_position', 'bottom_right' );
		?>
		<select id="cookie_consent_for_google_tag_manager_notice_position" name="cookie_consent_for_google_tag_manager_notice_position">
			<option value="bottom_left" <?php selected( $value, 'bottom_left' ); ?>><?php esc_html_e('Bottom Left', 'cookie-consent-for-google-tag-manager'); ?></option>
			<option value="bottom_right" <?php selected( $value, 'bottom_right' ); ?>><?php esc_html_e('Bottom Right', 'cookie-consent-for-google-tag-manager'); ?></option>
			<option value="center" <?php selected( $value, 'center' ); ?>><?php esc_html_e('Screen Center', 'cookie-consent-for-google-tag-manager'); ?></option>
		</select>
		<p class="description"><?php esc_html_e('Where to display the cookie notice.', 'cookie-consent-for-google-tag-manager'); ?></p>
		<?php
	}

	/**
	 * Print the Cookie Icon Position field.
	 */
	public function icon_position_callback() {
		$value = get_option( 'cookie_consent_for_google_tag_manager_icon_position', 'bottom_right' );
		?>
		<select id="cookie_consent_for_google_tag_manager_icon_position" name="cookie_consent_for_google_tag_manager_icon_position">
			<option value="bottom_left" <?php selected( $value, 'bottom_left' ); ?>><?php esc_html_e('Bottom Left', 'cookie-consent-for-google-tag-manager'); ?></option>
			<option value="bottom_right" <?php selected( $value, 'bottom_right' ); ?>><?php esc_html_e('Bottom Right', 'cookie-consent-for-google-tag-manager'); ?></option>
			<option value="hide" <?php selected( $value, 'hide' ); ?>><?php esc_html_e('Hide', 'cookie-consent-for-google-tag-manager'); ?></option>
		</select>
		<p class="description"><?php esc_html_e('Where to display the cookie icon. Select "Hide" to completely remove the icon.', 'cookie-consent-for-google-tag-manager'); ?></p>
		<?php
	}

	/**
	 * Print the Cookie Notice Auto Open field.
	 */
	public function auto_open_callback() {
		$value = get_option( 'cookie_consent_for_google_tag_manager_auto_open', false );
		?>
		<input type="checkbox" id="cookie_consent_for_google_tag_manager_auto_open" name="cookie_consent_for_google_tag_manager_auto_open" value="1" <?php checked( $value, 1 ); ?> />
		<label for="cookie_consent_for_google_tag_manager_auto_open"><?php esc_html_e('Automatically open the cookie notice on page load.', 'cookie-consent-for-google-tag-manager'); ?></label>
		<?php
	}

	/**
	 * Print the Auto Accept Cookie Consent field.
	 */
	public function auto_accept_callback() {
		$value = get_option( 'cookie_consent_for_google_tag_manager_auto_accept', false );
		?>
		<input type="checkbox" id="cookie_consent_for_google_tag_manager_auto_accept" name="cookie_consent_for_google_tag_manager_auto_accept" value="1" <?php checked( $value, 1 ); ?> />
		<label for="cookie_consent_for_google_tag_manager_auto_accept"><?php esc_html_e('Automatically accept cookie consent for all users on page load.', 'cookie-consent-for-google-tag-manager'); ?></label>
		<?php
	}

	/**
	 * Print the Enable Initial Traffic Source Tracking checkbox field.
	 */
	public function enable_initial_traffic_source_callback() {
		$value = get_option('cookie_consent_for_google_tag_manager_enable_initial_traffic_source', false);
		?>
		<input type="checkbox" id="cookie_consent_for_google_tag_manager_enable_initial_traffic_source" name="cookie_consent_for_google_tag_manager_enable_initial_traffic_source" value="1" <?php checked($value, 1); ?> />
		<label for="cookie_consent_for_google_tag_manager_enable_initial_traffic_source"> <?php esc_html_e('Enable tracking of the initial traffic source (referrer, UTM, etc.) for each user session.', 'cookie-consent-for-google-tag-manager'); ?></label>
		<?php
	}

	/**
	 * Print the Custom CSS field.
	 */
	public function custom_css_callback() {
		$value = get_option( 'cookie_consent_for_google_tag_manager_custom_css', '' );
		printf(
			'<textarea id="cookie_consent_for_google_tag_manager_custom_css" name="cookie_consent_for_google_tag_manager_custom_css" rows="6" cols="70" class="large-text code">%s</textarea>',
			esc_textarea( $value )
		);
		echo '<p class="description">' . esc_html__( 'Add custom CSS to be loaded on the frontend.', 'cookie-consent-for-google-tag-manager' ) . '</p>';
	}

	/**
	 * Print the Custom JS field.
	 */
	public function custom_js_callback() {
		$value = get_option( 'cookie_consent_for_google_tag_manager_custom_js', '' );
		printf(
			'<textarea id="cookie_consent_for_google_tag_manager_custom_js" name="cookie_consent_for_google_tag_manager_custom_js" rows="6" cols="70" class="large-text code">%s</textarea>',
			esc_textarea( $value )
		);
		echo '<p class="description">' . esc_html__( 'Add custom JavaScript to be loaded on the frontend. Do not include &lt;script&gt; tags.', 'cookie-consent-for-google-tag-manager' ) . '</p>';
	}

	/**
	 * Sanitize the GTAG ID input.
	 *
	 * @since 1.0.0
	 * @param string $input The input GTAG ID.
	 * @return string The sanitized GTAG ID.
	 */
	public function sanitize_gtag_id( $input ) {
		$sanitized_input = sanitize_text_field( $input );
		
		// Validate GTM ID format (GTM-XXXXXXX) or Google Analytics ID format (G-XXXXXXXXX)
		if (!empty($sanitized_input) && !preg_match('/^(GTM-[A-Z0-9]+|G-[A-Z0-9]+)$/', $sanitized_input)) {
			add_settings_error(
				'cookie_consent_for_google_tag_manager_gtag_id',
				'invalid_gtm_id',
				__('Invalid GTM/GA ID format. It should be in the format GTM-XXXXXXX or G-XXXXXXXXX', 'cookie-consent-for-google-tag-manager')
			);
			return get_option('cookie_consent_for_google_tag_manager_gtag_id', '');
		}
		
		return $sanitized_input;
	}

	/**
	 * Print the Accept Button Class field.
	 */
	public function accept_btn_class_callback() {
		$value = get_option('cookie_consent_for_google_tag_manager_accept_btn_class', '');
		printf(
			'<input type="text" id="cookie_consent_for_google_tag_manager_accept_btn_class" name="cookie_consent_for_google_tag_manager_accept_btn_class" value="%s" class="regular-text" />',
			esc_attr($value)
		);
		echo '<p class="description">' . esc_html__('CSS class(es) for the Accept button.', 'cookie-consent-for-google-tag-manager') . '</p>';
	}

	/**
	 * Print the Decline Button Class field.
	 */
	public function decline_btn_class_callback() {
		$value = get_option('cookie_consent_for_google_tag_manager_decline_btn_class', '');
		printf(
			'<input type="text" id="cookie_consent_for_google_tag_manager_decline_btn_class" name="cookie_consent_for_google_tag_manager_decline_btn_class" value="%s" class="regular-text" />',
			esc_attr($value)
		);
		echo '<p class="description">' . esc_html__('CSS class(es) for the Decline button.', 'cookie-consent-for-google-tag-manager') . '</p>';
	}

	/**
	 * Print the Accept Button Label field.
	 */
	public function accept_btn_label_callback() {
		$value = get_option('cookie_consent_for_google_tag_manager_accept_btn_label', __( 'Accept', 'cookie-consent-for-google-tag-manager' ));
		printf(
			'<input type="text" id="cookie_consent_for_google_tag_manager_accept_btn_label" name="cookie_consent_for_google_tag_manager_accept_btn_label" value="%s" class="regular-text" />',
			esc_attr($value)
		);
		echo '<p class="description">' . esc_html__('Text label for the Accept button.', 'cookie-consent-for-google-tag-manager') . '</p>';
	}

	/**
	 * Print the Decline Button Label field.
	 */
	public function decline_btn_label_callback() {
		$value = get_option('cookie_consent_for_google_tag_manager_decline_btn_label', __( 'Decline', 'cookie-consent-for-google-tag-manager' ));
		printf(
			'<input type="text" id="cookie_consent_for_google_tag_manager_decline_btn_label" name="cookie_consent_for_google_tag_manager_decline_btn_label" value="%s" class="regular-text" />',
			esc_attr($value)
		);
		echo '<p class="description">' . esc_html__('Text label for the Decline button.', 'cookie-consent-for-google-tag-manager') . '</p>';
	}

	/**
	 * Print the Terms of Use Link Label field.
	 */
	public function terms_label_callback() {
		$value = get_option('cookie_consent_for_google_tag_manager_terms_label', __( 'Terms of Use', 'cookie-consent-for-google-tag-manager' ));
		printf(
			'<input type="text" id="cookie_consent_for_google_tag_manager_terms_label" name="cookie_consent_for_google_tag_manager_terms_label" value="%s" class="regular-text" />',
			esc_attr($value)
		);
		echo '<p class="description">' . esc_html__('Text label for the Terms of Use link.', 'cookie-consent-for-google-tag-manager') . '</p>';
	}

	/**
	 * Print the Privacy Policy Link Label field.
	 */
	public function privacy_label_callback() {
		$value = get_option('cookie_consent_for_google_tag_manager_privacy_label', __( 'Privacy Policy', 'cookie-consent-for-google-tag-manager' ));
		printf(
			'<input type="text" id="cookie_consent_for_google_tag_manager_privacy_label" name="cookie_consent_for_google_tag_manager_privacy_label" value="%s" class="regular-text" />',
			esc_attr($value)
		);
		echo '<p class="description">' . esc_html__('Text label for the Privacy Policy link.', 'cookie-consent-for-google-tag-manager') . '</p>';
	}
}