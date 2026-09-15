<?php
/**
 * فئة قاعدة البيانات - إنشاء الجدول والعمليات CRUD
 *
 * @package Woo_Zero_Search_Miner
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSMPRO_Database
{
    /**
     * اسم الجدول (مع prefix)
     *
     * @return string
     */
    public static function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . WZSMPRO_DB_TABLE;
    }

    /**
     * إنشاء جدول السجلات
     * يستخدم dbDelta لضمان ترقية آمنة
     */
    public static function create_table()
    {
        global $wpdb;
        $table_name = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        // تأكد من تحميل dbDelta
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            search_term VARCHAR(255) NOT NULL,
            search_term_normalized VARCHAR(255) NOT NULL,
            results_count INT(11) NOT NULL DEFAULT 0,
            searched_at DATETIME NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            user_ip VARCHAR(100) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            referer VARCHAR(255) DEFAULT NULL,
            language VARCHAR(10) DEFAULT NULL,
            is_ajax TINYINT(1) NOT NULL DEFAULT 0,
            occurrence_count INT(11) NOT NULL DEFAULT 1,
            last_occurrence DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_search_term (search_term_normalized),
            KEY idx_searched_at (searched_at),
            KEY idx_user_id (user_id),
            KEY idx_results_count (results_count)
        ) {$charset_collate};";

        dbDelta($sql);

        // فهرس إضافي للاستعلامات السريعة
        $wpdb->query("CREATE INDEX IF NOT EXISTS idx_term_at ON {$table_name} (search_term_normalized, searched_at)");
    }

    /**
     * إدراج سجل بحث جديد أو تحديث عدد التكرارات إن كان موجوداً
     *
     * @param array $data البيانات المراد إدراجها
     * @return int|false  معرف السجل أو false عند الفشل
     */
    public static function insert_log($data)
    {
        global $wpdb;
        $table_name = self::table_name();

        // تطبيع المصطلح: lowercase + trim + إزالة الرموز الزائدة
        $normalized = self::normalize_term($data['search_term']);

        // تحقق من تكرار نفس البحث في آخر 5 دقائق لنفس المستخدم/IP
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name}
             WHERE search_term_normalized = %s
               AND searched_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
               AND user_ip = %s
             LIMIT 1",
            $normalized,
            $data['user_ip'] ?? ''
        ));

        if ($existing) {
            // تحديث عدد التكرارات
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table_name}
                 SET occurrence_count = occurrence_count + 1,
                     last_occurrence = NOW()
                 WHERE id = %d",
                $existing
            ));
            return (int) $existing;
        }

        // إدراج جديد
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'search_term'             => $data['search_term'],
                'search_term_normalized'  => $normalized,
                'results_count'           => $data['results_count'],
                'searched_at'             => current_time('mysql'),
                'user_id'                 => $data['user_id'] ?? 0,
                'user_ip'                 => $data['user_ip'] ?? null,
                'user_agent'              => $data['user_agent'] ?? null,
                'referer'                 => $data['referer'] ?? null,
                'language'                => $data['language'] ?? null,
                'is_ajax'                 => $data['is_ajax'] ?? 0,
                'last_occurrence'         => current_time('mysql'),
            ),
            array('%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    /**
     * تطبيع مصطلح البحث (للتجميع)
     *
     * @param string $term
     * @return string
     */
    public static function normalize_term($term)
    {
        $term = mb_strtolower(trim($term), 'UTF-8');
        $term = preg_replace('/\s+/', ' ', $term);
        return $term;
    }

    /**
     * جلب أعلى عمليات البحث بدون نتائج (top N)
     *
     * @param int $limit عدد النتائج
     * @param int $days  عدد الأيام الأخيرة (0 = الكل)
     * @return array
     */
    public static function get_top_zero_searches($limit = 20, $days = 0)
    {
        global $wpdb;
        $table_name = self::table_name();

        $where = "WHERE results_count = 0";
        if ($days > 0) {
            $where .= $wpdb->prepare(" AND searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        }

        return $wpdb->get_results(
            "SELECT
                search_term,
                search_term_normalized,
                SUM(occurrence_count) AS total_searches,
                COUNT(*) AS unique_searches,
                MIN(searched_at) AS first_seen,
                MAX(last_occurrence) AS last_seen
             FROM {$table_name}
             {$where}
             GROUP BY search_term_normalized
             ORDER BY total_searches DESC, unique_searches DESC
             LIMIT " . (int) $limit
        );
    }

    /**
     * إحصائيات شاملة
     *
     * @param int $days عدد الأيام (0 = الكل)
     * @return array
     */
    public static function get_stats($days = 0)
    {
        global $wpdb;
        $table_name = self::table_name();

        $where = "WHERE 1=1";
        if ($days > 0) {
            $where .= $wpdb->prepare(" AND searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)", $days);
        }

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) AS total_searches,
                SUM(occurrence_count) AS total_with_repeats,
                SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) AS zero_results,
                SUM(CASE WHEN results_count = 0 THEN occurrence_count ELSE 0 END) AS zero_with_repeats,
                COUNT(DISTINCT search_term_normalized) AS unique_terms,
                COUNT(DISTINCT CASE WHEN results_count = 0 THEN search_term_normalized ELSE NULL END) AS unique_zero_terms
             FROM {$table_name}
             {$where}"
        );

        return array(
            'total_searches'      => (int) ($stats->total_searches ?? 0),
            'total_with_repeats'  => (int) ($stats->total_with_repeats ?? 0),
            'zero_results'         => (int) ($stats->zero_results ?? 0),
            'zero_with_repeats'    => (int) ($stats->zero_with_repeats ?? 0),
            'unique_terms'         => (int) ($stats->unique_terms ?? 0),
            'unique_zero_terms'    => (int) ($stats->unique_zero_terms ?? 0),
            'zero_rate'            => ($stats->total_searches > 0)
                ? round(($stats->zero_results / $stats->total_searches) * 100, 1)
                : 0,
        );
    }

    /**
     * جلب آخر عمليات البحث
     *
     * @param int $limit
     * @param int $zero_only 1=فقط بدون نتائج
     * @return array
     */
    public static function get_recent_searches($limit = 50, $zero_only = 0)
    {
        global $wpdb;
        $table_name = self::table_name();

        $where = $zero_only ? "WHERE results_count = 0" : "WHERE 1=1";

        return $wpdb->get_results(
            "SELECT *
             FROM {$table_name}
             {$where}
             ORDER BY searched_at DESC
             LIMIT " . (int) $limit
        );
    }

    /**
     * اتجاه يومي (للرسم البياني)
     *
     * @param int $days
     * @return array
     */
    public static function get_daily_trend($days = 30)
    {
        global $wpdb;
        $table_name = self::table_name();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT
                DATE(searched_at) AS day,
                COUNT(*) AS total,
                SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) AS zero_count
             FROM {$table_name}
             WHERE searched_at > DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(searched_at)
             ORDER BY day ASC",
            $days
        ));
    }

    /**
     * حذف السجلات الأقدم من عدد أيام محدد
     *
     * @param int $days
     * @return int عدد الصفوف المحذوفة
     */
    public static function cleanup_old_logs($days)
    {
        global $wpdb;
        $table_name = self::table_name();

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name}
             WHERE searched_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            (int) $days
        ));

        return $deleted !== false ? (int) $deleted : 0;
    }

    /**
     * حذف كل السجلات
     *
     * @return bool
     */
    public static function truncate_logs()
    {
        global $wpdb;
        $table_name = self::table_name();
        return $wpdb->query("TRUNCATE TABLE {$table_name}") !== false;
    }

    /**
     * حذف سجلات محددة (بناءً على المصطلح المطبع)
     *
     * @param string $normalized_term
     * @return int
     */
    public static function delete_by_term($normalized_term)
    {
        global $wpdb;
        $table_name = self::table_name();
        return (int) $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE search_term_normalized = %s",
            $normalized_term
        ));
    }

    /**
     * حذف الجدول كاملاً (للإزالة)
     */
    public static function drop_table()
    {
        global $wpdb;
        $table_name = self::table_name();
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }
}
