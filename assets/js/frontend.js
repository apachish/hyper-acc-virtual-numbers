/**
 * Frontend JavaScript
 */

// Loading functions
function showLoading(message = 'در حال بارگذاری...') {
    const loadingOverlay = document.getElementById('havn-loading-overlay');
    const loadingText = loadingOverlay ? loadingOverlay.querySelector('.loading-text') : null;
    
    if (loadingOverlay) {
        if (loadingText) {
            loadingText.textContent = message;
        }
        loadingOverlay.classList.add('show');
        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.visibility = 'visible';
        loadingOverlay.style.opacity = '1';
        loadingOverlay.style.zIndex = '9999';
    }
}

function hideLoading() {
    const loadingOverlay = document.getElementById('havn-loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.classList.remove('show');
        loadingOverlay.style.display = 'none';
        loadingOverlay.style.visibility = 'hidden';
        loadingOverlay.style.opacity = '0';
        loadingOverlay.style.zIndex = '-1';
    }
    
    // Also try to hide any loading with class
    const loadingElements = document.querySelectorAll('.havn-loading-overlay');
    loadingElements.forEach(element => {
        element.classList.remove('show');
        element.style.display = 'none';
        element.style.visibility = 'hidden';
        element.style.opacity = '0';
        element.style.zIndex = '-1';
    });
}

// Hide loading on page load
document.addEventListener('DOMContentLoaded', function() {
    hideLoading();
});

// Hide loading on window load (backup)
window.addEventListener('load', function() {
    hideLoading();
});

// Emergency hide loading after 10 seconds
setTimeout(function() {
    hideLoading();
}, 10000);

// Add click to hide loading functionality
document.addEventListener('DOMContentLoaded', function() {
    const loadingOverlay = document.getElementById('havn-loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.addEventListener('click', function(e) {
            if (e.target === loadingOverlay) {
                hideLoading();
            }
        });
    }
});

// Initialize services page
window.initServicesPage = function() {
    console.log('Initializing services page...');
    
    // Hide loading overlay on page load
    hideLoading();
    
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
        window.havnUsdRate = window.havnUsdRate;
    }
    if (window.havnProfitMargin) {
        window.havnProfitMargin = window.havnProfitMargin;
    }
    
    // Initialize the page
    renderServicesPage(allServices.slice(0, perPage), 1);
    if(havn_ajax.is_logged_in) {
        renderCountriesUser();
    }
    attachSearchListeners();
    
    // Load user statistics
    loadUserStats();
    
    // Load wallet balance
    updateWalletBalance();
    
    console.log('Services page initialized successfully');
};

// Load user statistics
function loadUserStats() {
    if (!havn_ajax.is_logged_in) {
        hideLoading(); // Hide loading if user not logged in
        return;
    }
    
    showLoading('در حال بارگذاری آمار کاربر...');
    
    const formData = new FormData();
    formData.append('action', 'havn_get_user_stats');
    formData.append('nonce', havn_ajax.nonce);
    
    fetch(havn_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateUserLimitsDisplay(data.data);
        }
        hideLoading();
    })
    .catch(error => {
        console.error('Error loading user stats:', error);
        hideLoading();
    });
}

// Update wallet balance display
function updateWalletBalance() {
    if (!havn_ajax.is_logged_in) {
        hideLoading(); // Hide loading if user not logged in
        return;
    }
    
    showLoading('در حال بارگذاری موجودی کیف پول...');
    
    const formData = new FormData();
    formData.append('action', 'havn_get_user_balance');
    formData.append('nonce', havn_ajax.nonce);

    fetch(havn_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const balanceElement = document.getElementById('wallet-balance');
            if (balanceElement) {
                const formattedBalance = new Intl.NumberFormat('fa-IR', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(data.data);
                balanceElement.textContent = formattedBalance + ' تومان';
            }
        }
        hideLoading();
    })
    .catch(error => {
        console.error('Error loading wallet balance:', error);
        hideLoading();
    });
}

// Update user limits display
function updateUserLimitsDisplay(stats) {
    const limitsInfo = document.getElementById('user-limits-info');
    const blockStatus = document.getElementById('block-status');
    
    if (!limitsInfo) return;
    
    // Update limit values
    document.getElementById('pending-count').textContent = stats.pending_count || 0;
    document.getElementById('recent-purchases').textContent = stats.recent_purchases || 0;
    document.getElementById('recent-cancellations').textContent = stats.recent_cancellations || 0;
    
    // Show/hide block status
    if (stats.is_blocked) {
        blockStatus.style.display = 'block';
        document.getElementById('block-until').textContent = `تا: ${stats.block_until}`;
        limitsInfo.style.display = 'block';
    } else {
        blockStatus.style.display = 'none';
        limitsInfo.style.display = 'block';
    }
    
    // Highlight limits that are close to max
    const pendingCount = document.getElementById('pending-count');
    const recentPurchases = document.getElementById('recent-purchases');
    const recentCancellations = document.getElementById('recent-cancellations');
    
    if (stats.pending_count >= 2) {
        pendingCount.style.color = '#dc3545';
    }
    if (stats.recent_purchases >= 2) {
        recentPurchases.style.color = '#dc3545';
    }
    if (stats.recent_cancellations >= 4) {
        recentCancellations.style.color = '#dc3545';
    }
}

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

// Global variables are defined in shortcode

// Render services page
function renderServicesPage(services, page) {
    if (typeof currentPage !== 'undefined') {
        currentPage = page;
    }
    const container = document.getElementById('services-container');
    let html = '';

    if (!services || services.length === 0) {
        html = '<div style="text-align: center; padding: 40px; color: #6b7280;">هیچ سرویسی یافت نشد</div>';
    } else {
        services.forEach(service => {
            const serviceName = service.service_full_name || service.name || service.id || '';
            const serviceIcon = service.service_icon || '';
            const logoUrl = serviceIcon && typeof basePath !== 'undefined' ? (basePath + serviceIcon) : '';

            html += `
          <div class="list-item" onclick="viewService('${service.service_short_name}')">
            <div class="service-info">
              <img src="${logoUrl}" alt="${serviceName}" class="service-logo" onerror="this.style.display='none'">
              <div class="service-details">
                <div class="service-name">${serviceName}</div>
                <div class="service-status">سرویس فعال</div>
              </div>
            </div>
     
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
    if (typeof allServices === 'undefined' || typeof perPage === 'undefined') {
        return;
    }
    const totalPages = Math.ceil(allServices.length / perPage);
    const pageNumbers = document.getElementById('page-numbers');
    
    // Check if pagination elements exist (they may not exist on user-purchases page)
    if (!pageNumbers) {
        return;
    }
    
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

    // Update prev/next buttons (check if they exist)
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');
    if (prevBtn) prevBtn.disabled = page <= 1;
    if (nextBtn) nextBtn.disabled = page >= totalPages;
}

// Update pagination info
function updatePaginationInfo(page) {
    if (typeof allServices === 'undefined' || typeof perPage === 'undefined') {
        return;
    }
    const start = (page - 1) * perPage + 1;
    const end = Math.min(page * perPage, allServices.length);
    const info = document.getElementById('pagination-info');
    if (info) {
        info.innerHTML = `نمایش ${start} تا ${end}از ${allServices.length} سرویس`;
    }
}

// Change page
function changePage(page) {
    if (typeof allServices === 'undefined' || typeof perPage === 'undefined') {
        return;
    }
    const totalPages = Math.ceil(allServices.length / perPage);
    if (page < 1 || page > totalPages) return;

    const start = (page - 1) * perPage;
    const end = start + perPage;
    const services = allServices.slice(start, end);

    renderServicesPage(services, page);
    window.history.pushState({}, '', `?page=${page}`);
}



// View service countries
function viewService(serviceShortName) {
    if (typeof currentService !== 'undefined') {
        currentService = serviceShortName;
    }
    
    // Add active class to clicked item
    const items = document.querySelectorAll('.list-item');
    items.forEach(item => item.classList.remove('active'));
    if (event && event.currentTarget) {
        event.currentTarget.classList.add('active');
    }

    // Show loading overlay
    showLoading('در حال بارگذاری کشورها...');

    // Show loading in countries table as well
    document.getElementById('countries-table').innerHTML = `
      <div class="row">
        <div class="col" style="grid-column: 1 / -1; text-align: center; color: #FC5A44; padding: 40px 20px;">
          <div style="font-size: 16px; margin-bottom: 8px;">⏳</div>
          در حال بارگذاری کشورها...
        </div>
      </div>
    `;
    renderCountriesUser(serviceShortName);

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
            hideLoading();
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
            hideLoading();
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

// Search functionality - searchQuery will be defined in shortcode

function attachSearchListeners() {
    const searchInput = document.getElementById('havn-services-search');
    const searchResultsInfo = document.getElementById('search-results-info');
    const searchResultsText = document.getElementById('search-results-text');
    const clearSearchBtn = document.getElementById('clear-search');
    
    console.log('Attaching search listeners...', {
        searchInput: !!searchInput,
        searchResultsInfo: !!searchResultsInfo,
        searchResultsText: !!searchResultsText,
        ngBtn: !!clearSearchBtn
    });
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            if (typeof searchQuery !== 'undefined') {
                searchQuery = this.value.trim().toLowerCase();
                console.log('Search query:', searchQuery);
                performSearch();
            }
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (typeof clearSearch === 'function') {
                clearSearch();
            } else {
                // Fallback if clearSearch is not defined
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                }
                if (typeof searchQuery !== 'undefined') {
                    searchQuery = '';
                }
                if (typeof performSearch === 'function') {
                    performSearch();
                }
            }
        });
    }
}

// Clear search function is defined in shortcode

// Also attach listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // attachSearchListeners();
});

function performSearch() {
    if (typeof searchQuery === 'undefined' || typeof allServices === 'undefined') {
        return;
    }
    
    const searchResultsInfo = document.getElementById('search-results-info');
    const searchResultsText = document.getElementById('search-results-text');
    const clearSearchBtn = document.getElementById('clear-search');
    
    console.log('Performing search:', {
        searchQuery: searchQuery,
        allServices: allServices ? allServices.length : 'undefined',
        searchResultsInfo: !!searchResultsInfo,
        searchResultsText: !!searchResultsText
    });
    
    // Show/hide clear search button
    if (clearSearchBtn) {
        if (searchQuery && searchQuery.length > 0) {
            clearSearchBtn.style.display = 'block';
        } else {
            clearSearchBtn.style.display = 'none';
        }
    }
    
    if (!searchQuery && allServices) {
        const currentPageValue = typeof currentPage !== 'undefined' ? currentPage : 1;
        const perPageValue = typeof perPage !== 'undefined' ? perPage : 20;
        renderServicesPage(allServices.slice((currentPageValue - 1) * perPageValue, currentPageValue * perPageValue), currentPageValue);
        if (searchResultsInfo) {
            searchResultsInfo.classList.remove('show');
        }
        return;
    }

    const filteredServices = allServices.filter(function(service) {
        const serviceName = (service.service_full_name || service.name || service.id || '').toLowerCase();
        return serviceName.indexOf(searchQuery) !== -1;
    });

    console.log('Filtered services:', filteredServices.length);

    if (filteredServices.length > 0) {
        renderSearchResults(filteredServices);
        if (searchResultsText) {
            searchResultsText.textContent = `${filteredServices.length} نتیجه برای "${searchQuery}" یافت شد`;
        }
        if (searchResultsInfo) {
            searchResultsInfo.classList.add('show');
        }
    } else {
        renderSearchResults([]);
        if (searchResultsText) {
            searchResultsText.textContent = `هیچ نتیجه‌ای برای "${searchQuery}" یافت نشد`;
        }
        if (searchResultsInfo) {
            searchResultsInfo.classList.add('show');
        }
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
            const logoUrl = serviceIcon && typeof basePath !== 'undefined' ? (basePath + serviceIcon) : '';

            html += `
          <div class="list-item" onclick="viewService('${service.service_short_name}')">
            <div class="service-info">
              <img src="${logoUrl}" alt="${serviceName}" class="service-logo" onerror="this.style.display='none'">
              <div class="service-details">
                <div class="service-name">${serviceName}</div>
                <div class="service-status">سرویس فعال</div>
              </div>
            </div>
          </div>
        `;
        });
    }

    container.innerHTML = html;
    // attachSearchListeners();
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

    // Show loading overlay
    showLoading('در حال پردازش خرید...');

    // Show loading on button too
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
                // Update user statistics
                loadUserStats();
                // Update wallet balance
                updateWalletBalance();
                renderCountriesUser(serviceId)
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
            hideLoading();
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

function renderCountriesUser(serviceId= null) {
    const container = document.getElementById('countries-container');
    if (!container) {
        return;
    }
    fetch(havn_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'havn_get_user_purchases',
            service_id: serviceId,
            nonce: havn_ajax.nonce
        })
    })
        .then(response => response.json())
        .then(data => {
            console.log(data)
            if (data.success) {
                var allServices_user = data.data;
                if(serviceId)
                    window.currentService = allServices_user.find(s => s.service_id === serviceId);
                else
                    window.currentService = allServices_user;
                container.innerHTML = '';
                console.log(window.currentService , serviceId)

                if ((!window.currentService || !window.currentService.purchases || window.currentService.purchases.length === 0) && serviceId) {
                    container.innerHTML = `<div class="havn-no-purchases"><i class="fas fa-phone-slash"></i><p>هیچ شماره‌ای برای این سرویس یافت نشد</p></div>`;
                    return;
                }else if(window.currentService.length === 0 && !serviceId){
                    container.innerHTML = `
                    <div class="havn-no-purchases">
                <i class="fas fa-phone-slash"></i>
                <p> شما هنوز خریدی نداشته‌اید</p>
            </div>`;
                    return;
                }


                    window.currentService.forEach(purchase => {
                        const countryElement = document.createElement('div');
                        countryElement.className = 'havn-purchase-number-card';

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
                            if (timeDiff <= 20) {
                                shouldShowCancel = true;
                            }
                        }

                        countryElement.innerHTML = `
          <div class="service-info">
      
                <div class="havn-purchase-card-details">
                    <div class="havn-purchase-service-name">
                        <img src="${purchase.service_icon}" alt="${purchase.service_name}" onerror="this.style.display='none'">
                        <div>${purchase.service_name}</div>
                        <img src="https://flagcdn.com/${purchase.purchase.country_code}.svg" 
                             alt="${purchase.purchase.country_code}"
                             class="country-flag"
                             onerror="this.src='<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-flag.png'">
                        <span>کشور: ${purchase.purchase.country_code.toUpperCase()}</span>
                    </div>
                    <div class="havn-purchase-number">
                        <i class="fas fa-phone"></i>
                        <span>شماره: <a href="tel://${purchase.purchase.number || 'نامشخص'}">${purchase.purchase.number || 'نامشخص'}</a></span>
                        <button class="havn-copy-phone-btn" onclick="copyPhoneNumber('${purchase.purchase.number || ''}', this)" title="کپی شماره">
                            <i class="fas fa-copy"></i>
                            کپی
                        </button>
                    </div>
                    <div class="havn-purchase-status" id="satats-number-${purchase.purchase.number_id}">
                        <i class="fas fa-info-circle"></i>
                        <span>وضعیت: <span class="havn-purchase-status-badge havn-purchase-status-${purchase.purchase.status_number?.toLowerCase() || 'pending'}">${purchase.purchase.status_number || 'نامشخص'}</span></span>
                    </div>           
                    <div class="havn-purchase-sms">
                        <div>پیامک:</div>
                        <div class="havn-purchase-codes-box" id="codes-result-${purchase.purchase.number_id}">
                            ${existingCode}
                        </div>
                        <div id="codes-error-${purchase.purchase.number_id}"></div>
                    </div>
                </div>
            </div>
            <div class="havn-purchase-actions">
                ${purchase.purchase.status_number === 'CANCELED' ? `
                    <div></div>
                ` : purchase.purchase.number_id ? `
                    ${shouldShowCancel ? `
                 <button class="havn-purchase-btn havn-purchase-btn-primary" style="margin-bottom: 10px" onclick="getCodes('${purchase.purchase.number_id}', this)">
                            <i class="fas fa-key"></i>
                            دریافت کد
                        </button>
                        <button class="havn-purchase-btn havn-purchase-btn-danger" onclick="cancelNumber('${purchase.purchase.number_id}', this)">
                            <i class="fas fa-times"></i>
                            لغو شماره
                        </button>

                    ` : hasExistingCode ? `
                        <button class="havn-purchase-btn havn-purchase-btn-primary" onclick="getCodes('${purchase.purchase.number_id}', this)">
                            <i class="fas fa-key"></i>
                            دریافت کد 
                        </button>
                    ` : `
                        <button class="havn-purchase-btn havn-purchase-btn-primary" onclick="getCodes('${purchase.purchase.number_id}', this)">
                            <i class="fas fa-key"></i>
                            دریافت کد
                        </button>
                    `}
                ` : `
                    <div></div>
                `}
            </div>
        `;
                        container.appendChild(countryElement);
                    });

            } else {
                document.getElementById('countries-container').innerHTML = `
          <div class="row">
            <div class="col" style="grid-column: 1 / -1; text-align: center; color: #FC5A44; padding: 40px 20px;">
              <div style="font-size: 16px; margin-bottom: 8px;">❌</div>
              خطا در دریافت خط های خریداری شده: ${data.data || 'خطای نامشخص'}
            </div>
          </div>
        `;
            }
        })
        .catch(error => {
            document.getElementById('countries-container').innerHTML = `
        <div class="row">
          <div class="col" style="grid-column: 1 / -1; text-align: center; color: #FC5A44; padding: 40px 20px;">
            <div style="font-size: 16px; margin-bottom: 8px;">❌</div>
            خطا در ارتباط با سرور
          </div>
        </div>
      `;
        });

}
function getCodes(numberId, button) {
    // Show loading
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال دریافت...';
console.log(numberId)
    const formData = new FormData();
    formData.append('action', 'havn_get_number_codes');
    formData.append('number_id', numberId);
    formData.append('nonce',  document.getElementById('have_nonce').value);
    fetch(havn_ajax.ajax_url, {
        method: 'POST',
        body: formData


    })
        .then(response => response.json())
        .then(data => {
            // Get the result box
            const resultBox = document.getElementById(`codes-result-${numberId}`);
            const errorBox = document.getElementById(`codes-error-${numberId}`);
            const statusBox = document.getElementById(`satats-number-${numberId}`);
            if (!resultBox) return;
            console.log(data)
            if (data.success) {
                if (data.data && data.data.code && data.data.code.length > 0) {
                    // Show new codes in the result box
                    const codesHtml = data.data.code;
                    resultBox.innerHTML = `${codesHtml}`;
                    // Replace the button with the new code display
                    const actionsDiv = button.closest('.havn-purchase-actions');


                    // Update the status in the UI
                    const row = button.closest('.havn-purchase-number-card');
                    if (statusBox) {
                        statusBox.innerHTML = `<i class="fas fa-info-circle"></i> <span>وضعیت: <span class="havn-purchase-status-badge havn-purchase-status-${data.data.state?.toLowerCase() || 'pending'}">${data.data.state || 'PENDING'}</span></span>`;
                    }
                } else {
                    if(data.data.state?.toLowerCase() == "refunded"){
                        errorBox.innerHTML = `به علت عدم دریافت کد در زمان ممکن خط بازگرداند شد`;
                        renderCountriesUser();
                    }else {
                        errorBox.innerHTML = `کدی دریافت نشده است`;
                    }
                }
            } else {
                errorBox.innerHTML = `${data.data}`;
                errorBox.style.display = 'block';
            }
        })
        .catch(error => {
            const errorBox = document.getElementById(`codes-error-${numberId}`);

            if (resultBox) {
                resultBox.innerHTML = `<p>خطا در ارتباط با سرور</p>`;
                resultBox.style.display = 'block';
            }
        })
        .finally(() => {
            // Restore button
            button.disabled = false;
            button.innerHTML = originalText;
        });
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
    formData.append('nonce', document.getElementById('cancel_nonce').value);
    console.log(formData)
    fetch(havn_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the status in the UI
                const row = button.closest('.havn-purchase-number-card');
                const statusElement = row.querySelector('.havn-purchase-status');
                if (statusElement) {
                    statusElement.innerHTML = `<i class="fas fa-info-circle"></i> <span>وضعیت: <span class="havn-purchase-status-badge havn-purchase-status-canceled">CANCELED</span></span>`;
                }

                // Replace button with canceled message
                const actionsDiv = button.closest('.havn-purchase-actions');
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
