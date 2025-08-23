<?php
/**
 * VirtuNum API Integration Class
 */

if (!defined('ABSPATH')) {
	exit;
}

class HAVN_API {
	
	private $api_key;
	private $api_url;
	private $cache_duration;
	
	public function __construct() {
		$this->api_key = get_option('havn_virtunum_api_key');
		$this->api_url = get_option('havn_virtunum_api_url');
		$this->cache_duration = get_option('havn_cache_duration', 3600);
	}
	
	/**
	 * Debug method to check API configuration
	 */
	public function debug_config() {
		return array(
			'api_key' => $this->api_key,
			'api_url' => $this->api_url,
			'cache_duration' => $this->cache_duration
		);
	}

    private function get_curl($url,$cache_key)
    {
        // For testing purposes, return mock data if API is not configured
        if (empty($this->api_key) || empty($this->api_url)) {
            return [];
        }


        $cached_data = get_transient($cache_key);


        if ($cached_data !== false) {
            return $cached_data;
        }

        $response = wp_remote_get($this->api_url . $url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);


        if ($data) {
            set_transient($cache_key, $data, $this->cache_duration);
            return $data;
        }

        return [];
    }
	
	/**
	 * Get services list from VirtuNum API
	 */
	public function get_services() {
        $cache_key = 'hyper3_services_list';
        $url = '/services';
        return $this->get_curl($url, $cache_key);
	}

	
	/**
	 * Get countries for a specific service
	 */
	public function get_service_countries($service) {
        $cache_key = 'hyper_service_countries_list_.'.$service;
        $url = '/countries?service='.$service;
        return $this->get_curl($url, $cache_key);
	}

	
	/**
	 * Check service availability in a country
	 */
	public function check_availability($service_id, $country_code) {
		$countries = $this->get_service_countries($service_id);
		foreach ($countries as $country) {
			if ($country['code'] === $country_code) {
				return $country;
			}
		}
		return false;
	}
	
	/**
	 * Purchase virtual number
	 */
	public function purchase_number($service_id, $country_code,$service_price, $user_id) {
		// Get user balance first
		$user_balance = $this->get_user_balance($user_id);

		if ($user_balance < $service_price) {
			return array(
				'success' => false,
				'message' => 'موجودی کافی نیست'
			);
		}
		
		// For testing, simulate successful purchase
		$purchase_data = array(
			'purchase_id' => 'TEST_' . time(),
			'service_id' => $service_id,
			'country_code' => $country_code,
			'price' => $service_price,
			'status' => 'completed'
		);
		
		// Deduct balance from user account via TeraWallet
		$debit_ok = $this->deduct_user_balance($user_id, $service_price, $service_id, $country_code);
		if (!$debit_ok) {
			return array(
				'success' => false,
				'message' => 'کسر از کیف پول ناموفق بود'
			);
		}
		
		// Save purchase record
		$this->save_purchase_record($purchase_data, $user_id, $service_id, $country_code, $service_price);
		
		return array(
			'success' => true,
			'data' => $purchase_data
		);
	}
	
	/**
	 * Get service price with profit margin
	 */
	private function get_service_price($price) {

		
		// Fallback to default pricing
		$usd_rate = get_option('havn_usd_rate', 50000);
		$profit_margin = get_option('havn_profit_margin', 10);
		
		// Calculate final price in Tomans
		$final_price = $price * $usd_rate * (1 + $profit_margin / 100);
		
		return (float) $final_price;
	}
	
	/**
	 * Get user balance from TeraWallet
	 */
	private function get_user_balance($user_id) {
		if (function_exists('woo_wallet') && isset(woo_wallet()->wallet)) {
			// 'edit' context returns numeric value
			return (float) woo_wallet()->wallet->get_wallet_balance($user_id, 'edit');
		}
		// If TeraWallet is not available, treat balance as zero
		return 0.0;
	}
	
	/**
	 * Deduct balance from user account using TeraWallet
	 */
	private function deduct_user_balance($user_id, $amount, $service_id = '', $country_code = '') {
		if (function_exists('woo_wallet') && isset(woo_wallet()->wallet)) {
			$note = sprintf(
				'خرید شماره مجازی - سرویس: %s | کشور: %s',
				(string) $service_id,
				(string) $country_code
			);
			$txn_id = woo_wallet()->wallet->debit($user_id, (float) $amount, $note, array('for' => 'havn_purchase'));
			return !empty($txn_id);
		}
		return false;
	}
	
	/**
	 * Save purchase record to database
	 */
	private function save_purchase_record($api_data, $user_id, $service_id, $country_code, $price) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'havn_purchases';
		
		$wpdb->insert(
			$table_name,
			array(
				'user_id' => $user_id,
				'service_id' => $service_id,
				'country_code' => $country_code,
				'price' => $price,
				'status' => 'pending',
				'api_response' => json_encode($api_data),
				'created_at' => current_time('mysql'),
				'updated_at' => current_time('mysql')
			),
			array('%d', '%s', '%s', '%f', '%s', '%s', '%s')
		);
		
		return $wpdb->insert_id;
	}
	
	/**
	 * Clear cache for specific service
	 */
	public function clear_service_cache($service_id = null) {
		if ($service_id) {
			delete_transient('havn_service_countries_' . $service_id);
		} else {
			delete_transient('havn_services_list');
		}
	}
} 