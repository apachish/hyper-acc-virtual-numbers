<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="havn-user-purchases">
    <h2>تاریخچه درخواست‌های شما</h2>
    
    <?php if ($purchases && is_array($purchases)): ?>
        <div class="havn-purchases-list">
            <?php foreach ($purchases as $purchase): ?>
                <div class="havn-purchase-item">
                    <div class="purchase-header">
                        <h3>درخواست #<?php echo esc_html($purchase->id); ?></h3>
                        <span class="purchase-date"><?php echo esc_html($purchase->created_at); ?></span>
                    </div>
                    
                    <div class="purchase-details">
                        <div class="detail-row">
                            <span class="detail-label">سرویس:</span>
                            <span class="detail-value"><?php echo esc_html($purchase->service_id); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">کشور:</span>
                            <span class="detail-value"><?php echo esc_html($purchase->country_code); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">قیمت:</span>
                            <span class="detail-value price"><?php echo esc_html($frontend->format_price($purchase->price)); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">وضعیت:</span>
                            <span class="detail-value">
                                <span class="status-badge <?php echo esc_attr($frontend->get_status_class($purchase->status)); ?>">
                                    <?php echo esc_html($frontend->get_status_text($purchase->status)); ?>
                                </span>
                            </span>
                        </div>
                        
                        <?php if ($purchase->admin_notes): ?>
                            <div class="detail-row">
                                <span class="detail-label">یادداشت ادمین:</span>
                                <span class="detail-value admin-notes"><?php echo esc_html($purchase->admin_notes); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($purchase->status === 'pending'): ?>
                        <div class="purchase-actions">
                            <button class="button button-small" onclick="havnCancelPurchase(<?php echo $purchase->id; ?>)">
                                لغو درخواست
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="havn-purchases-summary">
            <h3>خلاصه</h3>
            <div class="summary-stats">
                <div class="summary-item">
                    <span class="summary-label">کل درخواست‌ها:</span>
                    <span class="summary-value"><?php echo count($purchases); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label">در انتظار:</span>
                    <span class="summary-value"><?php echo count(array_filter($purchases, function($p) { return $p->status === 'pending'; })); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label">تکمیل شده:</span>
                    <span class="summary-value"><?php echo count(array_filter($purchases, function($p) { return $p->status === 'completed'; })); ?></span>
                </div>
                
                <div class="summary-item">
                    <span class="summary-label">مجموع هزینه:</span>
                    <span class="summary-value total-cost">
                        <?php 
                        $total_cost = array_sum(array_column($purchases, 'price'));
                        echo esc_html($frontend->format_price($total_cost));
                        ?>
                    </span>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <div class="havn-no-purchases">
            <p>شما هنوز هیچ درخواستی ثبت نکرده‌اید.</p>
            <a href="<?php echo home_url(); ?>" class="button">مشاهده سرویس‌ها</a>
        </div>
    <?php endif; ?>
</div>

<script>
function havnCancelPurchase(purchaseId) {
    if (confirm('آیا از لغو این درخواست اطمینان دارید؟')) {
        // TODO: Implement AJAX call to cancel purchase
        alert('درخواست لغو شد');
        location.reload();
    }
}
</script> 