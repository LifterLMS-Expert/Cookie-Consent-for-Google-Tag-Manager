<?php
class Cookie_Consent_for_Google_Tag_Manager_Database {
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'gtm_consent_logs';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            consent_status varchar(20) NOT NULL,
            city varchar(100),
            state varchar(100),
            country varchar(100),
            utm_source varchar(255),
            utm_medium varchar(255),
            utm_campaign varchar(255),
            referrer_url text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Add columns if missing (for upgrades)
        $columns = $wpdb->get_col("DESC $table_name", 0);
        $alter = [];
        if (!in_array('utm_source', $columns)) {
            $alter[] = "ADD COLUMN utm_source varchar(255)";
        }
        if (!in_array('utm_medium', $columns)) {
            $alter[] = "ADD COLUMN utm_medium varchar(255)";
        }
        if (!in_array('utm_campaign', $columns)) {
            $alter[] = "ADD COLUMN utm_campaign varchar(255)";
        }
        if (!in_array('referrer_url', $columns)) {
            $alter[] = "ADD COLUMN referrer_url text";
        }
        if ($alter) {
            $wpdb->query("ALTER TABLE $table_name " . implode(', ', $alter));
        }
    }

    public static function get_consent_statistics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gtm_consent_logs';

        $stats = array(
            'accepted' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE consent_status = 'accepted'"),
            'declined' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE consent_status = 'declined'"),
            'no_action' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE consent_status = 'no_action'"),
            'total_visitors' => $wpdb->get_var("SELECT COUNT(DISTINCT id) FROM $table_name"),
        );
        return $stats;
    }

    public static function log_consent($status, $utm_data = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gtm_consent_logs';

        // Sanitize and get IP address
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $location = self::get_location_data($ip);

        // Sanitize user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

        // Get UTM values from passed data or fallback to cookies
        $utm_source   = !empty($utm_data['utm_source'])   ? sanitize_text_field($utm_data['utm_source'])   : sanitize_text_field($_COOKIE['lle_utm_source'] ?? '');
        $utm_medium   = !empty($utm_data['utm_medium'])   ? sanitize_text_field($utm_data['utm_medium'])   : sanitize_text_field($_COOKIE['lle_utm_medium'] ?? '');
        $utm_campaign = !empty($utm_data['utm_campaign']) ? sanitize_text_field($utm_data['utm_campaign']) : sanitize_text_field($_COOKIE['lle_utm_campaign'] ?? '');
        $referrer_url = !empty($utm_data['referrer_url']) ? esc_url_raw($utm_data['referrer_url'])         : esc_url_raw($_COOKIE['lle_referrer_url'] ?? '');

        // Insert data into the consent log table
        return $wpdb->insert(
            $table_name,
            array(
                'ip_address'     => $ip,
                'user_agent'     => $user_agent,
                'consent_status' => sanitize_text_field($status),
                'city'           => $location['city']    ?? '',
                'state'          => $location['state']   ?? '',
                'country'        => $location['country'] ?? '',
                'utm_source'     => $utm_source,
                'utm_medium'     => $utm_medium,
                'utm_campaign'   => $utm_campaign,
                'referrer_url'   => $referrer_url,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }


    private static function get_location_data($ip) {
        $response = wp_remote_get("http://ip-api.com/json/{$ip}");
        
        if (is_wp_error($response)) {
            return array('city' => '', 'state' => '', 'country' => '');
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return array(
            'city' => $data['city'] ?? '',
            'state' => $data['regionName'] ?? '',
            'country' => $data['country'] ?? ''
        );
    }

    public static function get_consent_logs($limit = 1000) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gtm_consent_logs';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
    }
}