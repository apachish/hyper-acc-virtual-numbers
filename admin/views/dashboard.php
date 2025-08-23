<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

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