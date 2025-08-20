<?php
/**
 * Consent Logs List Table
 *
 * @package    Cookie_Consent_for_Google_Tag_Manager
 * @subpackage Cookie_Consent_for_Google_Tag_Manager/admin
 */

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Consent_Logs_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'consent_log',
            'plural'   => 'consent_logs',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'             => '<input type="checkbox" />',
            'created_at'     => __('Date', 'cookie-consent-for-google-tag-manager'),
            'consent_status' => __('Status', 'cookie-consent-for-google-tag-manager'),
            'ip_address'     => __('IP Address', 'cookie-consent-for-google-tag-manager'),
            'utm_source'     => __('Source', 'cookie-consent-for-google-tag-manager'),
            'utm_medium'     => __('Medium', 'cookie-consent-for-google-tag-manager'),
            'utm_campaign'   => __('Campaign', 'cookie-consent-for-google-tag-manager'),
            'referrer_url'   => __('Referrer', 'cookie-consent-for-google-tag-manager')
        ];
    }

    public function get_sortable_columns() {
        return [
            'created_at'     => ['created_at', true],
            'consent_status' => ['consent_status', false],
            'utm_source'     => ['utm_source', false]
        ];
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="consent_log[]" value="%s" />', $item->id
        );
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'created_at':
                return wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at));
            case 'consent_status':
                $status_colors = [
                    'accepted'   => '#46b450',
                    'declined'   => '#dc3232',
                    'no_action'  => '#ffb900'
                ];
                $color = isset($status_colors[$item->consent_status]) ? $status_colors[$item->consent_status] : '#666';
                return sprintf('<span style="color: %s">%s</span>', esc_attr($color), esc_html($item->consent_status));
            case 'referrer_url':
                return !empty($item->$column_name) ? esc_url($item->$column_name) : '-';
            default:
                return !empty($item->$column_name) ? esc_html($item->$column_name) : '-';
        }
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gtm_consent_logs';
        
        // Items per page
        $per_page = 20;
        $current_page = $this->get_pagenum();

        // Sort parameters
        $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'created_at';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';

        // Search parameter
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

        // Build where clause for search
        $where = '';
        if (!empty($search)) {
            $where = $wpdb->prepare(
                "WHERE utm_source LIKE %s 
                OR utm_medium LIKE %s 
                OR utm_campaign LIKE %s 
                OR referrer_url LIKE %s 
                OR ip_address LIKE %s 
                OR consent_status LIKE %s",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        // Get total items
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where");

        // Get items
        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name $where ORDER BY %s %s LIMIT %d OFFSET %d",
                $orderby,
                $order,
                $per_page,
                ($current_page - 1) * $per_page
            )
        );

        // Set pagination arguments
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
