<?php
/**
 * فئة الكرون - جدولة التنظيف التلقائي للسجلات القديمة
 *
 * @package Woo_Zero_Search_Miner
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSMPRO_Cron
{
    const CLEANUP_HOOK = 'wzsm_daily_cleanup';

    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action(self::CLEANUP_HOOK, array($this, 'run_cleanup'));
    }

    /**
     * جدولة المهام عند التفعيل
     */
    public static function schedule_events()
    {
        if (!wp_next_scheduled(self::CLEANUP_HOOK)) {
            wp_schedule_event(time(), 'daily', self::CLEANUP_HOOK);
        }
    }

    /**
     * إلغاء الجدولات عند الإيقاف
     */
    public static function clear_schedules()
    {
        wp_clear_scheduled_hook(self::CLEANUP_HOOK);
    }

    /**
     * تنفيذ التنظيف اليومي
     * يقرأ مدة الاحتفاظ من الإعدادات
     */
    public function run_cleanup()
    {
        $settings = get_option('wzsmpro_basic_settings', array());
        if (empty($settings['auto_cleanup'])) {
            return;
        }

        $days = max(1, (int) ($settings['retention_days'] ?? 90));
        $deleted = WZSMPRO_Database::cleanup_old_logs($days);

        // سجل في WP log إن أمكن
        if ($deleted > 0 && function_exists('error_log')) {
            error_log(sprintf('Woo Zero Search Miner: cleaned up %d old logs (older than %d days).', $deleted, $days));
        }
    }
}
