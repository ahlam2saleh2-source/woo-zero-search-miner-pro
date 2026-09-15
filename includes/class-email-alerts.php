<?php
/**
 * التنبيهات البريدية - إرسال بريد فوري عند ظهور مصطلح جديد بلا نتائج
 * + تقرير يومي مجدول
 *
 * @package Woo_Zero_Search_Miner_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSMPRO_Email_Alerts
{
    private static $instance = null;
    private $last_alerted_terms = array();

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // الإستماع لحدث تسجيل بحث جديد
        add_action('wzsmpro_after_log_zero_search', array($this, 'maybe_send_alert'), 10, 1);
        // التقرير اليومي المجدول
        add_action('wzsmpro_daily_report', array($this, 'send_daily_report'));
    }

    /**
     * الحصول على الإعدادات
     */
    private function get_settings()
    {
        $defaults = array(
            'email_alerts_enabled'         => 0,
            'email_alerts_recipient'      => get_option('admin_email'),
            'email_alerts_min_interval'    => 300,
            'daily_report_enabled'         => 1,
            'daily_report_recipient'      => get_option('admin_email'),
        );
        $saved = get_option(WZSMPRO_OPTION_KEY, array());
        return wp_parse_args($saved, $defaults);
    }

    /**
     * إرسال تنبيه بريدي عند ظهور مصطلح جديد (لم يُسجّل من قبل)
     */
    public function maybe_send_alert($data)
    {
        if (!true) {
            return;
        }
        $settings = $this->get_settings();
        if (empty($settings['email_alerts_enabled'])) {
            return;
        }

        $term = $data['search_term'];
        $normalized = WZSMPRO_Database::normalize_term($term);

        // تحقق إن كان المصطلح قد سُجّل سابقاً (مصطلح جديد فريد)
        global $wpdb;
        $table = WZSMPRO_Database::table_name();
        $prev_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE search_term_normalized = %s
               AND id < %d",
            $normalized,
            $data['log_id']
        ));

        // إن كان قد سُجّل سابقاً، لا تنبه (تجنّب الإزعاج)
        if ($prev_count > 0) {
            return;
        }

        // منع التكرار للمصطلح نفسه خلال 5 دقائق
        $cache_key = 'wzsmpro_alerted_' . md5($normalized);
        if (isset($this->last_alerted_terms[$cache_key])) {
            return;
        }
        $this->last_alerted_terms[$cache_key] = true;

        $recipient = sanitize_email($settings['email_alerts_recipient']);
        if (!is_email($recipient)) {
            return;
        }

        $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $site_url = home_url();
        $admin_url = admin_url('admin.php?page=wzsmpro-dashboard');

        $subject = sprintf(
            /* translators: %1$s: site name, %2$s: search term */
            __('[%1$s] 🔍 مصطلح بحث جديد بلا نتائج: "%2$s"', 'woo-zero-search-miner-pro'),
            $site_name,
            $term
        );

        $message  = sprintf(__('مرحباً،', 'woo-zero-search-miner-pro')) . "\n\n";
        $message .= sprintf(
            /* translators: %s: search term */
            __('بحث أحد زوار متجرك عن "%s" ولم يجد نتائج.这可能 يكون فرصة لإضافة منتج جديد!', 'woo-zero-search-miner-pro'),
            $term
        ) . "\n\n";
        $message .= __('التفاصيل:', 'woo-zero-search-miner-pro') . "\n";
        $message .= sprintf('- %s: %s', __('المصطلح', 'woo-zero-search-miner-pro'), $term) . "\n";
        $message .= sprintf('- %s: %s', __('الوقت', 'woo-zero-search-miner-pro'), $data['occurred_at']) . "\n";
        $message .= sprintf('- %s: %s', __('اللغة', 'woo-zero-search-miner-pro'), $data['language']) . "\n";
        $message .= sprintf('- %s: %s', __('نوع الطلب', 'woo-zero-search-miner-pro'), $data['is_ajax'] ? 'AJAX' : __('عادي', 'woo-zero-search-miner-pro')) . "\n";
        $message .= "\n";
        $message .= __('لعرض كل السجلات:', 'woo-zero-search-miner-pro') . "\n";
        $message .= $admin_url . "\n\n";
        $message .= __('--', 'woo-zero-search-miner-pro') . "\n";
        $message .= __('Woo Zero Search Miner Pro', 'woo-zero-search-miner-pro') . "\n";
        $message .= $site_url . "\n";

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        wp_mail($recipient, $subject, $message, $headers);
    }

    /**
     * إرسال التقرير اليومي
     */
    public function send_daily_report()
    {
        if (!true) {
            return;
        }
        $settings = $this->get_settings();
        if (empty($settings['daily_report_enabled'])) {
            return;
        }
        $recipient = sanitize_email($settings['daily_report_recipient']);
        if (!is_email($recipient)) {
            return;
        }

        // إحصائيات آخر 24 ساعة
        $stats_24 = WZSMPRO_Database::get_stats(1);
        $top_5 = WZSMPRO_Database::get_top_zero_searches(5, 1);

        $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $admin_url = admin_url('admin.php?page=wzsmpro-dashboard');

        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] 📊 التقرير اليومي - عمليات البحث بلا نتائج', 'woo-zero-search-miner-pro'),
            $site_name
        );

        $message  = sprintf(__('مرحباً،', 'woo-zero-search-miner-pro')) . "\n\n";
        $message .= __('تقرير اليوم عن عمليات البحث في متجرك:', 'woo-zero-search-miner-pro') . "\n\n";
        $message .= "═══════════════════════════════════\n";
        $message .= __('الإحصائيات (آخر 24 ساعة):', 'woo-zero-search-miner-pro') . "\n";
        $message .= "═══════════════════════════════════\n";
        $message .= sprintf('- %s: %d', __('إجمالي البحوث', 'woo-zero-search-miner-pro'), $stats_24['total_searches']) . "\n";
        $message .= sprintf('- %s: %d', __('بلا نتائج', 'woo-zero-search-miner-pro'), $stats_24['zero_results']) . "\n";
        $message .= sprintf('- %s: %s%%', __('معدل الفشل', 'woo-zero-search-miner-pro'), $stats_24['zero_rate']) . "\n";
        $message .= sprintf('- %s: %d', __('مصطلحات فريدة بلا نتائج', 'woo-zero-search-miner-pro'), $stats_24['unique_zero_terms']) . "\n";
        $message .= "\n";

        if (!empty($top_5)) {
            $message .= "═══════════════════════════════════\n";
            $message .= __('أعلى 5 مصطلحات بلا نتائج:', 'woo-zero-search-miner-pro') . "\n";
            $message .= "═══════════════════════════════════\n";
            foreach ($top_5 as $i => $row) {
                $message .= sprintf('%d. "%s" - %s %s',
                    $i + 1,
                    $row->search_term,
                    $row->total_searches,
                    __('مرة', 'woo-zero-search-miner-pro')
                ) . "\n";
            }
            $message .= "\n";
        }

        $message .= "═══════════════════════════════════\n";
        $message .= __('لعرض التفاصيل الكاملة:', 'woo-zero-search-miner-pro') . "\n";
        $message .= $admin_url . "\n\n";
        $message .= __('--', 'woo-zero-search-miner-pro') . "\n";
        $message .= __('Woo Zero Search Miner Pro | تقرير يومي', 'woo-zero-search-miner-pro') . "\n";

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        wp_mail($recipient, $subject, $message, $headers);
    }
}
