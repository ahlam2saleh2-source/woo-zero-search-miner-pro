<?php
/**
 * الواجهة Premium - يحسّن تجربة المستخدم بأزرار تنقل (Tabs) و Breadcrumbs
 * ويعالج ملاحظات المستخدم حول عدم وجود أزرار تنقل بين التبويبات
 *
 * تم إصلاح خطأ أسماء hooks + استخدام admin_notices (أكثر موثوقية)
 *
 * @package Woo_Zero_Search_Miner_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSMPRO_Premium_UI
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
        // ✅ إصلاح: استخدام admin_notices بدلاً من admin_head (أكثر موثوقية)
        // يعمل على كل صفحات admin - نتحقق داخله إن كانت صفحة wzsm
        add_action('admin_notices', array($this, 'inject_navigation'), 1);
        add_action('admin_notices', array($this, 'inject_breadcrumbs'), 2);

        // ✅ إضافة زر Dark Mode Toggle في كل صفحات wzsm
        add_action('admin_footer', array($this, 'inject_theme_toggle_button'));

        // Dark mode body class
        add_filter('admin_body_class', array($this, 'maybe_dark_mode'));

        // AJAX للتبديل بين Dark/Light
        add_action('wp_ajax_wzsmpro_toggle_theme', array($this, 'ajax_toggle_theme'));
    }

    /**
     * حقن زر Dark Mode Toggle (يظهر في أسفل الشاشة)
     */
    public function inject_theme_toggle_button()
    {
        if (!$this->is_wzsm_page()) {
            return;
        }
        $settings = get_option(WZSMPRO_SETTINGS_KEY, array());
        $is_dark = !empty($settings['dark_mode']);
        ?>
        <div class="wzsmpro-theme-toggle">
            <button type="button" id="wzsmpro-toggle-theme" class="button button-secondary" title="<?php echo $is_dark ? esc_attr__('التبديل للوضع الفاتح', 'woo-zero-search-miner-pro') : esc_attr__('التبديل للوضع الداكن', 'woo-zero-search-miner-pro'); ?>">
                <span class="dashicons <?php echo $is_dark ? 'dashicons-lightbulb' : 'dashicons-dark-mode-2'; ?>"></span>
                <span class="toggle-text"><?php echo $is_dark ? esc_html__('فاتح', 'woo-zero-search-miner-pro') : esc_html__('داكن', 'woo-zero-search-miner-pro'); ?></span>
            </button>
        </div>
        <?php
    }

    /**
     * التحقق إن كانت الصفحة الحالية تابعة لـ Zero Search
     */
    private function is_wzsm_page()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen) {
            return false;
        }
        // الكشف: رقم ID الصفحة يحوي 'wzsmpro' أو 'toplevel_page_wzsmpro'
        if (strpos($screen->id, 'wzsmpro') !== false) {
            return true;
        }
        return false;
    }

    /**
     * حقن شريط التنقل Tabs (يعمل على كل صفحات wzsm-*)
     */
    public function inject_navigation()
    {
        if (!$this->is_wzsm_page()) {
            return;
        }
        // ✅ إصلاح: لا حاجة لـ license check - كل مزايا Pro فعّالة افتراضياً
        $settings = get_option(WZSMPRO_SETTINGS_KEY, array());
        if (empty($settings['premium_ui_enabled']) && isset($settings['premium_ui_enabled'])) {
            return;
        }

        $current = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        $tabs = array(
            'wzsmpro-dashboard' => array(
                'label'  => __('لوحة المعلومات', 'woo-zero-search-miner-pro'),
                'icon'    => 'dashicons-chart-area',
            ),
            'wzsmpro-logs' => array(
                'label'  => __('السجلات', 'woo-zero-search-miner-pro'),
                'icon'    => 'dashicons-list-view',
            ),
            'wzsmpro-settings' => array(
                'label'  => __('الإعدادات', 'woo-zero-search-miner-pro'),
                'icon'    => 'dashicons-admin-generic',
            ),
            'wzsmpro-license' => array(
                'label'  => __('⚡ Pro', 'woo-zero-search-miner-pro'),
                'icon'    => 'dashicons-star-filled',
            ),
        );

        echo '<div class="wzsmpro-nav-tabs"><div class="wzsmpro-nav-tabs-inner">';
        foreach ($tabs as $page => $data) {
            $url = admin_url('admin.php?page=' . $page);
            $active = ($current === $page) ? ' active' : '';
            printf(
                '<a href="%s" class="wzsmpro-nav-tab%s"><span class="dashicons %s"></span> <span class="tab-label">%s</span></a>',
                esc_url($url),
                esc_attr($active),
                esc_attr($data['icon']),
                esc_html($data['label'])
            );
        }
        echo '</div></div>';
    }

    /**
     * حقن Breadcrumbs
     */
    public function inject_breadcrumbs()
    {
        if (!$this->is_wzsm_page()) {
            return;
        }
        $current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'wzsmpro-dashboard';
        $page_titles = array(
            'wzsmpro-dashboard' => __('لوحة المعلومات', 'woo-zero-search-miner-pro'),
            'wzsmpro-logs'      => __('السجلات', 'woo-zero-search-miner-pro'),
            'wzsmpro-settings' => __('الإعدادات', 'woo-zero-search-miner-pro'),
            'wzsmpro-info'      => __('Pro', 'woo-zero-search-miner-pro'),
        );
        $title = isset($page_titles[$current_page]) ? $page_titles[$current_page] : '';

        echo '<div class="wzsmpro-breadcrumbs">';
        echo '<a href="' . esc_url(admin_url('admin.php?page=wzsmpro-dashboard')) . '">Zero Search Pro</a>';
        echo ' <span class="sep">›</span> ';
        echo '<span class="current">' . esc_html($title) . '</span>';
        echo '</div>';
    }

    /**
     * إضافة Dark Mode body class
     */
    public function maybe_dark_mode($classes)
    {
        $settings = get_option(WZSMPRO_OPTION_KEY, array());
        if (!empty($settings['dark_mode'])) {
            $classes .= ' wzsmpro-dark-mode';
        }
        return $classes;
    }

    /**
     * AJAX: تبديل Dark/Light
     */
    public function ajax_toggle_theme()
    {
        check_ajax_referer(WZSMPRO_NONCE_ACTION, 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('لا صلاحية.', 'woo-zero-search-miner-pro'), 403);
        }
        $settings = get_option(WZSMPRO_OPTION_KEY, array());
        $settings['dark_mode'] = empty($settings['dark_mode']) ? 1 : 0;
        update_option(WZSMPRO_OPTION_KEY, $settings);
        wp_send_json_success(array(
            'dark_mode' => $settings['dark_mode'] ? 1 : 0,
        ));
    }
}
