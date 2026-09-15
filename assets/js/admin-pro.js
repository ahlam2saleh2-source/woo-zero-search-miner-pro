/* ═══════════════════════════════════════════════════════
   Woo Zero Search Miner Pro - Admin JavaScript
   Handles: Theme toggle, License activation AJAX, Live updates
   ═══════════════════════════════════════════════════════ */

(function($) {
    'use strict';

    $(function() {

        // ════════════ تبديل Dark/Light Mode (مع تأثير سلس) ════════════
        $(document).on('click', '#wzsmpro-toggle-theme', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $icon = $btn.find('.dashicons');
            var $text = $btn.find('.toggle-text');
            $btn.prop('disabled', true);

            $.post(wzsmpro.ajax_url, {
                action: 'wzsmpro_toggle_theme',
                nonce:  wzsmpro.nonce
            })
            .done(function(resp) {
                if (resp && resp.success) {
                    if (resp.data.dark_mode) {
                        $('body').addClass('wzsmpro-dark-mode');
                        $icon.removeClass('dashicons-lightbulb').addClass('dashicons-dark-mode-2');
                        $text.text('فاتح');
                    } else {
                        $('body').removeClass('wzsmpro-dark-mode');
                        $icon.removeClass('dashicons-dark-mode-2').addClass('dashicons-lightbulb');
                        $text.text('داكن');
                    }
                    // تأثير نبضة بسيط للتأكيد
                    $btn.css('transform', 'scale(1.1)');
                    setTimeout(function() { $btn.css('transform', ''); }, 200);
                } else {
                    alert('فشل التبديل: ' + (resp.data || 'حاول مرة أخرى'));
                }
            })
            .fail(function() {
                alert('خطأ في الاتصال. تأكد من أنك مسجل الدخول.');
            })
            .always(function() {
                $btn.prop('disabled', false);
            });
        });

        // ════════════ تحميل License Key تلقائياً إن كان Trial ════════════
        // (تحسين UX: إذا كان حقل المفتاح فارغاً وعندنا مفتاح تجريبي، نعبّئه)
        var $keyField = $('#wzsmpro_license_key');
        if ($keyField.length && !$keyField.val()) {
            // إظهار زر "تجربة مجانية"
            var $trialBtn = $('<button type="button" class="button-link" style="margin-right:8px;">' +
                '<span class="dashicons dashicons-admin-plugins" style="vertical-align:middle;"></span> ' +
                'استخدم المفتاح التجريبي</button>');

            $keyField.after($trialBtn);

            $trialBtn.on('click', function() {
                $keyField.val('WZSMPRO-TRIAL-DEVELOPMENT-KEY-2026');
                $keyField.css('background', '#e7f9ed');
                setTimeout(function() { $keyField.css('background', ''); }, 1500);
            });
        }

        // ════════════ تأثير Hover على Cards ════════════
        $('.wzsmpro-card').on('mouseenter', function() {
            $(this).css('box-shadow', '0 4px 16px rgba(0,0,0,0.08)');
        }).on('mouseleave', function() {
            $(this).css('box-shadow', '0 1px 3px rgba(0,0,0,0.04)');
        });

        // ════════════ تحديث Widget تلقائياً كل 30 ثانية ════════════
        if ($('#wzsmpro_dashboard_widget').length) {
            setInterval(function() {
                // Widget يُدار من قبل WordPress - لإعادة التحميل، يلزم refresh يدوي
                // يمكن إضافة AJAX هنا لاحقاً للتحديث التلقائي
            }, 30000);
        }

        // ════════════ تأثير نسخ المفتاح عند الضغط على الـ code ════════════
        $('.wzsmpro-trial-notice code').on('click', function() {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val($(this).text()).select();
            try {
                document.execCommand('copy');
                var $msg = $('<div class="notice notice-success wzsmpro-copy-notice"><p>✓ تم نسخ المفتاح</p></div>')
                    .css({ position:'fixed', top:'40%', right:'40%', zIndex:99999 })
                    .appendTo('body');
                setTimeout(function() { $msg.fadeOut(300, function() { $(this).remove(); }); }, 1500);
            } catch (e) {}
            $temp.remove();
        }).css('cursor', 'pointer').attr('title', 'انقر للنسخ');

        // ════════════ إظهار/إخفاء إعدادات Slack/Discord ════════════
        function toggleWebhookFields() {
            var slackOn = $('input[name="wzsmpro_settings[slack_enabled]"]').is(':checked');
            var discordOn = $('input[name="wzsmpro_settings[discord_enabled]"]').is(':checked');

            $('input[name="wzsmpro_settings[slack_webhook_url]"]').closest('tr').toggle(slackOn || true);
            $('input[name="wzsmpro_settings[discord_webhook_url]"]').closest('tr').toggle(discordOn || true);
        }
        // تشغيل أول مرة
        if ($('input[name="wzsmpro_settings[slack_enabled]"]').length) {
            toggleWebhookFields();
            $('input[name="wzsmpro_settings[slack_enabled]"], input[name="wzsmpro_settings[discord_enabled]"]').on('change', toggleWebhookFields);
        }

        // ════════════ اختبار Slack webhook ════════════
        $('#wzsmpro-test-slack').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true).text('جارٍ الإرسال...');

            $.post(wzsmpro.ajax_url, {
                action: 'wzsmpro_test_slack',
                nonce:  wzsmpro.nonce
            }, function(resp) {
                if (resp.success) {
                    alert('✓ تم إرسال رسالة اختبار إلى Slack بنجاح!');
                } else {
                    alert('خطأ: ' + (resp.data || 'غير معروف'));
                }
            }).always(function() {
                $btn.prop('disabled', false).text('إرسال اختبار');
            });
        });
    });
})(jQuery);
