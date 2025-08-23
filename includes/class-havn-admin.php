<?php
/**
 * Admin Panel Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class HAVN_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_havn_update_purchase_status', array($this, 'ajax_update_purchase_status'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            'شماره‌های مجازی',
            'شماره‌های مجازی',
            'manage_options',
            'havn-virtual-numbers',
            array($this, 'admin_dashboard_page'),
            'dashicons-phone',
            30
        );
        
        add_submenu_page(
            'havn-virtual-numbers',
            'داشبورد',
            'داشبورد',
            'manage_options',
            'havn-virtual-numbers',
            array($this, 'admin_dashboard_page')
        );
        
        add_submenu_page(
            'havn-virtual-numbers',
            'درخواست‌ها',
            'درخواست‌ها',
            'manage_options',
            'havn-purchases',
            array($this, 'purchases_page')
        );
        
        add_submenu_page(
            'havn-virtual-numbers',
            'تنظیمات',
            'تنظیمات',
            'manage_options',
            'havn-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('havn_settings', 'havn_usd_rate');
        register_setting('havn_settings', 'havn_profit_margin');
        register_setting('havn_settings', 'havn_cache_duration');
        register_setting('havn_settings', 'havn_virtunum_api_key');
        register_setting('havn_settings', 'havn_virtunum_api_url');
        register_setting('havn_settings', 'havn_page_title');
        register_setting('havn_settings', 'havn_info_text');
        
        add_settings_section(
            'havn_general_settings',
            'تنظیمات عمومی',
            array($this, 'general_settings_section_callback'),
            'havn-settings'
        );
        
        add_settings_field(
            'havn_usd_rate',
            'نرخ دلار (تومان)',
            array($this, 'usd_rate_field_callback'),
            'havn-settings',
            'havn_general_settings'
        );
        
        add_settings_field(
            'havn_profit_margin',
            'حاشیه سود (%)',
            array($this, 'profit_margin_field_callback'),
            'havn-settings',
            'havn_general_settings'
        );
        
        add_settings_field(
            'havn_cache_duration',
            'مدت زمان کش (ثانیه)',
            array($this, 'cache_duration_field_callback'),
            'havn-settings',
            'havn_general_settings'
        );
        
        add_settings_field(
            'havn_virtunum_api_key',
            'کلید API VirtuNum',
            array($this, 'api_key_field_callback'),
            'havn-settings',
            'havn_general_settings'
        );
        
        add_settings_field(
            'havn_virtunum_api_url',
            'آدرس API VirtuNum',
            array($this, 'api_url_field_callback'),
            'havn-settings',
            'havn_general_settings'
        );
        
        add_settings_field(
            'havn_page_title',
            'عنوان صفحه',
            array($this, 'page_title_field_callback'),
            'havn-settings',
            'havn_general_settings'
        );
        
        add_settings_field(
            'havn_info_text',
            'متن اطلاعات',
            array($this, 'info_text_field_callback'),
            'havn-settings',
            'havn_general_settings'
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'havn') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('havn-admin', HAVN_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), HAVN_VERSION, true);
        wp_enqueue_style('havn-admin', HAVN_PLUGIN_URL . 'assets/css/admin.css', array(), HAVN_VERSION);
        
        wp_localize_script('havn-admin', 'havn_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('havn_nonce')
        ));
    }
    
    /**
     * Admin dashboard page
     */
    public function admin_dashboard_page() {
        $stats = HAVN_Database::get_purchase_stats();
        include HAVN_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Purchases page
     */
    public function purchases_page() {
        $purchases = HAVN_Database::get_purchases();
        include HAVN_PLUGIN_DIR . 'admin/views/purchases.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        include HAVN_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Settings field callbacks
     */
    public function general_settings_section_callback() {
        echo '<p>تنظیمات اصلی پلاگین شماره‌های مجازی</p>';
    }
    
    public function usd_rate_field_callback() {
        $value = get_option('havn_usd_rate', 50000);
        echo '<input type="number" name="havn_usd_rate" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">نرخ تبدیل دلار به تومان</p>';
    }
    
    public function profit_margin_field_callback() {
        $value = get_option('havn_profit_margin', 10);
        echo '<input type="number" name="havn_profit_margin" value="' . esc_attr($value) . '" class="regular-text" step="0.1" />';
        echo '<p class="description">درصد حاشیه سود که به قیمت پایه اضافه می‌شود</p>';
    }
    
    public function cache_duration_field_callback() {
        $value = get_option('havn_cache_duration', 3600);
        echo '<input type="number" name="havn_cache_duration" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">مدت زمان کش اطلاعات در ثانیه</p>';
    }
    
    public function api_key_field_callback() {
        $value = get_option('havn_virtunum_api_key', '');
        echo '<input type="text" name="havn_virtunum_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">کلید API دریافت شده از VirtuNum</p>';
    }
    
    public function api_url_field_callback() {
        $value = get_option('havn_virtunum_api_url', 'https://api.virtunum.com');
        echo '<input type="url" name="havn_virtunum_api_url" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">آدرس پایه API VirtuNum</p>';
    }
    
    public function page_title_field_callback() {
        $value = get_option('havn_page_title', 'شماره‌های مجازی');
        echo '<input type="text" name="havn_page_title" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">عنوان صفحه نمایش سرویس‌ها</p>';
    }
    
    public function info_text_field_callback() {
        $value = get_option('havn_info_text', 'اطلاعات شماره‌های مجازی در اینجا قرار می‌گیرد.');
        echo '<textarea name="havn_info_text" rows="5" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">متن اطلاعات که در مودال نمایش داده می‌شود</p>';
    }
    
    /**
     * AJAX handler for updating purchase status
     */
    public function ajax_update_purchase_status() {
        check_ajax_referer('havn_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $purchase_id = intval($_POST['purchase_id']);
        $status = sanitize_text_field($_POST['status']);
        $admin_notes = sanitize_textarea_field($_POST['admin_notes']);
        
        $result = HAVN_Database::update_purchase_status($purchase_id, $status, $admin_notes);
        
        if ($result) {
            wp_send_json_success('وضعیت با موفقیت بروزرسانی شد');
        } else {
            wp_send_json_error('خطا در بروزرسانی وضعیت');
        }
    }
} 