<?php
/**
 * Plugin Name: Hyper ACC Virtual Numbers
 * Plugin URI: https://hyper-acc.com/
 * Description: پلاگین مدیریت شماره‌های مجازی با اتصال به وب‌سرویس VirtuNum
 * Version: 1.0.0
 * Author: Hyper ACC
 * Author URI: https://hyper-acc.com/
 * Text Domain: hyper-acc-vn
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HAVN_VERSION', '1.0.0');
define('HAVN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HAVN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HAVN_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Main plugin class
class HyperAccVirtualNumbers {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Initialize plugin components
        $this->load_dependencies();
        $this->init_admin();
        $this->init_frontend();
        
        // Ensure public pages exist
        $this->ensure_public_pages();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('hyper-acc-vn', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    private function load_dependencies() {
        // Load required files
        require_once HAVN_PLUGIN_DIR . 'includes/class-havn-api.php';
        require_once HAVN_PLUGIN_DIR . 'includes/class-havn-admin.php';
        require_once HAVN_PLUGIN_DIR . 'includes/class-havn-frontend.php';
        require_once HAVN_PLUGIN_DIR . 'includes/class-havn-database.php';
    }
    
    private function init_admin() {
        if (is_admin()) {
            new HAVN_Admin();
        }
    }
    
    private function init_frontend() {
        new HAVN_Frontend();
    }
    
    public function activate() {
        // Load dependencies first
        $this->load_dependencies();
        
        try {
            // Create database tables
            if (class_exists('HAVN_Database')) {
                HAVN_Database::create_tables();
            }
            
            // Set default options
            $this->set_default_options();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
        } catch (Exception $e) {
            // Log error but don't prevent activation
            error_log('Hyper ACC Virtual Numbers activation error: ' . $e->getMessage());
        }
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function set_default_options() {
        $defaults = array(
            'usd_rate' => 50000, // نرخ دلار به تومان
            'profit_margin' => 10, // درصد حاشیه سود
            'cache_duration' => 3600, // مدت زمان کش (ثانیه)
            'virtunum_api_key' => '',
            'virtunum_api_url' => 'https://api.virtunum.com'
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option('havn_' . $key) === false) {
                update_option('havn_' . $key, $value);
            }
        }
    }

    private function ensure_public_pages() {
        // Create a page for listing services if not exists
        $page_id = get_option('havn_services_page_id');
        if (!$page_id || get_post_status($page_id) === false) {
            $page_data = array(
                'post_title'   => 'شماره‌های مجازی',
                'post_name'    => 'virtual-numbers',
                'post_content' => '[havn_services]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            );
            $new_page_id = wp_insert_post($page_data, true);
            if (!is_wp_error($new_page_id)) {
                update_option('havn_services_page_id', $new_page_id);
            }
        }
    }
}

// Initialize the plugin
function havn_init() {
    return HyperAccVirtualNumbers::get_instance();
}

// Start the plugin
havn_init(); 