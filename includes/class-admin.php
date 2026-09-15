<?php
/**
 * فئة لوحة التحكم - عرض الإحصائيات والسجلات
 *
 * @package Woo_Zero_Search_Miner
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSMPRO_Admin
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
        add_action('admin_menu', array($this, 'register_admin_menu'), 99);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_notices', array($this, 'maybe_show_setup_notice'));
    }

    /**
     * تسجيل القوائم في لوحة الإدارة
     */
    public function register_admin_menu()
    {
        // القائمة الرئيسية
        add_menu_page(
            __('Woo Zero Search', 'woo-zero-search-miner'),
            __('Zero Search', 'woo-zero-search-miner'),
            WZSMPRO_CAP,
            'wzsmpro-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-area',
            56
        );

        // صفحة فرعية - الإحصائيات
        add_submenu_page(
            'wzsmpro-dashboard',
            __('لوحة المعلومات', 'woo-zero-search-miner'),
            __('لوحة المعلومات', 'woo-zero-search-miner'),
            WZSMPRO_CAP,
            'wzsmpro-dashboard',
            array($this, 'render_dashboard_page')
        );

        // صفحة فرعية - السجلات
        add_submenu_page(
            'wzsmpro-dashboard',
            __('السجلات', 'woo-zero-search-miner'),
            __('السجلات', 'woo-zero-search-miner'),
            WZSMPRO_CAP,
            'wzsmpro-logs',
            array($this, 'render_logs_page')
        );

        // صفحة فرعية - الإعدادات
        add_submenu_page(
            'wzsmpro-dashboard',
            __('الإعدادات', 'woo-zero-search-miner'),
            __('الإعدادات', 'woo-zero-search-miner'),
            WZSMPRO_CAP,
            'wzsmpro-settings',
            array('WZSMPRO_Settings', 'render_settings_page')
        );
    }

    /**
     * تحميل الأصول (CSS/JS) في لوحة الإدارة فقط
     */
    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, 'wzsm') === false && strpos($hook, 'wzsmpro') === false) {
            return;
        }
        wp_enqueue_style(
            'wzsmpro-admin',
            WZSMPRO_PLUGIN_URL . 'assets/css/admin-pro.css',
            array(),
            WZSMPRO_VERSION
        );
        wp_enqueue_script(
            'wzsmpro-admin',
            WZSMPRO_PLUGIN_URL . 'assets/js/admin-pro.js',
            array('jquery'),
            WZSMPRO_VERSION,
            true
        );

        // مكتبة Chart.js من CDN للرسوم البيانية (نسخة محلية أنجح للاستخدام بدون انترنت)
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            array(),
            '4.4.0',
            true
        );

        // متغيرات JS
        wp_localize_script('wzsm-admin', 'wzsm', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce(WZSMPRO_NONCE_ACTION),
            'i18n'     => array(
                'confirm_delete' => __('هل أنت متأكد من حذف جميع السجلات؟', 'woo-zero-search-miner'),
                'confirm_delete_term' => __('سيتم حذف جميع سجلات هذا المصطلح. متابعة؟', 'woo-zero-search-miner'),
                'confirm_export'  => __('سيتم تصدير السجلات بصيغة CSV. متابعة؟', 'woo-zero-search-miner'),
                'loading'        => __('جارٍ التحميل...', 'woo-zero-search-miner'),
                'no_data'         => __('لا توجد بيانات', 'woo-zero-search-miner'),
            ),
        ));
    }

    /**
     * إشعار التثبيت الأول
     */
    public function maybe_show_setup_notice()
    {
        if (!current_user_can(WZSMPRO_CAP)) {
            return;
        }
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wzsm') === 0) {
            return;
        }
        $installed = get_option('wzsmpro_installed_at');
        if (!$installed) {
            update_option('wzsmpro_installed_at', current_time('mysql'));
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>✅ ';
            echo esc_html__('تم تفعيل Woo Zero Search Miner بنجاح. ابدأ بمتابعة عمليات البحث في ', 'woo-zero-search-miner');
            echo '<a href="' . esc_url(admin_url('admin.php?page=wzsmpro-dashboard')) . '">';
            echo esc_html__('لوحة المعلومات', 'woo-zero-search-miner');
            echo '</a>';
            echo '</p></div>';
        }
    }

    /**
     * صفحة لوحة المعلومات
     */
    public function render_dashboard_page()
    {
        if (!current_user_can(WZSMPRO_CAP)) {
            wp_die(__('ليس لديك صلاحية للوصول لهذه الصفحة.', 'woo-zero-search-miner'));
        }

        // فلتر الفترة الزمنية
        $days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
        $days = in_array($days, array(7, 30, 90, 365, 0)) ? $days : 30;

        $stats = WZSMPRO_Database::get_stats($days);
        $top_searches = WZSMPRO_Database::get_top_zero_searches(20, $days);
        $trend = WZSMPRO_Database::get_daily_trend($days > 0 ? $days : 90);

        require WZSMPRO_PLUGIN_DIR . 'includes/views/dashboard.php';
    }

    /**
     * صفحة السجلات
     */
    public function render_logs_page()
    {
        if (!current_user_can(WZSMPRO_CAP)) {
            wp_die(__('ليس لديك صلاحية للوصول لهذه الصفحة.', 'woo-zero-search-miner'));
        }

        $zero_only = isset($_GET['zero_only']) ? (int) $_GET['zero_only'] : 1;
        $page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        global $wpdb;
        $table = WZSMPRO_Database::table_name();
        $where = $zero_only ? "WHERE results_count = 0" : "WHERE 1=1";

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
        $logs = $wpdb->get_results(
            "SELECT * FROM {$table} {$where} ORDER BY searched_at DESC LIMIT {$offset}, {$per_page}"
        );

        $pages = ceil($total / $per_page);

        require WZSMPRO_PLUGIN_DIR . 'includes/views/logs.php';
    }
}
