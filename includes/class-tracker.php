<?php
/**
 * فئة المتتبع - تلتقط عمليات البحث في WooCommerce التي لا ترجع نتائج
 *
 * @package Woo_Zero_Search_Miner
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSMPRO_Tracker
{
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
        // الخطاف الرئيسي: عند عرض صفحة "لا توجد منتجات"
        add_action('woocommerce_no_products_found', array($this, 'capture_zero_search'), 10);
        add_action('woocommerce_shortcode_no_products', array($this, 'capture_shortcode_no_products'), 10, 1);

        // تتبع AJAX search (WooCommerce Blocks + AJAX)
        add_action('wc_ajax_woocommerce_ajax_search', array($this, 'maybe_capture_ajax_search'), 5);

        // تتبع نتائج البحث بشكل عام
        add_action('template_redirect', array($this, 'capture_search_query_on_template'), 100);

        // تخزين مؤقت لعدد نتائج آخر بحث
        add_filter('woocommerce_product_query', array($this, 'capture_query_count'), 100);
    }

    /**
     * جلب إعدادات الإضافة
     */
    private function get_settings()
    {
        $defaults = array(
            'enabled'             => 1,
            'retention_days'      => 90,
            'min_term_length'     => 2,
            'max_term_length'     => 100,
            'excluded_terms'      => '',
            'track_logged_in'     => 1,
            'track_anonymous'     => 1,
            'ip_anonymize'        => 1,
            'auto_cleanup'        => 1,
        );
        $saved = get_option('wzsmpro_basic_settings', array());
        return wp_parse_args($saved, $defaults);
    }

    /**
     * جلب معلومات المستخدم بصورة مجهولة
     */
    private function get_user_info()
    {
        $settings = $this->get_settings();
        $user_id = get_current_user_id();

        // تخفي IP إن كان مفعل
        $ip = '';
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
            if (!empty($settings['ip_anonymize'])) {
                $ip = $this->anonymize_ip($ip);
            }
        }

        $user_agent = !empty($_SERVER['HTTP_USER_AGENT'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))
            : '';
        $user_agent = substr($user_agent, 0, 255);

        $referer = !empty($_SERVER['HTTP_REFERER'])
            ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']))
            : '';
        $referer = substr($referer, 0, 255);

        // اللغة من خلال locale
        $language = substr(get_locale(), 0, 10);

        return array(
            'user_id'    => $user_id,
            'user_ip'    => $ip,
            'user_agent'  => $user_agent,
            'referer'     => $referer,
            'language'    => $language,
        );
    }

    /**
     * إخفاء جزء من IP لخصوصية أكبر
     */
    private function anonymize_ip($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4: إخفاء آخر أوكتيت
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: إخفاء آخر مقطعين
            $parts = explode(':', $ip);
            $count = count($parts);
            if ($count > 2) {
                $parts[$count - 1] = '0';
                $parts[$count - 2] = '0';
                return implode(':', $parts);
            }
        }
        return $ip;
    }

    /**
     * التحقق إن كان البحث صالحاً للتسجيل
     */
    private function should_log($term, $is_ajax = false)
    {
        $settings = $this->get_settings();

        if (empty($settings['enabled'])) {
            return false;
        }

        $term = trim($term);
        $len = function_exists('mb_strlen') ? mb_strlen($term) : strlen($term);

        // طول المصطلح ضمن النطاق المسموح
        if ($len < (int) $settings['min_term_length']) {
            return false;
        }
        if ($len > (int) $settings['max_term_length']) {
            return false;
        }

        // قائمة المصطلحات المستثناة
        if (!empty($settings['excluded_terms'])) {
            $excluded = array_filter(array_map('trim', explode("\n", $settings['excluded_terms'])));
            $term_lower = function_exists('mb_strtolower') ? mb_strtolower($term, 'UTF-8') : strtolower($term);
            foreach ($excluded as $ex) {
                if ($ex && stripos($term_lower, $ex) !== false) {
                    return false;
                }
            }
        }

        // تسجيل دخول / غير مسجل
        $user_id = get_current_user_id();
        if ($user_id > 0 && empty($settings['track_logged_in'])) {
            return false;
        }
        if ($user_id === 0 && empty($settings['track_anonymous'])) {
            return false;
        }

        return true;
    }

    /**
     * تسجيل عملية البحث بدون نتائج
     */
    private function log_zero_search($term, $is_ajax = 0)
    {
        if (!$this->should_log($term, $is_ajax)) {
            return;
        }

        $user_info = $this->get_user_info();

        $log_id = WZSMPRO_Database::insert_log(array(
            'search_term'   => sanitize_text_field($term),
            'results_count' => 0,
            'is_ajax'        => $is_ajax,
            'user_id'        => $user_info['user_id'],
            'user_ip'        => $user_info['user_ip'],
            'user_agent'      => $user_info['user_agent'],
            'referer'         => $user_info['referer'],
            'language'        => $user_info['language'],
        ));

        // 🔌 Hook للإصدار Pro (تنبيهات بريد، Slack، Dashboard Widget)
        if ($log_id) {
            do_action('wzsm_after_log_zero_search', array(
                'log_id'       => $log_id,
                'search_term'  => sanitize_text_field($term),
                'is_ajax'       => $is_ajax,
                'user_id'       => $user_info['user_id'],
                'user_ip'       => $user_info['user_ip'],
                'language'      => $user_info['language'],
                'occurred_at'  => current_time('mysql'),
            ));
        }
    }

    /**
     * التقاط خطاف woocommerce_no_products_found
     */
    public function capture_zero_search()
    {
        if (!is_search() || !function_exists('WC')) {
            return;
        }
        $term = get_search_query();
        if (!$term) {
            return;
        }
        $this->log_zero_search($term, 0);
    }

    /**
     * التقاط Shortcode بدون نتائج
     */
    public function capture_shortcode_no_products($query)
    {
        if (!is_a($query, 'WC_Query') && !is_object($query)) {
            return;
        }
        $term = get_search_query();
        if (!$term) {
            return;
        }
        $this->log_zero_search($term, 0);
    }

    /**
     * التقاط AJAX Search
     */
    public function maybe_capture_ajax_search()
    {
        $term = isset($_REQUEST['term']) ? sanitize_text_field(wp_unslash($_REQUEST['term'])) : '';
        if (!$term) {
            $term = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';
        }
        if (!$term || !$this->should_log($term, true)) {
            return;
        }

        // استعلام منتجات عادي
        $args = array(
            's'                => $term,
            'post_type'         => 'product',
            'posts_per_page'    => 1,
            'post_status'       => 'publish',
            'fields'            => 'ids',
            'no_found_rows'     => true,
            'tax_query'         => array(
                array(
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => array('exclude-from-search'),
                    'operator' => 'NOT IN',
                ),
            ),
        );
        $args = apply_filters('wzsm_ajax_search_args', $args, $term);

        $results = get_posts($args);

        if (empty($results)) {
            $this->log_zero_search($term, 1);
        }
    }

    /**
     * التقاط على template_redirect - للقوالب المخصصة
     */
    public function capture_search_query_on_template()
    {
        if (!is_search() || !function_exists('WC')) {
            return;
        }
        if (is_admin()) {
            return;
        }

        // تأكد أن البحث على المنتجات فقط
        if (!is_post_type_archive('product') && !is_shop()) {
            // فحص إن كان البحث يتضمن منتجات
            $query = get_query_var('post_type');
            if ($query !== 'product' && !empty($query)) {
                return;
            }
        }

        $term = get_search_query();
        if (!$term) {
            return;
        }

        // فحص عدد نتائج WP_Query الحالي
        global $wp_query;
        if ($wp_query && $wp_query->have_posts() === false) {
            // لا نتائج
            $this->log_zero_search($term, 0);
        }
    }

    /**
     * عند تنفيذ استعلام منتج WooCommerce
     * يخزن عدد النتائج لمقارنة لاحقة
     */
    public function capture_query_count($query)
    {
        // نوفر فرصة لاستخدام هذا لاحقاً لتتبع عمليات البحث التي ترجع نتائج أيضاً
        // حالياً نكتفي بـ no_products_found
        return $query;
    }
}
