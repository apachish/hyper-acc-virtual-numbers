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
  // Global variables
  let allServices = <?php echo $all_services_json; ?>;
  let currentPage = 1;
  let perPage = 20;
  let basePath = '<?php echo $base_path; ?>';
  let currentService = null;

  // Open info modal
  function openModal() {
    const modal = document.getElementById('info-modal');
    modal.style.setProperty('display', 'flex', 'important');
    document.body.style.overflow = 'hidden';
  }

  // Close info modal
  function closeModal() {
    const modal = document.getElementById('info-modal');
    modal.style.setProperty('display', 'none', 'important');
    document.body.style.overflow = 'auto';
  }

  // Initialize
  document.addEventListener('DOMContentLoaded', function() {
    renderServicesPage(allServices.slice(0, perPage), 1);
    attachSearchListeners();
    
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

  // Render services page
  function renderServicesPage(services, page) {
    currentPage = page;
    const container = document.getElementById('services-container');
    let html = '';

    if (services.length === 0) {
      html = '<div style="text-align: center; padding: 40px; color: #6b7280;">هیچ سرویسی یافت نشد</div>';
    } else {
      services.forEach(service => {
        const serviceName = service.service_full_name || service.name || service.id || '';
        const serviceIcon = service.service_icon || '';
        const logoUrl = serviceIcon ? (basePath + serviceIcon) : '';

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
    }

    container.innerHTML = html;
    generatePaginationHTML(page);
    updatePaginationInfo(page);
  }

  // Generate pagination HTML
  function generatePaginationHTML(page) {
    const totalPages = Math.ceil(allServices.length / perPage);
    const pageNumbers = document.getElementById('page-numbers');
    let html = '';

    // Show only 3 page numbers maximum
    let startPage = Math.max(1, page - 1);
    let endPage = Math.min(totalPages, page + 1);

    // Adjust if we're at the beginning or end
    if (page <= 2) {
      startPage = 1;
      endPage = Math.min(3, totalPages);
    } else if (page >= totalPages - 1) {
      startPage = Math.max(1, totalPages - 2);
      endPage = totalPages;
    }

    for (let i = startPage; i <= endPage; i++) {
      const isCurrent = i === page;
      html += `<button class="page ${isCurrent ? 'current' : ''}" onclick="changePage(${i})" style="
        background: ${isCurrent ? '#FC5A44' : '#ffffff'} !important;
        color: ${isCurrent ? '#ffffff' : '#6b7280'} !important;
        border: 2px solid ${isCurrent ? '#FC5A44' : '#e5e7eb'} !important;
        box-shadow: ${isCurrent ? '0 2px 8px rgba(252, 90, 68, 0.3)' : 'none'} !important;
        font-weight: ${isCurrent ? '600' : '500'} !important;
      ">${i}</button>`;
    }
    pageNumbers.innerHTML = html;

    // Update prev/next buttons
    document.getElementById('prev-page').disabled = page <= 1;
    document.getElementById('next-page').disabled = page >= totalPages;
  }

  // Update pagination info
  function updatePaginationInfo(page) {
    const start = (page - 1) * perPage + 1;
    const end = Math.min(page * perPage, allServices.length);
    const info = document.getElementById('pagination-info');
    info.innerHTML = `نمایش ${start} تا ${end}از ${allServices.length} سرویس`;
  }

  // Change page
  function changePage(page) {
    const totalPages = Math.ceil(allServices.length / perPage);
    if (page < 1 || page > totalPages) return;

    const start = (page - 1) * perPage;
    const end = start + perPage;
    const services = allServices.slice(start, end);

    renderServicesPage(services, page);
    window.history.pushState({}, '', `?page=${page}`);
  }

  // Select service
  function selectService(serviceShortName) {
    const items = document.querySelectorAll('.list-item');
    items.forEach(item => item.classList.remove('active'));
    event.currentTarget.classList.add('active');
    currentService = serviceShortName;
  }

  // View service countries
  function viewService(serviceShortName) {
    currentService = serviceShortName;

    // Show loading
    document.getElementById('countries-table').innerHTML = `
      <div class="row">
        <div class="col" style="grid-column: 1 / -1; text-align: center; color: #FC5A44; padding: 40px 20px;">
          <div style="font-size: 16px; margin-bottom: 8px;">⏳</div>
          در حال بارگذاری کشورها...
        </div>
      </div>
    `;

    // Make AJAX request
    fetch(havn_ajax.ajax_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `action=havn_get_service_countries&service_id=${serviceShortName}&nonce=${havn_ajax.nonce}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        renderCountries(data.data, serviceShortName);
      } else {
        document.getElementById('countries-table').innerHTML = `
          <div class="row">
            <div class="col" style="grid-column: 1 / -1; text-align: center; color: #FC5A44; padding: 40px 20px;">
              <div style="font-size: 16px; margin-bottom: 8px;">❌</div>
              خطا در بارگذاری کشورها: ${data.data || 'خطای نامشخص'}
            </div>
          </div>
        `;
      }
    })
    .catch(error => {
      document.getElementById('countries-table').innerHTML = `
        <div class="row">
          <div class="col" style="grid-column: 1 / -1; text-align: center; color: #FC5A44; padding: 40px 20px;">
            <div style="font-size: 16px; margin-bottom: 8px;">❌</div>
            خطا در ارتباط با سرور
          </div>
        </div>
      `;
    });
  }

  // Render countries
  function renderCountries(data, serviceId) {
    let html = '';

    if (data && data.info && data.info.length > 0) {
      data.info.forEach(c => {
        const countryInfo = c.country_info || {};
        const name = countryInfo.country_name || '';
        const code = countryInfo.country_iso_code || '';
        const flag = countryInfo.country_flag || '';
        const price = c.price || 0;
        const stock = c.count || 0;
        const disabled = stock <= 0;

        // Convert price to Tomans with profit margin
        const usdRate = <?php echo get_option('havn_usd_rate', 50000); ?>;
        const profitMargin = <?php echo get_option('havn_profit_margin', 10); ?>;
        const finalPrice = price * usdRate * (1 + profitMargin / 100);

        // Build flag URL
        const flagUrl = flag ?
          (flag.startsWith('http') ? flag : 'https://nerd-peek.ams3.cdn.digitaloceanspaces.com/Virtunum/countries-flag' + flag) :
          '';

        html += `
          <div class="row${disabled ? ' disabled' : ''}">
            <div class="col">
              ${flagUrl ? `<img src="${flagUrl}" alt="${name}" class="country-flag" onerror="this.style.display='none'">` : ''}
              <span>${name}</span>
            </div>
            <div class="col">${new Intl.NumberFormat('fa-IR').format(Math.round(finalPrice))} تومان</div>
            <div class="col">${new Intl.NumberFormat('fa-IR').format(stock)} عدد</div>
            <div class="col">
              ${disabled ?
                '<button class="btn disabled" disabled>غیرفعال</button>' :
                '<button class="btn" onclick="event.stopPropagation(); havnPurchaseNumber(\''+serviceId+'\', \''+code+'\')">دریافت</button>'
              }
            </div>
          </div>
        `;
      });
    } else {
      html = `
        <div class="row">
          <div class="col" style="grid-column: 1 / -1; text-align: center; color: #FC5A44; padding: 40px 20px;">
            <div style="font-size: 16px; margin-bottom: 8px;">📭</div>
            هیچ کشوری برای این سرویس موجود نیست
          </div>
        </div>
      `;
    }

    document.getElementById('countries-table').innerHTML = html;
  }

  // Search functionality
  const searchInput = document.getElementById('havn-services-search');
  const searchResultsInfo = document.getElementById('search-results-info');
  const searchResultsText = document.getElementById('search-results-text');
  const clearSearchBtn = document.getElementById('clear-search');
  let searchQuery = '';

  function attachSearchListeners() {
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        searchQuery = this.value.trim().toLowerCase();
        performSearch();
      });
    }

    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        searchQuery = '';
        performSearch();
      });
    }
  }

  function performSearch() {
    if (!searchQuery) {
      renderServicesPage(allServices.slice((currentPage - 1) * perPage, currentPage * perPage), currentPage);
      searchResultsInfo.classList.remove('show');
      return;
    }

    const filteredServices = allServices.filter(function(service) {
      const serviceName = (service.service_full_name || service.name || service.id || '').toLowerCase();
      return serviceName.indexOf(searchQuery) !== -1;
    });

    if (filteredServices.length > 0) {
      renderSearchResults(filteredServices);
      searchResultsText.textContent = `${filteredServices.length} نتیجه برای "${searchQuery}" یافت شد`;
      searchResultsInfo.classList.add('show');
    } else {
      renderSearchResults([]);
      searchResultsText.textContent = `هیچ نتیجه‌ای برای "${searchQuery}" یافت نشد`;
      searchResultsInfo.classList.add('show');
    }
  }

  function renderSearchResults(filteredServices) {
    const container = document.getElementById('services-container');
    let html = '';

    if (filteredServices.length === 0) {
      html = '<div style="text-align: center; padding: 40px; color: #6b7280;">هیچ سرویسی یافت نشد</div>';
    } else {
      filteredServices.forEach(service => {
        const serviceName = service.service_full_name || service.name || service.id || '';
        const serviceIcon = service.service_icon || '';
        const logoUrl = serviceIcon ? (basePath + serviceIcon) : '';

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
    }

    container.innerHTML = html;
    attachSearchListeners();
  }

  // Purchase number function
  function havnPurchaseNumber(serviceId, countryCode) {
    // Test: Show error modal directly
    showErrorModal('تست: مودال خطا کار می‌کند');
    return;
    
    // Check if user is logged in
    if (!havn_ajax.is_logged_in || havn_ajax.user_id === '0') {
      showErrorModal('لطفاً ابتدا وارد شوید');
      return;
    }
    
    // Show confirmation modal
    showPurchaseConfirmation(serviceId, countryCode);
  }

  // Show purchase confirmation modal
  function showPurchaseConfirmation(serviceId, countryCode) {
    const modal = document.getElementById('purchase-confirmation-modal');
    
    if (!modal) {
      alert('خطا در نمایش مودال تأیید');
      return;
    }
    
    // Store data for later use
    modal.dataset.serviceId = serviceId;
    modal.dataset.countryCode = countryCode;
    
    // Show modal
    modal.style.setProperty('display', 'flex', 'important');
    document.body.style.overflow = 'hidden';
  }

  // Confirm purchase
  function confirmPurchase() {
    const modal = document.getElementById('purchase-confirmation-modal');
    const serviceId = modal.dataset.serviceId;
    const countryCode = modal.dataset.countryCode;
    
    // Hide modal
    modal.style.setProperty('display', 'none', 'important');
    document.body.style.overflow = 'auto';
    
    // Show loading
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = 'در حال پردازش...';
    button.disabled = true;

    // Make AJAX request to purchase number
    fetch(havn_ajax.ajax_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'havn_purchase_number',
        service_id: serviceId,
        country_code: countryCode,
        nonce: havn_ajax.nonce
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showSuccessModal('شماره با موفقیت خریداری شد!');
        // Refresh the countries list
        viewService(serviceId);
      } else {
        showErrorModal('خطا: ' + (data.data || 'خطا در خرید شماره'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showErrorModal('خطا در ارتباط با سرور');
    })
    .finally(() => {
      // Restore button
      button.textContent = originalText;
      button.disabled = false;
    });
  }

  // Cancel purchase
  function cancelPurchase() {
    const modal = document.getElementById('purchase-confirmation-modal');
    modal.style.setProperty('display', 'none', 'important');
    document.body.style.overflow = 'auto';
  }

  // Show success modal
  function showSuccessModal(message) {
    const modal = document.getElementById('success-modal');
    const messageEl = document.getElementById('success-message');
    
    if (!modal || !messageEl) {
      alert(message);
      return;
    }
    
    messageEl.textContent = message;
    modal.style.setProperty('display', 'flex', 'important');
    document.body.style.overflow = 'hidden';
  }

  // Show error modal
  function showErrorModal(message) {
    const modal = document.getElementById('error-modal');
    const messageEl = document.getElementById('error-message');
    
    if (!modal || !messageEl) {
      alert(message);
      return;
    }
    
    messageEl.textContent = message;
    modal.style.setProperty('display', 'flex', 'important');
    document.body.style.overflow = 'hidden';
  }

  // Close success modal
  function closeSuccessModal() {
    const modal = document.getElementById('success-modal');
    modal.style.setProperty('display', 'none', 'important');
    document.body.style.overflow = 'auto';
  }

  // Close error modal
  function closeErrorModal() {
    const modal = document.getElementById('error-modal');
    modal.style.setProperty('display', 'none', 'important');
    document.body.style.overflow = 'auto';
  }
</script>
