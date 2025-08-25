<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get current page for pagination
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20; // Items per page

// Get services with pagination
$api = new HAVN_API();
$all_services = $api->get_services();

if (empty($all_services['info']) && !is_array($all_services['info'])) {
    echo '<div class="havn-no-services"><p>هیچ سرویسی در حال حاضر موجود نیست.</p></div>';
    return;
}

// If API returns associative array with base_path and items, normalize to list
$base_path = !empty($all_services['base_path'])?$all_services['base_path']:'https://nerd-peek.ams3.cdn.digitaloceanspaces.com/Virtunum/services-logo';
$services_list = isset($all_services['info']) && is_array($all_services['info']) ? $all_services['info'] : [];

// Pagination logic
$total_services = count($services_list);
$total_pages = ceil($total_services / $per_page);
$offset = ($current_page - 1) * $per_page;
$services = array_slice($services_list, $offset, $per_page);

// Pass all services to JavaScript for client-side pagination
$all_services_json = json_encode($services_list);
?>

<div class="havn-virtual-numbers-container">
  <div class="rent-wrapper">
    <!-- Header -->
    <div class="rent-header">
      <h1 class="rent-title"><?php echo esc_html(get_option('havn_page_title', 'شماره‌های مجازی')); ?></h1>
      <div class="search-box">
        <input type="text" id="havn-services-search" placeholder="جستجو در سرویس‌ها..." />
        <button class="clear-search" id="clear-search" style="display: none;">پاک کردن جستجو</button>
        <button class="info-btn" id="info-btn" onclick="openModal()" title="اطلاعات">i</button>
      </div>
    </div>
    
    <!-- User Limits Info -->
    <div class="user-limits-info" id="user-limits-info" style="display: none;">
      <div class="limits-container">
        <div class="limit-item">
          <span class="limit-label">شماره‌های در انتظار:</span>
          <span class="limit-value" id="pending-count">0</span>
          <span class="limit-max">/ 3</span>
        </div>
        <div class="limit-item">
          <span class="limit-label">خریدهای اخیر (5 دقیقه):</span>
          <span class="limit-value" id="recent-purchases">0</span>
          <span class="limit-max">/ 3</span>
        </div>
        <div class="limit-item">
          <span class="limit-label">لغوهای اخیر (24 ساعت):</span>
          <span class="limit-value" id="recent-cancellations">0</span>
          <span class="limit-max">/ 5</span>
        </div>
      </div>
      <div class="block-status" id="block-status" style="display: none;">
        <span class="block-message">کاربر مسدود شده است</span>
        <span class="block-until" id="block-until"></span>
      </div>
    </div>
      <input type="hidden" value="<?php echo $base_path; ?>" id="base_path_js">
      <textarea style="display: none"  id="all_services_json"><?php echo $all_services_json; ?></textarea>
      <input type="hidden" value="<?php echo get_option('havn_usd_rate', 50000); ?>" id="havn_usd_rate">
      <input type="hidden" value="<?php echo get_option('havn_profit_margin', 10); ?>" id="havn_profit_margin">
    <!-- Search Results Info -->
    <div class="search-results-info" id="search-results-info">
      <button class="clear-search" id="clear-search">پاک کردن جستجو</button>
      <span id="search-results-text"></span>
    </div>

    <!-- Main Content -->
    <div class="rent-body" id="main-content">
      <!-- Services List -->
      <div class="rent-list" id="services-section">


        <!-- Services Container -->
        <div class="services-container" id="services-container">
          <!-- Services will be loaded here -->
        </div>

        <!-- Pagination Footer -->
        <div class="rent-footer">
          <div class="pagination-controls">
            <button class="btn small" id="prev-page" onclick="changePage(currentPage - 1)">&lt;</button>
            <div class="page-numbers" id="page-numbers">
              <!-- Page numbers will be generated here -->
            </div>
            <button class="btn small" id="next-page" onclick="changePage(currentPage + 1)">&gt;</button>
          </div>
        </div>
        <div class="pagination-info" id="pagination-info">
          نمایش 1 تا 20 از <?php echo $total_services; ?> سرویس
        </div>
      </div>

      <!-- Info Modal -->
      <div class="info-modal" id="info-modal" style="display: none;">
        <div class="info-modal-overlay" onclick="closeModal()"></div>
        <div class="info-modal-content">
          <div class="info-modal-header">
            <h3><?php echo esc_html(get_option('havn_page_title', 'شماره‌های مجازی')); ?></h3>
            <button class="close-modal" onclick="closeModal()">×</button>
          </div>
          <div class="info-modal-body">
            <?php echo get_option('havn_info_text', 'اطلاعات شماره‌های مجازی در اینجا قرار می‌گیرد.'); ?>
          </div>
        </div>
      </div>

      <!-- Purchase Confirmation Modal -->
      <div class="purchase-modal" id="purchase-confirmation-modal" style="display: none;">
        <div class="purchase-modal-overlay" onclick="cancelPurchase()"></div>
        <div class="purchase-modal-content">
          <div class="purchase-modal-header">
            <h3>تأیید خرید</h3>
            <button class="close-modal" onclick="cancelPurchase()">×</button>
          </div>
          <div class="purchase-modal-body">
            <div class="purchase-icon">🛒</div>
            <p>آیا از خرید این شماره اطمینان دارید؟</p>
            <p class="purchase-note">مبلغ از کیف پول شما کسر خواهد شد.</p>
          </div>
          <div class="purchase-modal-footer">
            <button class="btn btn-secondary" onclick="cancelPurchase()">انصراف</button>
            <button class="btn btn-primary" id="confirm-purchase-btn" onclick="confirmPurchase()">تأیید خرید</button>
          </div>
        </div>
      </div>

      <!-- Success Modal -->
      <div class="success-modal" id="success-modal" style="display: none;">
        <div class="success-modal-overlay" onclick="closeSuccessModal()"></div>
        <div class="success-modal-content">
          <div class="success-modal-header">
            <h3>موفقیت</h3>
            <button class="close-modal" onclick="closeSuccessModal()">×</button>
          </div>
          <div class="success-modal-body">
            <div class="success-icon">✅</div>
            <p id="success-message"></p>
          </div>
          <div class="success-modal-footer">
            <button class="btn btn-primary" onclick="closeSuccessModal()">باشه</button>
          </div>
        </div>
      </div>

      <!-- Error Modal -->
      <div class="error-modal" id="error-modal" style="display: none;">
        <div class="error-modal-overlay" onclick="closeErrorModal()"></div>
        <div class="error-modal-content">
          <div class="error-modal-header">
            <h3>خطا</h3>
            <button class="close-modal" onclick="closeErrorModal()">×</button>
          </div>
          <div class="error-modal-body">
            <div class="error-icon">❌</div>
            <p id="error-message"></p>
          </div>
          <div class="error-modal-footer">
            <button class="btn btn-primary" onclick="closeErrorModal()">باشه</button>
          </div>
        </div>
      </div>

      <!-- Countries Table -->
      <div class="rent-table-box">
        <div class="rent-filters">
          <span>کشور</span>
          <span>قیمت (تومان)</span>
          <span>موجودی</span>
          <span>عملیات</span>
        </div>
        <div class="rent-table" id="countries-table">
          <div class="row">
            <div class="col" style="grid-column: 1 / -1; text-align: center; color: #FC5A44; padding: 40px 20px;">
              <div style="font-size: 16px; margin-bottom: 8px;">👆</div>
              برای مشاهده کشورهای موجود، روی یک سرویس کلیک کنید
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Initialize global variables
window.allServices = <?php echo $all_services_json; ?>;
window.currentPage = 1;
window.perPage = 20;
window.basePath = '<?php echo $base_path; ?>';
window.currentService = null;
window.searchQuery = '';
window.havnUsdRate = <?php echo get_option('havn_usd_rate', 50000); ?>;
window.havnProfitMargin = <?php echo get_option('havn_profit_margin', 10); ?>;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initServicesPage === 'function') {
        window.initServicesPage();
    } else {
        console.error('initServicesPage function not found');
        // Fallback: manually render services
        if (window.allServices && window.allServices.length > 0) {
            const container = document.getElementById('services-container');
            let html = '';
            window.allServices.slice(0, 20).forEach(service => {
                const serviceName = service.service_full_name || service.name || service.id || '';
                const serviceIcon = service.service_icon || '';
                const logoUrl = serviceIcon ? (window.basePath + serviceIcon) : '';

                html += `
                    <div class="list-item" onclick="selectService('${service.service_short_name}')">
                        <div class="service-info">
                            <img src="${logoUrl}" alt="${serviceName}" class="service-logo" onerror="this.style.display='none'">
                            <div class="service-details">
                                <div class="service-name">${serviceName}</div>
                                <div class="service-status">سرویس فعال</div>
                            </div>
                        </div>
                        <button class="view-btn" onclick="event.stopPropagation(); viewService('${service.service_short_name}')">
                            مشاهده 👁
                        </button>
                    </div>
                `;
            });
            container.innerHTML = html;
        }
    }
    
    // Add event listener for modal overlay
    const modal = document.getElementById('info-modal');
    const overlay = modal.querySelector('.info-modal-overlay');
    
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) {
        closeModal();
      }
    });
    
    // Close modal with ESC key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeModal();
        cancelPurchase();
        closeSuccessModal();
        closeErrorModal();
      }
    });
  });


</script>
