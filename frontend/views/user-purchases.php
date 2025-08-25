<?php
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_die('لطفاً ابتدا وارد شوید.');
}

$user_id = get_current_user_id();
$purchases = HAVN_Database::get_user_purchases($user_id, 'completed');

// Check if user has any purchases
$has_purchases = !empty($purchases);

// Group purchases by service only if user has purchases
$services_data = array();
$services_list = array();
$all_services_json = '[]';

if ($has_purchases) {
    foreach ($purchases as $purchase) {
        $service_key = $purchase->service_id;
        if (!isset($services_data[$service_key])) {
            $services_data[$service_key] = array(
                'service_id' => $purchase->service_id,
                'service_name' => $purchase->service_full_name,
                'service_icon' => $purchase->service_icon,
                'purchases' => array()
            );
        }
        $services_data[$service_key]['purchases'][] = $purchase;
    }
    
    // Convert to indexed array for JavaScript
    $services_list = array_values($services_data);
    $all_services_json = json_encode($services_list);
}
?>

<div class="havn-virtual-numbers-container">
  <div class="rent-wrapper">
    <!-- Header -->
    <div class="rent-header">
      <h1 class="rent-title">شماره‌های خریداری شده</h1>
      <div class="search-box">
        <input type="text" id="havn-services-search" placeholder="جستجو در خریدها..." />
        <button class="clear-search" id="clear-search" style="display: none;">پاک کردن جستجو</button>
      </div>
    </div>
      <input type="hidden" value="" id="base_path_js">
      <input type="hidden" id="all_services_json" value='<?php echo $all_services_json; ?>'>
      <input type="hidden" value="<?php echo get_option('havn_usd_rate', 50000); ?>" id="havn_usd_rate">
      <input type="hidden" value="<?php echo get_option('havn_profit_margin', 10); ?>" id="havn_profit_margin">
    <?php if ($has_purchases): ?>
    <!-- Search Results Info -->
    <div class="search-results-info" id="search-results-info">
      <button class="clear-search" id="clear-search">پاک کردن جستجو</button>
      <span id="search-results-text"></span>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="rent-body" id="main-content">
      <?php if ($has_purchases): ?>
      <!-- Services List -->
      <div class="rent-list" id="services-section" style="height: 100vh">
        <!-- Services Container -->
        <div class="services-container" id="services-container">
          <!-- Services will be loaded here by JavaScript -->
        </div>

        <!-- Pagination Footer -->
        <?php if (count($services_list) > 20): ?>
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
          نمایش 1 تا 20 از <?php echo count($services_list); ?> سرویس
        </div>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <!-- No Purchases Message -->
      <div class="havn-no-purchases-container">
        <div class="havn-no-purchases">
          <i class="fas fa-phone-slash"></i>
          <h2>شما هنوز خریدی نداشته‌اید</h2>
          <p>برای شروع خرید شماره مجازی، روی دکمه زیر کلیک کنید</p>
          <a href="<?php echo home_url('/?page_id=29'); ?>" class="havn-buy-button">خرید شماره مجازی</a>
        </div>
      </div>
      <?php endif; ?>

      <!-- Countries Section (Hidden by default) -->
      <div class="countries-section" id="countries-section" style="display: none;">
        <div class="countries-header">
          <h2 id="selected-service-name">سرویس انتخاب شده</h2>
        </div>
        
        <div class="countries-container" id="countries-container">
          <!-- Countries will be loaded here -->
        </div>
      </div>

      <!-- Code Modal -->
      <div class="code-modal" id="code-modal" style="display: none;">
        <div class="code-modal-overlay" onclick="closeCodeModal()"></div>
        <div class="code-modal-content">
          <div class="code-modal-header">
            <h3>کدهای دریافتی</h3>
            <button class="close-modal" onclick="closeCodeModal()">×</button>
          </div>
          <div class="code-modal-body">
            <div id="codes-list"></div>
            <div class="refresh-section">
              <button id="refresh-codes" class="refresh-button">
                <i class="fas fa-sync-alt"></i>
                بروزرسانی خودکار
              </button>
              <span id="refresh-status"></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Global variables
let allServices = [];
let currentPage = 1;
let perPage = 20;
let currentService = null;
let refreshInterval = null;
const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

// Test function
function testClick(serviceId) {
    console.log('testClick called with serviceId:', serviceId);
    alert('کلیک کار می‌کند! Service ID: ' + serviceId);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded event fired');
    <?php if ($has_purchases): ?>
    console.log('User has purchases, initializing page');
    initUserPurchasesPage();
    <?php else: ?>
    console.log('User has no purchases');
    <?php endif; ?>
});

function initUserPurchasesPage() {
    console.log('initUserPurchasesPage called');
    
    // Load services data
    const servicesJsonElement = document.getElementById('all_services_json');
    if (!servicesJsonElement) {
        console.log('all_services_json element not found');
        return;
    }
    
    const servicesJson = servicesJsonElement.value;
    console.log('servicesJson:', servicesJson);
    console.log('servicesJson length:', servicesJson.length);
    
    if (!servicesJson || servicesJson.trim() === '') {
        console.log('servicesJson is empty');
        allServices = [];
        return;
    }
    
    try {
        allServices = JSON.parse(servicesJson);
        console.log('allServices loaded:', allServices);
    } catch (error) {
        console.error('Error parsing services JSON:', error);
        console.error('JSON string:', servicesJson);
        allServices = [];
        return;
    }
    
    // Initialize page
    if (allServices.length > 0) {
        renderServicesPage();
    } else {
        console.log('No services to render');
        const container = document.getElementById('services-container');
        if (container) {
            container.innerHTML = `
                <div class="havn-no-purchases">
                    <i class="fas fa-phone-slash"></i>
                    <p>خطا در بارگذاری داده‌ها</p>
                    <button onclick="location.reload()" class="havn-buy-button">تلاش مجدد</button>
                </div>
            `;
        }
    }
    
    // Search functionality
    const searchInput = document.getElementById('havn-services-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterServices(searchTerm);
        });
    }
    
    // Clear search functionality
    const clearSearchBtn = document.getElementById('clear-search');
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            document.getElementById('havn-services-search').value = '';
            filterServices('');
        });
    }
}

function renderServicesPage() {
    console.log('renderServicesPage called');
    console.log('allServices:', allServices);
    
    // If services count is less than perPage, show all services
    let servicesToShow;
    if (allServices.length <= perPage) {
        servicesToShow = allServices;
    } else {
        const startIndex = (currentPage - 1) * perPage;
        const endIndex = startIndex + perPage;
        servicesToShow = allServices.slice(startIndex, endIndex);
    }
    
    console.log('servicesToShow:', servicesToShow);
    
    const container = document.getElementById('services-container');
    if (!container) {
        console.log('services-container not found');
        return;
    }
    
    container.innerHTML = '';
    
    if (servicesToShow.length === 0) {
        container.innerHTML = `
            <div class="havn-no-purchases">
                <i class="fas fa-phone-slash"></i>
                <p>شما هنوز خرید نداشته‌اید</p>
                <a href="<?php echo home_url('/?page_id=29'); ?>" class="havn-buy-button">خرید شماره</a>
            </div>
        `;
        return;
    }
    
    servicesToShow.forEach(service => {
        const serviceElement = document.createElement('div');
        serviceElement.className = 'list-item';
        serviceElement.innerHTML = `
            <div class="service-info">
                <img src="<?php echo HAVN_PLUGIN_URL; ?>${service.service_icon}"
                     alt="${service.service_name}" 
                     class="service-logo">
                <div class="service-details">
                    <div class="service-name">${service.service_name}</div>
                    <div class="service-status">${service.purchases.length} شماره خریداری شده</div>
                </div>
            </div>
            <button class="view-btn" onclick="showCountries('${service.service_id}')">
                مشاهده 👁
            </button>
        `;
        container.appendChild(serviceElement);
        
        // Add test button

    });
    
    // Update pagination only if needed
    if (allServices.length > perPage) {
        updatePagination();
        updatePaginationInfo();
    }
}

function showCountries(serviceId) {
    console.log('showCountries called with serviceId:', serviceId);
    console.log('allServices:', allServices);
    
    currentService = allServices.find(s => s.service_id === serviceId);
    console.log('currentService found:', currentService);
    
    if (!currentService) {
        console.log('No service found with ID:', serviceId);
        return;
    }
    
    // Hide services section
    const servicesSection = document.getElementById('services-section');
    const countriesSection = document.getElementById('countries-section');
    const selectedServiceName = document.getElementById('selected-service-name');
    
    if (servicesSection) servicesSection.style.display = 'none';
    if (countriesSection) countriesSection.style.display = 'block';
    if (selectedServiceName) selectedServiceName.textContent = currentService.service_name;
    
    // Render countries
    renderCountries();
}

function renderCountries() {
    console.log('renderCountries called');
    console.log('currentService:', currentService);
    
    const container = document.getElementById('countries-container');
    if (!container) {
        console.log('countries-container not found');
        return;
    }
    
    container.innerHTML = '';
    
    if (!currentService || !currentService.purchases || currentService.purchases.length === 0) {
        console.log('No purchases found for current service');
        container.innerHTML = `
            <div class="havn-no-purchases">
                <i class="fas fa-phone-slash"></i>
                <p>هیچ شماره‌ای برای این سرویس یافت نشد</p>
            </div>
        `;
        return;
    }
    
    console.log('Rendering', currentService.purchases.length, 'purchases');
    
    currentService.purchases.forEach(purchase => {
        const countryElement = document.createElement('div');
        countryElement.className = 'list-item';
        countryElement.innerHTML = `
            <div class="service-info">
                <img src="https://flagcdn.com/${purchase.country_code}.svg" 
                     alt="${purchase.country_code}"
                     class="service-logo"
                     onerror="this.src='<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-flag.png'">
                <div class="service-details">
                    <div class="service-name">کشور: ${purchase.country_code.toUpperCase()}</div>
                    <div class="service-status">شماره: ${purchase.number || 'نامشخص'}</div>
                    <div class="service-status">وضعیت: ${purchase.status_number || 'نامشخص'}</div>
                </div>
            </div>
            <div class="service-actions">
                <button class="view-btn" onclick="getCodes('${purchase.number_id}')">
                    دریافت کد 🔑
                </button>
                <button class="view-btn" onclick="showAllCodes('${purchase.number_id}')">
                    نمایش کدها 📋
                </button>
            </div>
        `;
        container.appendChild(countryElement);
    });
}

function showServices() {
    document.getElementById('countries-section').style.display = 'none';
    document.getElementById('services-section').style.display = 'block';
    currentService = null;
}

function getCodes(numberId) {
    console.log('getCodes called for numberId:', numberId);
    
    // Show loading
    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'در حال دریافت...';
    
    const formData = new FormData();
    formData.append('action', 'havn_get_number_codes');
    formData.append('number_id', numberId);
    formData.append('nonce', '<?php echo wp_create_nonce('havn_get_codes'); ?>');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('getCodes response:', data);
        
        if (data.success) {
            if (data.data && data.data.codes && data.data.codes.length > 0) {
                const latestCode = data.data.codes[data.data.codes.length - 1];
                alert(`آخرین کد دریافتی: ${latestCode.code}`);
                
                // Update the status in the UI
                const row = button.closest('.list-item');
                const statusElement = row.querySelector('.service-status:last-child');
                if (statusElement) {
                    statusElement.textContent = `وضعیت: ${data.data.state || 'PENDING'}`;
                }
            } else {
                alert('کدی دریافت نشده است');
            }
        } else {
            alert('خطا: ' + data.data);
        }
    })
    .catch(error => {
        console.error('getCodes error:', error);
        alert('خطا در ارتباط با سرور');
    })
    .finally(() => {
        // Restore button
        button.disabled = false;
        button.textContent = originalText;
    });
}

function showAllCodes(numberId) {
    console.log('showAllCodes called for numberId:', numberId);
    
    // Show loading
    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'در حال بارگذاری...';
    
    const formData = new FormData();
    formData.append('action', 'havn_get_number_codes');
    formData.append('number_id', numberId);
    formData.append('nonce', '<?php echo wp_create_nonce('havn_get_codes'); ?>');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('showAllCodes response:', data);
        
        if (data.success) {
            if (data.data && data.data.codes) {
                renderCodesModal(data.data.codes, numberId);
                document.getElementById('code-modal').style.display = 'block';
                startAutoRefresh(numberId);
            } else {
                alert('کدی دریافت نشده است');
            }
        } else {
            alert('خطا در دریافت کدها: ' + data.data);
        }
    })
    .catch(error => {
        console.error('showAllCodes error:', error);
        alert('خطا در ارتباط با سرور');
    })
    .finally(() => {
        // Restore button
        button.disabled = false;
        button.textContent = originalText;
    });
}

function renderCodesModal(codes, numberId) {
    console.log('renderCodesModal called with codes:', codes);
    
    const codesList = document.getElementById('codes-list');
    
    if (codes && codes.length > 0) {
        codesList.innerHTML = `
            <div class="codes-header">
                <h4>کدهای دریافتی برای شماره ${numberId}</h4>
            </div>
            <div class="codes-table">
                ${codes.map(code => `
                    <div class="code-item">
                        <span class="code-text">${code.code || 'کد خالی'}</span>
                        <span class="code-time">${code.received_at || 'نامشخص'}</span>
                    </div>
                `).join('')}
            </div>
        `;
    } else {
        codesList.innerHTML = `
            <div class="no-codes">
                <i class="fas fa-inbox"></i>
                <p>هنوز کدی دریافت نشده است</p>
                <p>وضعیت: ${codes && codes.state ? codes.state : 'نامشخص'}</p>
            </div>
        `;
    }
}

function startAutoRefresh(numberId) {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    refreshInterval = setInterval(() => {
        showAllCodes(numberId);
    }, 10000);
    
    document.getElementById('refresh-status').textContent = 'بروزرسانی خودکار فعال';
}

function closeCodeModal() {
    document.getElementById('code-modal').style.display = 'none';
    
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    
    document.getElementById('refresh-status').textContent = '';
}

function filterServices(searchTerm) {
    const filteredServices = allServices.filter(service => 
        service.service_name.toLowerCase().includes(searchTerm) ||
        service.purchases.some(purchase => 
            purchase.country_name.toLowerCase().includes(searchTerm) ||
            purchase.number.includes(searchTerm)
        )
    );
    
    // Update display
    const container = document.getElementById('services-container');
    container.innerHTML = '';
    
    if (filteredServices.length === 0) {
        container.innerHTML = '<div class="no-results">شما هنوز خرید نداشته‌اید</div>';
        return;
    }
    
    filteredServices.forEach(service => {
        const serviceElement = document.createElement('div');
        serviceElement.className = 'list-item';
        serviceElement.innerHTML = `
            <div class="service-info">
                <img src="<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-service.png" 
                     alt="${service.service_name}" 
                     class="service-logo">
                <div class="service-details">
                    <div class="service-name">${service.service_name}</div>
                    <div class="service-status">${service.purchases.length} شماره خریداری شده</div>
                </div>
            </div>
            <button class="view-btn" onclick="showCountries('${service.service_id}')">
                مشاهده 👁
            </button>
        `;
        container.appendChild(serviceElement);
    });
    
    // Show search results info
    const searchInfo = document.getElementById('search-results-info');
    const searchText = document.getElementById('search-results-text');
    if (searchTerm) {
        searchInfo.style.display = 'flex';
        searchText.textContent = `${filteredServices.length} نتیجه برای "${searchTerm}"`;
    } else {
        searchInfo.style.display = 'none';
    }
}

function changePage(page) {
    const totalPages = Math.ceil(allServices.length / perPage);
    if (page < 1 || page > totalPages || totalPages <= 1) return;
    
    currentPage = page;
    renderServicesPage();
}

function updatePagination() {
    const totalPages = Math.ceil(allServices.length / perPage);
    const pageNumbers = document.getElementById('page-numbers');
    
    if (!pageNumbers || totalPages <= 1) return;
    
    pageNumbers.innerHTML = '';
    
    // Show max 3 page numbers
    let startPage = Math.max(1, currentPage - 1);
    let endPage = Math.min(totalPages, currentPage + 1);
    
    if (endPage - startPage < 2) {
        if (startPage === 1) {
            endPage = Math.min(totalPages, startPage + 2);
        } else {
            startPage = Math.max(1, endPage - 2);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = `btn small ${i === currentPage ? 'active' : ''}`;
        pageBtn.textContent = i;
        pageBtn.onclick = () => changePage(i);
        pageNumbers.appendChild(pageBtn);
    }
    
    // Update prev/next buttons
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');
    if (prevBtn) prevBtn.disabled = currentPage === 1;
    if (nextBtn) nextBtn.disabled = currentPage === totalPages;
}

function updatePaginationInfo() {
    const totalServices = allServices.length;
    const paginationInfo = document.getElementById('pagination-info');
    
    if (paginationInfo) {
        if (totalServices <= perPage) {
            paginationInfo.textContent = `نمایش ${totalServices} سرویس`;
        } else {
            const startIndex = (currentPage - 1) * perPage + 1;
            const endIndex = Math.min(currentPage * perPage, totalServices);
            paginationInfo.textContent = `نمایش ${startIndex} تا ${endIndex} از ${totalServices} سرویس`;
        }
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const codeModal = document.getElementById('code-modal');
    if (event.target === codeModal) {
        closeCodeModal();
    }
}
</script>
