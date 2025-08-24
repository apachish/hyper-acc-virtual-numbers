<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
.havn-service-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.havn-service-logo {
    width: 24px;
    height: 24px;
    object-fit: contain;
    border-radius: 4px;
    flex-shrink: 0;
}

.havn-country-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.havn-country-flag {
    width: 20px;
    height: 15px;
    object-fit: cover;
    border-radius: 2px;
    border: 1px solid #ddd;
    flex-shrink: 0;
}

.havn-cell-content {
    min-width: 0;
}

.havn-cell-content strong {
    display: block;
    font-weight: 600;
    color: #23282d;
}

.havn-cell-content small {
    display: block;
    color: #666;
    font-size: 11px;
    margin-top: 2px;
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

.number-status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.number-status-badge.status-active {
    background-color: #d4edda;
    color: #155724;
}

.number-status-badge.status-canceled {
    background-color: #f8d7da;
    color: #721c24;
}

.number-status-badge.status-expired {
    background-color: #fff3cd;
    color: #856404;
}

.number-status-badge.status-unknown {
    background-color: #e2e3e5;
    color: #383d41;
}
</style>
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
                        <th>شماره</th>
                        <th>وضعیت شماره</th>
                        <th>کد دریافتی</th>
                        <th>قیمت</th>
                        <th>هزینه API</th>
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
                            <td>
                                <div class="havn-service-cell">
                                    <?php 
                                    // Get service logo from database
                                    $service = HAVN_Database::get_service($purchase->service_id);
                                    $logo_url = '';
                                    
                                    if ($service && $service->service_icon) {
                                        $logo_url = get_option('havn_services_base_path', '') . $service->service_icon;
                                    }
                                    ?>
                                    
                                    <?php 
                                    $service_name = $purchase->service_id; // fallback
                                    if ($service && $service->service_full_name) {
                                        $service_name = $service->service_full_name;
                                    }
                                    ?>
                                    
                                    <?php if ($logo_url): ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" 
                                             alt="<?php echo esc_attr($service_name); ?>" 
                                             class="havn-service-logo"
                                             onerror="this.style.display='none';">
                                    <?php endif; ?>
                                    
                                    <div class="havn-cell-content">
                                        <strong><?php echo esc_html($service_name); ?></strong>
                                        <small><?php echo esc_html($purchase->service_id); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="havn-country-cell">
                                    <?php 
                                    // Get country flag from database
                                    $country = HAVN_Database::get_country($purchase->service_id, $purchase->country_code);
                                    $flag_url = '';
                                    
                                    if ($country && $country->country_flag) {
                                        $flag_url = get_option('havn_countries_base_path', '') . $country->country_flag;
                                    }
                                    ?>
                                    
                                    <?php 
                                    $country_name = $purchase->country_code; // fallback
                                    if ($country && $country->country_name) {
                                        $country_name = $country->country_name;
                                    }
                                    ?>
                                    
                                    <?php if ($flag_url): ?>
                                        <img src="<?php echo esc_url($flag_url); ?>" 
                                             alt="<?php echo esc_attr($country_name); ?>" 
                                             class="havn-country-flag"
                                             onerror="this.style.display='none';">
                                    <?php endif; ?>
                                    
                                    <div class="havn-cell-content">
                                        <strong><?php echo esc_html($country_name); ?></strong>
                                        <small><?php echo esc_html($purchase->country_code); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($purchase->number): ?>
                                    <strong><?php echo esc_html($purchase->number); ?></strong><br>
                                    <small>ID: <?php echo esc_html($purchase->number_id); ?></small>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($purchase->status_number): ?>
                                    <?php
                                        $status_text = $purchase->status_number;
                                        switch ($status_text) {
                                            case 'ACTIVE':
                                                $status_class = 'active';
                                                $status_text = 'فعال';
                                                break;
                                            case 'REFUNDED':
                                                $status_class = 'canceled';
                                                $status_text = 'لغو سیستمی';
                                                break;
                                            case 'PENDING':
                                                $status_class = 'expired';
                                                $status_text = 'در انتظار کد';
                                                break;
                                            default:
                                                $status_text = 'نامشخص';
                                                $status_class = 'unknown';

                                    }
                                    ?>
                                    <span class="number-status-badge status-<?php echo $status_class; ?>">
                                        <?php echo esc_html($status_text); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($purchase->number_id): ?>
                                    <?php 
                                    $number_codes = $purchase->code;
                                    if (isset($number_codes['codes']) && is_array($number_codes['code']) && !empty($number_codes['code'])) {
                                        $latest_code = end($number_codes['code']);
                                        echo '<strong>' . esc_html($latest_code['code']) . '</strong>';
                                        if (isset($latest_code['time'])) {
                                            echo '<br><small>' . esc_html($latest_code['time']) . '</small>';
                                        }
                                    } else {
                                        echo '<span style="color: #999;">کد دریافت نشده</span>';
                                    }
                                    ?>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(number_format($purchase->price)) . ' تومان'; ?></td>
                            <td>
                                <?php if ($purchase->cost): ?>
                                    <?php echo esc_html(number_format($purchase->cost, 4)) . ' USD'; ?>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($purchase->status); ?>">
                                    <?php echo esc_html($purchase->status); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($purchase->created_at); ?></td>
                            <td>
                                <?php if ($purchase->number_id && $purchase->status_number !== 'CANCELED'): ?>
                                    <button class="button button-small" onclick="havnGetCodes('<?php echo esc_js($purchase->number_id); ?>', '<?php echo esc_js($purchase->number); ?>')">دریافت کد</button>
                                    <button class="button button-small button-secondary" onclick="havnCancelNumber('<?php echo esc_js($purchase->number_id); ?>', '<?php echo esc_js($purchase->number); ?>')" id="cancel-btn-<?php echo esc_attr($purchase->number_id); ?>">لغو شماره</button>
                                <?php endif; ?>
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
        <h2>کدهای دریافتی</h2>
        <div id="havn-purchase-details"></div>
    </div>
</div>



<script>
function havnGetCodes(numberId, number) {
    // Show loading
    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'در حال دریافت...';
    
    // Send AJAX request
    fetch(ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'havn_get_codes',
            nonce: '<?php echo wp_create_nonce('havn_get_codes'); ?>',
            number_id: numberId
        })
    })
    .then(response => response.json())
    .then(data => {
        button.disabled = false;
        button.textContent = originalText;
        
        if (data.success) {
            // Show codes in modal
            document.getElementById('havn-purchase-details').innerHTML = 
                '<h3>کدهای دریافتی برای شماره: ' + number + '</h3>' +
                '<div class="codes-list">' + data.data + '</div>';
            document.getElementById('havn-purchase-modal').style.display = 'block';
            
            // Hide cancel button if codes were received
            const cancelBtn = document.getElementById('cancel-btn-' + numberId);
            if (cancelBtn) {
                cancelBtn.style.display = 'none';
            }
        } else {
            alert('خطا: ' + data.message);
        }
    })
    .catch(error => {
        button.disabled = false;
        button.textContent = originalText;
        alert('خطا در ارتباط با سرور');
    });
}



function havnCloseModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function havnCancelNumber(numberId, number) {
    if (confirm('آیا از لغو شماره ' + number + ' اطمینان دارید؟\n\nاین عملیات فقط در صورتی موفق خواهد بود که هنوز کد/پیامی دریافت نشده باشد.')) {
        // Show loading
        const button = event.target;
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'در حال لغو...';
        
        // Send AJAX request
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'havn_cancel_number',
                nonce: '<?php echo wp_create_nonce('havn_cancel_number'); ?>',
                number_id: numberId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('عملیات موفق: ' + data.data);
                
                // Hide the cancel and get codes buttons
                const row = button.closest('tr');
                const buttonsCell = row.querySelector('td:last-child');
                if (buttonsCell) {
                    // Remove all buttons
                    const buttons = buttonsCell.querySelectorAll('button');
                    buttons.forEach(btn => {
                        if (btn.textContent.includes('لغو') || btn.textContent.includes('دریافت کد')) {
                            btn.style.display = 'none';
                        }
                    });
                }
                
                // Update status in the table
                const statusCell = row.querySelector('td:nth-child(5)'); // Status column
                if (statusCell) {
                    statusCell.innerHTML = '<span class="number-status-badge status-canceled">لغو شده</span>';
                }
            } else {
                alert('خطا: ' + data.data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطا در ارتباط با سرور');
        })
        .finally(() => {
            // Restore button
            button.disabled = false;
            button.textContent = originalText;
        });
    }
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