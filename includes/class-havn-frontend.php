<?php
/**
 * Frontend Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class HAVN_Frontend {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_havn_purchase_number', array($this, 'ajax_purchase_number'));
        add_action('wp_ajax_nopriv_havn_purchase_number', array($this, 'ajax_purchase_number'));
        add_action('wp_ajax_havn_get_number_codes', array($this, 'ajax_get_number_codes'));
        add_action('wp_ajax_havn_get_purchase_details', array($this, 'ajax_get_purchase_details'));
        add_action('wp_ajax_havn_get_service_countries', array($this, 'ajax_get_service_countries'));
        add_action('wp_ajax_nopriv_havn_get_service_countries', array($this, 'ajax_get_service_countries'));
        add_shortcode('havn_services', array($this, 'services_shortcode'));
        add_shortcode('havn_user_purchases', array($this, 'user_purchases_shortcode'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('havn-frontend', HAVN_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), '1.1.4', true);
        wp_enqueue_style('havn-frontend', HAVN_PLUGIN_URL . 'assets/css/frontend.css', array(), HAVN_VERSION);
        
        wp_localize_script('havn-frontend', 'havn_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('havn_frontend_nonce'),
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in(),
            'plugin_url' => HAVN_PLUGIN_URL,
        ));
    }
    
    /**
     * Services shortcode
     */
    public function services_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_countries' => 'true'
        ), $atts);
        
        $api = new HAVN_API();
        $services = $api->get_services();
        
        if (!$services) {
            return '<p>خطا در دریافت لیست سرویس‌ها</p>';
        }
        
        // Provide helper to view
        $frontend = $this;
        
        ob_start();
        include HAVN_PLUGIN_DIR . 'frontend/views/services.php';
        return ob_get_clean();
    }
    
    /**
     * User purchases shortcode
     */
    public function user_purchases_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>لطفاً ابتدا وارد شوید</p>';
        }
        
        $user_id = get_current_user_id();
        $purchases = HAVN_Database::get_purchases(array('user_id' => $user_id));
        
        // Provide helper to view
        $frontend = $this;
        
        ob_start();
        include HAVN_PLUGIN_DIR . 'frontend/views/user-purchases.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for getting service countries
     */
    public function ajax_get_service_countries() {
        check_ajax_referer('havn_frontend_nonce', 'nonce');
        
        $service_id = sanitize_text_field($_POST['service_id']);
        
        if (empty($service_id)) {
            wp_send_json_error('شناسه سرویس مشخص نشده است');
        }
        
        $api = new HAVN_API();
        $countries = $api->get_service_countries($service_id);
        
        if ($countries !== false) {
            wp_send_json_success($countries);
        } else {
            wp_send_json_error('خطا در دریافت کشورها');
        }
    }
    
    /**
     * AJAX handler for purchasing numbers
     */
    public function ajax_purchase_number() {
        check_ajax_referer('havn_frontend_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('لطفاً ابتدا وارد شوید');
        }
        
        $user_id = get_current_user_id();
        $service_id = sanitize_text_field($_POST['service_id']);
        $country_code = sanitize_text_field($_POST['country_code']);
        
        if (empty($service_id) || empty($country_code)) {
            wp_send_json_error('پارامترهای ورودی ناقص است');
        }
        
        // Get service price from countries list
        $api = new HAVN_API();
        $countries = $api->get_service_countries($service_id);

        
        if (!$countries || !isset($countries['info'])) {
            wp_send_json_error('خطا در دریافت اطلاعات قیمت');
        }
        
        // Find the specific country and get its price
        $service_price = null;
        foreach ($countries['info'] as $country) {
            if (isset($country['country_info']) && $country['country_info']['country_iso_code'] === $country_code) {
                $service_price = $country['price'];
                $api->save_service_record($service_id);
                $api->save_country_record($country,$service_id);
                break;
            }
        }
        if ($service_price === null) {
            wp_send_json_error('قیمت سرویس یافت نشد');
        }
        
        // Convert price to Tomans with profit margin
        $usd_rate = get_option('havn_usd_rate', 50000);
        $profit_margin = get_option('havn_profit_margin', 10);
        $final_price = $service_price * $usd_rate * (1 + $profit_margin / 100);

        $result = $api->purchase_number($service_id, $country_code, $final_price, $user_id);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    

    
    /**
     * Get country flag URL
     */
    public function get_country_flag($country_code) {
        return HAVN_PLUGIN_URL . 'assets/images/flags/' . strtolower($country_code) . '.png';
    }
    
    /**
     * Format price
     */
    public function format_price($price) {
        return number_format($price) . ' تومان';
    }
    
    /**
     * Get status text
     */
    public function get_status_text($status) {
        $statuses = array(
            'pending' => 'در انتظار',
            'processing' => 'در حال پردازش',
            'completed' => 'تکمیل شده',
            'cancelled' => 'لغو شده',
            'failed' => 'ناموفق'
        );
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
    
    /**
     * Get status class
     */
    public function get_status_class($status) {
        $classes = array(
            'pending' => 'status-pending',
            'processing' => 'status-processing',
            'completed' => 'status-completed',
            'cancelled' => 'status-cancelled',
            'failed' => 'status-failed'
        );
        
        return isset($classes[$status]) ? $classes[$status] : 'status-default';
    }
    
    /**
     * AJAX handler for getting number codes
     */
    public function ajax_get_number_codes() {
        check_ajax_referer('havn_get_codes', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('لطفاً ابتدا وارد شوید');
        }
        
        $number_id = sanitize_text_field($_POST['number_id']);
        
        if (empty($number_id)) {
            wp_send_json_error('شناسه شماره الزامی است');
        }
        
        // Verify user owns this number
        $user_id = get_current_user_id();
        $purchase = HAVN_Database::get_purchase_by_number_id($number_id, $user_id);
        
        if (!$purchase) {
            wp_send_json_error('شماره یافت نشد یا متعلق به شما نیست');
        }
        
        // Get codes from API
        $api = new HAVN_API();
        $codes_data = $api->get_number_codes($number_id);
        
        if (empty($codes_data) || !isset($codes_data['codes'])) {
            wp_send_json_success(array('codes' => array()));
        }
        
        wp_send_json_success($codes_data);
    }
    
    /**
     * AJAX handler for getting purchase details
     */
    public function ajax_get_purchase_details() {
        check_ajax_referer('havn_purchase_details', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('لطفاً ابتدا وارد شوید');
        }
        
        $purchase_id = intval($_POST['purchase_id']);
        
        if (empty($purchase_id)) {
            wp_send_json_error('شناسه خرید الزامی است');
        }
        
        // Get purchase details
        $user_id = get_current_user_id();
        $purchase = HAVN_Database::get_purchase($purchase_id);
        
        if (!$purchase || $purchase->user_id != $user_id) {
            wp_send_json_error('شما مجاز به دسترسی به این خرید نیستید');
        }
        
        // Get service and country info
        $api = new HAVN_API();
        
        $purchase_data = array(
            'id' => $purchase->id,
            'number_id' => $purchase->number_id,
            'number' => $purchase->number,
            'service_name' => $purchase->service_name,
            'service_short_name' => $purchase->service_short_name,
            'country_name' => $purchase->country_name,
            'country_iso_code' => $purchase->country_iso_code,
            'service_icon' => $api->get_service_icon_url($purchase->service_short_name),
            'country_flag' => $api->get_country_flag_url($purchase->country_iso_code),
            'cost' => $purchase->cost,
            'status' => $purchase->status,
            'created_at' => $purchase->created_at
        );
        
        wp_send_json_success($purchase_data);
    }
} 