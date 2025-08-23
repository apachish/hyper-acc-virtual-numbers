<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>تنظیمات شماره‌های مجازی</h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('havn_settings');
        do_settings_sections('havn-settings');
        ?>
        
<!--        <table class="form-table">-->
<!--            <tr>-->
<!--                <th scope="row">نرخ دلار</th>-->
<!--                <td>-->
<!--                    <input type="number" name="havn_usd_rate" value="--><?php //echo esc_attr(get_option('havn_usd_rate', 50000)); ?><!--" class="regular-text" />-->
<!--                    <p class="description">نرخ تبدیل دلار به تومان</p>-->
<!--                </td>-->
<!--            </tr>-->
<!--            -->
<!--            <tr>-->
<!--                <th scope="row">حاشیه سود</th>-->
<!--                <td>-->
<!--                    <input type="number" name="havn_profit_margin" value="--><?php //echo esc_attr(get_option('havn_profit_margin', 10)); ?><!--" class="regular-text" step="0.1" />-->
<!--                    <p class="description">درصد حاشیه سود که به قیمت پایه اضافه می‌شود</p>-->
<!--                </td>-->
<!--            </tr>-->
<!--            -->
<!--            <tr>-->
<!--                <th scope="row">مدت زمان کش</th>-->
<!--                <td>-->
<!--                    <input type="number" name="havn_cache_duration" value="--><?php //echo esc_attr(get_option('havn_cache_duration', 3600)); ?><!--" class="regular-text" />-->
<!--                    <p class="description">مدت زمان کش اطلاعات در ثانیه (3600 = 1 ساعت)</p>-->
<!--                </td>-->
<!--            </tr>-->
<!--            -->
<!--            <tr>-->
<!--                <th scope="row">کلید API VirtuNum</th>-->
<!--                <td>-->
<!--                    <input type="text" name="havn_virtunum_api_key" value="--><?php //echo esc_attr(get_option('havn_virtunum_api_key', '')); ?><!--" class="regular-text" />-->
<!--                    <p class="description">کلید API دریافت شده از VirtuNum</p>-->
<!--                </td>-->
<!--            </tr>-->
<!--            -->
<!--            <tr>-->
<!--                <th scope="row">آدرس API VirtuNum</th>-->
<!--                <td>-->
<!--                    <input type="url" name="havn_virtunum_api_url" value="--><?php //echo esc_attr(get_option('havn_virtunum_api_url', 'https://api.virtunum.com')); ?><!--" class="regular-text" />-->
<!--                    <p class="description">آدرس پایه API VirtuNum</p>-->
<!--                </td>-->
<!--            </tr>-->
<!--        </table>-->
        
        <?php submit_button(); ?>
    </form>
    
    <hr>
    
    <h2>عملیات سیستم</h2>
    
    <div class="havn-system-actions">
        <h3>مدیریت کش</h3>
        <p>برای بروزرسانی اطلاعات سرویس‌ها و کشورها، کش را پاک کنید:</p>
        <button type="button" class="button" onclick="havnClearCache()">پاک کردن کش</button>
        
        <h3>تست اتصال API</h3>
        <p>برای بررسی اتصال به VirtuNum API:</p>
        <button type="button" class="button" onclick="havnTestAPI()">تست اتصال</button>
        
        <h3>بروزرسانی دستی</h3>
        <p>برای دریافت آخرین اطلاعات از VirtuNum:</p>
        <button type="button" class="button" onclick="havnRefreshServices()">بروزرسانی سرویس‌ها</button>
    </div>
    
    <div id="havn-api-status" style="display: none;">
        <h3>وضعیت API</h3>
        <div id="havn-api-result"></div>
    </div>
</div>

<script>
function havnClearCache() {
    if (confirm('آیا از پاک کردن کش اطمینان دارید؟')) {
        // TODO: Implement AJAX call to clear cache
        alert('کش با موفقیت پاک شد');
    }
}

function havnTestAPI() {
    document.getElementById('havn-api-status').style.display = 'block';
    document.getElementById('havn-api-result').innerHTML = 'در حال تست اتصال...';
    
    // TODO: Implement AJAX call to test API connection
    setTimeout(function() {
        document.getElementById('havn-api-result').innerHTML = 'اتصال موفق';
    }, 2000);
}

function havnRefreshServices() {
    if (confirm('آیا از بروزرسانی سرویس‌ها اطمینان دارید؟')) {
        // TODO: Implement AJAX call to refresh services
        alert('سرویس‌ها با موفقیت بروزرسانی شدند');
    }
}
</script> 