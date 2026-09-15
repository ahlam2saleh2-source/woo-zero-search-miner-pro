<?php
/**
 * صفحة لوحة المعلومات - تعرض الإحصائيات وأعلى عمليات البحث
 *
 * @package Woo_Zero_Search_Miner
 */

if (!defined('ABSPATH')) {
    exit;
}

// تأمين الوصول
if (!current_user_can(WZSMPRO_CAP)) {
    wp_die(__('ليس لديك صلاحية.', 'woo-zero-search-miner'));
}
?>
<div class="wrap wzsm-wrap">

    <div class="wzsm-page-header">
        <h1>🔍 <?php esc_html_e('Woo Zero Search Miner', 'woo-zero-search-miner'); ?></h1>
        <div class="wzsm-filter">
            <form method="get">
                <input type="hidden" name="page" value="wzsm-dashboard">
                <label for="wzsm-days">
                    <?php esc_html_e('الفترة:', 'woo-zero-search-miner'); ?>
                </label>
                <select name="days" id="wzsm-days" onchange="this.form.submit()">
                    <option value="7"   <?php selected($days, 7); ?>><?php esc_html_e('آخر 7 أيام', 'woo-zero-search-miner'); ?></option>
                    <option value="30"  <?php selected($days, 30); ?>><?php esc_html_e('آخر 30 يوم', 'woo-zero-search-miner'); ?></option>
                    <option value="90"  <?php selected($days, 90); ?>><?php esc_html_e('آخر 90 يوم', 'woo-zero-search-miner'); ?></option>
                    <option value="365" <?php selected($days, 365); ?>><?php esc_html_e('آخر سنة', 'woo-zero-search-miner'); ?></option>
                    <option value="0"   <?php selected($days, 0); ?>><?php esc_html_e('الكل', 'woo-zero-search-miner'); ?></option>
                </select>
            </form>
        </div>
    </div>

    <p class="wzsm-subtitle">
        <?php esc_html_e('اكتشف ما يبحث عنه عملاؤك دون أن يجدوه - بيانات حقيقية تساعدك على تحسين متجرك ومخزونك.', 'woo-zero-search-miner'); ?>
    </p>

    <!-- بطاقات الإحصائيات -->
    <div class="wzsm-stats-grid">
        <div class="wzsm-stat-card stat-card-primary">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-label"><?php esc_html_e('إجمالي عمليات البحث', 'woo-zero-search-miner'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['total_searches']); ?></div>
                <div class="stat-sub">
                    <?php
                    printf(
                        /* translators: %d: unique searches count */
                        esc_html__('%d مصطلح فريد', 'woo-zero-search-miner'),
                        $stats['unique_terms']
                    );
                    ?>
                </div>
            </div>
        </div>

        <div class="wzsm-stat-card stat-card-danger">
            <div class="stat-icon">❌</div>
            <div class="stat-content">
                <div class="stat-label"><?php esc_html_e('عمليات بلا نتائج', 'woo-zero-search-miner'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['zero_results']); ?></div>
                <div class="stat-sub">
                    <?php
                    printf(
                        /* translators: %d: unique zero terms */
                        esc_html__('%d مصطلح فريد بلا نتائج', 'woo-zero-search-miner'),
                        $stats['unique_zero_terms']
                    );
                    ?>
                </div>
            </div>
        </div>

        <div class="wzsm-stat-card stat-card-warning">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <div class="stat-label"><?php esc_html_e('معدل الفشل', 'woo-zero-search-miner'); ?></div>
                <div class="stat-value"><?php echo esc_html($stats['zero_rate']); ?>%</div>
                <div class="stat-sub">
                    <?php esc_html_e('نسبة عمليات البحث التي لم تجد نتائج', 'woo-zero-search-miner'); ?>
                </div>
            </div>
        </div>

        <div class="wzsm-stat-card stat-card-success">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <div class="stat-label"><?php esc_html_e('فرص تحسين', 'woo-zero-search-miner'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['unique_zero_terms']); ?></div>
                <div class="stat-sub">
                    <?php esc_html_e('مصطلحات يمكنك إضافة منتجات لها', 'woo-zero-search-miner'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- الرسم البياني للاتجاه -->
    <div class="wzsm-chart-section">
        <h2><?php esc_html_e('اتجاه عمليات البحث', 'woo-zero-search-miner'); ?></h2>
        <div class="wzsm-chart-container">
            <canvas id="wzsm-trend-chart" height="80"></canvas>
        </div>
    </div>

    <!-- أعلى عمليات البحث بلا نتائج -->
    <div class="wzsm-top-section">
        <div class="wzsm-section-header">
            <h2>🎯 <?php esc_html_e('أعلى عمليات البحث بلا نتائج', 'woo-zero-search-miner'); ?></h2>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wzsmpro-logs&zero_only=1')); ?>" class="button button-secondary">
                <?php esc_html_e('عرض كل السجلات', 'woo-zero-search-miner'); ?>
            </a>
        </div>

        <?php if (empty($top_searches)) : ?>
            <div class="wzsm-empty">
                <div class="wzsm-empty-icon">✨</div>
                <h3><?php esc_html_e('لا توجد بيانات بعد', 'woo-zero-search-miner'); ?></h3>
                <p><?php esc_html_e('بمجرد أن يبحث عملاؤك عن مصطلحات غير موجودة في متجرك، ستظهر هنا.', 'woo-zero-search-miner'); ?></p>
            </div>
        <?php else : ?>
            <table class="widefat striped wzsm-table">
                <thead>
                    <tr>
                        <th width="40">#</th>
                        <th><?php esc_html_e('مصطلح البحث', 'woo-zero-search-miner'); ?></th>
                        <th width="120"><?php esc_html_e('مرات البحث', 'woo-zero-search-miner'); ?></th>
                        <th width="120"><?php esc_html_e('فريدة', 'woo-zero-search-miner'); ?></th>
                        <th width="180"><?php esc_html_e('آخر مرة', 'woo-zero-search-miner'); ?></th>
                        <th width="120"><?php esc_html_e('إجراء', 'woo-zero-search-miner'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($top_searches as $row) : ?>
                        <tr>
                            <td class="wzsm-rank">#<?php echo $i++; ?></td>
                            <td class="wzsm-term">
                                <strong><?php echo esc_html($row->search_term); ?></strong>
                            </td>
                            <td><span class="wzsm-count"><?php echo number_format_i18n($row->total_searches); ?></span></td>
                            <td><?php echo number_format_i18n($row->unique_searches); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' H:i', strtotime($row->last_seen))); ?></td>
                            <td>
                                <button class="button button-small button-link-delete wzsm-delete-term"
                                        data-term="<?php echo esc_attr($row->search_term_normalized); ?>"
                                        title="<?php esc_attr_e('حذف', 'woo-zero-search-miner'); ?>">
                                    <?php esc_html_e('حذف', 'woo-zero-search-miner'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- ملاحظات وإجراءات سريعة -->
    <div class="wzsm-quick-actions">
        <h2>⚡ <?php esc_html_e('إجراءات سريعة', 'woo-zero-search-miner'); ?></h2>
        <div class="wzsm-actions-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=wzsmpro-logs')); ?>" class="wzsm-action-card">
                <span class="dashicons dashicons-list-view"></span>
                <span><?php esc_html_e('عرض السجلات', 'woo-zero-search-miner'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wzsmpro-settings')); ?>" class="wzsm-action-card">
                <span class="dashicons dashicons-admin-generic"></span>
                <span><?php esc_html_e('الإعدادات', 'woo-zero-search-miner'); ?></span>
            </a>
            <form method="post" class="wzsm-action-form" id="wzsm-export-form">
                <input type="hidden" name="wzsm_action" value="export_csv">
                <input type="hidden" name="wzsm_days" value="<?php echo esc_attr($days); ?>">
                <?php wp_nonce_field(WZSMPRO_NONCE_ACTION, 'wzsmpro_nonce'); ?>
                <button type="submit" class="wzsm-action-card">
                    <span class="dashicons dashicons-download"></span>
                    <span><?php esc_html_e('تصدير CSV', 'woo-zero-search-miner'); ?></span>
                </button>
            </form>
            <button type="button" class="wzsm-action-card wzsm-danger" id="wzsm-clear-all">
                <span class="dashicons dashicons-trash"></span>
                <span><?php esc_html_e('مسح الكل', 'woo-zero-search-miner'); ?></span>
            </button>
        </div>
    </div>

</div>

<script>
jQuery(function($) {
    var trend = <?php echo wp_json_encode($trend); ?>;
    if (trend && trend.length) {
        var labels = trend.map(function(r) { return r.day; });
        var totals = trend.map(function(r) { return parseInt(r.total) || 0; });
        var zeros  = trend.map(function(r) { return parseInt(r.zero_count) || 0; });
        var ctx = document.getElementById('wzsm-trend-chart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '<?php echo esc_js(__('إجمالي البحث', 'woo-zero-search-miner')); ?>',
                            data: totals,
                            borderColor: '#2271b1',
                            backgroundColor: 'rgba(34,113,177,0.1)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: '<?php echo esc_js(__('بلا نتائج', 'woo-zero-search-miner')); ?>',
                            data: zeros,
                            borderColor: '#d63638',
                            backgroundColor: 'rgba(214,54,56,0.15)',
                            tension: 0.3,
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }
    }

    // حذف مصطلح
    $('.wzsm-delete-term').on('click', function(e) {
        e.preventDefault();
        if (!confirm(wzsm.i18n.confirm_delete_term)) return;
        var $btn = $(this);
        var term = $btn.data('term');
        $.post(wzsm.ajax_url, {
            action: 'wzsmpro_delete_term',
            nonce: wzsm.nonce,
            term: term
        }, function(resp) {
            if (resp && resp.success) {
                $btn.closest('tr').fadeOut(300, function(){ $(this).remove(); });
            } else {
                alert(resp && resp.data ? resp.data : 'Error');
            }
        });
    });

    // مسح الكل
    $('#wzsm-clear-all').on('click', function(e) {
        e.preventDefault();
        if (!confirm(wzsm.i18n.confirm_delete)) return;
        $.post(wzsm.ajax_url, {
            action: 'wzsmpro_clear_all',
            nonce: wzsm.nonce
        }, function(resp) {
            if (resp && resp.success) {
                location.reload();
            } else {
                alert(resp && resp.data ? resp.data : 'Error');
            }
        });
    });
});
</script>
