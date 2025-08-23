<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle filters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$service_filter = isset($_GET['service']) ? sanitize_text_field($_GET['service']) : '';
$country_filter = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '';

$filters = array();
if ($search) $filters['search'] = $search;
if ($status_filter) $filters['status'] = $status_filter;
if ($service_filter) $filters['service_id'] = $service_filter;
if ($country_filter) $filters['country_code'] = $country_filter;

$purchases = HAVN_Database::get_purchases($filters);
?>

<div class="wrap">
    <h1>مدیریت درخواست‌ها</h1>
    
    <!-- Filters -->
    <div class="havn-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="havn-purchases">
            
            <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="جستجو..." class="regular-text">
            
            <select name="status">
                <option value="">همه وضعیت‌ها</option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>>در انتظار</option>
                <option value="processing" <?php selected($status_filter, 'processing'); ?>>در حال پردازش</option>
                <option value="completed" <?php selected($status_filter, 'completed'); ?>>تکمیل شده</option>
                <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>لغو شده</option>
                <option value="failed" <?php selected($status_filter, 'failed'); ?>>ناموفق</option>
            </select>
            
            <input type="text" name="service" value="<?php echo esc_attr($service_filter); ?>" placeholder="سرویس" class="regular-text">
            <input type="text" name="country" value="<?php echo esc_attr($country_filter); ?>" placeholder="کشور" class="regular-text">
            
            <input type="submit" class="button" value="فیلتر">
            <a href="<?php echo admin_url('admin.php?page=havn-purchases'); ?>" class="button">پاک کردن فیلترها</a>
        </form>
    </div>
    
    <!-- Purchases Table -->
    <div class="havn-purchases-table">
        <?php if ($purchases): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>کاربر</th>
                        <th>سرویس</th>
                        <th>کشور</th>
                        <th>قیمت</th>
                        <th>وضعیت</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $purchase): ?>
                        <tr>
                            <td><?php echo esc_html($purchase->id); ?></td>
                            <td>
                                <strong><?php echo esc_html($purchase->display_name); ?></strong><br>
                                <small><?php echo esc_html($purchase->user_email); ?></small>
                            </td>
                            <td><?php echo esc_html($purchase->service_id); ?></td>
                            <td><?php echo esc_html($purchase->country_code); ?></td>
                            <td><?php echo esc_html(number_format($purchase->price)) . ' تومان'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($purchase->status); ?>">
                                    <?php echo esc_html($purchase->status); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($purchase->created_at); ?></td>
                            <td>
                                <button class="button button-small" onclick="havnViewDetails(<?php echo $purchase->id; ?>)">جزئیات</button>
                                <button class="button button-small" onclick="havnChangeStatus(<?php echo $purchase->id; ?>)">تغییر وضعیت</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>هیچ درخواستی یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Purchase Details Modal -->
<div id="havn-purchase-modal" class="havn-modal" style="display: none;">
    <div class="havn-modal-content">
        <span class="havn-modal-close">&times;</span>
        <h2>جزئیات درخواست</h2>
        <div id="havn-purchase-details"></div>
    </div>
</div>

<!-- Change Status Modal -->
<div id="havn-status-modal" class="havn-modal" style="display: none;">
    <div class="havn-modal-content">
        <span class="havn-modal-close">&times;</span>
        <h2>تغییر وضعیت</h2>
        <form id="havn-status-form">
            <input type="hidden" id="havn-purchase-id" name="purchase_id">
            
            <p>
                <label for="havn-status">وضعیت جدید:</label>
                <select id="havn-status" name="status" required>
                    <option value="pending">در انتظار</option>
                    <option value="processing">در حال پردازش</option>
                    <option value="completed">تکمیل شده</option>
                    <option value="cancelled">لغو شده</option>
                    <option value="failed">ناموفق</option>
                </select>
            </p>
            
            <p>
                <label for="havn-admin-notes">یادداشت ادمین:</label>
                <textarea id="havn-admin-notes" name="admin_notes" rows="4" class="large-text"></textarea>
            </p>
            
            <p>
                <button type="submit" class="button button-primary">بروزرسانی</button>
                <button type="button" class="button" onclick="havnCloseModal('havn-status-modal')">انصراف</button>
            </p>
        </form>
    </div>
</div>

<script>
function havnViewDetails(purchaseId) {
    // TODO: Implement AJAX call to get purchase details
    document.getElementById('havn-purchase-details').innerHTML = 'در حال بارگذاری...';
    document.getElementById('havn-purchase-modal').style.display = 'block';
}

function havnChangeStatus(purchaseId) {
    document.getElementById('havn-purchase-id').value = purchaseId;
    document.getElementById('havn-status-modal').style.display = 'block';
}

function havnCloseModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking on X or outside
document.addEventListener('DOMContentLoaded', function() {
    var modals = document.querySelectorAll('.havn-modal');
    var closeButtons = document.querySelectorAll('.havn-modal-close');
    
    closeButtons.forEach(function(button) {
        button.onclick = function() {
            button.closest('.havn-modal').style.display = 'none';
        }
    });
    
    window.onclick = function(event) {
        modals.forEach(function(modal) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    // Handle status form submission
    document.getElementById('havn-status-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'havn_update_purchase_status');
        formData.append('nonce', '<?php echo wp_create_nonce("havn_nonce"); ?>');
        
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('وضعیت با موفقیت بروزرسانی شد');
                location.reload();
            } else {
                alert('خطا: ' + data.data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطا در بروزرسانی وضعیت');
        });
    });
});
</script> 