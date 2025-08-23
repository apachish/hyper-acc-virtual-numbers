/**
 * Admin Panel JavaScript
 */

jQuery(document).ready(function($) {
    
    // Initialize admin functionality
    initAdminPanel();
    
    function initAdminPanel() {
        // Add any admin-specific initialization here
        console.log('Hyper ACC Virtual Numbers Admin Panel initialized');
    }
    
    // Handle purchase status updates
    $('.havn-update-status').on('click', function(e) {
        e.preventDefault();
        
        var purchaseId = $(this).data('purchase-id');
        var currentStatus = $(this).data('current-status');
        
        showStatusUpdateModal(purchaseId, currentStatus);
    });
    
    // Handle bulk actions
    $('#havn-bulk-actions').on('change', function() {
        var action = $(this).val();
        
        if (action) {
            var selectedPurchases = $('.havn-purchase-checkbox:checked');
            
            if (selectedPurchases.length === 0) {
                alert('لطفاً حداقل یک درخواست را انتخاب کنید');
                $(this).val('');
                return;
            }
            
            if (confirm('آیا از انجام این عملیات اطمینان دارید؟')) {
                performBulkAction(action, selectedPurchases);
            }
            
            $(this).val('');
        }
    });
    
    // Handle search and filters
    $('#havn-search-form').on('submit', function(e) {
        e.preventDefault();
        
        var searchTerm = $('#havn-search-input').val();
        var statusFilter = $('#havn-status-filter').val();
        var serviceFilter = $('#havn-service-filter').val();
        var countryFilter = $('#havn-country-filter').val();
        
        // Build query string
        var params = new URLSearchParams();
        if (searchTerm) params.append('search', searchTerm);
        if (statusFilter) params.append('status', statusFilter);
        if (serviceFilter) params.append('service', serviceFilter);
        if (countryFilter) params.append('country', countryFilter);
        
        // Redirect with filters
        var currentUrl = new URL(window.location);
        currentUrl.search = params.toString();
        window.location.href = currentUrl.toString();
    });
    
    // Handle cache refresh
    $('.havn-refresh-cache').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('آیا از بروزرسانی کش اطمینان دارید؟')) {
            refreshCache();
        }
    });
    
    // Handle API test
    $('.havn-test-api').on('click', function(e) {
        e.preventDefault();
        
        testAPIConnection();
    });
    
    // Handle service refresh
    $('.havn-refresh-services').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('آیا از بروزرسانی سرویس‌ها اطمینان دارید؟')) {
            refreshServices();
        }
    });
    
    // Handle export functionality
    $('.havn-export-data').on('click', function(e) {
        e.preventDefault();
        
        var format = $(this).data('format');
        exportData(format);
    });
    
    // Handle import functionality
    $('#havn-import-file').on('change', function() {
        var file = this.files[0];
        if (file) {
            importData(file);
        }
    });
    
    // Helper functions
    function showStatusUpdateModal(purchaseId, currentStatus) {
        // This would show a modal for updating purchase status
        // Implementation depends on your modal system
        console.log('Show status update modal for purchase:', purchaseId);
    }
    
    function performBulkAction(action, selectedPurchases) {
        var purchaseIds = [];
        selectedPurchases.each(function() {
            purchaseIds.push($(this).val());
        });
        
        // Send AJAX request for bulk action
        $.ajax({
            url: havn_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'havn_bulk_action',
                nonce: havn_ajax.nonce,
                bulk_action: action,
                purchase_ids: purchaseIds
            },
            success: function(response) {
                if (response.success) {
                    alert('عملیات با موفقیت انجام شد');
                    location.reload();
                } else {
                    alert('خطا: ' + response.data);
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور');
            }
        });
    }
    
    function refreshCache() {
        $.ajax({
            url: havn_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'havn_refresh_cache',
                nonce: havn_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('کش با موفقیت بروزرسانی شد');
                } else {
                    alert('خطا: ' + response.data);
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور');
            }
        });
    }
    
    function testAPIConnection() {
        var resultDiv = $('#havn-api-result');
        resultDiv.html('در حال تست اتصال...').show();
        
        $.ajax({
            url: havn_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'havn_test_api',
                nonce: havn_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.html('<div class="success-message">اتصال موفق</div>');
                } else {
                    resultDiv.html('<div class="error-message">خطا: ' + response.data + '</div>');
                }
            },
            error: function() {
                resultDiv.html('<div class="error-message">خطا در ارتباط با سرور</div>');
            }
        });
    }
    
    function refreshServices() {
        $.ajax({
            url: havn_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'havn_refresh_services',
                nonce: havn_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('سرویس‌ها با موفقیت بروزرسانی شدند');
                } else {
                    alert('خطا: ' + response.data);
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور');
            }
        });
    }
    
    function exportData(format) {
        var params = new URLSearchParams();
        params.append('action', 'havn_export_data');
        params.append('nonce', havn_ajax.nonce);
        params.append('format', format);
        
        // Create temporary form for download
        var form = $('<form>', {
            'method': 'POST',
            'action': havn_ajax.ajax_url
        });
        
        params.forEach(function(value, key) {
            form.append($('<input>', {
                'type': 'hidden',
                'name': key,
                'value': value
            }));
        });
        
        $('body').append(form);
        form.submit();
        form.remove();
    }
    
    function importData(file) {
        var formData = new FormData();
        formData.append('action', 'havn_import_data');
        formData.append('nonce', havn_ajax.nonce);
        formData.append('import_file', file);
        
        $.ajax({
            url: havn_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('داده‌ها با موفقیت وارد شدند');
                    location.reload();
                } else {
                    alert('خطا: ' + response.data);
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور');
            }
        });
    }
    
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
}); 