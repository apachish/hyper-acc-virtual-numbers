/**
 * Frontend JavaScript
 */

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

    // Handle purchase confirmation
    $(document).on('click', '#confirm-purchase', function() {
        var button = $(this);
        var originalText = button.text();

        // Show loading state
        button.prop('disabled', true).text('در حال پردازش...');

        // Get purchase details
        var purchaseData = window.currentPurchase;

        if (!purchaseData || !purchaseData.serviceId || !purchaseData.countryCode) {
            showPurchaseError('اطلاعات خرید ناقص است');
            button.prop('disabled', false).text(originalText);
            return;
        }

        // Send purchase request
        var formData = new FormData();
        formData.append('action', 'havn_purchase_number');
        formData.append('nonce', havn_ajax.nonce);
        formData.append('service_id', purchaseData.serviceId);
        formData.append('country_code', purchaseData.countryCode);

        $.ajax({
            url: havn_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showPurchaseSuccess('شماره با موفقیت خریداری شد!');

                    // Close modal after delay
                    setTimeout(function() {
                        $('#havn-purchase-modal').hide();
                        location.reload();
                    }, 2000);
                } else {
                    showPurchaseError('خطا: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Purchase error:', error);
                showPurchaseError('خطا در ارتباط با سرور');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    function showPurchaseSuccess(message) {
        var resultDiv = $('#purchase-result');
        resultDiv.html('<div class="success-message">' + message + '</div>').show();
    }

    function showPurchaseError(message) {
        var resultDiv = $('#purchase-result');
        resultDiv.html('<div class="error-message">' + message + '</div>').show();
    }

    // Handle service refresh
    $(document).on('click', '.havn-refresh-services', function(e) {
        e.preventDefault();

        var button = $(this);
        var originalText = button.text();

        button.prop('disabled', true).text('در حال بروزرسانی...');

        // Reload page to refresh services
        setTimeout(function() {
            location.reload();
        }, 1000);
    });

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
    $('[data-tooltip]').tooltip();

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

// Global function for purchasing numbers (called from HTML)
function havnPurchaseNumber(serviceId, countryCode) {
    if (!havn_ajax || !havn_ajax.is_logged_in) {
        alert('لطفاً ابتدا وارد شوید');
        return;
    }

    // Show purchase confirmation
    if (confirm('آیا از خرید این شماره اطمینان دارید؟')) {
        // Send purchase request
        const formData = new FormData();
        formData.append('action', 'havn_purchase_number');
        formData.append('nonce', havn_ajax.nonce);
        formData.append('service_id', serviceId);
        formData.append('country_code', countryCode);

        fetch(havn_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('شماره با موفقیت خریداری شد!');
                    // Optionally refresh the page or update UI
                    location.reload();
                } else {
                    alert('خطا: ' + data.data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('خطا در ارتباط با سرور');
            });
    }
}