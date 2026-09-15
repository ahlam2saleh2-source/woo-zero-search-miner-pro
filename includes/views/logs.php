<?php
/**
 * صفحة السجلات - تعرض كل عمليات البحث المسجلة
 *
 * @package Woo_Zero_Search_Miner
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can(WZSMPRO_CAP)) {
    wp_die(__('ليس لديك صلاحية.', 'woo-zero-search-miner'));
}

$base_url = admin_url('admin.php?page=wzsmpro-logs');
?>
<div class="wrap wzsm-wrap">

    <h1>📋 <?php esc_html_e('سجلات عمليات البحث', 'woo-zero-search-miner'); ?></h1>
    <p class="wzsm-subtitle">
        <?php esc_html_e('عرض جميع عمليات البحث المسجلة، يمكن التبديل بين عرض الكل وعرض فقط بدون نتائج.', 'woo-zero-search-miner'); ?>
    </p>

    <div class="wzsm-tabs">
        <a href="<?php echo esc_url(add_query_arg('zero_only', 1, $base_url)); ?>"
           class="nav-tab <?php echo $zero_only ? 'nav-tab-active' : ''; ?>">
            ❌ <?php esc_html_e('بلا نتائج', 'woo-zero-search-miner'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('zero_only', 0, $base_url)); ?>"
           class="nav-tab <?php echo !$zero_only ? 'nav-tab-active' : ''; ?>">
            📊 <?php esc_html_e('كل السجلات', 'woo-zero-search-miner'); ?>
        </a>
    </div>

    <?php if (empty($logs)) : ?>
        <div class="wzsm-empty">
            <div class="wzsm-empty-icon">📭</div>
            <h3><?php esc_html_e('لا توجد سجلات بعد', 'woo-zero-search-miner'); ?></h3>
            <p><?php esc_html_e('عمليات البحث المسجلة ستظهر هنا تلقائياً.', 'woo-zero-search-miner'); ?></p>
        </div>
    <?php else : ?>
        <table class="widefat striped wzsm-table">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th><?php esc_html_e('مصطلح البحث', 'woo-zero-search-miner'); ?></th>
                    <th width="80"><?php esc_html_e('نتائج', 'woo-zero-search-miner'); ?></th>
                    <th width="120"><?php esc_html_e('نوع', 'woo-zero-search-miner'); ?></th>
                    <th width="100"><?php esc_html_e('المستخدم', 'woo-zero-search-miner'); ?></th>
                    <th width="180"><?php esc_html_e('التاريخ', 'woo-zero-search-miner'); ?></th>
                    <th width="80"><?php esc_html_e('مكرر', 'woo-zero-search-miner'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $i = $offset + 1; foreach ($logs as $log) : ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td class="wzsm-term"><strong><?php echo esc_html($log->search_term); ?></strong></td>
                        <td>
                            <?php if ($log->results_count == 0) : ?>
                                <span class="wzsm-zero-badge">0</span>
                            <?php else : ?>
                                <?php echo esc_html($log->results_count); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $log->is_ajax ? '<span class="wzsm-tag">AJAX</span>' : '<span class="wzsm-tag wzsm-tag-soft">Page</span>'; ?>
                        </td>
                        <td>
                            <?php
                            if ($log->user_id > 0) {
                                $user = get_user_by('id', $log->user_id);
                                echo $user ? esc_html('#' . $user->ID) : esc_html('#' . $log->user_id);
                            } else {
                                echo '<span class="wzsm-muted">' . esc_html__('زائر', 'woo-zero-search-miner') . '</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($log->searched_at))); ?></td>
                        <td><?php echo $log->occurrence_count > 1 ? '<strong>' . esc_html($log->occurrence_count) . 'x</strong>' : '1'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        if ($pages > 1) :
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo paginate_links(array(
                'base'      => add_query_arg('paged', '%#%'),
                'format'    => '',
                'current'   => $page,
                'total'     => $pages,
                'prev_text' => __('السابق', 'woo-zero-search-miner'),
                'next_text' => __('التالي', 'woo-zero-search-miner'),
            ));
            echo '</div></div>';
        endif;
        ?>

        <div class="wzsm-export-bar">
            <form method="post" id="wzsm-export-form">
                <input type="hidden" name="wzsm_action" value="export_csv">
                <input type="hidden" name="wzsm_zero_only" value="<?php echo esc_attr($zero_only); ?>">
                <?php wp_nonce_field(WZSMPRO_NONCE_ACTION, 'wzsmpro_nonce'); ?>
                <button type="submit" class="button button-primary">
                    ⬇ <?php esc_html_e('تصدير هذه القائمة (CSV)', 'woo-zero-search-miner'); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
