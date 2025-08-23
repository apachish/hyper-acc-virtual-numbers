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

<style>
/* STRONG ISOLATION - Prevent theme interference */
.havn-virtual-numbers-container {
  all: initial !important;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
  direction: rtl !important;
  display: block !important;
  position: relative !important;
  z-index: 9999 !important;
  isolation: isolate !important;
}

.havn-virtual-numbers-container * {
  all: unset !important;
  box-sizing: border-box !important;
}

/* Main wrapper with maximum specificity */
.havn-virtual-numbers-container .rent-wrapper {
  background: #ffffff !important;
  border-radius: 16px !important;
  padding: 24px !important;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
  direction: rtl !important;
  box-shadow: 0 10px 40px rgba(0,0,0,0.1) !important;
  max-width: 1200px !important;
  margin: 40px auto !important;
  position: relative !important;
  z-index: 100 !important;
  display: block !important;
  overflow: visible !important;
}

/* Header with clean tabs */
.havn-virtual-numbers-container .rent-header {
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
  margin-bottom: 24px !important;
  padding-bottom: 16px !important;
  border-bottom: 2px solid #f3f4f6 !important;
  position: relative !important;
}

.havn-virtual-numbers-container .tabs {
  display: flex !important;
  gap: 8px !important;
  background: #f9fafb !important;
  padding: 4px !important;
  border-radius: 12px !important;
  position: relative !important;
}

.havn-virtual-numbers-container .tab {
  border: none !important;
  background: transparent !important;
  padding: 12px 20px !important;
  border-radius: 8px !important;
  cursor: pointer !important;
  font-weight: 600 !important;
  font-size: 14px !important;
  color: #6b7280 !important;
  transition: all 0.2s ease !important;
  position: relative !important;
  display: inline-block !important;
}

.havn-virtual-numbers-container .tab.active {
  background: #FC5A44 !important;
  color: #ffffff !important;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3) !important;
}

.havn-virtual-numbers-container .tab:hover:not(.active) {
  background: #e5e7eb !important;
  color: #374151 !important;
}

.havn-virtual-numbers-container .search-box {
  position: relative !important;
  display: block !important;
}

.havn-virtual-numbers-container .search-box input {
  border: 2px solid #e5e7eb !important;
  border-radius: 12px !important;
  padding: 12px 16px !important;
  background: #ffffff !important;
  color: #111111 !important;
  font-size: 14px !important;
  width: 280px !important;
  transition: border-color 0.2s ease !important;
  position: relative !important;
  display: block !important;
}

.havn-virtual-numbers-container .search-box input:focus {
  outline: none !important;
  border-color: #FC5A44 !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

/* Body - FORCE two column grid layout */
.havn-virtual-numbers-container .rent-body {
  display: grid !important;
  grid-template-columns: 1fr 2fr !important;
  gap: 24px !important;
  align-items: start !important;
  position: relative !important;
  width: 100% !important;
}

/* Services List (Left) - FORCE width */
.havn-virtual-numbers-container .rent-list {
  background: #fafafa !important;
  border: 1px solid #e5e7eb !important;
  border-radius: 12px !important;
  max-height: 60vh !important;
  overflow-y: auto !important;
  position: relative !important;
  width: 100% !important;
  min-width: 0 !important;
  flex-shrink: 0 !important;
}

.havn-virtual-numbers-container .services-container {
  max-height: calc(60vh - 80px) !important;
  overflow-y: auto !important;
  margin-bottom: 16px !important;
  padding: 16px !important;
}

.havn-virtual-numbers-container .list-item {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 16px !important;
  background: #ffffff !important;
  border: 1px solid #e5e7eb !important;
  border-radius: 10px !important;
  margin-bottom: 12px !important;
  cursor: pointer !important;
  transition: all 0.2s ease !important;
  position: relative !important;
  width: 100% !important;
}

.havn-virtual-numbers-container .list-item:hover {
  border-color: #FC5A44 !important;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1) !important;
  transform: translateY(-1px) !important;
}

.havn-virtual-numbers-container .list-item.active {
  background: #fef2f2 !important;
  border-color: #FC5A44 !important;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15) !important;
}

.havn-virtual-numbers-container .list-item .icon {
  width: 32px !important;
  height: 32px !important;
  background: #3b82f6 !important;
  border-radius: 8px !important;
  overflow: hidden !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  flex-shrink: 0 !important;
  position: relative !important;
}

.havn-virtual-numbers-container .list-item .icon img {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  position: relative !important;
}

.havn-virtual-numbers-container .list-item span {
  font-weight: 500 !important;
  color: #374151 !important;
  flex: 1 !important;
  position: relative !important;
  display: block !important;
}

.havn-virtual-numbers-container .list-item .view-btn {
  background: #FC5A44 !important;
  color: #ffffff !important;
  border: none !important;
  padding: 8px 16px !important;
  border-radius: 6px !important;
  cursor: pointer !important;
  font-size: 12px !important;
  font-weight: 500 !important;
  transition: all 0.2s ease !important;
  flex-shrink: 0 !important;
  position: relative !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 4px !important;
}

.havn-virtual-numbers-container .list-item .view-btn .view-text {
  color: #ffffff !important;
}

.havn-virtual-numbers-container .list-item .view-btn .view-icon {
  color: #ffffff !important;
  display: none !important;
}

.havn-virtual-numbers-container .list-item .view-btn:hover {
  background: #dc2626 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3) !important;
}

.havn-virtual-numbers-container .list-item .view-btn .view-icon {
  display: none !important;
}

/* Enhanced Pagination - Fixed at bottom of services list */
.havn-virtual-numbers-container .rent-footer {
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;
  padding: 16px !important;
  background: #ffffff !important;
  border-top: 1px solid #e5e7eb !important;
  position: sticky !important;
  bottom: 0 !important;
  left: 0 !important;
  right: 0 !important;
  z-index: 10 !important;
  flex-wrap: wrap !important;
  gap: 12px !important;
  border-radius: 0 0 12px 12px !important;
  box-shadow: 0 -2px 10px rgba(0,0,0,0.1) !important;
  width: 100% !important;
}

.havn-virtual-numbers-container .pagination-info {
  color: #6b7280 !important;
  font-size: 14px !important;
  font-weight: 500 !important;
}

.havn-virtual-numbers-container .pagination-controls {
  display: flex !important;
  gap: 8px !important;
  align-items: center !important;
  flex-wrap: nowrap !important;
  overflow: hidden !important;
}

.havn-virtual-numbers-container .btn.small {
  background: #FC5A44 !important;
  color: #ffffff !important;
  border: none !important;
  padding: 0 !important;
  border-radius: 50% !important;
  cursor: pointer !important;
  font-size: 13px !important;
  font-weight: 500 !important;
  transition: all 0.2s ease !important;
  position: relative !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  text-decoration: none !important;
  width: 32px !important;
  height: 32px !important;
  min-width: 32px !important;
  max-width: 32px !important;
  flex-shrink: 0 !important;
}

.havn-virtual-numbers-container .btn.small:hover {
  background: #dc2626 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3) !important;
}

.havn-virtual-numbers-container .btn.small:disabled {
  background: #9ca3af !important;
  cursor: not-allowed !important;
  transform: none !important;
  box-shadow: none !important;
}

.havn-virtual-numbers-container .page-numbers {
  display: flex !important;
  gap: 4px !important;
  align-items: center !important;
  flex-wrap: nowrap !important;
  overflow: hidden !important;
  max-width: 120px !important;
}

.havn-virtual-numbers-container .page {
  background: #FC5A44 !important;
  color: #ffffff !important;
  border-radius: 50% !important;
  width: 32px !important;
  height: 32px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-weight: 600 !important;
  font-size: 14px !important;
  position: relative !important;
}

.havn-virtual-numbers-container .page-link {
  background: #f3f4f6 !important;
  color: #374151 !important;
  border-radius: 50% !important;
  width: 32px !important;
  height: 32px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-weight: 500 !important;
  font-size: 14px !important;
  text-decoration: none !important;
  transition: all 0.2s ease !important;
  position: relative !important;
  cursor: pointer !important;
}

.havn-virtual-numbers-container .page-link:hover {
  background: #e5e7eb !important;
  color: #111827 !important;
  transform: translateY(-1px) !important;
}

.havn-virtual-numbers-container .page-link.current {
  background: #FC5A44 !important;
  color: #ffffff !important;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3) !important;
}

/* Search results info */
.havn-virtual-numbers-container .search-results-info {
  background: #f0f9ff !important;
  border: 1px solid #0ea5e9 !important;
  border-radius: 8px !important;
  padding: 12px 16px !important;
  margin-bottom: 16px !important;
  color: #0369a1 !important;
  font-size: 14px !important;
  font-weight: 500 !important;
  display: none !important;
}

.havn-virtual-numbers-container .search-results-info.show {
  display: block !important;
}

.havn-virtual-numbers-container .clear-search {
  background: #FC5A44 !important;
  color: #ffffff !important;
  border: none !important;
  padding: 4px 8px !important;
  border-radius: 4px !important;
  font-size: 12px !important;
  cursor: pointer !important;
  margin-right: 8px !important;
  transition: all 0.2s ease !important;
}

.havn-virtual-numbers-container .clear-search:hover {
  background: #FFEFEC !important;
}

/* Table (Right) - FORCE width */
.havn-virtual-numbers-container .rent-table-box {
  background: #ffffff !important;
  border: 1px solid #e5e7eb !important;
  border-radius: 12px !important;
  padding: 20px !important;
  min-height: 400px !important;
  position: relative !important;
  width: 100% !important;
  min-width: 0 !important;
  flex-shrink: 0 !important;
}

.havn-virtual-numbers-container .rent-filters {
  display: grid !important;
  grid-template-columns: 1fr 1fr 1fr auto !important;
  gap: 16px !important;
  padding: 16px !important;
  background: #FFEFEC !important;
  border-radius: 10px !important;
  font-weight: 600 !important;
  margin-bottom: 20px !important;
  color: #FC5A44 !important;
  font-size: 14px !important;
  position: relative !important;
}

.havn-virtual-numbers-container .rent-filters span {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  position: relative !important;
  padding-right: 32px !important;
}

.havn-virtual-numbers-container .rent-filters span::after {
  content: '' !important;
  position: absolute !important;
  right: 8px !important;
  top: 50% !important;
  transform: translateY(-50%) !important;
  width: 16px !important;
  height: 16px !important;
  background-size: contain !important;
  background-repeat: no-repeat !important;
  background-position: center !important;
}

.havn-virtual-numbers-container .rent-filters span:first-child::after {
  background-image: url('<?php echo HAVN_PLUGIN_URL; ?>assets/images/world.svg') !important;
}

.havn-virtual-numbers-container .rent-filters span:nth-child(2)::after {
  background-image: url('<?php echo HAVN_PLUGIN_URL; ?>assets/images/price-tag.svg') !important;
}

.havn-virtual-numbers-container .rent-filters span:nth-child(3)::after {
  background-image: url('<?php echo HAVN_PLUGIN_URL; ?>assets/images/check-circle.svg') !important;
}

.havn-virtual-numbers-container .rent-filters span:nth-child(4)::after {
  background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23FC5A44"><path d="M19 13H5v-2h14v2z"/></svg>') !important;
}

.havn-virtual-numbers-container .rent-table .row {
  display: grid !important;
  grid-template-columns: 1fr 1fr 1fr auto !important;
  gap: 16px !important;
  align-items: center !important;
  padding: 16px !important;
  border-bottom: 1px solid #f3f4f6 !important;
  transition: background 0.2s ease !important;
  position: relative !important;
  background: #f5f5f5 !important;
  border-radius: 8px !important;
  margin-bottom: 8px !important;
}

.havn-virtual-numbers-container .rent-table .row:hover {
  background: #f0f0f0 !important;
}

.havn-virtual-numbers-container .rent-table .row.disabled {
  opacity: 0.6 !important;
  background: #f9fafb !important;
}

.havn-virtual-numbers-container .rent-table .row .col {
  font-size: 14px !important;
  color: #374151 !important;
  position: relative !important;
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
}

.havn-virtual-numbers-container .rent-table .row .col {
  font-size: 14px !important;
  color: #374151 !important;
  position: relative !important;
  display: block !important;
}

.havn-virtual-numbers-container .country-flag {
  width: 16px !important;
  height: 12px !important;
  border-radius: 2px !important;
  object-fit: cover !important;
  margin-right: 8px !important;
}

.havn-virtual-numbers-container .btn {
  background: #FC5A44 !important;
  color: #ffffff !important;
  border: none !important;
  padding: 10px 20px !important;
  border-radius: 8px !important;
  cursor: pointer !important;
  font-size: 14px !important;
  font-weight: 500 !important;
  transition: all 0.2s ease !important;
  min-width: 100px !important;
  position: relative !important;
  display: inline-block !important;
}

.havn-virtual-numbers-container .btn:hover {
  background: #dc2626 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
}

.havn-virtual-numbers-container .btn.disabled {
  background: #9ca3af !important;
  cursor: not-allowed !important;
  transform: none !important;
  box-shadow: none !important;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .havn-virtual-numbers-container .rent-wrapper {
    margin: 20px !important;
    padding: 20px !important;
  }
  
  .havn-virtual-numbers-container .rent-body {
    grid-template-columns: 1fr !important;
    gap: 20px !important;
  }
  
  .havn-virtual-numbers-container .rent-list {
    max-height: 50vh !important;
  }
  
  .havn-virtual-numbers-container .search-box input {
    width: 200px !important;
  }
  
  .havn-virtual-numbers-container .rent-footer {
    padding: 14px !important;
  }
  
  .havn-virtual-numbers-container .pagination-info {
    font-size: 13px !important;
  }
}

@media (max-width: 768px) {
  .havn-virtual-numbers-container .rent-header {
    flex-direction: column !important;
    gap: 16px !important;
    align-items: stretch !important;
  }
  
  .havn-virtual-numbers-container .search-box input {
    width: 100% !important;
  }
  
  .havn-virtual-numbers-container .tabs {
    justify-content: center !important;
  }
  
  .havn-virtual-numbers-container .rent-filters {
    grid-template-columns: 1fr !important;
    text-align: center !important;
  }
  
  .havn-virtual-numbers-container .rent-table .row {
    grid-template-columns: 1fr !important;
    gap: 8px !important;
    text-align: center !important;
  }
  
  .havn-virtual-numbers-container .btn {
    width: 100% !important;
  }
  
  .havn-virtual-numbers-container .rent-footer {
    flex-direction: column !important;
    text-align: center !important;
    gap: 8px !important;
    padding: 12px !important;
  }
  
  .havn-virtual-numbers-container .pagination-controls {
    justify-content: center !important;
    gap: 4px !important;
  }
  
  .havn-virtual-numbers-container .pagination-info {
    font-size: 12px !important;
  }
  
  .havn-virtual-numbers-container .btn.small {
    padding: 0 !important;
    font-size: 12px !important;
    width: 28px !important;
    height: 28px !important;
    min-width: 28px !important;
    max-width: 28px !important;
  }
  
  .havn-virtual-numbers-container .page-numbers {
    max-width: 100px !important;
    gap: 2px !important;
  }
  
  .havn-virtual-numbers-container .page,
  .havn-virtual-numbers-container .page-link {
    width: 28px !important;
    height: 28px !important;
    font-size: 12px !important;
  }
  
  /* Mobile view button */
  .havn-virtual-numbers-container .list-item .view-btn .view-text {
    display: none !important;
  }
  
  .havn-virtual-numbers-container .list-item .view-btn .view-icon {
    display: block !important;
  }
  
  .havn-virtual-numbers-container .list-item .view-btn {
    padding: 8px !important;
    min-width: 40px !important;
  }
}

/* Extra small mobile devices */
@media (max-width: 480px) {
  .havn-virtual-numbers-container .rent-footer {
    padding: 8px !important;
    gap: 6px !important;
  }
  
  .havn-virtual-numbers-container .pagination-info {
    font-size: 11px !important;
  }
  
  .havn-virtual-numbers-container .btn.small {
    padding: 0 !important;
    font-size: 11px !important;
    width: 24px !important;
    height: 24px !important;
    min-width: 24px !important;
    max-width: 24px !important;
  }
  
  .havn-virtual-numbers-container .page-numbers {
    max-width: 80px !important;
    gap: 1px !important;
  }
  
  .havn-virtual-numbers-container .page,
  .havn-virtual-numbers-container .page-link {
    width: 24px !important;
    height: 24px !important;
    font-size: 11px !important;
  }
}

/* Smooth scrolling for list */
.havn-virtual-numbers-container .rent-list {
  scrollbar-width: thin !important;
  scrollbar-color: #d1d5db #f3f4f6 !important;
}

.havn-virtual-numbers-container .rent-list::-webkit-scrollbar {
  width: 6px !important;
}

.havn-virtual-numbers-container .rent-list::-webkit-scrollbar-track {
  background: #f3f4f6 !important;
  border-radius: 3px !important;
}

.havn-virtual-numbers-container .rent-list::-webkit-scrollbar-thumb {
  background: #d1d5db !important;
  border-radius: 3px !important;
}

.havn-virtual-numbers-container .rent-list::-webkit-scrollbar-thumb:hover {
  background: #9ca3af !important;
}
</style>

<div class="havn-virtual-numbers-container">
  <div class="rent-wrapper">
    <!-- Header -->
    <div class="rent-header">
      <div class="search-box">
        <input type="text" id="havn-services-search" placeholder="جستجو در سرویس‌ها...">
      </div>
      <div class="tabs">
        <button class="tab active">اجاره‌ای</button>
        <button class="tab">توضیحات</button>
      </div>
    </div>

    <!-- Body: Two columns -->
    <div class="rent-body">
      <!-- Left: Services List -->
      <div class="rent-list" id="rent-services-list">
        <!-- Search Results Info -->
        <div class="search-results-info" id="search-results-info">
          <button class="clear-search" id="clear-search">پاک کردن جستجو</button>
          <span id="search-results-text"></span>
        </div>
        
        <!-- Services Container -->
        <div class="services-container" id="services-container">
          <?php foreach ($services as $service): ?>
            <?php
              $service_name = $service['service_full_name'] ?? ($service['name'] ?? ($service['id'] ?? ''));
              $service_short = $service['service_short_name'] ?? ($service['short'] ?? ($service['id'] ?? ''));
              $service_icon = $service['service_icon'] ?? '';
              $icon_url = $service_icon ? ( (strpos($service_icon, 'http')===0) ? $service_icon : rtrim($base_path,'/') . '/' . ltrim($service_icon,'/') ) : (HAVN_PLUGIN_URL . 'assets/images/default-service.png');
            ?>
            <div class="list-item" data-service-id="<?php echo esc_attr($service_short); ?>" data-service-name="<?php echo esc_attr($service_name); ?>">
              <div class="icon">
                <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($service_name); ?>" onerror="this.src='<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-service.png'">
              </div>
                          <span><?php echo esc_html($service_name); ?></span>
            <button class="view-btn" onclick="viewService('<?php echo esc_attr($service_short); ?>')">
              <span class="view-text">مشاهده</span>
              <span class="view-icon">👁️</span>
            </button>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Client-side Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="rent-footer">
          <div class="pagination-info">
            نمایش <?php echo esc_html($offset + 1); ?> تا <?php echo esc_html(min($offset + $per_page, $total_services)); ?> از <?php echo esc_html($total_services); ?> سرویس
          </div>
          
          <div class="pagination-controls">
            <!-- Previous Page -->
            <button class="btn small" id="prev-page" <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>>&lt;</button>
            
            <!-- Page Numbers -->
            <div class="page-numbers">
              <?php
              // Show only 3 page numbers: current, one before, one after
              $pages_to_show = array();
              
              if ($total_pages <= 3) {
                // If total pages is 3 or less, show all pages
                for ($i = 1; $i <= $total_pages; $i++) {
                  $pages_to_show[] = $i;
                }
              } else {
                // Show current page and one before/after
                if ($current_page === 1) {
                  $pages_to_show = array(1, 2, 3);
                } elseif ($current_page === $total_pages) {
                  $pages_to_show = array($total_pages - 2, $total_pages - 1, $total_pages);
                } else {
                  $pages_to_show = array($current_page - 1, $current_page, $current_page + 1);
                }
              }
              
              // Render the 3 page numbers
              foreach ($pages_to_show as $page_num) {
                if ($page_num == $current_page) {
                  echo '<span class="page">' . esc_html($page_num) . '</span>';
                } else {
                  echo '<button class="page-link" data-page="' . esc_attr($page_num) . '">' . esc_html($page_num) . '</button>';
                }
              }
              ?>
            </div>
            
            <!-- Next Page -->
            <button class="btn small" id="next-page" <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>>&gt;</button>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Right: Countries Table -->
      <div class="rent-table-box">
        <div class="rent-filters">
          <span>کشور</span>
          <span>قیمت(تومان)</span>
          <span>موجودی</span>
          <span>عملیات</span>
        </div>
        <div class="rent-table" id="countries-table">
          <div class="row">
            <div class="col" style="grid-column: 1 / -1; text-align: center; color: #6b7280; padding: 40px 20px;">
              <div style="font-size: 16px; margin-bottom: 8px;">📱</div>
              لطفاً یک سرویس را از لیست انتخاب کنید
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  let currentSelectedService = null;
  
  // Store all services for client-side pagination
  const allServices = <?php echo $all_services_json; ?>;
  const perPage = <?php echo $per_page; ?>;
  const totalPages = <?php echo $total_pages; ?>;
  let currentPage = <?php echo $current_page; ?>;
  const basePath = '<?php echo $base_path; ?>';

  // Service selection
  document.querySelectorAll('.services-container .list-item').forEach(function(item){
    item.addEventListener('click', function(){
      const serviceId = this.getAttribute('data-service-id');
      const serviceName = this.getAttribute('data-service-name');
      selectService(this, serviceId, serviceName);
    });
  });

  function selectService(el, serviceId, serviceName){
    // Remove active from all items
    document.querySelectorAll('.services-container .list-item').forEach(function(i){ 
      i.classList.remove('active'); 
    });
    
    // Add active to clicked item
    el.classList.add('active');
    currentSelectedService = serviceId;
    
    // Load countries for this service
    loadServiceCountries(serviceId);
  }

  function loadServiceCountries(serviceId){
    const formData = new FormData();
    formData.append('action', 'havn_get_service_countries');
    formData.append('nonce', havn_ajax.nonce);
    formData.append('service_id', serviceId);

    // Show loading state
    document.getElementById('countries-table').innerHTML = `
      <div class="row">
        <div class="col" style="grid-column: 1 / -1; text-align: center; color: #6b7280; padding: 40px 20px;">
          <div style="font-size: 16px; margin-bottom: 8px;">⏳</div>
          در حال بارگذاری کشورها...
        </div>
      </div>
    `;

    fetch(havn_ajax.ajax_url, { method: 'POST', body: formData })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
            console.log(data);
          renderCountries(data.data, serviceId);
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
      .catch(() => {
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

  function formatPrice(price){
    return new Intl.NumberFormat('fa-IR').format(price) + ' تومان';
  }

  function renderCountries(countries, serviceId){
      if (!Array.isArray(countries['info'])) countries = [];
    let html = '';
      console.log(countries);

      if (countries['info'].length === 0){
      html += `
        <div class="row">
          <div class="col" style="grid-column: 1 / -1; text-align: center; color: #6b7280; padding: 40px 20px;">
            <div style="font-size: 16px; margin-bottom: 8px;">🌍</div>
            هیچ کشوری برای این سرویس یافت نشد
          </div>
        </div>
      `;
    } else {
          countries['info'].forEach(function(c){
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
    }
    
    document.getElementById('countries-table').innerHTML = html;
  }

  // Enhanced Search functionality
  const searchInput = document.getElementById('havn-services-search');
  const searchResultsInfo = document.getElementById('search-results-info');
  const searchResultsText = document.getElementById('search-results-text');
  const clearSearchBtn = document.getElementById('clear-search');
  let searchQuery = '';
  
  if (searchInput){
    searchInput.addEventListener('input', function(){
      searchQuery = this.value.trim().toLowerCase();
      performSearch();
    });
  }
  
  if (clearSearchBtn) {
    clearSearchBtn.addEventListener('click', function(){
      searchInput.value = '';
      searchQuery = '';
      performSearch();
    });
  }
  
  function performSearch() {
    if (!searchQuery) {
      // Show all services from current page
      renderServicesPage(allServices.slice((currentPage - 1) * perPage, currentPage * perPage), currentPage);
      searchResultsInfo.classList.remove('show');
      return;
    }
    
    // Search in all services from the complete list
    const filteredServices = allServices.filter(function(service) {
      const serviceName = (service.service_full_name || service.name || service.id || '').toLowerCase();
      return serviceName.indexOf(searchQuery) !== -1;
    });
    
    // Show filtered results
    if (filteredServices.length > 0) {
      // Display all filtered services (no pagination during search)
      renderSearchResults(filteredServices);
      searchResultsText.textContent = `${filteredServices.length} نتیجه برای "${searchQuery}" یافت شد`;
      searchResultsInfo.classList.add('show');
    } else {
      // No results found
      renderSearchResults([]);
      searchResultsText.textContent = `هیچ نتیجه‌ای برای "${searchQuery}" یافت نشد`;
      searchResultsInfo.classList.add('show');
    }
  }
  
  function renderSearchResults(filteredServices) {
    // Search results info
    let searchInfoHtml = `
      <div class="search-results-info" id="search-results-info">
        <button class="clear-search" id="clear-search">پاک کردن جستجو</button>
        <span id="search-results-text"></span>
      </div>
    `;
    
    // Services container
    let servicesHtml = '<div class="services-container" id="services-container">';
    
    if (filteredServices.length === 0) {
      servicesHtml += '<div style="text-align: center; padding: 40px; color: #6b7280;">هیچ سرویسی یافت نشد</div>';
    } else {
      filteredServices.forEach(function(service) {
        const service_name = service.service_full_name || service.name || service.id || '';
        const service_short = service.service_short_name || service.short || service.id || '';
        const service_icon = service.service_icon || '';
        const icon_url = service_icon ? 
          (service_icon.startsWith('http') ? service_icon : basePath + service_icon) : 
          '<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-service.png';
        
        servicesHtml += `
          <div class="list-item" data-service-id="${service_short}" data-service-name="${service_name}">
            <div class="icon">
              <img src="${icon_url}" alt="${service_name}" onerror="this.src='<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-service.png'">
            </div>
            <span>${service_name}</span>
            <button class="view-btn" onclick="viewService('${service_short}')">
              <span class="view-text">مشاهده</span>
              <span class="view-icon">👁️</span>
            </button>
          </div>
        `;
      });
    }
    
    servicesHtml += '</div>';
    
    // No pagination during search
    const fullHtml = searchInfoHtml + servicesHtml;
    
    document.getElementById('rent-services-list').innerHTML = fullHtml;
    
    // Reattach event listeners
    attachServiceListeners();
    attachSearchListeners();
    
    // Clear selection
    currentSelectedService = null;
    document.getElementById('countries-table').innerHTML = `
      <div class="row">
        <div class="col" style="grid-column: 1 / -1; text-align: center; color: #6b7280; padding: 40px 20px;">
          <div style="font-size: 16px; margin-bottom: 8px;">📱</div>
          لطفاً یک سرویس را از لیست انتخاب کنید
        </div>
      </div>
    `;
  }

  // Client-side Pagination functionality
  const prevBtn = document.getElementById('prev-page');
  const nextBtn = document.getElementById('next-page');
  
  if (prevBtn) {
    prevBtn.addEventListener('click', function(){
      if (!this.disabled) {
        loadPage(currentPage - 1);
      }
    });
  }
  
  if (nextBtn) {
    nextBtn.addEventListener('click', function(){
      if (!this.disabled) {
        loadPage(currentPage + 1);
      }
    });
  }
  
  // Page number clicks
  document.querySelectorAll('.page-link').forEach(function(link){
    link.addEventListener('click', function(){
      const page = parseInt(this.getAttribute('data-page'));
      loadPage(page);
    });
  });
  
  function loadPage(page) {
    if (page < 1 || page > totalPages) return;
    
    currentPage = page;
    
    // Calculate offset
    const offset = (page - 1) * perPage;
    const pageServices = allServices.slice(offset, offset + perPage);
    
    // Render services with fresh pagination
    renderServicesPage(pageServices, page);
    
    // Update URL without reload
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    window.history.pushState({}, '', url);
  }
  
  function renderServicesPage(services, page) {
    // Search results info
    let searchInfoHtml = `
      <div class="search-results-info" id="search-results-info">
        <button class="clear-search" id="clear-search">پاک کردن جستجو</button>
        <span id="search-results-text"></span>
      </div>
    `;
    
    // Services container
    let servicesHtml = '<div class="services-container" id="services-container">';
    
    if (!Array.isArray(services) || services.length === 0) {
      servicesHtml += '<div style="text-align: center; padding: 40px; color: #6b7280;">هیچ سرویسی در این صفحه یافت نشد</div>';
    } else {
      services.forEach(function(service) {
        const service_name = service.service_full_name || service.name || service.id || '';
        const service_short = service.service_short_name || service.short || service.id || '';
        const service_icon = service.service_icon || '';
        const icon_url = service_icon ? 
          (service_icon.startsWith('http') ? service_icon : basePath + service_icon) : 
          '<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-service.png';
        
        servicesHtml += `
          <div class="list-item" data-service-id="${service_short}" data-service-name="${service_name}">
            <div class="icon">
              <img src="${icon_url}" alt="${service_name}" onerror="this.src='<?php echo HAVN_PLUGIN_URL; ?>assets/images/default-service.png'">
            </div>
            <span>${service_name}</span>
            <button class="view-btn" onclick="viewService('${service_short}')">
              <span class="view-text">مشاهده</span>
              <span class="view-icon">👁️</span>
            </button>
          </div>
        `;
      });
    }
    
    servicesHtml += '</div>';
    
    // Generate fresh pagination HTML for the current page
    const paginationHtml = generatePaginationHTML(page);
    
    // Combine all HTML
    const fullHtml = searchInfoHtml + servicesHtml + paginationHtml;
    
    document.getElementById('rent-services-list').innerHTML = fullHtml;
    
    // Reattach event listeners
    attachServiceListeners();
    attachPaginationListeners();
    attachSearchListeners();
    
    // Clear selection
    currentSelectedService = null;
    document.getElementById('countries-table').innerHTML = `
      <div class="row">
        <div class="col" style="grid-column: 1 / -1; text-align: center; color: #6b7280; padding: 40px 20px;">
          <div style="font-size: 16px; margin-bottom: 8px;">📱</div>
          لطفاً یک سرویس را از لیست انتخاب کنید
        </div>
      </div>
    `;
  }
  
  function generatePaginationHTML(currentPage) {
    if (totalPages <= 1) return '';
    
    const offset = (currentPage - 1) * perPage;
    const start = offset + 1;
    const end = Math.min(offset + perPage, allServices.length);
    const total = allServices.length;
    
    let paginationHTML = `
      <div class="rent-footer">
        <div class="pagination-info">
          نمایش ${start} تا ${end} از ${total} سرویس
        </div>
        
        <div class="pagination-controls">
          <button class="btn small" id="prev-page" ${currentPage <= 1 ? 'disabled' : ''}>&lt;</button>
          
          <div class="page-numbers">
    `;
    
    // Show only 3 page numbers: current, one before, one after
    let pagesToShow = [];
    
    if (totalPages <= 3) {
      // If total pages is 3 or less, show all pages
      for (let i = 1; i <= totalPages; i++) {
        pagesToShow.push(i);
      }
    } else {
      // Show current page and one before/after
      if (currentPage === 1) {
        pagesToShow = [1, 2, 3];
      } else if (currentPage === totalPages) {
        pagesToShow = [totalPages - 2, totalPages - 1, totalPages];
      } else {
        pagesToShow = [currentPage - 1, currentPage, currentPage + 1];
      }
    }
    
    // Render the 3 page numbers
    pagesToShow.forEach(function(pageNum) {
      if (pageNum == currentPage) {
        paginationHTML += `<span class="page">${pageNum}</span>`;
      } else {
        paginationHTML += `<button class="page-link" data-page="${pageNum}">${pageNum}</button>`;
      }
    });
    
    paginationHTML += `
          </div>
          
          <button class="btn small" id="next-page" ${currentPage >= totalPages ? 'disabled' : ''}>&gt;</button>
        </div>
      </div>
    `;
    
    return paginationHTML;
  }
  
  
  
  function attachServiceListeners() {
    document.querySelectorAll('.services-container .list-item').forEach(function(item){
      item.addEventListener('click', function(){
        const serviceId = this.getAttribute('data-service-id');
        const serviceName = this.getAttribute('data-service-name');
        selectService(this, serviceId, serviceName);
      });
    });
  }
  
  function attachPaginationListeners() {
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');
    
    if (prevBtn) {
      prevBtn.addEventListener('click', function(){
        if (!this.disabled) {
          loadPage(currentPage - 1);
        }
      });
    }
    
    if (nextBtn) {
      nextBtn.addEventListener('click', function(){
        if (!this.disabled) {
          loadPage(currentPage + 1);
        }
      });
    }
    
    document.querySelectorAll('.page-link').forEach(function(link){
      link.addEventListener('click', function(){
        const page = parseInt(this.getAttribute('data-page'));
        loadPage(page);
      });
    });
  }
  
  function attachSearchListeners() {
    const searchInput = document.getElementById('havn-services-search');
    const clearSearchBtn = document.getElementById('clear-search');
    
    if (searchInput) {
      searchInput.addEventListener('input', function(){
        searchQuery = this.value.trim().toLowerCase();
        performSearch();
      });
    }
    
    if (clearSearchBtn) {
      clearSearchBtn.addEventListener('click', function(){
        if (searchInput) {
          searchInput.value = '';
        }
        searchQuery = '';
        performSearch();
      });
    }
  }
  
  // Global function to view service countries
  window.viewService = function(serviceShortName) {
    loadServiceCountries(serviceShortName);
  }

  // Tab switching
  document.querySelectorAll('.tab').forEach(function(tab){
    tab.addEventListener('click', function(){
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      
      // Handle tab content here if needed
      if (this.textContent === 'توضیحات') {
        // Show service descriptions
        document.getElementById('countries-table').innerHTML = `
          <div class="row">
            <div class="col" style="grid-column: 1 / -1; text-align: center; color: #6b7280; padding: 40px 20px;">
              <div style="font-size: 16px; margin-bottom: 8px;">📋</div>
              توضیحات سرویس‌ها
            </div>
          </div>
        `;
      } else {
        // Show countries table
        if (currentSelectedService) {
          loadServiceCountries(currentSelectedService);
        }
      }
    });
  });
})();
</script> 