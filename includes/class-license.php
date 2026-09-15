<?php
/**
 * فئة Pro - صفحة معلومات Pro والإعدادات المُجمّعة
 * (تم تبسيطها: لا نظام مفاتيح ترخيص - كل المزايا فعّالة افتراضياً)
 *
 * @package Woo_Zero_Search_Miner_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSMPRO_License
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
        add_action('admin_menu', array($this, 'register_license_menu'), 99);
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * هل Pro مفعّل؟ (دائماً true - لا يوجد نظام مفاتيح)
     */
    public static function is_valid()
    {
        return true;
    }

    /**
     * تسجيل قائمة Pro
     */
    public function register_license_menu()
    {
        add_submenu_page(
            'wzsmpro-dashboard',
            __('معلومات Pro', 'woo-zero-search-miner-pro'),
            __('⚡ Pro', 'woo-zero-search-miner-pro'),
            'manage_woocommerce',
            'wzsmpro-license',
            array($this, 'render_license_page')
        );
    }

    /**
     * صفحة Pro
     */
    public function render_license_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('لا صلاحية.', 'woo-zero-search-miner-pro'));
        }
        $settings = get_option(WZSMPRO_SETTINGS_KEY, array());
        require WZSMPRO_PLUGIN_DIR . 'includes/views/license-settings.php';
    }

    /**
     * معالجة إجراءات POST
     */
    public function handle_actions()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        if (empty($_POST['wzsmpro_action'])) {
            return;
        }
        if (!isset($_POST['wzsmpro_nonce']) || !wp_verify_nonce($_POST['wzsmpro_nonce'], WZSMPRO_NONCE_ACTION)) {
            wp_die(__('رمز الأمان غير صالح.', 'woo-zero-search-miner-pro'));
        }

        $action = sanitize_key($_POST['wzsmpro_action']);

        switch ($action) {
            case 'export_csv':
                $days      = isset($_POST['wzsmpro_days'])      ? (int) $_POST['wzsmpro_days']      : 0;
                $zero_only = isset($_POST['wzsmpro_zero_only']) ? (int) $_POST['wzsmpro_zero_only'] : 0;
                WZSMPRO_Export::export_csv($days, $zero_only);
                break;

            case 'clear_all':
                WZSMPRO_Database::truncate_logs();
                add_settings_error('wzsmpro', 'wzsmpro_cleared', __('تم حذف جميع السجلات.', 'woo-zero-search-miner-pro'), 'updated');
                break;

            case 'save_settings':
                $settings = isset($_POST['wzsmpro_settings']) ? (array) $_POST['wzsmpro_settings'] : array();
                $sanitized = $this->sanitize_settings($settings);
                update_option(WZSMPRO_SETTINGS_KEY, $sanitized);

                // جدولة/إلغاء التقرير اليومي
                $this->manage_daily_report_schedule($sanitized);

                add_settings_error('wzsmpro', 'wzsmpro_success', __('تم حفظ الإعدادات بنجاح.', 'woo-zero-search-miner-pro'), 'success');
                break;
        }

        if (get_settings_errors('wzsmpro')) {
            foreach (get_settings_errors('wzsmpro') as $err) {
                add_action('admin_notices', function () use ($err) {
                    echo '<div class="notice notice-' . esc_attr($err['type']) . ' is-dismissible"><p>' . esc_html($err['message']) . '</p></div>';
                });
            }
        }
    }

    /**
     * تعقيم الإعدادات
     */
    private function sanitize_settings($input)
    {
        $sanitized = array();

        // إعدادات Basic
        $sanitized['enabled']             = !empty($input['enabled'])             ? 1 : 0;
        $sanitized['retention_days']      = max(1, min(3650, (int) ($input['retention_days']  ?? 90)));
        $sanitized['min_term_length']     = max(1, min(50,  (int) ($input['min_term_length'] ?? 2)));
        $sanitized['max_term_length']     = max(10, min(255, (int) ($input['max_term_length'] ?? 100)));
        $sanitized['excluded_terms']       = sanitize_textarea_field($input['excluded_terms'] ?? '');
        $sanitized['track_logged_in']     = !empty($input['track_logged_in']) ? 1 : 0;
        $sanitized['track_anonymous']     = !empty($input['track_anonymous']) ? 1 : 0;
        $sanitized['ip_anonymize']        = !empty($input['ip_anonymize'])    ? 1 : 0;
        $sanitized['auto_cleanup']        = !empty($input['auto_cleanup'])    ? 1 : 0;

        // إعدادات Premium
        $sanitized['email_alerts_enabled']        = !empty($input['email_alerts_enabled']) ? 1 : 0;
        $sanitized['email_alerts_recipient']      = sanitize_email($input['email_alerts_recipient'] ?? get_option('admin_email'));
        $sanitized['email_alerts_min_interval']  = max(60, (int) ($input['email_alerts_min_interval'] ?? 300));
        $sanitized['daily_report_enabled']        = !empty($input['daily_report_enabled']) ? 1 : 0;
        $sanitized['daily_report_recipient']      = sanitize_email($input['daily_report_recipient'] ?? get_option('admin_email'));
        $sanitized['daily_report_hour']           = max(0, min(23, (int) ($input['daily_report_hour'] ?? 8)));
        $sanitized['slack_webhook_url']           = esc_url_raw($input['slack_webhook_url'] ?? '');
        $sanitized['slack_enabled']               = !empty($input['slack_enabled']) ? 1 : 0;
        $sanitized['discord_webhook_url']         = esc_url_raw($input['discord_webhook_url'] ?? '');
        $sanitized['discord_enabled']             = !empty($input['discord_enabled']) ? 1 : 0;
        $sanitized['dashboard_widget_enabled']    = !empty($input['dashboard_widget_enabled']) ? 1 : 0;
        $sanitized['premium_ui_enabled']          = !empty($input['premium_ui_enabled']) ? 1 : 0;
        $sanitized['dark_mode']                   = !empty($input['dark_mode']) ? 1 : 0;
        $sanitized['white_label']                  = !empty($input['white_label']) ? 1 : 0;

        return $sanitized;
    }

    /**
     * جدولة التقرير اليومي
     */
    private function manage_daily_report_schedule($settings)
    {
        if (!empty($settings['daily_report_enabled'])) {
            if (!wp_next_scheduled('wzsmpro_daily_report')) {
                $tomorrow = strtotime('tomorrow ' . $settings['daily_report_hour'] . ':00:00');
                wp_schedule_event($tomorrow, 'daily', 'wzsmpro_daily_report');
            }
        } else {
            wp_clear_scheduled_hook('wzsmpro_daily_report');
        }
    }

    /**
     * رفع الأصول
     */
    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'wzsmpro') === false && strpos($hook, 'wzsm') === false) {
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
        wp_localize_script('wzsmpro-admin', 'wzsmpro', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce(WZSMPRO_NONCE_ACTION),
            'is_pro'   => self::is_valid(),
        ));
    }
}
