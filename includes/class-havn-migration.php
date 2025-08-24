<?php
/**
 * Database Migration Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class HAVN_Migration {
    
    /**
     * Run database migrations
     */
    public static function run_migrations() {
        self::add_new_fields_to_purchases_table();
        self::create_new_tables();
    }
    
    /**
     * Add new fields to purchases table
     */
    private static function add_new_fields_to_purchases_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_purchases';
        
        // Check if fields already exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $existing_columns = array_column($columns, 'Field');
        
        $new_fields = array(
            'number_id' => "ALTER TABLE $table_name ADD COLUMN number_id varchar(255) AFTER price",
            'number' => "ALTER TABLE $table_name ADD COLUMN number varchar(50) AFTER number_id",
            'cost' => "ALTER TABLE $table_name ADD COLUMN cost decimal(10,4) AFTER number",
            'code' => "ALTER TABLE $table_name ADD COLUMN code longtext AFTER cost",
            'status_number' => "ALTER TABLE $table_name ADD COLUMN status_number varchar(50) DEFAULT 'PENDING' AFTER code"
        );
        
        foreach ($new_fields as $field_name => $sql) {
            if (!in_array($field_name, $existing_columns)) {
                $wpdb->query($sql);
            }
        }
        
        // Add index for number_id if it doesn't exist
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
        $existing_indexes = array_column($indexes, 'Key_name');
        
        if (!in_array('number_id', $existing_indexes)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX number_id (number_id)");
        }
    }
    
    /**
     * Create new tables for services and countries
     */
    private static function create_new_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
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
            service_short_name varchar(100) NOT NULL,
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
        dbDelta($sql_services);
        dbDelta($sql_countries);
    }
}
