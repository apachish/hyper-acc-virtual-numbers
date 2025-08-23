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
      <h1 class="rent-title">شماره‌های مجازی</h1>
      <div class="search-box">
        <input type="text" id="havn-services-search" placeholder="جستجو در سرویس‌ها..." />
        <button class="clear-search" id="clear-search" style="display: none;">پاک کردن جستجو</button>
      </div>
    </div>

    <!-- Search Results Info -->
    <div class="search-results-info" id="search-results-info">
      <button class="clear-search" id="clear-search">پاک کردن جستجو</button>
      <span id="search-results-text"></span>
    </div>

    <!-- Main Content -->
    <div class="rent-body">
      <!-- Services List -->
      <div class="rent-list">
        <!-- Tabs -->
        <div class="tabs">
          <button class="tab active" onclick="switchTab('services')">سرویس‌ها</button>
          <button class="tab" onclick="switchTab('countries')">کشورها</button>
        </div>

        <!-- Services Container -->
        <div class="services-container" id="services-container">
          <!-- Services will be loaded here -->
        </div>

        <!-- Pagination Footer -->
        <div class="rent-footer">
          <div class="pagination-info" id="pagination-info">
            نمایش 1 تا 20 از <?php echo $total_services; ?> سرویس
          </div>
          <div class="pagination-controls">
            <button class="btn small" id="prev-page" onclick="changePage(currentPage - 1)">&lt;</button>
            <div class="page-numbers" id="page-numbers">
              <!-- Page numbers will be generated here -->
            </div>
            <button class="btn small" id="next-page" onclick="changePage(currentPage + 1)">&gt;</button>
          </div>
        </div>
      </div>

      <!-- Countries Table -->
      <div class="rent-table-box">
        <div class="rent-filters">
          <span>کشور</span>
          <span>قیمت(تومان)</span>
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

  // Initialize
  document.addEventListener('DOMContentLoaded', function() {
    renderServicesPage(allServices.slice(0, perPage), 1);
    attachSearchListeners();
  });

  // Tab switching
  function switchTab(tabName) {
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    if (tabName === 'services') {
      document.querySelector('.rent-list').style.display = 'flex';
      document.querySelector('.rent-table-box').style.display = 'none';
    } else {
      document.querySelector('.rent-list').style.display = 'none';
      document.querySelector('.rent-table-box').style.display = 'block';
    }
  }

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
              مشاهده 👁️
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

    // Show only 3 page numbers (current, one before, one after)
    let startPage = Math.max(1, page - 1);
    let endPage = Math.min(totalPages, page + 1);

    if (startPage > 1) {
      html += `<button class="page" onclick="changePage(1)">1</button>`;
      if (startPage > 2) {
        html += `<span style="padding: 0 8px;">...</span>`;
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      html += `<button class="page ${i === page ? 'current' : ''}" onclick="changePage(${i})">${i}</button>`;
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        html += `<span style="padding: 0 8px;">...</span>`;
      }
      html += `<button class="page" onclick="changePage(${totalPages})">${totalPages}</button>`;
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
    info.textContent = `نمایش ${start} تا ${end} از ${allServices.length} سرویس`;
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
    fetch(ajaxurl, {
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
            <div class="col">$${price}</div>
            <div class="col">${new Intl.NumberFormat('fa-IR').format(stock)} عدد</div>
            <div class="col">
              ${disabled ? 
                '<button class="btn disabled" disabled>غیرفعال</button>' : 
                '<button class="btn" onclick="havnPurchaseNumber(\''+serviceId+'\', \''+code+'\')">دریافت</button>'
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
              مشاهده 👁️
            </button>
          </div>
        `;
      });
    }
    
    container.innerHTML = html;
    attachSearchListeners();
  }

  // Purchase number function (placeholder)
  function havnPurchaseNumber(serviceId, countryCode) {
    alert(`درخواست خرید شماره برای سرویس ${serviceId} در کشور ${countryCode}`);
    // TODO: Implement purchase logic
  }
</script>
