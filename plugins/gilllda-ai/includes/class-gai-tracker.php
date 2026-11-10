<?php
/**
 * AI bot visit log and AI-assistant referral counter.
 *
 * Per request: one preg_match on the User-Agent (and, for humans, a strpos on Referer/utm_source).
 * Only matching requests write — a single INSERT … ON DUPLICATE KEY UPDATE at shutdown into {prefix}gai_hits
 * (daily buckets). No IPs, no user agents, no query strings are stored: just day, kind, bot/source and path.
 *
 * kind: bot  = AI crawler request (any path)
 *       ref  = human visit arriving from an AI assistant (bot = source label, path = landing page)
 *       file = fetch of llms.txt / llms-full.txt / *.md (bot = agent token or "other")
 *
 * @package GillldaAI
 */

if (!defined('ABSPATH')) {
    exit;
}

final class GAI_Tracker
{
    /** @var string|null Matched bot token. */
    private static $bot = null;

    /** @var string|null AI referral source label. */
    private static $ref = null;

    /** @var string Original request path (captured before .md rewriting). */
    private static $path = '';

    /** @var string|null llms.txt | llms-full.txt | md */
    private static $file = null;

    public static function table()
    {
        global $wpdb;
        return $wpdb->prefix . 'gai_hits';
    }

    public static function boot()
    {
        add_action('init', array(__CLASS__, 'capture'), 1);
    }

    /** Regex of all UA substrings → token map. */
    private static function ua_map()
    {
        $map = array();
        foreach (GAI_Core::bots() as $token => $b) {
            if ($b[3] !== '') {
                $map[strtolower($b[3])] = $token;
            }
        }
        return $map;
    }

    /** Detect an AI agent token from a user-agent string. */
    public static function detect_bot($ua)
    {
        if ($ua === '') {
            return null;
        }
        static $re = null;
        static $map = null;
        if ($re === null) {
            $map = self::ua_map();
            $re  = '/(' . implode('|', array_map(function ($s) {
                return preg_quote($s, '/');
            }, array_keys($map))) . ')/i';
        }
        return preg_match($re, $ua, $m) ? ($map[strtolower($m[1])] ?? null) : null;
    }

    /** Detect an AI assistant from Referer host or utm_source. */
    public static function detect_ref($referer, $utm)
    {
        $host = strtolower((string) wp_parse_url($referer, PHP_URL_HOST));
        $utm  = strtolower($utm);
        if ($host === '' && $utm === '') {
            return null;
        }
        foreach (GAI_Core::referrers() as $domain => $label) {
            if ($host !== '' && ($host === $domain || substr($host, -strlen('.' . $domain)) === '.' . $domain)) {
                return $label;
            }
            if ($utm !== '' && strpos($utm, $domain) !== false) {
                return $label;
            }
        }
        if ($utm !== '' && preg_match('/^(chatgpt|openai|perplexity|gemini|copilot|claude)\b/', $utm, $m)) {
            $labels = array('chatgpt' => 'ChatGPT', 'openai' => 'ChatGPT', 'perplexity' => 'Perplexity', 'gemini' => 'Gemini', 'copilot' => 'Copilot', 'claude' => 'Claude');
            return $labels[$m[1]];
        }
        return null;
    }

    public static function capture()
    {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('WP_CLI') && WP_CLI)) {
            return;
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'GET' && $method !== 'HEAD') {
            return;
        }
        $s = GAI_Core::settings();
        if (empty($s['track']) && empty($s['track_refs'])) {
            return;
        }
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (!empty($s['track'])) {
            self::$bot = self::detect_bot($ua);
        }
        if (!self::$bot && !empty($s['track_refs'])) {
            $utm       = isset($_GET['utm_source']) ? sanitize_text_field(wp_unslash($_GET['utm_source'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification
            self::$ref = self::detect_ref((string) ($_SERVER['HTTP_REFERER'] ?? ''), $utm);
            if (self::$ref && preg_match('/bot|crawl|spider|preview|fetch/i', $ua)) {
                self::$ref = null; // link-preview fetchers, not people
            }
        }
        self::$path = GAI_Router::rel_path();
        if (self::$bot || self::$ref) {
            add_action('shutdown', array(__CLASS__, 'record'), 0);
        }
    }

    /** Called by the router when it serves llms.txt / llms-full.txt / a .md document. */
    public static function note_file($what)
    {
        self::$file = $what;
        if (empty(GAI_Core::get('track'))) {
            return;
        }
        if (!has_action('shutdown', array(__CLASS__, 'record'))) {
            add_action('shutdown', array(__CLASS__, 'record'), 0);
        }
    }

    /** Normalise a path for storage (no query, bounded length, decoded for readable Persian slugs). */
    private static function clean_path($path)
    {
        $path = rawurldecode((string) $path);
        $path = preg_replace('#/+#', '/', $path);
        if (function_exists('mb_strcut')) {
            $path = mb_strcut($path, 0, 190, 'UTF-8');
        }
        return $path === '' ? '/' : $path;
    }

    public static function record()
    {
        $code = (int) http_response_code();
        $path = self::clean_path(self::$path);
        if ($code >= 400) {
            $path = '(' . $code . ')'; // collapse junk/scanner URLs into one bucket
        }
        $day = current_time('Y-m-d');
        if (self::$bot) {
            self::bump($day, 'bot', self::$bot, $path);
        }
        if (self::$ref && $code < 400) {
            if (!(function_exists('current_user_can') && is_user_logged_in() && current_user_can('edit_posts'))) {
                self::bump($day, 'ref', self::$ref, $path);
            }
        }
        if (self::$file && $code < 400) {
            self::bump($day, 'file', self::$bot ?: 'other', self::$file === 'md' ? 'md' : self::$file);
        }
    }

    private static function bump($day, $kind, $bot, $path)
    {
        global $wpdb;
        $table = self::table();
        $prev  = $wpdb->suppress_errors(true);
        $wpdb->query($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "INSERT INTO {$table} (day, kind, bot, path, hits) VALUES (%s, %s, %s, %s, 1) ON DUPLICATE KEY UPDATE hits = hits + 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $day,
            $kind,
            substr($bot, 0, 40),
            $path
        ));
        $wpdb->suppress_errors($prev);
    }

    public static function prune()
    {
        global $wpdb;
        $days  = max(7, (int) GAI_Core::get('retention'));
        $table = self::table();
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE day < %s", gmdate('Y-m-d', time() - $days * DAY_IN_SECONDS))); // phpcs:ignore
    }
}
