<?php
/**
 * Webhooks - إشعارات Slack و Discord عند ظهور مصطلح بلا نتائج جديد
 *
 * @package Woo_Zero_Search_Miner_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class WZSMPRO_Webhooks
{
    private static $instance = null;
    private $recently_notified = array();

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wzsmpro_after_log_zero_search', array($this, 'maybe_send_webhooks'), 20, 1);
    }

    /**
     * إرسال الإشعارات للمحادثات الجديدة فقط
     */
    public function maybe_send_webhooks($data)
    {
        if (!true) {
            return;
        }
        $settings = get_option(WZSMPRO_OPTION_KEY, array());

        $term = $data['search_term'];
        $normalized = WZSMPRO_Database::normalize_term($term);

        // منع التكرار للمصطلح نفسه خلال 5 دقائق
        $cache_key = md5($normalized);
        if (isset($this->recently_notified[$cache_key])) {
            return;
        }
        $this->recently_notified[$cache_key] = true;

        // Slack
        if (!empty($settings['slack_enabled']) && !empty($settings['slack_webhook_url'])) {
            $this->send_slack($data, $settings['slack_webhook_url']);
        }

        // Discord
        if (!empty($settings['discord_enabled']) && !empty($settings['discord_webhook_url'])) {
            $this->send_discord($data, $settings['discord_webhook_url']);
        }
    }

    /**
     * إرسال إلى Slack
     */
    private function send_slack($data, $webhook_url)
    {
        $site_name = get_bloginfo('name');
        $admin_url = admin_url('admin.php?page=wzsmpro-dashboard');

        $payload = array(
            'text' => sprintf('🔍 %s: مصطلح بحث جديد بلا نتائج', $site_name),
            'attachments' => array(
                array(
                    'color'   => '#FF7A45',
                    'fields'  => array(
                        array(
                            'title' => __('المصطلح', 'woo-zero-search-miner-pro'),
                            'value' => $data['search_term'],
                            'short' => true,
                        ),
                        array(
                            'title' => __('الوقت', 'woo-zero-search-miner-pro'),
                            'value' => $data['occurred_at'],
                            'short' => true,
                        ),
                        array(
                            'title' => __('اللغة', 'woo-zero-search-miner-pro'),
                            'value' => $data['language'] ?: 'N/A',
                            'short' => true,
                        ),
                        array(
                            'title' => __('نوع الطلب', 'woo-zero-search-miner-pro'),
                            'value' => $data['is_ajax'] ? 'AJAX' : 'Page',
                            'short' => true,
                        ),
                    ),
                    'actions' => array(
                        array(
                            'type'  => 'button',
                            'text'  => __('عرض السجلات', 'woo-zero-search-miner-pro'),
                            'url'   => $admin_url,
                        ),
                    ),
                ),
            ),
        );

        $this->http_post($webhook_url, $payload);
    }

    /**
     * إرسال إلى Discord
     */
    private function send_discord($data, $webhook_url)
    {
        $site_name = get_bloginfo('name');
        $admin_url = admin_url('admin.php?page=wzsmpro-dashboard');

        $embed = array(
            'title'       => sprintf('🔍 %s: مصطلح بحث جديد بلا نتائج', $site_name),
            'description' => sprintf('بحث أحد زوار متجرك عن "**%s**" ولم يجد نتائج.', $data['search_term']),
            'url'         => $admin_url,
            'color'       => hexdec('FF7A45'),
            'fields'      => array(
                array(
                    'name'   => __('الوقت', 'woo-zero-search-miner-pro'),
                    'value'  => $data['occurred_at'],
                    'inline' => true,
                ),
                array(
                    'name'   => __('اللغة', 'woo-zero-search-miner-pro'),
                    'value'  => $data['language'] ?: 'N/A',
                    'inline' => true,
                ),
                array(
                    'name'   => __('نوع الطلب', 'woo-zero-search-miner-pro'),
                    'value'  => $data['is_ajax'] ? 'AJAX' : 'Page',
                    'inline' => true,
                ),
            ),
            'footer'      => array(
                'text' => 'Woo Zero Search Miner Pro',
            ),
            'timestamp' => gmdate('c', strtotime($data['occurred_at'])),
        );

        $payload = array('embeds' => array($embed));

        $this->http_post($webhook_url, $payload);
    }

    /**
     * إرسال HTTP POST (payload كـ JSON)
     */
    private function http_post($url, $payload)
    {
        $args = array(
            'body'        => wp_json_encode($payload),
            'headers'     => array(
                'Content-Type' => 'application/json',
            ),
            'timeout'     => 15,
            'redirection' => 5,
            'blocking'    => false,  // عدم انتظار الرد لتسريع العمل
        );
        wp_remote_post($url, $args);
    }
}
