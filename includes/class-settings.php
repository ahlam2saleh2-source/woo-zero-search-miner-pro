<?php
/**
 * فئة الإعدادات - صفحة إعدادات الإضافة
 *
 * @package Woo_Zero_Search_Miner
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSMPRO_Settings
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
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('wp_ajax_wzsm_save_settings', array($this, 'ajax_save_settings'));
    }

    /**
     * الخيارات الافتراضية
     */
    public static function get_defaults()
    {
        return array(
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
    }

    /**
     * تسجيل الإعدادات
     */
    public function register_settings()
    {
        register_setting('wzsmpro_settings_group', 'wzsmpro_basic_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
            'default'            => self::get_defaults(),
        ));

        add_settings_section(
            'wzsm_general_section',
            __('الإعدادات العامة', 'woo-zero-search-miner'),
            function () {
                echo '<p>' . esc_html__('تحكم في سلوك الإضافة من هنا. احفظ التغييرات بعد التعديل.', 'woo-zero-search-miner') . '</p>';
            },
            'wzsmpro_basic_settings_page'
        );

        $fields = array(
            'enabled'         => __('تفعيل التتبع', 'woo-zero-search-miner'),
            'track_logged_in' => __('تتبع المستخدمين المسجلين', 'woo-zero-search-miner'),
            'track_anonymous' => __('تتبع الزوار', 'woo-zero-search-miner'),
            'ip_anonymize'    => __('إخفاء آخر IP للمستخدم (للخصوصية)', 'woo-zero-search-miner'),
            'auto_cleanup'    => __('تنظيف السجلات القديمة تلقائياً', 'woo-zero-search-miner'),
        );

        foreach ($fields as $key => $label) {
            add_settings_field(
                'wzsm_' . $key,
                $label,
                array($this, 'render_checkbox_field'),
                'wzsmpro_basic_settings_page',
                'wzsm_general_section',
                array('key' => $key)
            );
        }

        add_settings_field(
            'wzsm_retention_days',
            __('مدة الاحتفاظ (أيام)', 'woo-zero-search-miner'),
            array($this, 'render_number_field'),
            'wzsmpro_basic_settings_page',
            'wzsm_general_section',
            array('key' => 'retention_days', 'min' => 1, 'max' => 3650)
        );

        add_settings_field(
            'wzsm_min_term_length',
            __('الحد الأدنى لطول المصطلح', 'woo-zero-search-miner'),
            array($this, 'render_number_field'),
            'wzsmpro_basic_settings_page',
            'wzsm_general_section',
            array('key' => 'min_term_length', 'min' => 1, 'max' => 50)
        );

        add_settings_field(
            'wzsm_max_term_length',
            __('الحد الأقصى لطول المصطلح', 'woo-zero-search-miner'),
            array($this, 'render_number_field'),
            'wzsmpro_basic_settings_page',
            'wzsm_general_section',
            array('key' => 'max_term_length', 'min' => 10, 'max' => 255)
        );

        add_settings_field(
            'wzsm_excluded_terms',
            __('مصطلحات مستثناة (كل مصطلح في سطر)', 'woo-zero-search-miner'),
            array($this, 'render_textarea_field'),
            'wzsmpro_basic_settings_page',
            'wzsm_general_section',
            array('key' => 'excluded_terms')
        );
    }

    /**
     * معالجة إجراءات POST (تصدير/تنظيف)
     */
    public function handle_actions()
    {
        if (!current_user_can(WZSMPRO_CAP)) {
            return;
        }
        if (empty($_POST['wzsmpro_action'])) {
            return;
        }
        if (!isset($_POST['wzsmpro_nonce']) || !wp_verify_nonce($_POST['wzsmpro_nonce'], WZSMPRO_NONCE_ACTION)) {
            wp_die(__('رمز الأمان غير صالح.', 'woo-zero-search-miner'));
        }

        $action = sanitize_key($_POST['wzsmpro_action']);

        switch ($action) {
            case 'export_csv':
                $days      = isset($_POST['wzsm_days'])      ? (int) $_POST['wzsm_days']      : 0;
                $zero_only = isset($_POST['wzsm_zero_only']) ? (int) $_POST['wzsm_zero_only'] : 0;
                WZSMPRO_Export::export_csv($days, $zero_only);
                break;

            case 'clear_all':
                WZSMPRO_Database::truncate_logs();
                add_settings_error('wzsm', 'wzsm_cleared', __('تم حذف جميع السجلات.', 'woo-zero-search-miner'), 'updated');
                break;
        }
    }

    /**
     * تعقيم المدخلات
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();
        $sanitized['enabled']         = !empty($input['enabled'])         ? 1 : 0;
        $sanitized['retention_days']  = max(1, min(3650, (int) ($input['retention_days']  ?? 90)));
        $sanitized['min_term_length'] = max(1, min(50,  (int) ($input['min_term_length'] ?? 2)));
        $sanitized['max_term_length'] = max(10, min(255, (int) ($input['max_term_length'] ?? 100)));
        $sanitized['excluded_terms']   = sanitize_textarea_field($input['excluded_terms'] ?? '');
        $sanitized['track_logged_in'] = !empty($input['track_logged_in']) ? 1 : 0;
        $sanitized['track_anonymous'] = !empty($input['track_anonymous']) ? 1 : 0;
        $sanitized['ip_anonymize']    = !empty($input['ip_anonymize'])    ? 1 : 0;
        $sanitized['auto_cleanup']    = !empty($input['auto_cleanup'])    ? 1 : 0;
        return $sanitized;
    }

    /**
     * رندرة حقل checkbox
     */
    public function render_checkbox_field($args)
    {
        $options = get_option('wzsmpro_basic_settings', self::get_defaults());
        $value = !empty($options[$args['key']]) ? 1 : 0;
        echo '<label><input type="checkbox" name="wzsmpro_basic_settings[' . esc_attr($args['key']) . ']" value="1" ' . checked($value, 1, false) . '/> ';
        echo esc_html__('نعم، فعّل', 'woo-zero-search-miner');
        echo '</label>';
    }

    /**
     * رندرة حقل رقم
     */
    public function render_number_field($args)
    {
        $options = get_option('wzsmpro_basic_settings', self::get_defaults());
        $value = $options[$args['key']] ?? '';
        $min = isset($args['min']) ? 'min="' . esc_attr($args['min']) . '"' : '';
        $max = isset($args['max']) ? 'max="' . esc_attr($args['max']) . '"' : '';
        echo '<input type="number" name="wzsmpro_basic_settings[' . esc_attr($args['key']) . ']" value="' . esc_attr($value) . '" ' . $min . ' ' . $max . ' class="small-text" />';
    }

    /**
     * رندرة حقل textarea
     */
    public function render_textarea_field($args)
    {
        $options = get_option('wzsmpro_basic_settings', self::get_defaults());
        $value = $options[$args['key']] ?? '';
        echo '<textarea name="wzsmpro_basic_settings[' . esc_attr($args['key']) . ']" rows="6" cols="60" class="large-text code">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('أدخل كلمة أو جملة في كل سطر. أي مصطلح يحتوي على هذه الكلمات لن يُسجل.', 'woo-zero-search-miner') . '</p>';
    }

    /**
     * رندرة صفحة الإعدادات
     */
    public static function render_settings_page()
    {
        if (!current_user_can(WZSMPRO_CAP)) {
            wp_die(__('ليس لديك صلاحية.', 'woo-zero-search-miner'));
        }
        ?>
        <div class="wrap wzsm-wrap">
            <h1>⚙️ <?php esc_html_e('إعدادات Woo Zero Search Miner', 'woo-zero-search-miner'); ?></h1>
            <p class="wzsm-subtitle"><?php esc_html_e('تحكم في كيفية تتبع عمليات البحث وحفظها.', 'woo-zero-search-miner'); ?></p>
            <form method="post" action="options.php">
                <?php
                settings_fields('wzsmpro_settings_group');
                do_settings_sections('wzsmpro_basic_settings_page');
                submit_button(__('حفظ الإعدادات', 'woo-zero-search-miner'));
                ?>
            </form>
        </div>
        <?php
    }
}
