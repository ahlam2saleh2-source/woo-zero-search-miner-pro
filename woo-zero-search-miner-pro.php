<?php
/**
 * Plugin Name:       Woo Zero Search Miner Pro
 * Plugin URI:        https://troyawin.tech
 * Description:        الإصدار الاحترافي الكامل والمستقل - يحوي كل مزايا Basic + تنبيهات بريدية فورية، Dashboard Widget، إشعارات Slack/Discord، واجهة محسّنة بأزرار تنقل، Dark Mode، White-label. لا يحتاج تفعيل أي إضافة أخرى.
 * Version:           1.0.0
 * Author:            az-soft4media
 * Author URI:        https://troyawin.tech
 * Text Domain:       woo-zero-search-miner-pro
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 * WC tested up to:   9.0
 */

// الخروج المباشر
if (!defined('ABSPATH')) {
    exit;
}

// ════════════════════════════════════════════════════════
//  التعريفات
// ════════════════════════════════════════════════════════
define('WZSMPRO_VERSION',           '1.0.0');
define('WZSMPRO_PLUGIN_FILE',        __FILE__);
define('WZSMPRO_PLUGIN_DIR',         plugin_dir_path(__FILE__));
define('WZSMPRO_PLUGIN_URL',         plugin_dir_url(__FILE__));
define('WZSMPRO_PLUGIN_BASENAME',    plugin_basename(__FILE__));
define('WZSMPRO_DB_TABLE',           'woo_zero_search_logs_pro');  // ✅ جدول مستقل
define('WZSMPRO_NONCE_ACTION',       'wzsmpro_admin_nonce');
define('WZSMPRO_OPTION_KEY',         'wzsmpro_license');
define('WZSMPRO_SETTINGS_KEY',       'wzsmpro_settings');
define('WZSMPRO_CAP',                'manage_woocommerce');

// ════════════════════════════════════════════════════════
//  Autoloader
// ════════════════════════════════════════════════════════
spl_autoload_register(function ($class) {
    $prefix = 'WZSMPRO_';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative  = substr($class, strlen($prefix));
    $filename = 'class-' . strtolower(str_replace('_', '-', $relative)) . '.php';
    $path     = WZSMPRO_PLUGIN_DIR . 'includes/' . $filename;
    if (file_exists($path)) {
        require_once $path;
    }
});

// ════════════════════════════════════════════════════════
//  التهيئة (مستقلة - لا تعتمد على Basic)
// ════════════════════════════════════════════════════════
function wzsmpro_init()
{
    // التحقق من تفعيل WooCommerce فقط
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . esc_html__('Woo Zero Search Miner Pro', 'woo-zero-search-miner-pro') . '</strong> ';
            echo esc_html__('يتطلب تفعيل WooCommerce أولاً.', 'woo-zero-search-miner-pro');
            echo '</p></div>';
        });
        return;
    }

    // تحميل ملف الترجمة
    load_plugin_textdomain(
        'woo-zero-search-miner-pro',
        false,
        dirname(WZSMPRO_PLUGIN_BASENAME) . '/languages'
    );

    // بدء الكلاسات الأساسية (مستقلة)
    WZSMPRO_Tracker::instance();

    if (is_admin()) {
        WZSMPRO_Admin::instance();
        WZSMPRO_Settings::instance();
        WZSMPRO_Export::instance();
    }

    WZSMPRO_Cron::instance();

    // بدء الكلاسات Premium
    WZSMPRO_License::instance();
    WZSMPRO_Email_Alerts::instance();
    WZSMPRO_Dashboard_Widget::instance();
    WZSMPRO_Webhooks::instance();
    WZSMPRO_Premium_UI::instance();
}
add_action('plugins_loaded', 'wzsmpro_init');

// ════════════════════════════════════════════════════════
//  التفعيل - إنشاء جدول مستقل
// ════════════════════════════════════════════════════════
register_activation_hook(__FILE__, function () {
    require_once WZSMPRO_PLUGIN_DIR . 'includes/class-database.php';
    require_once WZSMPRO_PLUGIN_DIR . 'includes/class-cron.php';

    // إنشاء جدول Pro المستقل
    WZSMPRO_Database::create_table();

    // جدولة مهام التنظيف والتقارير
    WZSMPRO_Cron::schedule_events();

    // خيارات افتراضية (Basic + Premium معاً)
    if (!get_option(WZSMPRO_SETTINGS_KEY)) {
        add_option(WZSMPRO_SETTINGS_KEY, array(
            // إعدادات Basic
            'enabled'             => 1,
            'retention_days'      => 90,
            'min_term_length'     => 2,
            'max_term_length'     => 100,
            'excluded_terms'      => '',
            'track_logged_in'     => 1,
            'track_anonymous'     => 1,
            'ip_anonymize'        => 1,
            'auto_cleanup'        => 1,
            // إعدادات Premium
            'email_alerts_enabled'        => 0,
            'email_alerts_recipient'      => get_option('admin_email'),
            'email_alerts_min_interval'    => 300,
            'daily_report_enabled'         => 1,
            'daily_report_recipient'      => get_option('admin_email'),
            'daily_report_hour'            => 8,
            'slack_webhook_url'             => '',
            'slack_enabled'                => 0,
            'discord_webhook_url'          => '',
            'discord_enabled'              => 0,
            'dashboard_widget_enabled'     => 1,
            'premium_ui_enabled'           => 1,
            'dark_mode'                    => 0,
            'white_label'                  => 0,
        ));
    }

    update_option('wzsmpro_installed_at', current_time('mysql'));
    update_option('wzsmpro_db_version', WZSMPRO_VERSION);
    flush_rewrite_rules();
});

// ════════════════════════════════════════════════════════
//  الإيقاف
// ════════════════════════════════════════════════════════
register_deactivation_hook(__FILE__, function () {
    require_once WZSMPRO_PLUGIN_DIR . 'includes/class-cron.php';
    WZSMPRO_Cron::clear_schedules();
    wp_clear_scheduled_hook('wzsmpro_daily_report');
    flush_rewrite_rules();
});

// ════════════════════════════════════════════════════════
//  فحص تحديثات الجدول
// ════════════════════════════════════════════════════════
add_action('admin_init', function () {
    $current_version = get_option('wzsmpro_db_version');
    if ($current_version !== WZSMPRO_VERSION) {
        require_once WZSMPRO_PLUGIN_DIR . 'includes/class-database.php';
        WZSMPRO_Database::create_table();
        update_option('wzsmpro_db_version', WZSMPRO_VERSION);
    }
});

// ════════════════════════════════════════════════════════
//  رابط الإضافة في قائمة الإضافات
// ════════════════════════════════════════════════════════
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=wzsmpro-license')) . '">'
        . esc_html__('إعدادات Pro', 'woo-zero-search-miner-pro') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
