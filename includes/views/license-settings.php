<?php
/**
 * صفحة معلومات Pro - تعرض الإعدادات المُجمّعة + مزايا Premium
 *
 * @package Woo_Zero_Search_Miner_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_woocommerce')) {
    wp_die(__('لا صلاحية.', 'woo-zero-search-miner-pro'));
}

$settings = get_option(WZSMPRO_SETTINGS_KEY, array());
$buy_url = 'https://gumroad.com/l/woo-zero-search-miner-pro';
?>
<div class="wrap wzsm-wrap wzsmpro-wrap">

    <h1>⚡ <?php esc_html_e('Woo Zero Search Miner Pro', 'woo-zero-search-miner-pro'); ?></h1>
    <p class="wzsm-subtitle">
        <?php esc_html_e('الإصدار الكامل المستقل - كل مزايا Basic + Premium فعّالة افتراضياً.', 'woo-zero-search-miner-pro'); ?>
    </p>

    <!-- بطاقة الحالة -->
    <div class="wzsmpro-card" style="background:linear-gradient(135deg,#e7f9ed 0%,#d4edda 100%); border-left:4px solid #00a32a;">
        <div style="display:flex; align-items:center; gap:14px;">
            <span class="dashicons dashicons-yes-alt" style="font-size:32px; color:#00a32a;"></span>
            <div>
                <strong style="font-size:16px; display:block; margin-bottom:4px;">
                    ✅ <?php esc_html_e('كل المزايا فعّالة', 'woo-zero-search-miner-pro'); ?>
                </strong>
                <p style="margin:0; color:#555;">
                    <?php esc_html_e('استمتع بكل مزايا Basic + Premium بدون أي قيود - تنبيهات بريدية، تقرير يومي، Slack/Discord، Dashboard Widget، Dark Mode، شريط تنقل، White-label.', 'woo-zero-search-miner-pro'); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="wzsmpro-license-grid">

        <!-- العمود الأيسر: الإعدادات -->
        <div class="wzsmpro-license-section">

            <div class="wzsmpro-card">
                <h2 class="wzsmpro-card-title">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php esc_html_e('كل الإعدادات', 'woo-zero-search-miner-pro'); ?>
                </h2>
                <form method="post">
                    <?php wp_nonce_field(WZSMPRO_NONCE_ACTION, 'wzsmpro_nonce'); ?>
                    <input type="hidden" name="wzsmpro_action" value="save_settings">

                    <h3>📊 <?php esc_html_e('إعدادات التتبع (Basic)', 'woo-zero-search-miner-pro'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('تفعيل التتبع', 'woo-zero-search-miner-pro'); ?></th>
                            <td><label><input type="checkbox" name="wzsmpro_settings[enabled]" value="1" <?php checked(!isset($settings['enabled']) || !empty($settings['enabled'])); ?>> <?php esc_html_e('تفعيل', 'woo-zero-search-miner-pro'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('مدة الاحتفاظ (أيام)', 'woo-zero-search-miner-pro'); ?></th>
                            <td><input type="number" name="wzsmpro_settings[retention_days]" value="<?php echo esc_attr($settings['retention_days'] ?? 90); ?>" min="1" max="3650" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('الحد الأدنى لطول المصطلح', 'woo-zero-search-miner-pro'); ?></th>
                            <td><input type="number" name="wzsmpro_settings[min_term_length]" value="<?php echo esc_attr($settings['min_term_length'] ?? 2); ?>" min="1" max="50" class="small-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('تتبع المسجلين', 'woo-zero-search-miner-pro'); ?></th>
                            <td><label><input type="checkbox" name="wzsmpro_settings[track_logged_in]" value="1" <?php checked(!empty($settings['track_logged_in'])); ?>> <?php esc_html_e('نعم', 'woo-zero-search-miner-pro'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('تتبع الزوار', 'woo-zero-search-miner-pro'); ?></th>
                            <td><label><input type="checkbox" name="wzsmpro_settings[track_anonymous]" value="1" <?php checked(!empty($settings['track_anonymous'])); ?>> <?php esc_html_e('نعم', 'woo-zero-search-miner-pro'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('إخفاء IP (GDPR)', 'woo-zero-search-miner-pro'); ?></th>
                            <td><label><input type="checkbox" name="wzsmpro_settings[ip_anonymize]" value="1" <?php checked(!empty($settings['ip_anonymize'])); ?>> <?php esc_html_e('نعم', 'woo-zero-search-miner-pro'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('تنظيف تلقائي', 'woo-zero-search-miner-pro'); ?></th>
                            <td><label><input type="checkbox" name="wzsmpro_settings[auto_cleanup]" value="1" <?php checked(!empty($settings['auto_cleanup'])); ?>> <?php esc_html_e('نعم', 'woo-zero-search-miner-pro'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('مصطلحات مستثناة', 'woo-zero-search-miner-pro'); ?></th>
                            <td>
                                <textarea name="wzsmpro_settings[excluded_terms]" rows="4" cols="50" class="large-text code"><?php echo esc_textarea($settings['excluded_terms'] ?? ''); ?></textarea>
                                <p class="description"><?php esc_html_e('كل سطر = مصطلح مستثنى.', 'woo-zero-search-miner-pro'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h3>📧 <?php esc_html_e('التنبيهات البريدية', 'woo-zero-search-miner-pro'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('تنبيهات فورية', 'woo-zero-search-miner-pro'); ?></th>
                            <td><label><input type="checkbox" name="wzsmpro_settings[email_alerts_enabled]" value="1" <?php checked(!empty($settings['email_alerts_enabled'])); ?>> <?php esc_html_e('إرسال بريد فوري', 'woo-zero-search-miner-pro'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('بريد الاستقبال', 'woo-zero-search-miner-pro'); ?></th>
                            <td><input type="email" name="wzsmpro_settings[email_alerts_recipient]" value="<?php echo esc_attr($settings['email_alerts_recipient'] ?? get_option('admin_email')); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('تقرير يومي', 'woo-zero-search-miner-pro'); ?></th>
                            <td><label><input type="checkbox" name="wzsmpro_settings[daily_report_enabled]" value="1" <?php checked(!empty($settings['daily_report_enabled'])); ?>> <?php esc_html_e('تفعيل', 'woo-zero-search-miner-pro'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('ساعة الإرسال', 'woo-zero-search-miner-pro'); ?></th>
                            <td>
                                <select name="wzsmpro_settings[daily_report_hour]">
                                    <?php for ($h = 0; $h < 24; $h++) : ?>
                                        <option value="<?php echo $h; ?>" <?php selected($settings['daily_report_hour'] ?? 8, $h); ?>><?php echo esc_html(sprintf('%02d:00', $h)); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <h3>💬 <?php esc_html_e('Slack / Discord', 'woo-zero-search-miner-pro'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Slack', 'woo-zero-search-miner-pro'); ?></th>
                            <td>
                                <label><input type="checkbox" name="wzsmpro_settings[slack_enabled]" value="1" <?php checked(!empty($settings['slack_enabled'])); ?>> <?php esc_html_e('تفعيل', 'woo-zero-search-miner-pro'); ?></label><br>
                                <input type="url" name="wzsmpro_settings[slack_webhook_url]" value="<?php echo esc_attr($settings['slack_webhook_url'] ?? ''); ?>" class="regular-text" placeholder="https://hooks.slack.com/services/...">
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Discord', 'woo-zero-search-miner-pro'); ?></th>
                            <td>
                                <label><input type="checkbox" name="wzsmpro_settings[discord_enabled]" value="1" <?php checked(!empty($settings['discord_enabled'])); ?>> <?php esc_html_e('تفعيل', 'woo-zero-search-miner-pro'); ?></label><br>
                                <input type="url" name="wzsmpro_settings[discord_webhook_url]" value="<?php echo esc_attr($settings['discord_webhook_url'] ?? ''); ?>" class="regular-text" placeholder="https://discord.com/api/webhooks/...">
                            </td>
                        </tr>
                    </table>

                    <h3>🎨 <?php esc_html_e('الواجهة', 'woo-zero-search-miner-pro'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('واجهة Premium', 'woo-zero-search-miner-pro'); ?></th>
                            <td><label><input type="checkbox" name="wzsmpro_settings[premium_ui_enabled]" value="1" <?php checked(!isset($settings['premium_ui_enabled']) || !empty($settings['premium_ui_enabled'])); ?>> <?php esc_html_e('تفعيل شريط التنقل', 'woo-zero-search-miner-pro'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Dark Mode', 'woo-zero-search-miner-pro'); ?></th>
                            <td><label><input type="checkbox" name="wzsmpro_settings[dark_mode]" value="1" <?php checked(!empty($settings['dark_mode'])); ?>> <?php esc_html_e('تفعيل', 'woo-zero-search-miner-pro'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Dashboard Widget', 'woo-zero-search-miner-pro'); ?></th>
                            <td><label><input type="checkbox" name="wzsmpro_settings[dashboard_widget_enabled]" value="1" <?php checked(!isset($settings['dashboard_widget_enabled']) || !empty($settings['dashboard_widget_enabled'])); ?>> <?php esc_html_e('إظهار Widget', 'woo-zero-search-miner-pro'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('White-label', 'woo-zero-search-miner-pro'); ?></th>
                            <td>
                                <label><input type="checkbox" name="wzsmpro_settings[white_label]" value="1" <?php checked(!empty($settings['white_label'])); ?>> <?php esc_html_e('إزالة علامتنا التجارية', 'woo-zero-search-miner-pro'); ?></label>
                                <p class="description"><?php esc_html_e('إخفاء اسم az-soft4media.', 'woo-zero-search-miner-pro'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('حفظ الإعدادات', 'woo-zero-search-miner-pro'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>

        <!-- العمود الأيمن: مزايا Pro -->
        <div class="wzsmpro-features-section">
            <div class="wzsmpro-card">
                <h2 class="wzsmpro-card-title">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('مزايا Pro المُفعّلة', 'woo-zero-search-miner-pro'); ?>
                </h2>
                <ul class="wzsmpro-features-list">
                    <li class="active"><span class="dashicons dashicons-yes-alt"></span><div><strong><?php esc_html_e('تتبع كامل للبحوث بلا نتائج', 'woo-zero-search-miner-pro'); ?></strong><p><?php esc_html_e('Page + AJAX + Live Search.', 'woo-zero-search-miner-pro'); ?></p></div></li>
                    <li class="active"><span class="dashicons dashicons-yes-alt"></span><div><strong><?php esc_html_e('تنبيهات بريدية فورية', 'woo-zero-search-miner-pro'); ?></strong><p><?php esc_html_e('عند ظهور مصطلح جديد.', 'woo-zero-search-miner-pro'); ?></p></div></li>
                    <li class="active"><span class="dashicons dashicons-yes-alt"></span><div><strong><?php esc_html_e('تقرير يومي مجدول', 'woo-zero-search-miner-pro'); ?></strong><p><?php esc_html_e('ملخص يومي تلقاه في بريدك.', 'woo-zero-search-miner-pro'); ?></p></div></li>
                    <li class="active"><span class="dashicons dashicons-yes-alt"></span><div><strong><?php esc_html_e('Dashboard Widget', 'woo-zero-search-miner-pro'); ?></strong><p><?php esc_html_e('عنصر على الصفحة الرئيسية.', 'woo-zero-search-miner-pro'); ?></p></div></li>
                    <li class="active"><span class="dashicons dashicons-yes-alt"></span><div><strong><?php esc_html_e('Slack / Discord', 'woo-zero-search-miner-pro'); ?></strong><p><?php esc_html_e('إشعارات فورية عبر الـ webhooks.', 'woo-zero-search-miner-pro'); ?></p></div></li>
                    <li class="active"><span class="dashicons dashicons-yes-alt"></span><div><strong><?php esc_html_e('شريط تنقل + Breadcrumbs', 'woo-zero-search-miner-pro'); ?></strong><p><?php esc_html_e('تنقل سهل بين التبويبات.', 'woo-zero-search-miner-pro'); ?></p></div></li>
                    <li class="active"><span class="dashicons dashicons-yes-alt"></span><div><strong><?php esc_html_e('Dark Mode', 'woo-zero-search-miner-pro'); ?></strong><p><?php esc_html_e('الوضع الداكن للعين.', 'woo-zero-search-miner-pro'); ?></p></div></li>
                    <li class="active"><span class="dashicons dashicons-yes-alt"></span><div><strong><?php esc_html_e('White-label', 'woo-zero-search-miner-pro'); ?></strong><p><?php esc_html_e('أعد وضع العلامة باسمك.', 'woo-zero-search-miner-pro'); ?></p></div></li>
                    <li class="active"><span class="dashicons dashicons-yes-alt"></span><div><strong><?php esc_html_e('تصدير CSV', 'woo-zero-search-miner-pro'); ?></strong><p><?php esc_html_e('مع UTF-8 BOM للعربية.', 'woo-zero-search-miner-pro'); ?></p></div></li>
                    <li class="active"><span class="dashicons dashicons-yes-alt"></span><div><strong><?php esc_html_e('تنظيف تلقائي', 'woo-zero-search-miner-pro'); ?></strong><p><?php esc_html_e('cron يومي للسجلات القديمة.', 'woo-zero-search-miner-pro'); ?></p></div></li>
                </ul>

                <div class="wzsmpro-thank-you" style="text-align:center; margin-top:24px; padding:20px; background:#f8f9fa; border-radius:8px;">
                    <h3 style="margin:0 0 8px 0;">🎉 <?php esc_html_e('شكراً لاستخدامك Pro', 'woo-zero-search-miner-pro'); ?></h3>
                    <p style="margin:0 0 12px 0; color:#666;">
                        <?php echo esc_html(sprintf(__('الإصدار %s | صنعته: az-soft4media', 'woo-zero-search-miner-pro'), WZSMPRO_VERSION)); ?>
                    </p>
                    <p style="margin:0;">
                        <a href="https://troyawin.tech" target="_blank" class="button button-secondary"><?php esc_html_e('موقعنا', 'woo-zero-search-miner-pro'); ?></a>
                        <a href="https://github.com/ahlam2saleh2-source/woo-zero-search-miner-pro" target="_blank" class="button button-secondary">GitHub</a>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
