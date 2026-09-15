<?php
/**
 * Dashboard Widget - يضيف عنصر "Zero Search" على الصفحة الرئيسية لـ WordPress admin
 *
 * @package Woo_Zero_Search_Miner_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSMPRO_Dashboard_Widget
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
        add_action('wp_dashboard_setup', array($this, 'register_widget'));
    }

    /**
     * تسجيل الـ Widget
     */
    public function register_widget()
    {
        $settings = get_option(WZSMPRO_OPTION_KEY, array());
        if (empty($settings['dashboard_widget_enabled'])) {
            return;
        }
        if (!true) {
            return;
        }
        wp_add_dashboard_widget(
            'wzsmpro_dashboard_widget',
            __('🔍 Woo Zero Search - آخر المصطلحات بلا نتائج', 'woo-zero-search-miner-pro'),
            array($this, 'render_widget')
        );
    }

    /**
     * رندرة الـ Widget
     */
    public function render_widget()
    {
        $stats = WZSMPRO_Database::get_stats(1);
        $recent = WZSMPRO_Database::get_recent_searches(5, 1);
        $admin_url = admin_url('admin.php?page=wzsmpro-dashboard');

        echo '<div class="wzsmpro-widget">';

        // بطاقات صغيرة
        echo '<div class="wzsmpro-widget-stats">';
        echo '<div class="wzsmpro-widget-stat"><span class="num">' . esc_html(number_format_i18n($stats['zero_results'])) . '</span><span class="lbl">' . esc_html__('بلا نتائج (24س)', 'woo-zero-search-miner-pro') . '</span></div>';
        echo '<div class="wzsmpro-widget-stat"><span class="num">' . esc_html($stats['zero_rate']) . '%</span><span class="lbl">' . esc_html__('معدل الفشل', 'woo-zero-search-miner-pro') . '</span></div>';
        echo '<div class="wzsmpro-widget-stat"><span class="num">' . esc_html(number_format_i18n($stats['unique_zero_terms'])) . '</span><span class="lbl">' . esc_html__('مصطلحات فريدة', 'woo-zero-search-miner-pro') . '</span></div>';
        echo '</div>';

        if (empty($recent)) {
            echo '<p style="text-align:center; color:#666; padding:20px 0;">';
            echo esc_html__('لا توجد بيانات بعد. بمجرد أن يبحث عملاؤك عن مصطلح غير موجود، ستظهر هنا.', 'woo-zero-search-miner-pro');
            echo '</p>';
        } else {
            echo '<table class="wzsmpro-widget-table"><tbody>';
            foreach ($recent as $row) {
                echo '<tr>';
                echo '<td class="wzsmpro-term">' . esc_html($row->search_term) . '</td>';
                echo '<td class="wzsmpro-time">' . esc_html(human_time_diff(strtotime($row->searched_at), current_time('timestamp'))) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '<p style="text-align:center; margin-top:12px;"><a href="' . esc_url($admin_url) . '" class="button button-primary button-small">' . esc_html__('عرض كل السجلات', 'woo-zero-search-miner-pro') . '</a></p>';
        echo '</div>';

        // أنماط مدمجة
        echo '<style>
        .wzsmpro-widget-stats { display:flex; gap:10px; margin-bottom:12px; }
        .wzsmpro-widget-stat { flex:1; background:#f8f9fa; padding:10px; border-radius:6px; text-align:center; }
        .wzsmpro-widget-stat .num { display:block; font-size:20px; font-weight:700; color:#2271b1; }
        .wzsmpro-widget-stat .lbl { display:block; font-size:11px; color:#666; }
        .wzsmpro-widget-table { width:100%; border-collapse:collapse; }
        .wzsmpro-widget-table td { padding:6px 4px; border-bottom:1px solid #eee; font-size:13px; }
        .wzsmpro-widget-table .wzsmpro-term { font-weight:600; }
        .wzsmpro-widget-table .wzsmpro-time { text-align:left; color:#888; font-size:11px; }
        </style>';
    }
}
