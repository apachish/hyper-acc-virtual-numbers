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

    private function get_curl($url, $cache_key, $method = 'GET', $post_data = null)
    {
        // For testing purposes, return mock data if API is not configured
        if (empty($this->api_key) || empty($this->api_url)) {
            return [];
        }

        $cached_data = null;
        if($cache_key && $method === 'GET')
            $cached_data = get_transient($cache_key);

        if ($cache_key && $cached_data !== false && $method === 'GET') {
            return $cached_data;
        }

        $request_args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );

        if ($method === 'POST' && $post_data !== null) {
            $request_args['method'] = 'POST';
            $request_args['body'] = json_encode($post_data);
        }

        $response = wp_remote_request($this->api_url . $url, $request_args);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data && $method === 'GET') {
            set_transient($cache_key, $data, $this->cache_duration);
            return $data;
        }

        return $data ?: [];
    }
	
	/**
	 * Get services list from VirtuNum API
	 */
	public function get_balance() {
        $cache_key = false;
        $url = '/balance';
        return $this->get_curl($url, $cache_key);
	}

    	/**
	 * Get services list from VirtuNum API
	 */
	public function get_services() {
        $cache_key = 'hyper_services_list';
        $url = '/services';
        $result = $this->get_curl($url, $cache_key);
        
        // Save base_path to settings if available
        if (isset($result['base_path']) && !empty($result['base_path'])) {
            update_option('havn_services_base_path', $result['base_path']);
        }
        
        // Filter out "Full Rent" service
        if (isset($result['info']) && is_array($result['info'])) {
            $result['info'] = array_filter($result['info'], function($service) {
                $serviceName = strtolower($service['service_full_name'] ?? $service['name'] ?? $service['id'] ?? '');
                return $serviceName !== 'full rent';
            });
            // Re-index array after filtering
            $result['info'] = array_values($result['info']);
        }
        
        return $result;
	}

	
	/**
	 * Get countries for a specific service
	 */
	public function get_service_countries($service) {
        $cache_key = 'hyper_service_countries_list_.'.$service;
        $url = '/countries?service='.$service;
        $result = $this->get_curl($url, $cache_key);
        
        // Save base_path to settings if available
        if (isset($result['base_path']) && !empty($result['base_path'])) {
            update_option('havn_countries_base_path', $result['base_path']);
        }
        
        return $result;
	}
	
	/**
	 * Get service icon URL
	 */
	public function get_service_icon_url($service_short_name) {
		$services = $this->get_services();
		$base_path = get_option('havn_services_base_path', 'https://nerd-peek.ams3.cdn.digitaloceanspaces.com/Virtunum/services-logo');
		
		if (isset($services['info'])) {
			foreach ($services['info'] as $service) {
				if ($service['service_short_name'] === $service_short_name) {
					return $base_path . $service['service_icon'];
				}
			}
		}
		
		// Return default icon if service not found
		return HAVN_PLUGIN_URL . 'assets/images/default-service.png';
	}
	
	/**
	 * Get country flag URL
	 */
	public function get_country_flag_url($country_iso_code) {
		$base_path = get_option('havn_countries_base_path', 'https://nerd-peek.ams3.cdn.digitaloceanspaces.com/Virtunum/countries-flag');
		
		// Try to get flag from API base path first
		if ($base_path) {
			return $base_path . '/' . strtolower($country_iso_code) . '.png';
		}
		
		// Fallback to flagcdn.com
		return 'https://flagcdn.com/' . strtolower($country_iso_code) . '.svg';
	}
	

	


    public function get_numbers($service,$country) {
        $cache_key = false;
        $url = "/numbers?service=".$service."&country=".$country;
        $post_data = [];
        return $this->get_curl($url, $cache_key, 'POST', $post_data);
    }

    public function get_number_codes($number_id) {
        $cache_key = false;
        $url = "/numbers/".$number_id."/codes";
        $result = $this->get_curl($url, $cache_key);
        error_log(print_r($result, true));


        // Save codes to database if received
        if (!empty($result) && isset($result['code'])) {
            $this->save_codes_to_database($number_id, $result);
        }
        
        return $result;
    }
    
    /**
     * Save received codes to database
     */
    private function save_codes_to_database($number_id, $codes_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'havn_purchases';
        
        // Prepare codes for storage
        $codes_json = json_encode($codes_data);
        
        // Get status from API response and map it to our status
        $status_number = isset($codes_data['state']) ? $codes_data['state'] : 'PENDING';
        $status = $this->map_api_status_to_local_status($status_number);

        // Get purchase details for refund if needed
        $purchase = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE number_id = %s",
            $number_id
        ));
        
        // Update the purchase record with received codes and status
        $update_data = array(
            'code' => $codes_json,
            'status_number' => $status_number,
            'status' => $status,
            'updated_at' => current_time('mysql')
        );
        
        $wpdb->update(
            $table_name,
            $update_data,
            array('number_id' => $number_id),
            array('%s', '%s', '%s'),
            array('%s')
        );
        
        // Handle refund if status is CANCELED or REFUNDED
        if (($status_number === 'CANCELED' || $status_number === 'REFUNDED') && $purchase) {
            $this->process_refund($purchase);
        }
    }
    
    /**
     * Map API status to local status
     */
    private function map_api_status_to_local_status($api_status) {
        switch (strtoupper($api_status)) {
            case 'COMPLETED':
                return 'COMPLETED';
            case 'CANCELED':
            case 'CANCELLED':
                return 'CANCELED';
            case 'REFUNDED':
                return 'CANCELED'; // Map REFUNDED to CANCELED for consistency
            case 'PENDING':
            default:
                return 'PENDING';
        }
    }
    
    /**
     * Process refund for canceled/refunded numbers
     */
    private function process_refund($purchase) {
        global $wpdb;
        
        // Only refund if status was PENDING (not already processed)
        if ($purchase->status_number !== 'PENDING') {
            return;
        }
        
        $user_id = $purchase->user_id;
        $cost = floatval($purchase->cost);
        
        if ($cost <= 0) {
            return;
        }
        
        // Get current user balance
        $current_balance = get_user_meta($user_id, 'havn_balance', true);
        $current_balance = floatval($current_balance ?: 0);
        
        // Add refund amount to user balance
        $new_balance = $current_balance + $cost;
        update_user_meta($user_id, 'havn_balance', $new_balance);
        
        // Log the refund
        $wpdb->insert(
            $wpdb->prefix . 'havn_transactions',
            array(
                'user_id' => $user_id,
                'type' => 'refund',
                'amount' => $cost,
                'description' => "بازگشت مبلغ برای شماره {$purchase->number} (وضعیت: {$purchase->status_number})",
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%f', '%s', '%s')
        );
    }
    
    /**
     * Check and update status of all pending numbers
     */
    public function check_pending_numbers_status() {
        global $wpdb;
        
        // Get all pending numbers
        $pending_numbers = $wpdb->get_results(
            "SELECT number_id FROM {$wpdb->prefix}havn_purchases 
             WHERE status_number = 'PENDING' AND number_id IS NOT NULL"
        );
        
        $updated_count = 0;
        $refunded_count = 0;
        
        foreach ($pending_numbers as $number) {
            try {
                $status_result = $this->get_number_status($number->number_id);
                
                if (!empty($status_result) && isset($status_result['state'])) {
                    $api_status = $status_result['state'];
                    $local_status = $this->map_api_status_to_local_status($api_status);
                    
                    // Update status if changed
                    if ($local_status !== 'PENDING') {
                        $wpdb->update(
                            $wpdb->prefix . 'havn_purchases',
                            array(
                                'status_number' => $local_status,
                                'updated_at' => current_time('mysql')
                            ),
                            array('number_id' => $number->number_id),
                            array('%s', '%s'),
                            array('%s')
                        );
                        
                        $updated_count++;
                        
                        // Process refund for canceled/refunded numbers
                        if ($local_status === 'CANCELED' || $api_status === 'REFUNDED') {
                            $purchase = $wpdb->get_row($wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}havn_purchases WHERE number_id = %s",
                                $number->number_id
                            ));
                            
                            if ($purchase) {
                                $this->process_refund($purchase);
                                $refunded_count++;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Log error but continue with other numbers
                error_log("Error checking status for number {$number->number_id}: " . $e->getMessage());
            }
        }
        
        return array(
            'updated_count' => $updated_count,
            'refunded_count' => $refunded_count
        );
    }

    public function get_number_status($number_id) {
        $cache_key = false;
        $url = "/numbers/".$number_id."/state";
        $result = $this->get_curl($url, $cache_key);
        
        // Update local status if API status is different
        if (!empty($result) && isset($result['state'])) {
            $this->update_number_status_from_api($number_id, $result['state']);
        }
        
        return $result;
    }
    
    /**
     * Update number status from API response
     */
    private function update_number_status_from_api($number_id, $api_status) {
        global $wpdb;
        
        $local_status = $this->map_api_status_to_local_status($api_status);
        
        // Get current purchase status
        $purchase = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}havn_purchases WHERE number_id = %s",
            $number_id
        ));
        
        if (!$purchase) {
            return;
        }
        
        // Only update if status has changed
        if ($purchase->status_number !== $local_status) {
            $wpdb->update(
                $wpdb->prefix . 'havn_purchases',
                array(
                    'status_number' => $local_status,
                    'updated_at' => current_time('mysql')
                ),
                array('number_id' => $number_id),
                array('%s', '%s'),
                array('%s')
            );
            
            // Process refund if status changed to CANCELED/REFUNDED
            if (($local_status === 'CANCELED' || $api_status === 'REFUNDED') && $purchase->status_number === 'PENDING') {
                $this->process_refund($purchase);
            }
        }
    }
    
    /**
     * Cancel a rented phone number
     */
    public function cancel_number($number_id) {
        global $wpdb;
        
        // Get purchase details before cancellation
        $purchase = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}havn_purchases WHERE number_id = %s",
            $number_id
        ));
        
        $url = "/numbers/{$number_id}/state?state=CANCELED";
        
        $response = wp_remote_request($this->api_url . $url, array(
            'method' => 'PATCH',
            'headers' => array(
                'Authorization' => 'Basic ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'خطا در ارتباط با سرور'
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 204) {
            // Update local status to CANCELED
            $wpdb->update(
                $wpdb->prefix . 'havn_purchases',
                array(
                    'status' => 'CANCELED',
                    'status_number' => 'CANCELED',
                    'updated_at' => current_time('mysql')
                ),
                array('number_id' => $number_id),
                array('%s', '%s'),
                array('%s')
            );
            
            // Process refund if purchase exists and was PENDING
            if ($purchase && $purchase->status_number === 'PENDING') {
                $this->process_refund($purchase);
            }
            
            return array(
                'success' => true,
                'message' => 'شماره با موفقیت لغو شد'
            );
        } elseif ($status_code === 400) {
            return array(
                'success' => false,
                'message' => 'پارامترهای نامعتبر'
            );
        } elseif ($status_code === 401) {
            return array(
                'success' => false,
                'message' => 'احراز هویت ناموفق'
            );
        } elseif ($status_code === 404) {
            return array(
                'success' => false,
                'message' => 'شماره یافت نشد'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'خطای نامشخص: ' . $status_code
            );
        }
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
	public function purchase_number($service_id, $country_code, $service_price, $user_id) {
		// Get user balance first
		$user_balance = $this->get_user_balance($user_id);


		if ($user_balance < ($service_price*10)) {
			return array(
				'success' => false,
				'message' => 'موجودی کافی نیست'
			);
		}

		
		// Get service and country names for display
		
		// First, save purchase record with pending status
		$purchase_id = $this->save_purchase_record_pending($user_id, $service_id, $country_code, $service_price);
		
		if (!$purchase_id) {
			return array(
				'success' => false,
				'message' => 'خطا در ذخیره درخواست'
			);
		}
		
		// Deduct balance from user account via TeraWallet
		$debit_ok = $this->deduct_user_balance($user_id, $service_price, $service_id, $country_code);
        error_log(print_r($debit_ok, true));

        if (!$debit_ok) {
			// Update status to failed if debit fails
			$this->update_purchase_status($purchase_id, 'failed', 'کسر از کیف پول ناموفق بود');
			return array(
				'success' => false,
				'message' => 'کسر از کیف پول ناموفق بود'
			);
		}
        error_log(print_r([$service_id, $country_code], true));

        // Call get_numbers API to allocate a number
		$api_response = $this->get_numbers($service_id, $country_code);
        error_log(print_r($api_response, true));

        if (empty($api_response) || !isset($api_response['number_id'])) {
			// Refund the user's money since API failed
			$refund_ok = $this->refund_user_balance($user_id, $service_price, $service_id, $country_code, 'خطا در تخصیص شماره از API');
			
			// Update status to failed if API fails
			$this->update_purchase_status($purchase_id, 'failed', 'خطا در تخصیص شماره از API');
			
			return array(
				'success' => false,
				'message' => 'خطا در تخصیص شماره از API' . ($refund_ok ? ' - پول به حساب شما برگشت' : ' - خطا در بازگشت پول')
			);
		}
		
		// Extract data from API response
		$number_id = $api_response['number_id'];
		$number = $api_response['number'];
		$cost = $api_response['cost'];
		
		// Update purchase record with completed status and API data
		$update_ok = $this->update_purchase_completed($purchase_id, $number_id, $number, $cost, $api_response);
		
		if (!$update_ok) {
			// Refund the user's money since database update failed
			//$refund_ok = $this->refund_user_balance($user_id, $service_price, $service_id, $country_code, 'خطا در به‌روزرسانی اطلاعات خرید');
			
			// Update status to failed
			$this->update_purchase_status($purchase_id, 'failed', 'خطا در به‌روزرسانی اطلاعات خرید');
			
			return array(
				'success' => false,
				'message' => 'خطا در به‌روزرسانی اطلاعات خرید' //. ($refund_ok ? ' - پول به حساب شما برگشت' : ' - خطا در بازگشت پول')
			);
		}
		
		// Prepare success response
		$purchase_data = array(
			'purchase_id' => $purchase_id,
			'service_id' => $service_id,
			'country_code' => $country_code,
			'price' => $service_price,
			'number_id' => $number_id,
			'number' => $number,
			'cost' => $cost,
			'status' => 'completed'
		);
		
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
	public function get_user_balance($user_id) {
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

        //$amount *=10;
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
	 * Refund balance to user account using TeraWallet
	 */
	public function refund_user_balance($user_id, $amount, $service_id = '', $country_code = '', $reason = '') {
		if (function_exists('woo_wallet') && isset(woo_wallet()->wallet)) {
			$note = sprintf(
				'بازگشت پول خرید شماره مجازی - سرویس: %s | کشور: %s | دلیل: %s',
				(string) $service_id,
				(string) $country_code,
				(string) $reason
			);
			$txn_id = woo_wallet()->wallet->credit($user_id, (float) $amount, $note, array('for' => 'havn_refund'));
			return !empty($txn_id);
		}
		return false;
	}
	
	/**
	 * Save purchase record with pending status
	 */
	private function save_purchase_record_pending($user_id, $service_id, $country_code, $price) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'havn_purchases';
		
		$wpdb->insert(
			$table_name,
			array(
				'user_id' => $user_id,
				'service_id' => $service_id,
				'country_code' => $country_code,
				'price' => $price,
				'number_id' => '',
				'number' => '',
				'cost' => 0,
				'status' => 'pending',
				'status_number' => 'PENDING',
				'api_response' => '',
				'created_at' => current_time('mysql'),
				'updated_at' => current_time('mysql')
			),
			array('%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s')
		);
		
		return $wpdb->insert_id;
	}
	
	/**
	 * Update purchase status
	 */
	private function update_purchase_status($purchase_id, $status, $admin_notes = '') {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'havn_purchases';
		
		return $wpdb->update(
			$table_name,
			array(
				'status' => $status,
				'status_number' => $status,
				'admin_notes' => $admin_notes,
				'updated_at' => current_time('mysql')
			),
			array('id' => $purchase_id),
			array('%s', '%s', '%s'),
			array('%d')
		);
	}
	
	/**
	 * Update purchase record with completed status and API data
	 */
	private function update_purchase_completed($purchase_id, $number_id, $number, $cost, $api_response) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'havn_purchases';
		
		return $wpdb->update(
			$table_name,
			array(
				'number_id' => $number_id,
				'number' => $number,
				'cost' => $cost,
				'status' => 'pending',
				'api_response' => json_encode($api_response),
				'updated_at' => current_time('mysql')
			),
			array('id' => $purchase_id),
			array('%s', '%s', '%f', '%s', '%s', '%s'),
			array('%d')
		);
	}
	
	/**
	 * Save purchase record to database (legacy function - kept for compatibility)
	 */
	private function save_purchase_record($api_data, $user_id, $service_id, $country_code, $price, $service_name = '', $country_name = '', $number_id = '', $number = '', $cost = 0) {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'havn_purchases';
		
		$wpdb->insert(
			$table_name,
			array(
				'user_id' => $user_id,
				'service_id' => $service_id,
				'service_name' => $service_name,
				'country_code' => $country_code,
				'country_name' => $country_name,
				'price' => $price,
				'number_id' => $number_id,
				'number' => $number,
				'cost' => $cost,
				'status' => 'completed',
				'api_response' => json_encode($api_data),
				'created_at' => current_time('mysql'),
				'updated_at' => current_time('mysql')
			),
			array('%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%f', '%s', '%s', '%s', '%s')
		);
		
		return $wpdb->insert_id;
	}

    /**
     * Save service record to database (legacy function - kept for compatibility)
     */
    public function save_service_record($service_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'havn_services';
        $services = $this->get_services();
        $service = [];
        if(empty($services['info'])) return null;
        foreach ($services['info'] as $item) {
            if (isset($item['service_short_name']) && $item['service_short_name'] === $service_id) {
                $service = $item;
                break;
            }
        }
        
        // Check if service already exists
        $existing_service = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE service_short_name = %s",
                $service_id
            )
        );

        $data = array(
            'service_short_name' => $service['service_short_name'],
            'service_full_name' => $service['service_full_name'],
            'service_icon' => $service['service_icon'] ?? '',
            'is_active' => 1,
            'updated_at' => current_time('mysql')
        );

        if ($existing_service) {
            // Update existing record
            $wpdb->update(
                $table_name,
                $data,
                array('service_short_name' => $service_id),
                array('%s', '%s', '%s', '%d', '%s'),
                array('%s')
            );
            return $existing_service->id;
        } else {
            // Insert new record
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(
                $table_name,
                $data,
                array('%s', '%s', '%s', '%d', '%s', '%s')
            );
            return $wpdb->insert_id;
        }
    }
	
	/**
	 * Get service name from database
	 */
	private function get_service_name($service_id) {
		$service = HAVN_Database::get_service($service_id);
		if ($service) {
			return $service->service_full_name;
		}
		return $service_id;
	}


    /**
     * Save countrt record to database (legacy function - kept for compatibility)
     */
    public function save_country_record($country,$service_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'havn_countries';

        $country_info = $country['country_info'];
        $country_iso_code = $country_info['country_iso_code'];
        
        // Check if country already exists
        $existing_country = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE country_iso_code = %s",
                $country_iso_code
            )
        );

        $data = array(
            'service_short_name' => $service_id,
            'country_code' => $country_info["country_code"],
            'country_iso_code' => $country_iso_code,
            'country_name' => $country_info['country_name'],
            'country_flag' => $country_info['country_flag'] ?? '',
            'count_available' => $country['count'] ?? 0,
            'price_usd' => $country['price'] ?? 0.0000,
            'rental_time' => 0,
            'is_active' => 1,
            'updated_at' => current_time('mysql')
        );

        if ($existing_country) {
            // Update existing record
            $wpdb->update(
                $table_name,
                $data,
                array('country_iso_code' => $country_iso_code),
                array('%s', '%s', '%s', '%s', '%d', '%f', '%d', '%d', '%s'),
                array('%s')
            );
            return $existing_country->id;
        } else {
            // Insert new record
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(
                $table_name,
                $data,
                array('%s', '%s', '%s', '%s', '%d', '%f', '%d', '%d', '%s', '%s')
            );
            return $wpdb->insert_id;
        }
    }
	/**
	 * Get country name from database
	 */
	public function get_country_name($country_code) {
		// We need service_id to get country info, so this will be called from purchase context
		// For now, return the country code as fallback
		return $country_code;
	}
	

	
	/**
	 * Clear cache for specific service
	 */
	public function clear_service_cache($service_id = null) {
		if ($service_id) {
			delete_transient('hyper_service_countries_list_' . $service_id);
		} else {
			delete_transient('hyper_services_list');
		}
	}
} 
