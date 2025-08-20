<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing hooks.
 */
class Cookie_Consent_for_Google_Tag_Manager {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      GTM_Consent_Manager_Loader    $loader    Maintains and registers all hooks.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->plugin_name = 'cookie-consent-for-google-tag-manager';
		$this->version = Cookie_Consent_for_Google_Tag_Manager_VERSION;

		add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
		add_action('wp_head', array($this, 'output_custom_code'), 1); // Priority 1 for early load

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// THIS LINE IS CRUCIAL AND CORRECT AS IT NOW POINTS TO THE SEPARATE LOADER FILE
		require_once Cookie_Consent_for_Google_Tag_Manager_DIR . 'includes/class-cookie-consent-for-google-tag-manager-loader.php';
		$this->loader = new GTM_Consent_Manager_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$admin_page = new Cookie_Consent_for_Google_Tag_Manager_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $admin_page, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $admin_page, 'register_settings' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$gtm_id = get_option('cookie_consent_for_google_tag_manager_gtag_id');

		if (!empty($gtm_id)) {
			$this->loader->add_action('wp_head', $this, 'add_gtm_consent_script');
			$this->loader->add_action('wp_footer', $this, 'add_consent_notice');
			// Add Ajax actions for all consent types (logged in and logged out users)
			$this->loader->add_action('wp_ajax_gtm_consent_granted', $this, 'ajax_gtm_consent_granted');
			$this->loader->add_action('wp_ajax_nopriv_gtm_consent_granted', $this, 'ajax_gtm_consent_granted');
			$this->loader->add_action('wp_ajax_gtm_consent_declined', $this, 'ajax_gtm_consent_declined');
			$this->loader->add_action('wp_ajax_nopriv_gtm_consent_declined', $this, 'ajax_gtm_consent_declined');
			$this->loader->add_action('wp_ajax_gtm_consent_no_action', $this, 'ajax_gtm_consent_no_action');
			$this->loader->add_action('wp_ajax_nopriv_gtm_consent_no_action', $this, 'ajax_gtm_consent_no_action');
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functions.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Adds the initial GTM consent script to the head of the page.
	 * This script sets default consent to 'denied'.
	 *
	 * @since 1.0.0
	 */
	public function add_gtm_consent_script() {
		$gtm_id = get_option( 'cookie_consent_for_google_tag_manager_gtag_id' );
		if ( empty( $gtm_id ) ) {

			return;
		}
	}

	/**
	 * Adds the consent notice to the footer of the website.
	 *
	 * @since 1.0.0
	 */
	public function add_consent_notice() {
		$consent_title   = get_option( 'cookie_consent_for_google_tag_manager_consent_title', __( 'This website uses cookies', 'cookie-consent-for-google-tag-manager' ) );
		$consent_text    = get_option( 'cookie_consent_for_google_tag_manager_consent_text', __( 'We use cookies to improve your experience. By continuing to visit this site you agree to our use of cookies.', 'cookie-consent-for-google-tag-manager' ) );
        $terms_url = get_option('cookie_consent_for_google_tag_manager_terms_url', '#');
        $privacy_url = get_option('cookie_consent_for_google_tag_manager_privacy_url', '#');
        $accept_message = get_option('cookie_consent_for_google_tag_manager_accept_message', __('Thank you for accepting cookies. We will now be able to personalize your experience and analyze web traffic to improve our site.', 'cookie-consent-for-google-tag-manager'));
        $decline_message = get_option('cookie_consent_for_google_tag_manager_decline_message', __('We understand your choice, but by declining non-essential cookies, you may miss out on personalized content and certain features designed to enhance your experience. If you change your mind, you can always enable cookies and enjoy a more tailored browsing experience.', 'cookie-consent-for-google-tag-manager'));
		$notice_position = get_option('cookie_consent_for_google_tag_manager_notice_position', 'bottom_right');
		$icon_position = get_option('cookie_consent_for_google_tag_manager_icon_position', 'bottom_right');
		$auto_open = get_option('cookie_consent_for_google_tag_manager_auto_open', false);
        $accept_btn_class = get_option('cookie_consent_for_google_tag_manager_accept_btn_class', '');
        $decline_btn_class = get_option('cookie_consent_for_google_tag_manager_decline_btn_class', '');
        $accept_btn_label = get_option('cookie_consent_for_google_tag_manager_accept_btn_label', __('Accept', 'cookie-consent-for-google-tag-manager'));
        $decline_btn_label = get_option('cookie_consent_for_google_tag_manager_decline_btn_label', __('Decline', 'cookie-consent-for-google-tag-manager'));
        $terms_label = get_option('cookie_consent_for_google_tag_manager_terms_label', __('Terms of use', 'cookie-consent-for-google-tag-manager'));
        $privacy_label = get_option('cookie_consent_for_google_tag_manager_privacy_label', __('Privacy Policy', 'cookie-consent-for-google-tag-manager'));

		// Always inject GTM script if consent was granted
		if ( isset( $_COOKIE['gtm_consent_granted'] ) && $_COOKIE['gtm_consent_granted'] === 'yes' ) {
			$this->inject_gtm_full_code();
		} else {
			?>
			<script>
				window.dataLayer = window.dataLayer || [];
				function gtag(){dataLayer.push(arguments);}
				gtag("consent", "default", {
					ad_storage: "denied",
					analytics_storage: "denied",
					ad_user_data: "denied",
					ad_personalization: "denied",
					functionality_storage: "denied",
					security_storage: "denied",
					personalization_storage: "denied"
				});
			</script>
			<?php
		}

		// Always enqueue the script
		wp_enqueue_script(
			'cookie-consent-for-google-tag-manager-public',
			Cookie_Consent_for_Google_Tag_Manager_URL . 'public/js/user-permission-settings.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Always output the HTML - let JavaScript handle visibility
		?>
		<div id="gtm-consent-notice" class="gtm-consent-notice-expanded"
            data-notice-position="<?php echo esc_attr($notice_position); ?>"
            data-auto-open="<?php echo esc_attr($auto_open ? '1' : '0'); ?>"
            style="position: fixed; max-width: 500px; z-index: 2147483647; bottom: 10px; right: 10px; display: none;
            <?php echo $notice_position === 'bottom_left' ? 'bottom: 10px; left: 10px;' : 
                  ($notice_position === 'bottom_right' ? 'bottom: 10px; right: 10px;' : 
                  'top: 50%; left: 50%; transform: translate(-50%, -50%);'); ?>"
            >
			<div class="gtm-consent-content_inner" style="margin: 0 auto;background: #fff;border-radius: 25px;position: relative;box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);">
				
				<div class="gtm-consent-content-top-title">
					
					<h3 id="gtm-consent-title" style="margin: 0;font-size: 19px;font-weight: 700;color: #000;background-clip: text;line-height: 1.7em;text-align: center;padding-top: 20px;">
						<?php echo esc_html( $consent_title ); ?>
					</h3>
					<button id="gtm-consent-close" style="background: #1167e8;color: white;border: none;padding: 13px 20px;cursor: pointer;border-radius: 40px;position: absolute;right: -10px;top: -19px;font-size: 16px;">Close X</button>
				</div>
				<div class="consent-divider"></div>
				<p id="gtm-consent-text" style="font-family: inherit;font-weight: 300;font-size: 16px;line-height: 1.5em;color: #333;margin: 0 0;text-align: -webkit-left;padding: 10px 20px;">
                    <?php echo wp_kses_post( $consent_text ); ?>
                </p>
				<div class="consent-divider"></div>
				<div style="display: flex;justify-content: center;align-items: center;padding: 0px 15px;align-content: center;gap: 12px;">
                    <button id="gtm-consent-no" class="<?php echo esc_attr($decline_btn_class); ?> consent-btn" style="width: 100%; height: 45px; margin-right: 8px;">
                        <span class="consent-btn-label"> <?php echo esc_html($decline_btn_label); ?> </span>
                        <span class="consent-btn-status" style="display:none; margin-left: 6px; font-size: 18px;">✔️</span>
                    </button>
                    <button id="gtm-consent-yes" class="<?php echo esc_attr($accept_btn_class); ?> consent-btn" style="width: 100%; height: 45px;">
                        <span class="consent-btn-label"> <?php echo esc_html($accept_btn_label); ?> </span>
                        <span class="consent-btn-status" style="display:none; margin-left: 6px; font-size: 18px;">✔️</span>
                    </button>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var declineBtn = document.getElementById('gtm-consent-no');
                            var yesBtn = document.getElementById('gtm-consent-yes');
                            var consentText = document.querySelector('.gtm-consent-content_inner p');
                            var acceptMsg = <?php echo json_encode($accept_message); ?>;
                            var declineMsg = <?php echo json_encode($decline_message); ?>;
                            var declineLabel = <?php echo json_encode($decline_btn_label); ?>;
                            var acceptLabel = <?php echo json_encode($accept_btn_label); ?>;
                            var declineStatus = declineBtn.querySelector('.consent-btn-status');
                            var yesStatus = yesBtn.querySelector('.consent-btn-status');
                            var declineBtnLabel = declineBtn.querySelector('.consent-btn-label');
                            var yesBtnLabel = yesBtn.querySelector('.consent-btn-label');

                            function setSelected(btn, status, label, selectedText, color) {
                                btn.classList.add('selected');
                                btn.style.background = color;
                                btn.style.color = '#fff';
                                btn.style.opacity = '1';
                                btn.style.border = '1px solid #1167e8';
                                status.style.display = 'inline';
                                label.textContent = selectedText;
                            }
                            function setUnselected(btn, status, label, defaultText, color) {
                                btn.classList.remove('selected');
                                btn.style.background = '#fff';
                                btn.style.color = color;
                                btn.style.opacity = '0.85';
                                btn.style.border = '1px solid ' + color;
                                status.style.display = 'none';
                                label.textContent = defaultText;
                            }
                            // On load, check cookie and update UI
                            if (document.cookie.indexOf('gtm_consent_declined=yes') !== -1) {
                                setSelected(declineBtn, declineStatus, declineBtnLabel, declineLabel + ' (Declined)', '#e74c3c');
                                setUnselected(yesBtn, yesStatus, yesBtnLabel, acceptLabel, '#27ae60');
                                if (consentText) consentText.textContent = declineMsg;
                            } else if (document.cookie.indexOf('gtm_consent_granted=yes') !== -1) {
                                setSelected(yesBtn, yesStatus, yesBtnLabel, acceptLabel + ' (Accepted)', '#27ae60');
                                setUnselected(declineBtn, declineStatus, declineBtnLabel, declineLabel, '#e74c3c');
                                if (consentText) consentText.textContent = acceptMsg;
                            } else {
                                setUnselected(declineBtn, declineStatus, declineBtnLabel, declineLabel, '#e74c3c');
                                setUnselected(yesBtn, yesStatus, yesBtnLabel, acceptLabel, '#27ae60');
                            }
                            // On click Accept
                            yesBtn.addEventListener('click', function() {
                                setSelected(yesBtn, yesStatus, yesBtnLabel, acceptLabel + ' (Accepted)', '#27ae60');
                                setUnselected(declineBtn, declineStatus, declineBtnLabel, declineLabel, '#e74c3c');
                                if (consentText) consentText.textContent = acceptMsg;
                            });
                            // On click Decline
                            declineBtn.addEventListener('click', function() {
                                setSelected(declineBtn, declineStatus, declineBtnLabel, declineLabel + ' (Declined)', '#e74c3c');
                                setUnselected(yesBtn, yesStatus, yesBtnLabel, acceptLabel, '#27ae60');
                                if (consentText) consentText.textContent = declineMsg;
                            });
                        });
                    </script>
                </div>
                <p id="gtm-consent-message" style="margin-top: 10px; font-weight: bold; color: green; font-size: 16px; text-align: center; display: none;"></p>
                <div class="consent-divider"></div>
                <div class="consent-links">
                    <?php if (!empty($terms_url) && $terms_url !== '#'): ?>
                        <a href="<?php echo esc_url($terms_url); ?>" class="consent-link" target="_blank" rel="noopener noreferrer"><?php echo esc_html($terms_label); ?></a>
                    <?php endif; ?>
                    <?php if (!empty($terms_url) && !empty($privacy_url) && $terms_url !== '#' && $privacy_url !== '#'): ?>
                        <span class="consent-separator">|</span>
                    <?php endif; ?>
                    <?php if (!empty($privacy_url) && $privacy_url !== '#'): ?>
                        <a href="<?php echo esc_url($privacy_url); ?>" class="consent-link" target="_blank" rel="noopener noreferrer"><?php echo esc_html($privacy_label); ?></a>
                    <?php endif; ?>
                </div>
			</div>
		</div>
		<?php if ($icon_position !== 'hide') : ?>
        <div id="gtm-consent-minimized"
					data-icon-position="<?php echo esc_attr($icon_position); ?>"
					style="position: fixed; <?php echo $icon_position === 'bottom_left' ? 'bottom: 20px; left: 20px;' : 'bottom: 20px; right: 20px;'; ?> background: #fff; padding: 15px; border-radius: 35px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); cursor: pointer; z-index: 99999999999;"
					title="Cookie Settings">
			<span style="display: flex; align-items: center; gap: 8px;">
				<span style="display:inline-block; position:relative;height: 38px;">
					<svg version="1.1" id="svg636" xml:space="preserve" width="38" height="38" viewBox="0 0 682.66669 682.66669" xmlns="http://www.w3.org/2000/svg"><g transform="matrix(1.3333333,0,0,-1.3333333,0,682.66667)"><g><g><g><g transform="translate(44.082,381.0029)"><path d="m 0,0 c -21.655,-36.632 -34.082,-79.366 -34.082,-125.003 0,-135.862 110.138,-246 246,-246 135.862,0 246,110.138 246,246 0,9.413 -0.547,18.696 -1.576,27.834 -4.039,-0.873 -8.227,-1.344 -12.528,-1.344 -10.073,0 -19.554,2.528 -27.855,6.973 -18.997,-11.755 -43.68,-12.958 -64.382,-1.005 -15.358,8.867 -25.528,23.255 -29.486,39.208 -23.052,6.094 -43.331,21.995 -54.191,45.296 -10.76,23.09 -9.998,48.584 -0.099,70.035 -31.143,1.668 -55.883,27.444 -55.883,59.003 -57.864,0 -111.063,-19.978 -153.073,-53.414" style="fill:none;stroke:#000;stroke-width:20;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1"/></g><g transform="translate(463.8477,395.353)"><path d="m 0,0 c -4.824,3.007 -10.813,3.664 -16.147,1.703 -0.03,-0.011 -0.058,-0.021 -0.086,-0.032 -14.115,-5.216 -24.632,-15.876 -30.061,-28.723 -3.107,-7.354 -1.228,-15.871 4.627,-21.3 5.152,-4.777 10.91,-8.902 17.109,-12.322 6.314,-3.483 14.106,-2.95 19.977,1.236 9.305,6.633 16.719,15.199 21.943,24.872 3.514,6.504 2.912,14.518 -1.592,20.378 C 11.445,-8.563 6.077,-3.788 0,0 Z" style="fill:none;stroke:#000;stroke-width:20;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1"/></g><g transform="translate(196.8516,349.7446)"><path d="m 0,0 c 0,-18.919 -15.336,-34.255 -34.255,-34.255 -18.919,0 -34.256,15.336 -34.256,34.255 0,18.919 15.337,34.255 34.256,34.255 C -15.336,34.255 0,18.919 0,0 Z" style="fill:none;stroke:#000;stroke-width:20;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1"/></g><g transform="translate(215.1924,165.9775)"><path d="m 0,0 c 0,28.457 -23.069,51.525 -51.525,51.525 -28.457,0 -51.526,-23.068 -51.526,-51.525 0,-28.457 23.069,-51.525 51.526,-51.525 C -23.069,-51.525 0,-28.457 0,0 Z" style="fill:none;stroke:#000;stroke-width:20;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1"/></g><g transform="translate(410.6973,204.9619)"><path d="m 0,0 c 0,-18.006 -14.597,-32.604 -32.603,-32.604 -18.006,0 -32.603,14.598 -32.603,32.604 0,18.006 14.597,32.604 32.603,32.604 C -14.597,32.604 0,18.006 0,0 Z" style="fill:none;stroke:#000;stroke-width:20;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1"/></g><g transform="translate(276.4883,274.5386)"><path d="m 0,0 c 0,-11.245 -9.116,-20.361 -20.361,-20.361 -11.245,0 -20.362,9.116 -20.362,20.361 0,11.246 9.117,20.361 20.362,20.361 C -9.116,20.361 0,11.246 0,0 Z" style="fill:none;stroke:#000;stroke-width:20;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1"/></g><g transform="translate(335.4541,111.9214)"><path d="m 0,0 c 0,-11.245 -9.116,-20.361 -20.361,-20.361 -11.245,0 -20.362,9.116 -20.362,20.361 0,11.246 9.117,20.361 20.362,20.361 C -9.116,20.361 0,11.246 0,0 Z" style="fill:none;stroke:#000;stroke-width:20;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1"/></g><g transform="translate(396.7656,440.8345)"><path d="M 0,0 H -25.531 C -38.3,0 -47.489,12.265 -43.9,24.519 l 4.602,15.719 C -36.909,48.395 -29.428,54 -20.929,54 h 16.326 c 8.499,0 15.981,-5.605 18.37,-13.762 L 18.369,24.519 C 21.957,12.265 12.769,0 0,0 Z" style="fill:none;stroke:#000;stroke-width:20;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1"/></g><g transform="translate(458.9434,465.7124)"><path d="m 0,0 c 0,-11.89 9.639,-21.528 21.528,-21.528 11.89,0 21.529,9.638 21.529,21.528 0,11.89 -9.639,21.528 -21.529,21.528 C 9.639,21.528 0,11.89 0,0 Z" style="fill:none;stroke:#000;stroke-width:20;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1"/></g><g transform="translate(70.3838,417.5186)"><path d="M 0,0 V 0" style="fill:none;stroke:#000;stroke-width:20;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1"/></g></g></g></g></g></svg>
				</span>
			</span>
			<span class="cookie-tooltip" style="visibility:hidden;opacity:0;transition:opacity 0.2s;position:absolute;bottom:120%;left:50%;transform:translateX(-50%);background:#222;color:#fff;padding:4px 10px;border-radius:5px;font-size:13px;white-space:nowrap;z-index:1000;pointer-events:none;">Cookie Settings</span>
		</div>
		<?php endif; ?>
		<style>
            button#gtm-consent-yes, button#gtm-consent-no {
                outline: none;
                opacity: 0.7;
                border: none;
            }
            button#gtm-consent-yes.selected, button#gtm-consent-no.selected {
                opacity: 1;
                border: 1px solid #1167e8;
                box-shadow: 0 4px 16px rgba(39,174,96,0.18);
            }
            button#gtm-consent-yes:hover {
                background: #219150;
                box-shadow: 0 4px 16px rgba(39,174,96,0.18);
                opacity: 1;
            }
            button#gtm-consent-no:hover {
                background: #c0392b;
                box-shadow: 0 4px 16px rgba(231,76,60,0.18);
                opacity: 1;
            }
            #gtm-consent-close:hover {color: #000;}
            #gtm-consent-minimized:hover {background: #f0f0f0;}
            .consent-divider{width: 100%;height: 1px;background: #E5E7EB;margin: 10px 0px;}
            .consent-links{display: flex;justify-content: center;align-items: center;gap: 12px;padding-bottom: 10px;}
            .consent-link{color: #6B7280;text-decoration: none;font-size: 14px;font-weight: 500;transition: color 0.3s ease;}
            .consent-link:hover{color: #000;text-decoration: underline;}
            .consent-separator{color: #D1D5DB;font-size: 14px;}
            .consent-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                font-size: 16px;
                font-weight: 500;
                border-radius: 10px;
                transition: background 0.2s, box-shadow 0.2s, opacity 0.2s, border 0.2s, color 0.2s;
                box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                outline: none;
                border: 1px solid #e5e7eb;
                background: #fff;
                color: #333;
                cursor: pointer;
                padding: 0 18px;
                min-width: 120px;
                margin-bottom: 0;
            }
            .consent-btn.selected {
                opacity: 1 !important;
                color: #fff !important;
                box-shadow: 0 4px 16px rgba(17,103,232,0.10);
            }
            .consent-btn:hover {
                opacity: 1;
                box-shadow: 0 4px 16px rgba(17,103,232,0.13);
            }
            #gtm-consent-no.selected {
                background: #e74c3c !important;
                border-color: #1167e8 !important;
                color: #fff !important;
            }
            #gtm-consent-yes.selected {
                background: #27ae60 !important;
                border-color: #1167e8 !important;
                color: #fff !important;
            }
            #gtm-consent-no:not(.selected) {
                color: #e74c3c;
                border-color: #e74c3c;
                background: #fff;
            }
            #gtm-consent-yes:not(.selected) {
                color: #27ae60;
                border-color: #27ae60;
                background: #fff;
            }
            .consent-btn-label {
                font-weight: 500;
                font-size: 16px;
            }
            .consent-btn-status {
                font-size: 18px;
            }
            @media (max-width: 480px) {#gtm-consent-minimized {padding: 8px 16px !important; border-radius: 20px !important;}#gtm-consent-minimized span {font-size: 14px;} }
		</style>
		<?php
	}

	/**
	 * Output custom CSS and JS in the head with high priority.
	 */
	public function output_custom_code() {
		$custom_css = get_option('cookie_consent_for_google_tag_manager_custom_css', '');
		$custom_js = get_option('cookie_consent_for_google_tag_manager_custom_js', '');
		if ($custom_css) {
			echo '<style id="cookie-consent-for-google-tag-manager-custom-css">' . $custom_css . '</style>';
		}
		if ($custom_js) {
			echo '<script id="cookie-consent-for-google-tag-manager-custom-js">' . $custom_js . '</script>';
		}
	}

	/**
	 * Enqueues the public-facing JavaScript for consent management.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_public_scripts() {
		wp_enqueue_script(
			'cookie-consent-for-google-tag-manager-public',
			Cookie_Consent_for_Google_Tag_Manager_URL . 'public/js/user-permission-settings.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Generate the nonce
		$nonce = wp_create_nonce('gtm_consent_nonce');
		error_log('Generated nonce: ' . $nonce);
		
		// Log the current user and session info for debugging
		error_log('Current user ID: ' . get_current_user_id());
		error_log('Session ID: ' . session_id());

		wp_localize_script(
			'cookie-consent-for-google-tag-manager-public',
			'gtm_consent_ajax',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce'       => $nonce,
				'gtm_id'      => get_option( 'cookie_consent_for_google_tag_manager_gtag_id' ),
				'cookie_days' => absint(get_option( 'cookie_consent_for_google_tag_manager_cookie_expiry', 90 )),
				'auto_accept' => get_option( 'cookie_consent_for_google_tag_manager_auto_accept', false ),
			)
		);
	}

	/**
	 * AJAX handler for when consent is granted.
	 * Sets a cookie and injects the full GTM code.
	 *
	 * @since 1.0.0
	 */
	/**
	 * Handle consent granted Ajax request
	 */
	public function ajax_gtm_consent_granted() {
		try {
			check_ajax_referer('gtm_consent_nonce', 'nonce');
			$cookie_days = absint(get_option('cookie_consent_for_google_tag_manager_cookie_expiry', 90));
			$expiry_time = time() + ($cookie_days * DAY_IN_SECONDS);

			$utm_data = array(
				'utm_source'   => isset($_POST['lle_utm_source']) ? sanitize_text_field(wp_unslash($_POST['lle_utm_source'])) : '',
				'utm_medium'   => isset($_POST['lle_utm_medium']) ? sanitize_text_field(wp_unslash($_POST['lle_utm_medium'])) : '',
				'utm_campaign' => isset($_POST['lle_utm_campaign']) ? sanitize_text_field(wp_unslash($_POST['lle_utm_campaign'])) : '',
				'referrer_url' => isset($_POST['lle_referrer_url']) ? esc_url_raw(wp_unslash($_POST['lle_referrer_url'])) : ''
			);

			// Log consent
			$db = new Cookie_Consent_for_Google_Tag_Manager_Database();
			$db->log_consent('accepted', $utm_data);

			wp_send_json_success(array(
				'message' => __('Consent granted successfully', 'cookie-consent-for-google-tag-manager')
			));

		} catch (Exception $e) {
			wp_send_json_error(array(
				'message' => $e->getMessage()
			));
		}
	}


	/**
	 * AJAX handler for when consent is declined
	 *
	 * @since 1.0.0
	 */
	public function ajax_gtm_consent_declined() {
		try {
			check_ajax_referer('gtm_consent_nonce', 'nonce');

			$utm_data = array(
				'utm_source'   => isset($_POST['lle_utm_source']) ? sanitize_text_field(wp_unslash($_POST['lle_utm_source'])) : '',
				'utm_medium'   => isset($_POST['lle_utm_medium']) ? sanitize_text_field(wp_unslash($_POST['lle_utm_medium'])) : '',
				'utm_campaign' => isset($_POST['lle_utm_campaign']) ? sanitize_text_field(wp_unslash($_POST['lle_utm_campaign'])) : '',
				'referrer_url' => isset($_POST['lle_referrer_url']) ? esc_url_raw(wp_unslash($_POST['lle_referrer_url'])) : ''
			);

			// Log consent
			$db = new Cookie_Consent_for_Google_Tag_Manager_Database();
			$db->log_consent('declined', $utm_data);

			wp_send_json_success(array(
				'message' => __('Consent declined successfully', 'cookie-consent-for-google-tag-manager')
			));

		} catch (Exception $e) {
			wp_send_json_error(array(
				'message' => $e->getMessage()
			));
		}
	}

	/**
	 * AJAX handler for when no action is taken on the consent notice.
	 *
	 * @since 1.0.0
	 */
	public function ajax_gtm_consent_no_action() {
		try {
			check_ajax_referer('gtm_consent_nonce', 'nonce');

			$utm_data = array(
				'utm_source'   => isset($_POST['lle_utm_source']) ? sanitize_text_field(wp_unslash($_POST['lle_utm_source'])) : '',
				'utm_medium'   => isset($_POST['lle_utm_medium']) ? sanitize_text_field(wp_unslash($_POST['lle_utm_medium'])) : '',
				'utm_campaign' => isset($_POST['lle_utm_campaign']) ? sanitize_text_field(wp_unslash($_POST['lle_utm_campaign'])) : '',
				'referrer_url' => isset($_POST['lle_referrer_url']) ? esc_url_raw(wp_unslash($_POST['lle_referrer_url'])) : ''
			);

			// Log consent
			$db = new Cookie_Consent_for_Google_Tag_Manager_Database();
			$db->log_consent('no_action', $utm_data);

			wp_send_json_success(array(
				'message' => __('Preference recorded', 'cookie-consent-for-google-tag-manager')
			));

		} catch (Exception $e) {
			wp_send_json_error(array(
				'message' => $e->getMessage()
			));
		}
	}

	/**
	 * Injects the full GTM code into the head.
	 *
	 * @since 1.0.0
	 */
	public function inject_gtm_full_code() {
		$gtm_id = get_option('cookie_consent_for_google_tag_manager_gtag_id');
		if (empty($gtm_id)) {
			return;
		}

		?>
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($gtm_id); ?>"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}

			gtag('js', new Date());
			gtag('config', '<?php echo esc_attr($gtm_id); ?>');

			// Update consent state when user has accepted
			gtag("consent", "update", {
				ad_storage: "granted",
				analytics_storage: "granted",
				ad_user_data: "granted",
				ad_personalization: "granted",
				functionality_storage: "granted",
				security_storage: "granted",
				personalization_storage: "granted"
			});
			jQuery('div#gtm-consent-notice').hide();
		</script>
		<?php
	}

}