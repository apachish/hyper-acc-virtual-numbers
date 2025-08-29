<?php
/**
 * User Purchases Page
 * 
 * @package Hyper-Acc Virtual Numbers
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Security check - user must be logged in
if (!is_user_logged_in()) {
    wp_die('لطفاً ابتدا وارد شوید.');
}

// Get user data
$user_id = get_current_user_id();
$all_purchases = HAVN_Database::get_user_purchases($user_id, null);

// Filter to show only completed and pending purchases
$purchases = array_filter($all_purchases, function($purchase) {
    return in_array($purchase->status, ['completed', 'pending']);
});

$has_purchases = !empty($purchases);

// Initialize variables
$services_data = array();
$services_list = array();
$all_services_json = '[]';

// Process purchases if user has any
if ($has_purchases) {
    // Get base path for service icons
    $base_path = get_option('havn_services_base_path', '');
    
    // Group purchases by service
    foreach ($purchases as $purchase) {
        $service_key = $purchase->service_id;
        
        if (!isset($services_data[$service_key])) {
            // Build complete service icon URL
            $service_icon_url = '';
            if (!empty($purchase->service_icon)) {
                $service_icon_url = $base_path . $purchase->service_icon;
            }
            
            $services_data[$service_key] = array(
                'service_id' => $purchase->service_id,
                'service_name' => $purchase->service_full_name,
                'service_icon' => $service_icon_url,
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

<!-- Main Container -->
<div class="havn-virtual-numbers-container">
  <div class="rent-wrapper">
    
    <!-- Page Header -->
    <div class="rent-header">
      <div class="header-content">
        <h1 class="rent-title">
          <i class="fas fa-mobile-alt"></i>
          شماره‌های خریداری شده
        </h1>
        <p class="header-subtitle">مدیریت و مشاهده شماره‌های مجازی خریداری شده</p>
      </div>
      
      <?php if ($has_purchases): ?>
      <div class="search-box">
        <div class="search-input-wrapper">
          <i class="fas fa-search search-icon"></i>
          <input type="text" id="havn-services-search" placeholder="جستجو در خریدها..." />
        </div>
        <button class="clear-search" id="clear-search" style="display: none;">
          <i class="fas fa-times"></i>
          پاک کردن جستجو
        </button>
      </div>
      <?php endif; ?>
    </div>
    
    <!-- Hidden Data for JavaScript -->
    <div class="hidden-data">
      <input type="hidden" value="" id="base_path_js">
        <textarea style="display: none"  id="all_services_json"><?php echo esc_html($all_services_json); ?></textarea>

        <input type="hidden" value="<?php echo get_option('havn_usd_rate', 50000); ?>" id="havn_usd_rate">
      <input type="hidden" value="<?php echo get_option('havn_profit_margin', 10); ?>" id="havn_profit_margin">
    </div>
    
    <?php if ($has_purchases): ?>
    <!-- Search Results Info -->
    <div class="search-results-info" id="search-results-info" style="display: none;">
      <div class="search-results-content">
        <i class="fas fa-search"></i>
        <span id="search-results-text"></span>
        <button class="clear-search-btn" id="clear-search">
          <i class="fas fa-times"></i>
          پاک کردن جستجو
        </button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="rent-body" id="main-content">
      <?php if ($has_purchases): ?>
      <!-- Services List -->
      <div class="rent-list" id="services-section">
        <!-- Services Container -->
        <div class="services-container" id="services-container">
          <!-- Services will be loaded here by JavaScript -->
        </div>

        <!-- Pagination Footer -->
        <?php if (count($services_list) > 20): ?>
        <div class="rent-footer">
          <div class="pagination-controls">
            <button class="btn small" id="prev-page" onclick="changePage(currentPage - 1)">
              <i class="fas fa-chevron-left"></i>
            </button>
            <div class="page-numbers" id="page-numbers">
              <!-- Page numbers will be generated here -->
            </div>
            <button class="btn small" id="next-page" onclick="changePage(currentPage + 1)">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
        <div class="pagination-info" id="pagination-info">
          <i class="fas fa-info-circle"></i>
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
          <a href="<?php echo esc_url(get_option('havn_buy_page_url', home_url('/?page_id=29'))); ?>" class="havn-buy-button">خرید شماره مجازی</a>
        </div>
      </div>
      <?php endif; ?>

      <!-- Countries Section (Hidden by default) -->
      <div class="countries-section" id="countries-section" style="display: none;">
        <div class="countries-header">
          <div class="service-info-header">
            <div class="service-title-wrapper">
              <i class="fas fa-phone-alt service-icon"></i>
              <div class="service-title-content">
                <h3 id="selected-service-name">سرویس انتخاب شده</h3>
                <p class="service-description">شماره‌های خریداری شده از این سرویس</p>
              </div>
            </div>
          </div>
          <div class="countries-search-box">
            <div class="search-input-wrapper">
              <i class="fas fa-search search-icon"></i>
              <input type="text" id="countries-search" placeholder="جستجو در شماره‌ها..." />
            </div>
          </div>
        </div>
        
        <div class="countries-container" id="countries-container">
          <!-- Countries will be loaded here -->
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Global variables are defined in shortcode
const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($has_purchases): ?>
    initUserPurchasesPage();
    <?php endif; ?>
});

function initUserPurchasesPage() {
    // Initialize global variables if not defined
    if (typeof window.allServices === 'undefined') {
        window.allServices = [];
    }
    if (typeof window.currentPage === 'undefined') {
        window.currentPage = 1;
    }
    if (typeof window.perPage === 'undefined') {
        window.perPage = 20;
    }
    if (typeof window.currentService === 'undefined') {
        window.currentService = null;
    }
    
    // Load services data
    const servicesJsonElement = document.getElementById('all_services_json');
    if (!servicesJsonElement) {
        console.log('all_services_json element not found');
        return;
    }
    
    const servicesJson = servicesJsonElement.textContent || servicesJsonElement.value;
    console.log('Services JSON:', servicesJson.substring(0, 100) + '...');
    
    if (!servicesJson || servicesJson.trim() === '' || servicesJson.trim() === '[]') {
        window.allServices = [];
        console.log('No services data found');
        return;
    }
    
    try {
        window.allServices = JSON.parse(servicesJson);
        console.log('Parsed services:', window.allServices.length, 'services found');
    } catch (error) {
        console.error('Error parsing services JSON:', error);
        window.allServices = [];
        return;
    }

    // Initialize page
    if (window.allServices.length > 0) {
        renderServicesPageUser();
    } else {
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
            clearSearch();
        });
    }
}

function renderServicesPageUser() {
    // If services count is less than perPage, show all services
    let servicesToShow;
    console.log("render")

    if (window.allServices.length <= window.perPage) {
        servicesToShow = window.allServices;
        console.log(servicesToShow)
    } else {
        const startIndex = (window.currentPage - 1) * window.perPage;
        const endIndex = startIndex + window.perPage;
        servicesToShow = window.allServices.slice(startIndex, endIndex);
    }
    
    const container = document.getElementById('services-container');
    if (!container) {
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
        serviceElement.className = 'list-item service-item';
        serviceElement.style.cursor = 'pointer';
        serviceElement.onclick = () => showCountries(service.service_id);
        serviceElement.innerHTML = `
            <div class="service-info">
                <div class="service-icon-wrapper">
                    <img src="${service.service_icon || '<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-service.png'}"
                         alt="${service.service_name}" 
                         class="service-logo"
                         onerror="this.src='<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-service.png'">
                </div>
                <div class="service-details">
                    <div class="service-name">${service.service_name}</div>
                    <div class="service-stats">
                        <span class="purchase-count">
                            <i class="fas fa-phone"></i>
                            ${service.purchases.length} شماره خریداری شده
                        </span>
                    </div>
                </div>
            </div>
            <div class="service-actions">
                <button class="view-btn primary-btn" onclick="event.stopPropagation(); showCountries('${service.service_id}')">
                    <i class="fas fa-eye"></i>
                    مشاهده
                </button>
            </div>
        `;
        container.appendChild(serviceElement);
    });
    
    // Update pagination only if needed
    if (window.allServices.length > window.perPage) {
        updatePagination();
        updatePaginationInfo();
    }
}

function showCountries(serviceId) {
    window.currentService = window.allServices.find(s => s.service_id === serviceId);
    
    if (!window.currentService) {
        return;
    }
    
    // Hide services section
    const servicesSection = document.getElementById('services-section');
    const countriesSection = document.getElementById('countries-section');
    const selectedServiceName = document.getElementById('selected-service-name');
    
    if (servicesSection) servicesSection.style.display = 'none';
    if (countriesSection) countriesSection.style.display = 'block';
    if (selectedServiceName) selectedServiceName.textContent = window.currentService.service_name;
    
    // Render countries
    renderCountriesUser();
    
    // Add search functionality for countries
    const countriesSearchInput = document.getElementById('countries-search');
    if (countriesSearchInput) {
        countriesSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterCountries(searchTerm);
        });
    }
}

function renderCountriesUser() {
    const container = document.getElementById('countries-container');
    if (!container) {
        return;
    }
    
    container.innerHTML = '';
    
    if (!window.currentService || !window.currentService.purchases || window.currentService.purchases.length === 0) {
        container.innerHTML = `
            <div class="havn-no-purchases">
                <i class="fas fa-phone-slash"></i>
                <p>هیچ شماره‌ای برای این سرویس یافت نشد</p>
            </div>
        `;
        return;
    }
    
    window.currentService.purchases.forEach(purchase => {
        const countryElement = document.createElement('div');
        countryElement.className = 'list-item country-item';
        
        // Check if purchase has existing codes
        let hasExistingCode = false;
        let existingCode = '';
        let existingCodeTime = '';
        let shouldShowCancel = false;
        
        if (purchase.code) {
            try {
                const codesData = JSON.parse(purchase.code);
                if (codesData && codesData.code && codesData.code.trim() !== '') {
                    hasExistingCode = true;
                    existingCode = codesData.code;
                    existingCodeTime = codesData.received_at || 'نامشخص';
                }
            } catch (e) {
                // Error parsing codes
            }
        }
        
        // Check if 5 minutes have passed since purchase (for cancel button)
        if (purchase.number_id && purchase.status_number !== 'CANCELED' && !hasExistingCode) {
            const purchaseTime = new Date(purchase.created_at);
            const currentTime = new Date();
            const timeDiff = (currentTime - purchaseTime) / (1000 * 60); // minutes
            console.log(timeDiff);
            if (timeDiff <= 5) {
                shouldShowCancel = true;
            }
        }
        
        countryElement.innerHTML = `
            <div class="service-info">
                <div class="country-flag-wrapper">
                    <img src="https://flagcdn.com/${purchase.country_code}.svg" 
                         alt="${purchase.country_code}"
                         class="service-logo"
                         onerror="this.src='<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-flag.png'">
                </div>
                <div class="service-details">
                    <div class="service-name">
                        <i class="fas fa-flag"></i>
                        کشور: ${purchase.country_code.toUpperCase()}
                    </div>
                    <div class="service-status">
                        <i class="fas fa-phone"></i>
                        شماره: ${purchase.number || 'نامشخص'}
                    </div>
                    <div class="service-status">
                        <i class="fas fa-info-circle"></i>
                        وضعیت: <span class="status-badge status-${purchase.status_number?.toLowerCase() || 'pending'}">${purchase.status_number || 'نامشخص'}</span>
                    </div>
                </div>
            </div>
            <div class="service-actions">
                ${purchase.status_number === 'CANCELED' ? `
                    <div></div>
                ` : purchase.number_id ? `
                    ${shouldShowCancel ? `
                 <button class="view-btn primary-btn" style="margin-bottom: 10px" onclick="getCodes('${purchase.number_id}', this)">
                            <i class="fas fa-key"></i>
                            دریافت کد
                        </button>
                        <button class="view-btn danger-btn" onclick="cancelNumber('${purchase.number_id}', this)">
                            <i class="fas fa-times"></i>
                            لغو شماره
                        </button>

                    ` : hasExistingCode ? `
                        <div class="existing-code-display">
                            <span class="code-text">${existingCode}</span>
                            <span class="code-time">${existingCodeTime}</span>
                        </div>
                        <button class="view-btn primary-btn" onclick="getCodes('${purchase.number_id}', this)">
                            <i class="fas fa-key"></i>
                            دریافت کد جدید
                        </button>
                    ` : `
                        <button class="view-btn primary-btn" onclick="getCodes('${purchase.number_id}', this)">
                            <i class="fas fa-key"></i>
                            دریافت کد
                        </button>
                    `}
                ` : `
                    <div></div>
                `}
            </div>
            <div class="codes-result-box" id="codes-result-${purchase.number_id}" style="display: none;">
                <!-- کدهای جدید اینجا نمایش داده می‌شود -->
            </div>
        `;
        container.appendChild(countryElement);
    });
}

function getCodes(numberId, button) {
    // Show loading
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال دریافت...';
    
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
        // Get the result box
        const resultBox = document.getElementById(`codes-result-${numberId}`);
        if (!resultBox) return;
        
        if (data.success) {
            if (data.data && data.data.codes && data.data.codes.length > 0) {
                // Show new codes in the result box
                const codesHtml = data.data.codes.map(code => `
                    <div class="code-item new-code">
                        <span class="code-text">${code.code || 'کد خالی'}</span>
                        <span class="code-time">${code.received_at || 'نامشخص'}</span>
                        <span class="new-badge">جدید</span>
                    </div>
                `).join('');
                
                resultBox.innerHTML = `
                    <div class="codes-header">
                        <h4><i class="fas fa-plus-circle"></i> کدهای جدید</h4>
                        <button class="close-codes-btn" onclick="closeCodesResult('${numberId}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="codes-content">
                        ${codesHtml}
                    </div>
                    <div class="codes-footer">
                        <span class="status-info">وضعیت: ${data.data.state || 'PENDING'}</span>
                    </div>
                `;
                resultBox.style.display = 'block';
                
                // Replace the button with the new code display
                const actionsDiv = button.closest('.service-actions');
                if (actionsDiv) {
                    const newCodeHtml = data.data.codes.map(code => `
                        <div class="existing-code-display">
                            <span class="code-text">${code.code || 'کد خالی'}</span>
                            <span class="code-time">${code.received_at || 'نامشخص'}</span>
                            <span class="new-badge">جدید</span>
                        </div>
                    `).join('');
                    actionsDiv.innerHTML = newCodeHtml;
                }
                
                // Update the status in the UI
                const row = button.closest('.list-item');
                const statusElement = row.querySelector('.service-status:last-child');
                if (statusElement) {
                    statusElement.innerHTML = `<i class="fas fa-info-circle"></i> وضعیت: <span class="status-badge status-${data.data.state?.toLowerCase() || 'pending'}">${data.data.state || 'PENDING'}</span>`;
                }
            } else {
                resultBox.innerHTML = `
                    <div class="codes-header">
                        <h4><i class="fas fa-info-circle"></i> نتیجه</h4>
                        <button class="close-codes-btn" onclick="closeCodesResult('${numberId}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="codes-content">
                        <div class="no-codes">
                            <i class="fas fa-inbox"></i>
                            <p>کدی دریافت نشده است</p>
                        </div>
                    </div>
                `;
                resultBox.style.display = 'block';
            }
        } else {
            resultBox.innerHTML = `
                <div class="codes-header">
                    <h4><i class="fas fa-exclamation-triangle"></i> خطا</h4>
                    <button class="close-codes-btn" onclick="closeCodesResult('${numberId}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="codes-content">
                    <div class="error-message">
                        <i class="fas fa-times-circle"></i>
                        <p>${data.data}</p>
                    </div>
                </div>
            `;
            resultBox.style.display = 'block';
        }
    })
    .catch(error => {
        const resultBox = document.getElementById(`codes-result-${numberId}`);
        if (resultBox) {
            resultBox.innerHTML = `
                <div class="codes-header">
                    <h4><i class="fas fa-exclamation-triangle"></i> خطا</h4>
                    <button class="close-codes-btn" onclick="closeCodesResult('${numberId}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="codes-content">
                    <div class="error-message">
                        <i class="fas fa-times-circle"></i>
                        <p>خطا در ارتباط با سرور</p>
                    </div>
                </div>
            `;
            resultBox.style.display = 'block';
        }
    })
    .finally(() => {
        // Restore button
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function filterServices(searchTerm) {
    const filteredServices = window.allServices.filter(service => {
        // Search in service name
        const serviceNameMatch = service.service_name.toLowerCase().includes(searchTerm.toLowerCase());
        
        // Search in purchases (country code, number, status)
        const purchaseMatch = service.purchases.some(purchase => 
            purchase.country_code.toLowerCase().includes(searchTerm.toLowerCase()) ||
            purchase.number.includes(searchTerm) ||
            (purchase.status_number && purchase.status_number.toLowerCase().includes(searchTerm.toLowerCase()))
        );
        
        return serviceNameMatch || purchaseMatch;
    });
    
    // Update display
    const container = document.getElementById('services-container');
    container.innerHTML = '';
    
    if (filteredServices.length === 0) {
        container.innerHTML = `
            <div class="havn-no-results">
                <i class="fas fa-search"></i>
                <h3>نتیجه‌ای یافت نشد</h3>
                <p>برای "${searchTerm}" هیچ سرویس یا شماره‌ای یافت نشد</p>
                <button class="clear-search-btn" onclick="clearSearch()">
                    <i class="fas fa-times"></i>
                    پاک کردن جستجو
                </button>
            </div>
        `;
        return;
    }
    
    filteredServices.forEach(service => {
        const serviceElement = document.createElement('div');
        serviceElement.className = 'list-item service-item';
        serviceElement.style.cursor = 'pointer';
        serviceElement.onclick = () => showCountries(service.service_id);
        serviceElement.innerHTML = `
            <div class="service-info">
                <div class="service-icon-wrapper">
                    <img src="${service.service_icon || '<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-service.png'}"
                         alt="${service.service_name}" 
                         class="service-logo"
                         onerror="this.src='<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-service.png'">
                </div>
                <div class="service-details">
                    <div class="service-name">${service.service_name}</div>
                    <div class="service-stats">
                        <span class="purchase-count">
                            <i class="fas fa-phone"></i>
                            ${service.purchases.length} شماره خریداری شده
                        </span>
                    </div>
                </div>
            </div>
            <div class="service-actions">
                <button class="view-btn primary-btn" onclick="event.stopPropagation(); showCountries('${service.service_id}')">
                    <i class="fas fa-eye"></i>
                    مشاهده
                </button>
            </div>
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

// clearSearch function is defined in shortcode

function filterCountries(searchTerm) {
    if (!window.currentService || !window.currentService.purchases) {
        return;
    }
    
    const filteredPurchases = window.currentService.purchases.filter(purchase => 
        purchase.country_code.toLowerCase().includes(searchTerm) ||
        purchase.number.includes(searchTerm) ||
        (purchase.status_number && purchase.status_number.toLowerCase().includes(searchTerm))
    );
    
    const container = document.getElementById('countries-container');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (filteredPurchases.length === 0) {
        container.innerHTML = `
            <div class="havn-no-results">
                <i class="fas fa-search"></i>
                <h3>نتیجه‌ای یافت نشد</h3>
                <p>برای "${searchTerm}" هیچ شماره‌ای یافت نشد</p>
            </div>
        `;
        return;
    }
    
    filteredPurchases.forEach(purchase => {
        const countryElement = document.createElement('div');
        countryElement.className = 'list-item country-item';
        
        // Check if purchase has existing codes
        let hasExistingCode = false;
        let existingCode = '';
        let existingCodeTime = '';
        let shouldShowCancel = false;
        
        if (purchase.code) {
            try {
                const codesData = JSON.parse(purchase.code);
                if (codesData && codesData.code && codesData.code.trim() !== '') {
                    hasExistingCode = true;
                    existingCode = codesData.code;
                    existingCodeTime = codesData.received_at || 'نامشخص';
                }
            } catch (e) {
                // Error parsing codes
            }
        }
        
        // Check if 5 minutes have passed since purchase (for cancel button)
        if (purchase.number_id && purchase.status_number !== 'CANCELED' && !hasExistingCode) {
            const purchaseTime = new Date(purchase.created_at);
            const currentTime = new Date();
            const timeDiff = (currentTime - purchaseTime) / (1000 * 60); // minutes
            
            if (timeDiff >= 5) {
                shouldShowCancel = true;
            }
        }
        
        countryElement.innerHTML = `
            <div class="service-info">
                <div class="country-flag-wrapper">
                    <img src="https://flagcdn.com/${purchase.country_code}.svg" 
                         alt="${purchase.country_code}"
                         class="service-logo"
                         onerror="this.src='<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-flag.png'">
                </div>
                <div class="service-details">
                    <div class="service-name">
                        <i class="fas fa-flag"></i>
                        کشور: ${purchase.country_code.toUpperCase()}
                    </div>
                    <div class="service-status">
                        <i class="fas fa-phone"></i>
                        شماره: ${purchase.number || 'نامشخص'}
                    </div>
                    <div class="service-status">
                        <i class="fas fa-info-circle"></i>
                        وضعیت: <span class="status-badge status-${purchase.status_number?.toLowerCase() || 'pending'}">${purchase.status_number || 'نامشخص'}</span>
                    </div>
                </div>
            </div>
            <div class="service-actions">
                ${purchase.status_number === 'CANCELED' ? `
                    <div></div>
                ` : purchase.number_id ? `
                    ${shouldShowCancel ? `
                        <button class="view-btn danger-btn" onclick="cancelNumber('${purchase.number_id}', this)">
                            <i class="fas fa-times"></i>
                            لغو شماره
                        </button>
                    ` : hasExistingCode ? `
                        <div class="existing-code-display">
                            <span class="code-text">${existingCode}</span>
                            <span class="code-time">${existingCodeTime}</span>
                        </div>
                        <button class="view-btn primary-btn" onclick="getCodes('${purchase.number_id}', this)">
                            <i class="fas fa-key"></i>
                            دریافت کد جدید
                        </button>
                    ` : `
                        <button class="view-btn primary-btn" onclick="getCodes('${purchase.number_id}', this)">
                            <i class="fas fa-key"></i>
                            دریافت کد
                        </button>
                    `}
                ` : `
                    <div></div>
                `}
            </div>
            <div class="codes-result-box" id="codes-result-${purchase.number_id}" style="display: none;">
                <!-- کدهای جدید اینجا نمایش داده می‌شود -->
            </div>
        `;
        container.appendChild(countryElement);
    });
}

function closeCodesResult(numberId) {
    const resultBox = document.getElementById(`codes-result-${numberId}`);
    if (resultBox) {
        resultBox.style.display = 'none';
    }
}

function cancelNumber(numberId, button) {
    if (!confirm('آیا مطمئن هستید که می‌خواهید این شماره را لغو کنید؟')) {
        return;
    }
    
    // Show loading
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال لغو...';
    
    const formData = new FormData();
    formData.append('action', 'havn_cancel_number');
    formData.append('number_id', numberId);
    formData.append('nonce', '<?php echo wp_create_nonce('havn_cancel_number'); ?>');
    
    fetch(ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the status in the UI
            const row = button.closest('.list-item');
            const statusElement = row.querySelector('.service-status:last-child');
            if (statusElement) {
                statusElement.innerHTML = `<i class="fas fa-info-circle"></i> وضعیت: <span class="status-badge status-canceled">CANCELED</span>`;
            }
            
            // Replace button with canceled message
            const actionsDiv = button.closest('.service-actions');
            if (actionsDiv) {
                actionsDiv.innerHTML = `
                    <div class="canceled-message">
                        <i class="fas fa-times-circle"></i>
                        <span>شماره لغو شد</span>
                    </div>
                `;
            }
            
            // Show success message
            if (data.data && data.data.warning) {
                alert('شماره با موفقیت لغو شد\n\nهشدار: ' + data.data.warning);
            } else {
                alert('شماره با موفقیت لغو شد');
            }
            
            // Reload page to update statistics
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            // Restore button
            button.disabled = false;
            button.innerHTML = originalText;
            
            // Show error message
            alert('خطا در لغو شماره: ' + (data.data || 'خطای نامشخص'));
        }
    })
    .catch(error => {
        // Restore button
        button.disabled = false;
        button.innerHTML = originalText;
        
        // Show error message
        alert('خطا در لغو شماره');
    });
}

function changePage(page) {
    const totalPages = Math.ceil(window.allServices.length / window.perPage);
    if (page < 1 || page > totalPages || totalPages <= 1) return;
    
    window.currentPage = page;
    renderServicesPageUser();
}

function updatePagination() {
    const totalPages = Math.ceil(window.allServices.length / window.perPage);
    const pageNumbers = document.getElementById('page-numbers');
    
    if (!pageNumbers || totalPages <= 1) return;
    
    pageNumbers.innerHTML = '';
    
    // Show max 3 page numbers
    let startPage = Math.max(1, window.currentPage - 1);
    let endPage = Math.min(totalPages, window.currentPage + 1);
    
    if (endPage - startPage < 2) {
        if (startPage === 1) {
            endPage = Math.min(totalPages, startPage + 2);
        } else {
            startPage = Math.max(1, endPage - 2);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = `btn small ${i === window.currentPage ? 'active' : ''}`;
        pageBtn.textContent = i;
        pageBtn.onclick = () => changePage(i);
        pageNumbers.appendChild(pageBtn);
    }
    
    // Update prev/next buttons
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');
    if (prevBtn) prevBtn.disabled = window.currentPage === 1;
    if (nextBtn) nextBtn.disabled = window.currentPage === totalPages;
}

function updatePaginationInfo() {
    const totalServices = window.allServices.length;
    const paginationInfo = document.getElementById('pagination-info');
    
    if (paginationInfo) {
        if (totalServices <= window.perPage) {
            paginationInfo.textContent = `نمایش ${totalServices} سرویس`;
        } else {
            const startIndex = (window.currentPage - 1) * window.perPage + 1;
            const endIndex = Math.min(window.currentPage * window.perPage, totalServices);
            paginationInfo.textContent = `نمایش ${startIndex} تا ${endIndex} از ${totalServices} سرویس`;
        }
    }
}
</script>
