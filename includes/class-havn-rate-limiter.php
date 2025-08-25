<?php
/**
 * Rate Limiter Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class HAVN_Rate_Limiter {
    
    /**
     * Check if user can purchase a new number
     */
    public static function can_purchase_number($user_id) {
        global $wpdb;
        
        // Check if user is blocked
        if (self::is_user_blocked($user_id)) {
            return array(
                'can_purchase' => false,
                'reason' => 'کاربر به دلیل لغو مکرر شماره‌ها مسدود شده است',
                'block_until' => get_user_meta($user_id, 'havn_block_until', true)
            );
        }
        
        // Check pending numbers limit (max 3)
        $pending_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}havn_purchases 
             WHERE user_id = %d AND status_number = 'PENDING'",
            $user_id
        ));
        
        if ($pending_count >= 3) {
            return array(
                'can_purchase' => false,
                'reason' => 'حداکثر 3 شماره در حالت انتظار مجاز است',
                'pending_count' => $pending_count
            );
        }
        
        // Check rate limit (max 3 purchases per 5 minutes)
        $five_minutes_ago = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $recent_purchases = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}havn_purchases 
             WHERE user_id = %d AND created_at >= %s",
            $user_id, $five_minutes_ago
        ));
        
        if ($recent_purchases >= 3) {
            return array(
                'can_purchase' => false,
                'reason' => 'حداکثر 3 خرید در 5 دقیقه مجاز است',
                'recent_purchases' => $recent_purchases
            );
        }
        
        return array('can_purchase' => true);
    }
    
    /**
     * Check if user is blocked
     */
    public static function is_user_blocked($user_id) {
        $block_until = get_user_meta($user_id, 'havn_block_until', true);
        
        if (!$block_until) {
            return false;
        }
        
        $block_until_time = strtotime($block_until);
        $current_time = time();
        
        if ($current_time < $block_until_time) {
            return true;
        } else {
            // Block expired, remove it
            delete_user_meta($user_id, 'havn_block_until');
            return false;
        }
    }
    
    /**
     * Block user for specified duration
     */
    public static function block_user($user_id, $duration_hours = 24) {
        $block_until = date('Y-m-d H:i:s', strtotime("+{$duration_hours} hours"));
        update_user_meta($user_id, 'havn_block_until', $block_until);
    }
    
    /**
     * Check for excessive cancellations and block user if needed
     */
    public static function check_cancellation_pattern($user_id) {
        global $wpdb;
        
        // Check cancellations in last 24 hours
        $one_day_ago = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $cancellations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}havn_purchases 
             WHERE user_id = %d AND status_number = 'CANCELED' AND created_at >= %s",
            $user_id, $one_day_ago
        ));
        
        // If more than 5 cancellations in 24 hours, block user
        if ($cancellations >= 5) {
            self::block_user($user_id, 24); // Block for 24 hours
            return array(
                'blocked' => true,
                'reason' => 'کاربر به دلیل لغو مکرر شماره‌ها مسدود شد',
                'cancellations' => $cancellations
            );
        }
        
        return array('blocked' => false);
    }
    
    /**
     * Get user statistics
     */
    public static function get_user_stats($user_id) {
        global $wpdb;
        
        $stats = array();
        
        // Pending numbers count
        $stats['pending_count'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}havn_purchases 
             WHERE user_id = %d AND status_number = 'PENDING'",
            $user_id
        ));
        
        // Recent purchases (last 5 minutes)
        $five_minutes_ago = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $stats['recent_purchases'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}havn_purchases 
             WHERE user_id = %d AND created_at >= %s",
            $user_id, $five_minutes_ago
        ));
        
        // Recent cancellations (last 24 hours)
        $one_day_ago = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $stats['recent_cancellations'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}havn_purchases 
             WHERE user_id = %d AND status_number = 'CANCELED' AND created_at >= %s",
            $user_id, $one_day_ago
        ));
        
        // Block status
        $stats['is_blocked'] = self::is_user_blocked($user_id);
        $stats['block_until'] = get_user_meta($user_id, 'havn_block_until', true);
        
        return $stats;
    }
    
    /**
     * Log purchase attempt
     */
    public static function log_purchase_attempt($user_id, $success, $reason = '') {
        $log_data = array(
            'user_id' => $user_id,
            'success' => $success,
            'reason' => $reason,
            'timestamp' => current_time('mysql'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );
        
        update_user_meta($user_id, 'havn_last_purchase_attempt', $log_data);
    }
}
