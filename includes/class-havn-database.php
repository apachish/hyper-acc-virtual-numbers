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
            number_id varchar(255),
            number varchar(50),
            cost decimal(10,4),
            status varchar(50) NOT NULL DEFAULT 'pending',
            api_response longtext,
            admin_notes text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY service_id (service_id),
            KEY status (status),
            KEY created_at (created_at),
            KEY number_id (number_id)
        ) $charset_collate;";
        
        // Services table
        $table_services = $wpdb->prefix . 'havn_services';
        $sql_services = "CREATE TABLE $table_services (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_short_name varchar(100) NOT NULL,
            service_full_name varchar(255) NOT NULL,
            service_icon varchar(255),
            is_active tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY service_short_name (service_short_name),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Countries table
        $table_countries = $wpdb->prefix . 'havn_countries';
        $sql_countries = "CREATE TABLE $table_countries (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            country_code varchar(100) NOT NULL,
            country_iso_code varchar(10) NOT NULL,
            country_name varchar(255) NOT NULL,
            country_flag varchar(255),
            count_available int(11) DEFAULT 0,
            price_usd decimal(10,4) DEFAULT 0.0000,
            rental_time int(11) DEFAULT 20,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY service_country (service_short_name, country_iso_code),
            KEY service_short_name (service_short_name),
            KEY country_iso_code (country_iso_code),
            KEY is_active (is_active)
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
     * Get user purchases
     */
    public static function get_user_purchases($user_id, $status = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_purchases';
        
        $where_clause = "WHERE user_id = %d";
        $params = array($user_id);
        
        if ($status) {
            $where_clause .= " AND status = %s";
            $params[] = $status;
        }
        
        $query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
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
     * Save services data to database
     */
    public static function save_services_data($services_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_services';
        $base_path = $services_data['base_path'] ?? '';
        
        // First, deactivate all services
        $wpdb->update($table_name, array('is_active' => 0), array(), array('%d'), array());
        
        if (isset($services_data['info']) && is_array($services_data['info'])) {
            foreach ($services_data['info'] as $service) {
                $data = array(
                    'service_short_name' => $service['service_short_name'],
                    'service_full_name' => $service['service_full_name'],
                    'service_icon' => $service['service_icon'] ?? '',
                    'base_path' => $base_path,
                    'is_active' => 1,
                    'updated_at' => current_time('mysql')
                );
                
                // Check if service exists
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE service_short_name = %s",
                    $service['service_short_name']
                ));
                
                if ($existing) {
                    // Update existing service
                    $wpdb->update(
                        $table_name,
                        $data,
                        array('service_short_name' => $service['service_short_name']),
                        array('%s', '%s', '%s', '%s', '%d', '%s'),
                        array('%s')
                    );
                } else {
                    // Insert new service
                    $data['created_at'] = current_time('mysql');
                    $wpdb->insert(
                        $table_name,
                        $data,
                        array('%s', '%s', '%s', '%s', '%d', '%s', '%s')
                    );
                }
            }
        }
        
        return true;
    }
    
    /**
     * Save countries data to database
     */
    public static function save_countries_data($service_short_name, $countries_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_countries';
        $base_path = $countries_data['base_path'] ?? '';
        $rental_time = $countries_data['rental_time'] ?? 20;
        
        // First, deactivate all countries for this service
        $wpdb->update(
            $table_name, 
            array('is_active' => 0), 
            array('service_short_name' => $service_short_name), 
            array('%d'), 
            array('%s')
        );
        
        if (isset($countries_data['info']) && is_array($countries_data['info'])) {
            foreach ($countries_data['info'] as $country) {
                $country_info = $country['country_info'];
                $data = array(
                    'service_short_name' => $service_short_name,
                    'country_iso_code' => $country_info['country_iso_code'],
                    'country_name' => $country_info['country_name'],
                    'country_flag' => $country_info['country_flag'] ?? '',
                    'base_path' => $base_path,
                    'count_available' => $country['count'] ?? 0,
                    'price_usd' => $country['price'] ?? 0.0000,
                    'rental_time' => $rental_time,
                    'is_active' => 1,
                    'updated_at' => current_time('mysql')
                );
                
                // Check if country exists for this service
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE service_short_name = %s AND country_iso_code = %s",
                    $service_short_name,
                    $country_info['country_iso_code']
                ));
                
                if ($existing) {
                    // Update existing country
                    $wpdb->update(
                        $table_name,
                        $data,
                        array(
                            'service_short_name' => $service_short_name,
                            'country_iso_code' => $country_info['country_iso_code']
                        ),
                        array('%s', '%s', '%s', '%s', '%s', '%d', '%f', '%d', '%d', '%s'),
                        array('%s', '%s')
                    );
                } else {
                    // Insert new country
                    $data['created_at'] = current_time('mysql');
                    $wpdb->insert(
                        $table_name,
                        $data,
                        array('%s', '%s', '%s', '%s', '%s', '%d', '%f', '%d', '%d', '%s', '%s')
                    );
                }
            }
        }
        
        return true;
    }
    
    /**
     * Get all active services from database
     */
    public static function get_services() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_services';
        
        $services = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE is_active = 1 ORDER BY service_full_name ASC"
        );
        
        return $services;
    }
    
    /**
     * Get countries for a specific service from database
     */
    public static function get_service_countries($service_short_name) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_countries';
        
        $countries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE service_short_name = %s AND is_active = 1 ORDER BY country_name ASC",
            $service_short_name
        ));
        
        return $countries;
    }
    
    /**
     * Get service by short name
     */
    public static function get_service($service_short_name) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_services';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE service_short_name = %s AND is_active = 1",
            $service_short_name
        ));
    }
    
    /**
     * Get country by service and country code
     */
    public static function get_country($service_short_name, $country_iso_code) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_countries';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE service_short_name = %s AND country_iso_code = %s AND is_active = 1",
            $service_short_name,
            $country_iso_code
        ));
    }
    
    /**
     * Get purchase by number ID and user ID
     */
    public static function get_purchase_by_number_id($number_id, $user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_purchases';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE number_id = %s AND user_id = %d AND status = 'completed'",
            $number_id,
            $user_id
        ));
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