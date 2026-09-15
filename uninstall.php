<?php
/**
 * ملف الإزالة - يُنفذ عند حذف Pro
 *
 * @package Woo_Zero_Search_Miner_Pro
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// حذف خيارات الترخيص
delete_option('wzsmpro_license');
delete_option('wzsmpro_settings');
delete_option('wzsmpro_installed_at');

// إلغاء الجدولات المجدولة
wp_clear_scheduled_hook('wzsmpro_daily_report');

// لا نحذف سجلات Basic - هذه تحت إدارة Basic plugin
