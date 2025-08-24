/**
 * Frontend JavaScript
 */

// Initialize services page
window.initServicesPage = function() {
    console.log('Initializing services page...');
    
    // Get data from PHP
    if (window.allServices) {
        allServices = window.allServices;
    }
    if (window.perPage) {
        perPage = window.perPage;
    }
    if (window.basePath) {
        basePath = window.basePath;
    }
    if (window.havnUsdRate) {
        havnUsdRate = window.havnUsdRate;
    }
    if (window.havnProfitMargin) {
        havnProfitMargin = window.havnProfitMargin;
    }
    
    // Initialize the page
    renderServicesPage(allServices.slice(0, perPage), 1);
    attachSearchListeners();
    
    console.log('Services page initialized successfully');
};

jQuery(document).ready(function($) {

    // Initialize frontend functionality
    initFrontend();

    function initFrontend() {
        console.log('Hyper ACC Virtual Numbers Frontend initialized');

        // Initialize any frontend-specific functionality
        initServiceCards();
        initPurchaseButtons();
        initModals();
    }

    function initServiceCards() {
        // Add hover effects and interactions to service cards
        $('.havn-service-card').hover(
            function() {
                $(this).addClass('hovered');
            },
            function() {
                $(this).removeClass('hovered');
            }
        );

        // Add click to expand functionality for service descriptions
        $('.service-description').on('click', function() {
            $(this).toggleClass('expanded');
        });
    }

    function initPurchaseButtons() {
        // Initialize purchase buttons with loading states
        $('.havn-purchase-btn').on('click', function(e) {
            e.preventDefault();

            var button = $(this);
            var originalText = button.text();

            // Show loading state
            button.prop('disabled', true).text('در حال بارگذاری...');

            // Get service and country info
            var serviceId = button.data('service-id');
            var countryCode = button.data('country-code');

            if (!serviceId || !countryCode) {
                // Fallback to onclick attribute parsing
                var onclick = button.attr('onclick');
                var matches = onclick.match(/havnPurchaseNumber\('([^']+)',\s*'([^']+)'\)/);
                if (matches) {
                    serviceId = matches[1];
                    countryCode = matches[2];
                }
            }

            if (serviceId && countryCode) {
                showPurchaseModal(serviceId, countryCode);
            }

            // Restore button state
            button.prop('disabled', false).text(originalText);
        });
    }

    function initModals() {
        // Close modal when clicking outside
        $(document).on('click', '.havn-modal', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });

        // Close modal when clicking close button
        $(document).on('click', '.havn-modal-close', function() {
            $(this).closest('.havn-modal').hide();
        });

        // Close modal with Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.havn-modal').hide();
            }
        });
    }

    function showPurchaseModal(serviceId, countryCode) {
        // Get service and country details from DOM
        var serviceCard = $('.havn-service-card').filter(function() {
            return $(this).find(`[onclick*="${serviceId}"]`).length > 0;
        });

        var countryItem = serviceCard.find(`[onclick*="${countryCode}"]`).closest('.country-item');

        if (serviceCard.length && countryItem.length) {
            var serviceName = serviceCard.find('h3').text();
            var countryName = countryItem.find('.country-name').text();
            var price = countryItem.find('.country-price').text();

            // Populate modal
            $('#purchase-service').text(serviceName);
            $('#purchase-country').text(countryName);
            $('#purchase-price').text(price);

            // Store current purchase info
            window.currentPurchase = {
                serviceId: serviceId,
                countryCode: countryCode
            };

            // Show modal
            $('#havn-purchase-modal').show();
        }
    }

    function showPurchaseSuccess(message) {
        var resultDiv = $('#purchase-result');
        resultDiv.html('<div class="success-message">' + message + '</div>').show();
    }

    function showPurchaseError(message) {
        var resultDiv = $('#purchase-result');
        resultDiv.html('<div class="error-message">' + message + '</div>').show();
    }

    // Handle country flag loading errors
    $(document).on('error', '.country-flag', function() {
        // Replace broken flag with default icon
        $(this).attr('src', havn_ajax.plugin_url + 'assets/images/default-flag.png');
    });

    // Initialize lazy loading for country flags
    if ('IntersectionObserver' in window) {
        const flagObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const flag = entry.target;
                    flag.src = flag.dataset.src;
                    flag.classList.remove('lazy');
                    observer.unobserve(flag);
                }
            });
        });

        $('.country-flag[data-src]').each(function() {
            flagObserver.observe(this);
        });
    }

    // Handle responsive design
    function handleResponsive() {
        var windowWidth = $(window).width();

        if (windowWidth <= 768) {
            // Mobile optimizations
            $('.havn-services-grid').addClass('mobile');
            $('.country-item').addClass('mobile');
        } else {
            $('.havn-services-grid').removeClass('mobile');
            $('.country-item').removeClass('mobile');
        }
    }

    // Call on load and resize
    handleResponsive();
    $(window).on('resize', handleResponsive);

    // Add smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();

        var target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });

    // Add loading states for dynamic content
    function showLoading(element) {
        element.addClass('loading').html('<div class="loading-spinner">در حال بارگذاری...</div>');
    }

    function hideLoading(element) {
        element.removeClass('loading');
    }

    // Handle form submissions with loading states
    $('form').on('submit', function() {
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');

        if (submitButton.length) {
            var originalText = submitButton.text();
            submitButton.prop('disabled', true).text('در حال ارسال...');

            // Re-enable button after form submission (in case of validation errors)
            setTimeout(function() {
                submitButton.prop('disabled', false).text(originalText);
            }, 5000);
        }
    });

    // Initialize tooltips and other UI enhancements
    // $('[data-tooltip]').tooltip(); // Removed - tooltip not available

    // Handle responsive table
    $('.havn-responsive-table').on('click', '.havn-toggle-details', function() {
        var row = $(this).closest('tr');
        var detailsRow = row.next('.havn-details-row');

        if (detailsRow.length) {
            detailsRow.toggle();
        } else {
            // Create details row if it doesn't exist
            createDetailsRow(row);
        }
    });

    function createDetailsRow(row) {
        var purchaseId = row.find('.havn-purchase-id').text();
        var detailsRow = $('<tr class="havn-details-row">').html(
            '<td colspan="8">' +
            '<div class="havn-details-content">' +
            '<h4>جزئیات درخواست #' + purchaseId + '</h4>' +
            '<div class="havn-details-loading">در حال بارگذاری...</div>' +
            '</div>' +
            '</td>'
        );

        row.after(detailsRow);

        // Load details via AJAX
        loadPurchaseDetails(purchaseId, detailsRow);
    }

    function loadPurchaseDetails(purchaseId, detailsRow) {
        $.ajax({
            url: havn_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'havn_get_purchase_details',
                nonce: havn_ajax.nonce,
                purchase_id: purchaseId
            },
            success: function(response) {
                if (response.success) {
                    detailsRow.find('.havn-details-content').html(response.data);
                } else {
                    detailsRow.find('.havn-details-content').html(
                        '<div class="error-message">خطا در بارگذاری جزئیات</div>'
                    );
                }
            },
            error: function() {
                detailsRow.find('.havn-details-content').html(
                    '<div class="error-message">خطا در ارتباط با سرور</div>'
                );
            }
        });
    }

    // Add accessibility improvements
    $('.havn-service-card').attr('tabindex', '0').attr('role', 'button');

    $('.havn-service-card').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).click();
        }
    });

    // Add ARIA labels for better accessibility
    $('.havn-purchase-btn').attr('aria-label', 'خرید شماره مجازی');
    $('.havn-modal-close').attr('aria-label', 'بستن');

    // Handle keyboard navigation in modals
    $('.havn-modal').on('keydown', function(e) {
        if (e.key === 'Tab') {
            var focusableElements = $(this).find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            var firstElement = focusableElements.first();
            var lastElement = focusableElements.last();

            if (e.shiftKey) {
                if (document.activeElement === firstElement[0]) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                if (document.activeElement === lastElement[0]) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }
    });

    // Add smooth animations
    $('.havn-service-card').addClass('animate-in');

    // Add intersection observer for animations
    if ('IntersectionObserver' in window) {
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        });

        $('.havn-service-card').each(function() {
            animationObserver.observe(this);
        });
    }

    // Handle error states gracefully
    $(document).on('ajaxError', function(event, xhr, settings, error) {
        console.error('AJAX Error:', error);

        // Show user-friendly error message
        if (xhr.status === 0) {
            showGlobalError('خطا در ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.');
        } else if (xhr.status === 500) {
            showGlobalError('خطای سرور. لطفاً بعداً تلاش کنید.');
        } else {
            showGlobalError('خطا در پردازش درخواست.');
        }
    });

    function showGlobalError(message) {
        // Create global error notification
        var notification = $('<div class="havn-global-error">' + message + '</div>');
        $('body').append(notification);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
});





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

// Global variables
let allServices = document.getElementById('all_services_json').value;

let currentPage = 1;
let perPage = 20;
let basePath = document.getElementById('base_path_js').value;
let currentService = null;

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
            const usdRate = document.getElementById('havn_usd_rate').value;
            const profitMargin = document.getElementById('havn_profit_margin').value;
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
    if (!searchQuery && allServices) {
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
                showSuccessModal('شماره با موفقیت خریداری شد!به صفحه شماره مجازی خود برید و دریافت کد بزنید');
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