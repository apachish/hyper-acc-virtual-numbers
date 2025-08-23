<?php
/**
 * Database Operations Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class HAVN_Database {
    
    /**
     * Create required database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Purchases table
        $table_purchases = $wpdb->prefix . 'havn_purchases';
        $sql_purchases = "CREATE TABLE $table_purchases (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            service_id varchar(100) NOT NULL,
            country_code varchar(10) NOT NULL,
            price decimal(10,2) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            api_response longtext,
            admin_notes text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY service_id (service_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Services cache table
        $table_services = $wpdb->prefix . 'havn_services_cache';
        $sql_services = "CREATE TABLE $table_services (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_id varchar(100) NOT NULL,
            service_data longtext NOT NULL,
            countries_data longtext,
            last_updated datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY service_id (service_id)
        ) $charset_collate;";
        
        // Countries cache table
        $table_countries = $wpdb->prefix . 'havn_countries_cache';
        $sql_countries = "CREATE TABLE $table_countries (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            country_code varchar(10) NOT NULL,
            country_name varchar(100) NOT NULL,
            flag_url varchar(255),
            last_updated datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY country_code (country_code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_purchases);
        dbDelta($sql_services);
        dbDelta($sql_countries);
    }
    
    /**
     * Get purchases with filters
     */
    public static function get_purchases($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'user_id' => 0,
            'service_id' => '',
            'country_code' => '',
            'status' => '',
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_conditions = array();
        $where_values = array();
        
        if ($args['user_id']) {
            $where_conditions[] = 'p.user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        if ($args['service_id']) {
            $where_conditions[] = 'p.service_id = %s';
            $where_values[] = $args['service_id'];
        }
        
        if ($args['country_code']) {
            $where_conditions[] = 'p.country_code = %s';
            $where_values[] = $args['country_code'];
        }
        
        if ($args['status']) {
            $where_conditions[] = 'p.status = %s';
            $where_values[] = $args['status'];
        }
        
        if ($args['search']) {
            $where_conditions[] = '(u.display_name LIKE %s OR p.service_id LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $order_clause = 'ORDER BY p.' . esc_sql($args['orderby']) . ' ' . esc_sql($args['order']);
        $limit_clause = 'LIMIT %d OFFSET %d';
        
        $sql = "SELECT p.*, u.display_name, u.user_email 
                FROM {$wpdb->prefix}havn_purchases p
                LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                $where_clause
                $order_clause
                $limit_clause";
        
        $query = $wpdb->prepare($sql, array_merge($where_values, array($args['limit'], $args['offset'])));
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get single purchase by ID
     */
    public static function get_purchase($purchase_id) {
        global $wpdb;
        
        $sql = "SELECT p.*, u.display_name, u.user_email 
                FROM {$wpdb->prefix}havn_purchases p
                LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
                WHERE p.id = %d";
        
        return $wpdb->get_row($wpdb->prepare($sql, $purchase_id));
    }
    
    /**
     * Update purchase status
     */
    public static function update_purchase_status($purchase_id, $status, $admin_notes = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_purchases';
        
        $data = array(
            'status' => $status,
            'updated_at' => current_time('mysql')
        );
        
        if ($admin_notes) {
            $data['admin_notes'] = $admin_notes;
        }
        
        return $wpdb->update(
            $table_name,
            $data,
            array('id' => $purchase_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Get purchase statistics
     */
    public static function get_purchase_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_purchases';
        
        $stats = array();
        
        // Total purchases
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Pending purchases
        $stats['pending'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE status = %s",
            'pending'
        ));
        
        // Completed purchases
        $stats['completed'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE status = %s",
            'completed'
        ));
        
        // Total revenue
        $stats['revenue'] = $wpdb->get_var(
            "SELECT SUM(price) FROM $table_name WHERE status = 'completed'"
        );
        
        // Recent purchases (last 7 days)
        $stats['recent'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE created_at >= %s",
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));
        
        return $stats;
    }
    
    /**
     * Save service data to cache
     */
    public static function cache_service_data($service_id, $service_data, $countries_data = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_services_cache';
        
        $data = array(
            'service_id' => $service_id,
            'service_data' => json_encode($service_data),
            'last_updated' => current_time('mysql')
        );
        
        if ($countries_data) {
            $data['countries_data'] = json_encode($countries_data);
        }
        
        return $wpdb->replace(
            $table_name,
            $data,
            array('%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get cached service data
     */
    public static function get_cached_service_data($service_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_services_cache';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE service_id = %s",
            $service_id
        ));
        
        if ($result) {
            $result->service_data = json_decode($result->service_data, true);
            if ($result->countries_data) {
                $result->countries_data = json_decode($result->countries_data, true);
            }
        }
        
        return $result;
    }
    
    /**
     * Clear expired cache
     */
    public static function clear_expired_cache() {
        global $wpdb;
        
        $cache_duration = get_option('havn_cache_duration', 3600);
        $expiry_time = date('Y-m-d H:i:s', time() - $cache_duration);
        
        $tables = array(
            $wpdb->prefix . 'havn_services_cache',
            $wpdb->prefix . 'havn_countries_cache'
        );
        
        foreach ($tables as $table) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table WHERE last_updated < %s",
                $expiry_time
            ));
        }
    }
} 