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
        add_action('wp_ajax_havn_cancel_number', array($this, 'ajax_cancel_number'));
        add_action('wp_ajax_havn_get_codes', array($this, 'ajax_get_codes'));
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
        
        add_submenu_page(
            'havn-virtual-numbers',
            'تست سیستم',
            'تست سیستم',
            'manage_options',
            'havn-test',
            array($this, 'test_page')
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
        register_setting('havn_settings', 'havn_services_base_path');
        register_setting('havn_settings', 'havn_countries_base_path');
        register_setting('havn_settings', 'havn_buy_page_url');
        
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
        
        add_settings_field(
            'havn_services_base_path',
            'آدرس پایه لوگو سرویس‌ها',
            array($this, 'services_base_path_field_callback'),
            'havn-settings',
            'havn_general_settings'
        );
        
        add_settings_field(
            'havn_countries_base_path',
            'آدرس پایه پرچم کشورها',
            array($this, 'countries_base_path_field_callback'),
            'havn-settings',
            'havn_general_settings'
        );
        
        add_settings_field(
            'havn_buy_page_url',
            'آدرس صفحه خرید شماره مجازی',
            array($this, 'buy_page_url_field_callback'),
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
     * Test page
     */
    public function test_page() {
        include HAVN_PLUGIN_DIR . 'frontend/views/test-menu.php';
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
    
    public function services_base_path_field_callback() {
        $value = get_option('havn_services_base_path', 'https://nerd-peek.ams3.cdn.digitaloceanspaces.com/Virtunum/services-logo');
        echo '<input type="url" name="havn_services_base_path" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">آدرس پایه برای لوگو سرویس‌ها (بدون / در انتها)</p>';
    }
    
    public function countries_base_path_field_callback() {
        $value = get_option('havn_countries_base_path', 'https://nerd-peek.ams3.cdn.digitaloceanspaces.com/Virtunum/countries-flag');
        echo '<input type="url" name="havn_countries_base_path" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">آدرس پایه برای پرچم کشورها (بدون / در انتها)</p>';
    }
    
    public function buy_page_url_field_callback() {
        $value = get_option('havn_buy_page_url', home_url('/?page_id=29'));
        echo '<input type="url" name="havn_buy_page_url" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">آدرس کامل صفحه خرید شماره مجازی که در دکمه "خرید شماره مجازی" استفاده می‌شود</p>';
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
    
    /**
     * AJAX handler for canceling number
     */
    public function ajax_cancel_number() {
        check_ajax_referer('havn_cancel_number', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $number_id = sanitize_text_field($_POST['number_id']);
        
        if (empty($number_id)) {
            wp_send_json_error('شناسه شماره الزامی است');
        }
        
        // Get purchase details to refund the user
        global $wpdb;
        $table_name = $wpdb->prefix . 'havn_purchases';
        $purchase = $wpdb->get_row($wpdb->prepare("SELECT user_id, price FROM $table_name WHERE number_id = %s", $number_id));
        
        if (!$purchase) {
            wp_send_json_error('رکورد خرید یافت نشد');
        }
        
        $api = new HAVN_API();
        $result = $api->cancel_number($number_id);
        
        if ($result['success']) {
            // Refund the user's money
            $refund_ok = $api->refund_user_balance($purchase->user_id, $purchase->price, '', '', 'لغو شماره توسط ادمین');
            
            // Update status in database
            $wpdb->update(
                $table_name,
                array(
                    'status_number' => 'canceled',
                    'updated_at' => current_time('mysql')
                ),
                array('number_id' => $number_id),
                array('%s', '%s'),
                array('%s')
            );
            
            $message = $result['message'];
            if ($refund_ok) {
                $message .= ' - پول به حساب کاربر برگشت';
            } else {
                $message .= ' - خطا در بازگشت پول';
            }
            
            wp_send_json_success($message);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX handler for getting number codes
     */
    public function ajax_get_codes() {
        check_ajax_referer('havn_get_codes', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $number_id = sanitize_text_field($_POST['number_id']);
        
        if (empty($number_id)) {
            wp_send_json_error('شناسه شماره الزامی است');
        }
        
        // First try to get codes from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'havn_purchases';
        $purchase = $wpdb->get_row($wpdb->prepare("SELECT code FROM $table_name WHERE number_id = %s", $number_id));
        
        if ($purchase) {
            // If not in database, get from API
            $api = new HAVN_API();
            $codes = $api->get_number_codes($number_id);
            error_log(print_r($codes, true));
        }


        if (!empty($codes) && isset($codes['code'])) {
            // Format codes for display
            $html = '<table class="wp-list-table widefat fixed striped">';
            $html .= '<thead><tr><th>کد</th><th>زمان دریافت</th><th>وضعیت</th></tr></thead>';
            $html .= '<tbody>';
            
            if (is_array($codes['code'])) {
                foreach ($codes['code'] as $code) {
                    $html .= '<tr>';
                    $html .= '<td>' . esc_html($code['code']) . '</td>';
                    $html .= '<td>' . esc_html($code['received_at'] ?? 'نامشخص') . '</td>';
                    $html .= '<td><span class="status-badge status-received">دریافت شده</span></td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($codes['code']) . '</td>';
                $html .= '<td>' . esc_html($codes['received_at'] ?? 'نامشخص') . '</td>';
                $html .= '<td><span class="status-badge status-received">دریافت شده</span></td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            
            wp_send_json_success($html);
        } else {
            wp_send_json_error('هیچ کدی یافت نشد');
        }
    }
} 