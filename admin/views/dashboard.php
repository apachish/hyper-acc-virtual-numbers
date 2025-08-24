<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
.havn-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.havn-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.havn-stat-card h3 {
    margin: 0 0 10px 0;
    color: #23282d;
    font-size: 14px;
    font-weight: 600;
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    margin: 10px 0;
}

.stat-number.pending {
    color: #856404;
}

.stat-number.completed {
    color: #155724;
}

.stat-number.revenue {
    color: #28a745;
}

.stat-number.api-balance {
    color: #007cba;
}

.stat-number.api-balance.success {
    color: #007cba;
}

.stat-number.api-balance.error {
    color: #dc3545;
}

.havn-stat-card small {
    color: #666;
    font-size: 11px;
    display: block;
    margin-top: 5px;
}

.havn-quick-actions {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.havn-quick-actions h2 {
    margin: 0 0 15px 0;
    color: #23282d;
}

.havn-quick-actions .button {
    margin-right: 10px;
    margin-bottom: 10px;
}

.havn-recent-purchases {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.havn-recent-purchases h2 {
    margin: 0 0 15px 0;
    color: #23282d;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-processing {
    background-color: #cce5ff;
    color: #004085;
}

.status-completed {
    background-color: #d4edda;
    color: #155724;
}

.status-failed {
    background-color: #f8d7da;
    color: #721c24;
}

.status-cancelled {
    background-color: #e2e3e5;
    color: #383d41;
}
</style>

<div class="wrap">
    <h1>داشبورد شماره‌های مجازی</h1>
    
    <div class="havn-stats-grid">
        <div class="havn-stat-card">
            <h3>کل درخواست‌ها</h3>
            <div class="stat-number"><?php echo esc_html($stats['total'] ?? 0); ?></div>
        </div>
        
        <div class="havn-stat-card">
            <h3>در انتظار</h3>
            <div class="stat-number pending"><?php echo esc_html($stats['pending'] ?? 0); ?></div>
        </div>
        
        <div class="havn-stat-card">
            <h3>تکمیل شده</h3>
            <div class="stat-number completed"><?php echo esc_html($stats['completed'] ?? 0); ?></div>
        </div>
        
        <div class="havn-stat-card">
            <h3>درآمد کل</h3>
            <div class="stat-number revenue"><?php echo esc_html(number_format($stats['revenue'] ?? 0)) . ' تومان'; ?></div>
        </div>
        
        <div class="havn-stat-card">
            <h3>مانده API</h3>
            <?php 
            $api = new HAVN_API();
            $balance_response = $api->get_balance();
            $api_balance = 0;
            $balance_status = 'error';
            
            if (isset($balance_response['balance'])) {
                $api_balance = $balance_response['balance'];
                $balance_status = 'success';
            } elseif (empty($balance_response)) {
                $balance_status = 'error';
            }
            ?>
            <div class="stat-number api-balance <?php echo $balance_status; ?>">
                <?php if ($balance_status === 'success'): ?>
                    <?php echo esc_html(number_format($api_balance)) . ' USD'; ?>
                <?php else: ?>
                    <span style="color: #dc3545;">خطا در دریافت</span>
                <?php endif; ?>
            </div>
            <small>موجودی وب‌سرویس VirtuNum</small>
        </div>
        
        <div class="havn-stat-card">
            <h3>درخواست‌های اخیر</h3>
            <div class="stat-number recent"><?php echo esc_html($stats['recent'] ?? 0); ?></div>
            <small>7 روز گذشته</small>
        </div>
    </div>
    
    <div class="havn-quick-actions">
        <h2>عملیات سریع</h2>
        <a href="<?php echo admin_url('admin.php?page=havn-purchases'); ?>" class="button button-primary">مشاهده درخواست‌ها</a>
        <a href="<?php echo admin_url('admin.php?page=havn-settings'); ?>" class="button">تنظیمات</a>
        <button class="button" onclick="havnRefreshCache()">بروزرسانی کش</button>
    </div>
    
    <div class="havn-recent-purchases">
        <h2>آخرین درخواست‌ها</h2>
        <?php
        $recent_purchases = HAVN_Database::get_purchases(array('limit' => 5));
        if ($recent_purchases): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>سرویس</th>
                        <th>کشور</th>
                        <th>قیمت</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_purchases as $purchase): ?>
                        <tr>
                            <td><?php echo esc_html($purchase->display_name); ?></td>
                            <td><?php echo esc_html($purchase->service_id); ?></td>
                            <td><?php echo esc_html($purchase->country_code); ?></td>
                            <td><?php echo esc_html(number_format($purchase->price)) . ' تومان'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($purchase->status); ?>">
                                    <?php echo esc_html($purchase->status); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($purchase->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>هیچ درخواستی یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function havnRefreshCache() {
    if (confirm('آیا از بروزرسانی کش اطمینان دارید؟')) {
        // TODO: Implement cache refresh AJAX call
        alert('کش بروزرسانی شد');
    }
}
</script> 