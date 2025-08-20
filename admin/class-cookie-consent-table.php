<?php
/**
 * Cookie Consent Table Class
 */

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Cookie_Consent_Table extends WP_List_Table {
    
    public function __construct() {
        parent::__construct([
            'singular' => 'consent',
            'plural'   => 'consents',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'ip_address'     => __('IP Address', 'cookie-consent-for-google-tag-manager'),
            'consent_status' => __('Consent Status', 'cookie-consent-for-google-tag-manager'),
            'utm_source'     => __('UTM Source', 'cookie-consent-for-google-tag-manager'),
            'utm_medium'     => __('UTM Medium', 'cookie-consent-for-google-tag-manager'),
            'utm_campaign'   => __('UTM Campaign', 'cookie-consent-for-google-tag-manager'),
            'referrer_url'   => __('Referrer URL', 'cookie-consent-for-google-tag-manager'),
            'created_at'     => __('Date', 'cookie-consent-for-google-tag-manager'),
        ];
    }

    public function get_sortable_columns() {
        return [
            'created_at'     => ['created_at', true],
            'consent_status' => ['consent_status', false],
            'utm_source'     => ['utm_source', false]
        ];
    }

    public function column_default($item, $column_name) {
        return esc_html($item->$column_name);
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gtm_consent_logs';
        
        // Pagination
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // Sort
        $orderby = (!empty($_GET['orderby'])) ? sanitize_sql_orderby($_GET['orderby']) : 'created_at';
        $order = (!empty($_GET['order'])) ? sanitize_text_field($_GET['order']) : 'desc';

        // Get data
        $this->items = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table_name 
            ORDER BY %s %s 
            LIMIT %d OFFSET %d", 
            $orderby, 
            $order,
            $per_page, 
            ($current_page - 1) * $per_page
        ));

        // Set pagination
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns()
        ];
    }
}
