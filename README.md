# Woo Zero Search Miner Pro ⚡

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![License](https://img.shields.io/badge/license-GPLv2-brightgreen)
![Type](https://img.shields.io/badge/type-Pro-purple)

> **النسخة الاحترافية من Woo Zero Search Miner** - يحوّل بيانات البحث بلا نتائج إلى تنبيهات فورية وإحصائيات يومية.

## ✨ المزايا Premium

| الميزة | الوصف |
| --- | --- |
| 📧 **تنبيهات بريدية فورية** | بريد فوري عند ظهور مصطلح جديد بلا نتائج |
| 📊 **تقرير يومي مجدول** | ملخص يومي بإحصائيات وأعلى المصطلحات |
| 🏠 **Dashboard Widget** | عنصر "Zero Search" على الصفحة الرئيسية لـ WordPress admin |
| 💬 **Slack/Discord** | إشعارات فورية للـ webhooks |
| 🎨 **شريط تنقل + Breadcrumbs** | حلّ مشكلة عدم وجود أزرار تنقل بين التبويبات |
| 🌙 **Dark Mode** | وضع داكن للعمل لساعات طويلة |
| 🏷️ **White-label** | إزالة علامتنا التجارية، استخدم علامتك الخاصة |
| 🔌 **WordPress Hooks** | متكامل مع `wzsm_after_log_zero_search` للتخصيص |

## 🚀 التثبيت

### المتطلبات الأساسية:
- ✅ WordPress 5.6+
- ✅ PHP 7.4+
- ✅ WooCommerce 5.0+
- ✅ **Woo Zero Search Miner Basic** (مفعّل)

### خطوات التثبيت:

1. ارفع مجلد `woo-zero-search-miner-pro` إلى `wp-content/plugins/`
2. اذهب إلى: WordPress Admin → الإضافات
3. ابحث عن "Woo Zero Search Miner Pro" → اضغط "تفعيل"
4. اذهب إلى: Zero Search → ⚡ ترخيص Pro
5. أدخل مفتاح الترخيص:

### المفاتيح:
- **للتجربة المحلية**: `WZSMPRO-TRIAL-DEVELOPMENT-KEY-2026`
- **للاستخدام التجاري**: اشترِ مفتاحاً من [Gumroad](https://gumroad.com/l/woo-zero-search-miner)

## 📋 إعداد Slack Webhook

1. اذهب إلى: https://api.slack.com/messaging/webhooks
2. اختر القناة (Channel) المطلوب
3. انسخ الـ Webhook URL
4. في صفحة إعدادات Pro، الصق الـ URL في حقل Slack Webhook
5. فعّل الإعداد

## 📋 إعداد Discord Webhook

1. في Discord: Settings → Integrations → Webhooks
2. اختر القناة وانسخ الـ Webhook URL
3. في صفحة إعدادات Pro، الصق الـ URL في حقل Discord Webhook
4. فعّل الإعداد

## 🎯 كيف يعمل؟

```
[Zبائن يبحث عن منتج غير موجود]
            ↓
[WooCommerce: No products found]
            ↓
[BASIC Plugin: تسجيل في قاعدة البيانات]
            ↓
[Pro Plugin: استلام الحدث wzsm_after_log_zero_search]
            ↓
    ┌───────┴────────┬──────────┐
    ↓                ↓          ↓
[بريد فوري]      [Slack]    [Dashboard Widget]
(إن كان مصطلح جديد)  (إن كان مفعّل)  (يُحدّث فوراً)
```

## 🛡️ الأمان

- ✅ Nonces في كل طلبات AJAX
- ✅ فحص الصلاحية `manage_woocommerce`
- ✅ Sanitization كامل
- ✅ Webhook URLs لا تُخزّن في logs

## 📦 الترخيص

GPL-2.0+ - حر للاستخدام والتعديل. المفاتيح التجارية تُباع لإلغاء قفل المزايا Premium.

## 👤 المؤلف

- **Brand**: az-soft4media
- **Website**: https://troyawin.tech
- **Buy**: https://gumroad.com/l/woo-zero-search-miner

---

صُنع بحب لكل مطوّر ووردبريس عربي 🌟
