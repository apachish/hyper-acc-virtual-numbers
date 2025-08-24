<?php
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('دسترسی غیرمجاز');
}

// Handle test actions
$action = isset($_GET['test_action']) ? sanitize_text_field($_GET['test_action']) : '';
$result = '';

if ($action) {
    switch ($action) {
        case 'test_api_connection':
            $api = new HAVN_API();
            $result = $api->debug_config();
            break;
            
        case 'test_get_services':
            $api = new HAVN_API();
            $services = $api->get_services();
            $result = $services;
            break;
            
        case 'test_get_balance':
            $api = new HAVN_API();
            $balance = $api->get_balance();
            $result = $balance;
            break;
            
        case 'test_get_countries':
            $service = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : 'go';
            $api = new HAVN_API();
            $countries = $api->get_service_countries($service);
            $result = $countries;
            break;
            
        case 'test_database_tables':
            global $wpdb;
            $tables = array(
                'havn_purchases' => $wpdb->prefix . 'havn_purchases',
                'havn_services' => $wpdb->prefix . 'havn_services',
                'havn_countries' => $wpdb->prefix . 'havn_countries'
            );
            
            $result = array();
            foreach ($tables as $name => $table) {
                $result[$name] = array(
                    'exists' => $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table,
                    'count' => $wpdb->get_var("SELECT COUNT(*) FROM $table") ?? 0
                );
            }
            break;
            
        case 'test_user_balance':
            $user_id = get_current_user_id();
            if (function_exists('woo_wallet') && isset(woo_wallet()->wallet)) {
                $balance = woo_wallet()->wallet->get_wallet_balance($user_id, 'edit');
                $result = array(
                    'user_id' => $user_id,
                    'balance' => $balance,
                    'wallet_available' => true
                );
            } else {
                $result = array(
                    'user_id' => $user_id,
                    'balance' => 0,
                    'wallet_available' => false
                );
            }
            break;
            
        case 'test_purchase_flow':
            // Simulate purchase flow without actual purchase
            $api = new HAVN_API();
            $user_id = get_current_user_id();
            
            $result = array(
                'step1_check_balance' => $api->get_user_balance($user_id),
                'step2_get_services' => $api->get_services(),
                'step3_get_countries' => $api->get_service_countries('go'),
                'step4_simulate_purchase' => 'Purchase simulation completed'
            );
            break;
            
        case 'clear_cache':
            // Clear all transients
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hyper%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_hyper%'");
            $result = 'Cache cleared successfully';
            break;
            
        case 'test_number_status':
            $number_id = isset($_GET['number_id']) ? sanitize_text_field($_GET['number_id']) : 'test_id';
            $api = new HAVN_API();
            $status = $api->get_number_status($number_id);
            $result = $status;
            break;
            
        case 'test_number_codes':
            $number_id = isset($_GET['number_id']) ? sanitize_text_field($_GET['number_id']) : 'test_id';
            $api = new HAVN_API();
            $codes = $api->get_number_codes($number_id);
            $result = $codes;
            break;
    }
}
?>

<div class="wrap">
    <h1>منوی تست سیستم شماره‌های مجازی</h1>
    
    <div class="test-menu-grid">
        <!-- API Tests -->
        <div class="test-section">
            <h2>تست‌های API</h2>
            <div class="test-buttons">
                <a href="?page=havn-test&test_action=test_api_connection" class="button button-primary">تست اتصال API</a>
                <a href="?page=havn-test&test_action=test_get_services" class="button">تست دریافت سرویس‌ها</a>
                <a href="?page=havn-test&test_action=test_get_balance" class="button">تست موجودی API</a>
                <a href="?page=havn-test&test_action=test_get_countries&service=go" class="button">تست دریافت کشورها</a>
            </div>
        </div>
        
        <!-- Database Tests -->
        <div class="test-section">
            <h2>تست‌های دیتابیس</h2>
            <div class="test-buttons">
                <a href="?page=havn-test&test_action=test_database_tables" class="button">تست جداول دیتابیس</a>
                <a href="?page=havn-test&test_action=test_user_balance" class="button">تست موجودی کاربر</a>
            </div>
        </div>
        
        <!-- System Tests -->
        <div class="test-section">
            <h2>تست‌های سیستم</h2>
            <div class="test-buttons">
                <a href="?page=havn-test&test_action=test_purchase_flow" class="button">تست جریان خرید</a>
                <a href="?page=havn-test&test_action=clear_cache" class="button">پاک کردن کش</a>
            </div>
        </div>
        
        <!-- Number Tests -->
        <div class="test-section">
            <h2>تست‌های شماره</h2>
            <div class="test-buttons">
                <a href="?page=havn-test&test_action=test_number_status&number_id=test_id" class="button">تست وضعیت شماره</a>
                <a href="?page=havn-test&test_action=test_number_codes&number_id=test_id" class="button">تست کدهای شماره</a>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>عملیات سریع</h2>
        <div class="action-buttons">
            <a href="<?php echo admin_url('admin.php?page=havn-dashboard'); ?>" class="button button-primary">داشبورد ادمین</a>
            <a href="<?php echo admin_url('admin.php?page=havn-purchases'); ?>" class="button">مدیریت درخواست‌ها</a>
            <a href="<?php echo admin_url('admin.php?page=havn-settings'); ?>" class="button">تنظیمات</a>
            <a href="<?php echo home_url('/?page_id=29'); ?>" class="button">صفحه سرویس‌ها</a>
            <a href="<?php echo home_url('/?page_id=30'); ?>" class="button">صفحه خریدهای کاربر</a>
        </div>
    </div>
    
    <!-- System Info -->
    <div class="system-info">
        <h2>اطلاعات سیستم</h2>
        <div class="info-grid">
            <div class="info-item">
                <strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?>
            </div>
            <div class="info-item">
                <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
            </div>
            <div class="info-item">
                <strong>Plugin Version:</strong> <?php echo HAVN_VERSION; ?>
            </div>
            <div class="info-item">
                <strong>Current User:</strong> <?php echo wp_get_current_user()->display_name; ?> (ID: <?php echo get_current_user_id(); ?>)
            </div>
            <div class="info-item">
                <strong>API Key:</strong> <?php echo get_option('havn_virtunum_api_key') ? 'تنظیم شده' : 'تنظیم نشده'; ?>
            </div>
            <div class="info-item">
                <strong>API URL:</strong> <?php echo get_option('havn_virtunum_api_url', 'تنظیم نشده'); ?>
            </div>
            <div class="info-item">
                <strong>TeraWallet:</strong> <?php echo function_exists('woo_wallet') ? 'فعال' : 'غیرفعال'; ?>
            </div>
            <div class="info-item">
                <strong>Cache Duration:</strong> <?php echo get_option('havn_cache_duration', 3600); ?> ثانیه
            </div>
        </div>
    </div>
    
    <!-- Test Results -->
    <?php if ($result): ?>
        <div class="test-results">
            <h2>نتیجه تست: <?php echo esc_html($action); ?></h2>
            <div class="result-content">
                <pre><?php echo esc_html(print_r($result, true)); ?></pre>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.test-menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.test-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.test-section h2 {
    margin: 0 0 15px 0;
    color: #23282d;
    font-size: 16px;
}

.test-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.test-buttons .button {
    text-align: left;
    justify-content: flex-start;
}

.quick-actions {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.quick-actions h2 {
    margin: 0 0 15px 0;
    color: #23282d;
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.system-info {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.system-info h2 {
    margin: 0 0 15px 0;
    color: #23282d;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.info-item {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #007cba;
}

.info-item strong {
    color: #23282d;
}

.test-results {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.test-results h2 {
    margin: 0 0 15px 0;
    color: #23282d;
}

.result-content {
    background: #f8f9fa;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 15px;
    overflow-x: auto;
}

.result-content pre {
    margin: 0;
    font-size: 12px;
    line-height: 1.4;
    color: #374151;
}

.button {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #fff;
    color: #23282d;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.button:hover {
    background: #f8f9fa;
    border-color: #007cba;
    color: #007cba;
}

.button-primary {
    background: #007cba;
    color: white;
    border-color: #007cba;
}

.button-primary:hover {
    background: #005a87;
    border-color: #005a87;
    color: white;
}
</style>
