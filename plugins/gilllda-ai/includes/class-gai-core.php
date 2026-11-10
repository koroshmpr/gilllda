<?php
/**
 * Core: settings, shared helpers, storage, debounced background regeneration, install/upgrade.
 *
 * Front-end cost: settings() is one autoloaded option read; nothing else here runs on a normal page view.
 *
 * @package GillldaAI
 */

if (!defined('ABSPATH')) {
    exit;
}

final class GAI_Core
{
    const OPT_SETTINGS = 'gai_settings';
    const OPT_STATE    = 'gai_state';
    const OPT_AUDIT    = 'gai_audit';
    const OPT_POLICY   = 'gai_policy';
    const OPT_LOCK     = 'gai_lock';
    const OPT_CHANGED  = 'gai_changed';
    const OPT_VERSION  = 'gai_version';
    const HOOK_BUILD   = 'gai_rebuild';
    const HOOK_DAILY   = 'gai_daily';
    const DELAY        = 60;
    const DB_VERSION   = '1';

    /** @var array|null */
    private static $settings = null;

    /** @var bool Rebuild already scheduled during this request. */
    private static $touched = false;

    /** Meta keys whose change affects the generated files. */
    const WATCHED_META = array(
        'rank_math_description', 'rank_math_robots', 'rank_math_title', 'gl_tldr', 'gl_takeaways', 'gl_series', 'gl_series_part',
        '_price', '_regular_price', '_sale_price', '_stock_status', '_stock', 'faq_list', 'faq_cat', '_thumbnail_id', '_wp_page_template',
    );

    /* =====================================================================
     * Boot
     * ================================================================== */

    public static function boot()
    {
        add_action(self::HOOK_BUILD, array(__CLASS__, 'rebuild'));
        add_action(self::HOOK_DAILY, array(__CLASS__, 'daily'));

        // Content changes → debounced rebuild.
        add_action('transition_post_status', array(__CLASS__, 'on_status'), 10, 3);
        add_action('deleted_post', array(__CLASS__, 'on_post_id'));
        add_action('trashed_post', array(__CLASS__, 'on_post_id'));
        foreach (array('added_post_meta', 'updated_post_meta', 'deleted_post_meta') as $h) {
            add_action($h, array(__CLASS__, 'on_meta'), 10, 3);
        }
        foreach (array('created_term', 'edited_term', 'delete_term') as $h) {
            add_action($h, array(__CLASS__, 'on_term'), 10, 3);
        }
        foreach (array(
            'woocommerce_product_set_stock', 'woocommerce_variation_set_stock',
            'woocommerce_product_set_stock_status', 'woocommerce_variation_set_stock_status',
            'woocommerce_update_product', 'woocommerce_update_product_variation',
            'wc_after_products_starting_sales', 'wc_after_products_ending_sales',
            'woocommerce_after_shipping_zone_object_save', 'woocommerce_shipping_zone_method_added',
            'woocommerce_shipping_zone_method_deleted', 'woocommerce_shipping_zone_method_status_toggled',
        ) as $h) {
            add_action($h, array(__CLASS__, 'touch'));
        }
        add_action('updated_option', array(__CLASS__, 'on_option'), 10, 1);
        add_action('acf/save_post', array(__CLASS__, 'on_acf_save'), 30);

        // Self-heal after an FTP deploy of a new version (activation hook does not run then).
        add_action('admin_init', array(__CLASS__, 'maybe_upgrade'));
        add_action(self::HOOK_DAILY, array(__CLASS__, 'maybe_upgrade'), 1);
    }

    /* =====================================================================
     * Bots
     * ================================================================== */

    /**
     * AI agents: robots.txt token => [vendor, Persian purpose, kind (training|search|user), UA substring or '' for robots-only tokens].
     */
    public static function bots()
    {
        return array(
            'OAI-SearchBot'        => array('OpenAI', 'جستجوی ChatGPT (نمایش و لینک در پاسخ‌ها)', 'search', 'OAI-SearchBot'),
            'ChatGPT-User'         => array('OpenAI', 'باز کردن صفحه به درخواست کاربر ChatGPT', 'user', 'ChatGPT-User'),
            'GPTBot'               => array('OpenAI', 'جمع‌آوری داده برای آموزش مدل‌های GPT', 'training', 'GPTBot'),
            'Claude-SearchBot'     => array('Anthropic', 'جستجوی Claude (نمایش در پاسخ‌ها)', 'search', 'Claude-SearchBot'),
            'Claude-User'          => array('Anthropic', 'باز کردن صفحه به درخواست کاربر Claude', 'user', 'Claude-User'),
            'ClaudeBot'            => array('Anthropic', 'جمع‌آوری داده برای آموزش مدل‌های Claude', 'training', 'ClaudeBot'),
            'PerplexityBot'        => array('Perplexity', 'ایندکس موتور پاسخ Perplexity', 'search', 'PerplexityBot'),
            'Perplexity-User'      => array('Perplexity', 'باز کردن صفحه به درخواست کاربر Perplexity', 'user', 'Perplexity-User'),
            'Google-Extended'      => array('Google', 'استفاده در Gemini و آموزش مدل (روی رتبه گوگل اثری ندارد)', 'training', ''),
            'Applebot-Extended'    => array('Apple', 'آموزش مدل‌های Apple Intelligence (روی Siri/Spotlight اثری ندارد)', 'training', ''),
            'Amazonbot'            => array('Amazon', 'دستیار Alexa و پاسخ‌های Amazon', 'search', 'Amazonbot'),
            'DuckAssistBot'        => array('DuckDuckGo', 'پاسخ‌های هوشمند DuckDuckGo', 'search', 'DuckAssistBot'),
            'meta-externalagent'   => array('Meta', 'جمع‌آوری داده برای آموزش Meta AI', 'training', 'meta-externalagent'),
            'meta-externalfetcher' => array('Meta', 'باز کردن صفحه به درخواست کاربر Meta AI', 'user', 'meta-externalfetcher'),
            'MistralAI-User'       => array('Mistral', 'باز کردن صفحه به درخواست کاربر Le Chat', 'user', 'MistralAI-User'),
            'YouBot'               => array('You.com', 'موتور پاسخ You.com', 'search', 'YouBot'),
            'CCBot'                => array('Common Crawl', 'دیتاست عمومی که در آموزش بیشتر مدل‌ها استفاده می‌شود', 'training', 'CCBot'),
            'cohere-ai'            => array('Cohere', 'جمع‌آوری داده برای آموزش مدل', 'training', 'cohere-ai'),
            'Bytespider'           => array('ByteDance', 'جمع‌آوری داده برای آموزش مدل (پرمصرف، کم‌فایده برای ایران)', 'training', 'Bytespider'),
        );
    }

    public static function kind_label($kind)
    {
        $map = array('search' => 'جستجو/پاسخ', 'user' => 'درخواست کاربر', 'training' => 'آموزش مدل');
        return $map[$kind] ?? $kind;
    }

    /** AI assistants that send visitors (Referer host or utm_source fragment => label). */
    public static function referrers()
    {
        return array(
            'chatgpt.com'           => 'ChatGPT',
            'chat.openai.com'       => 'ChatGPT',
            'perplexity.ai'         => 'Perplexity',
            'gemini.google.com'     => 'Gemini',
            'bard.google.com'       => 'Gemini',
            'copilot.microsoft.com' => 'Copilot',
            'claude.ai'             => 'Claude',
            'chat.deepseek.com'     => 'DeepSeek',
            'grok.com'              => 'Grok',
            'meta.ai'               => 'Meta AI',
            'chat.mistral.ai'       => 'Mistral',
            'you.com'               => 'You.com',
        );
    }

    /* =====================================================================
     * Settings
     * ================================================================== */

    public static function defaults()
    {
        $policy = array();
        foreach (self::bots() as $token => $b) {
            // Search/answer/user agents: allow. Training: allowed by default (brand knowledge inside models helps a store),
            // except Bytespider which ignores the region and only costs bandwidth. The owner decides in Settings.
            $policy[$token] = $token === 'Bytespider' ? 'disallow' : 'allow';
        }
        return array(
            'llms_txt'      => 1,
            'llms_full'     => 1,
            'md'            => 1,
            'md_accept'     => 1,
            'md_head'       => 1,
            'types'         => array('product', 'post', 'page'),
            'exclude_ids'   => '',
            'full_cap_kb'   => 1500,
            'intro'         => '',
            'facts'         => '',
            'phone'         => '',
            'email'         => '',
            'robots'        => 1,
            'bots'          => $policy,
            'robots_extra'  => '',
            'schema_faq'    => 'auto',
            'schema_org'    => 1,
            'schema_store'  => 1,
            'schema_speak'  => 1,
            'speakable'     => "h1\n[aria-label=\"خلاصه سریع\"] p\n#takeaways-title + *",
            'track'         => 1,
            'track_refs'    => 1,
            'retention'     => 90,
        );
    }

    public static function settings()
    {
        if (self::$settings === null) {
            $saved = get_option(self::OPT_SETTINGS, array());
            $saved = is_array($saved) ? $saved : array();
            $d     = self::defaults();
            $s     = array_merge($d, $saved);
            $s['bots']  = array_merge($d['bots'], is_array($saved['bots'] ?? null) ? $saved['bots'] : array());
            $s['types'] = is_array($s['types']) ? array_values($s['types']) : $d['types'];
            self::$settings = $s;
        }
        return self::$settings;
    }

    public static function get($key)
    {
        $s = self::settings();
        return $s[$key] ?? null;
    }

    public static function save_settings(array $s)
    {
        update_option(self::OPT_SETTINGS, $s, true);
        self::$settings = null;
    }

    public static function state()
    {
        $s = get_option(self::OPT_STATE, array());
        return is_array($s) ? $s : array();
    }

    /* =====================================================================
     * Environment
     * ================================================================== */

    public static function wc()
    {
        return class_exists('WooCommerce') && function_exists('wc_get_product');
    }

    public static function rank_math()
    {
        return defined('RANK_MATH_VERSION') || class_exists('RankMath');
    }

    public static function acf()
    {
        return function_exists('get_field');
    }

    /* =====================================================================
     * Content visibility
     * ================================================================== */

    /** Post types exposed to AI (setting ∩ registered public types). */
    public static function types()
    {
        $out = array();
        foreach ((array) self::get('types') as $pt) {
            if (post_type_exists($pt) && ($pt !== 'product' || self::wc())) {
                $out[] = $pt;
            }
        }
        return $out;
    }

    /** IDs never exposed: cart/checkout/account, login, compare, owner's manual exclusions. */
    public static function excluded_ids()
    {
        static $ids = null;
        if ($ids !== null) {
            return $ids;
        }
        $cached = get_transient('gai_excluded');
        if (is_array($cached)) {
            return $ids = $cached;
        }
        $ids = array();
        if (function_exists('wc_get_page_id')) {
            foreach (array('cart', 'checkout', 'myaccount') as $p) {
                $id = (int) wc_get_page_id($p);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }
        foreach (array('login', 'compare', 'cart', 'checkout', 'my-account', 'wishlist') as $slug) {
            $p = get_page_by_path($slug);
            if ($p) {
                $ids[] = (int) $p->ID;
            }
        }
        $tpl = get_posts(array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => array(array('key' => '_wp_page_template', 'value' => array('login.php', 'page-compare.php'), 'compare' => 'IN')),
        ));
        $ids = array_merge($ids, array_map('intval', $tpl));
        foreach (preg_split('/[\s,،]+/u', (string) self::get('exclude_ids')) as $id) {
            if ((int) $id > 0) {
                $ids[] = (int) $id;
            }
        }
        $ids = array_values(array_unique($ids));
        set_transient('gai_excluded', $ids, DAY_IN_SECONDS); // cleared by touch(); keeps page views to one cached read
        return $ids;
    }

    /** Rank Math noindex for a post (per-post robots meta, then the post type default). */
    public static function noindex($post_id, $post_type)
    {
        $robots = get_post_meta($post_id, 'rank_math_robots', true);
        if (is_array($robots) && $robots) {
            return in_array('noindex', $robots, true);
        }
        if (is_string($robots) && $robots !== '') {
            return strpos($robots, 'noindex') !== false;
        }
        static $titles = null;
        if ($titles === null) {
            $titles = get_option('rank-math-options-titles', array());
            $titles = is_array($titles) ? $titles : array();
        }
        if (($titles['pt_' . $post_type . '_custom_robots'] ?? '') === 'on') {
            return in_array('noindex', (array) ($titles['pt_' . $post_type . '_robots'] ?? array()), true);
        }
        return in_array('noindex', (array) ($titles['robots_global'] ?? array()), true);
    }

    public static function term_noindex($term_id)
    {
        $robots = get_term_meta($term_id, 'rank_math_robots', true);
        return is_array($robots) ? in_array('noindex', $robots, true) : (is_string($robots) && strpos($robots, 'noindex') !== false);
    }

    /** Published, public, not password-protected, not excluded, not noindex, not hidden from the catalog. */
    public static function is_public($p)
    {
        $p = $p instanceof WP_Post ? $p : get_post($p);
        if (!$p || $p->post_status !== 'publish' || $p->post_password !== '' || !in_array($p->post_type, self::types(), true)) {
            return false;
        }
        if (in_array((int) $p->ID, self::excluded_ids(), true) || self::noindex($p->ID, $p->post_type)) {
            return false;
        }
        if ($p->post_type === 'product' && taxonomy_exists('product_visibility')
            && has_term('exclude-from-catalog', 'product_visibility', $p) && has_term('exclude-from-search', 'product_visibility', $p)) {
            return false;
        }
        return (bool) apply_filters('gai_is_public', true, $p);
    }

    /* =====================================================================
     * Text helpers
     * ================================================================== */

    /** One-line plain text, cut on a word boundary. */
    public static function short($text, $max = 160)
    {
        $text = html_entity_decode(wp_strip_all_tags(strip_shortcodes((string) $text)), ENT_QUOTES, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 1);
        $pos = mb_strrpos($cut, ' ');
        if ($pos !== false && $pos > $max * 0.6) {
            $cut = mb_substr($cut, 0, $pos);
        }
        return preg_replace('/[\s،,;:؛.\-–—]+$/u', '', $cut) . '…';
    }

    public static function words($text)
    {
        return (int) preg_match_all('/[\p{L}\p{N}]+/u', wp_strip_all_tags((string) $text));
    }

    /** Rank Math description with variables resolved, or ''. */
    public static function rm_description(WP_Post $p)
    {
        $d = trim((string) get_post_meta($p->ID, 'rank_math_description', true));
        if ($d !== '' && strpos($d, '%') !== false) {
            if (class_exists('\\RankMath\\Helper') && method_exists('\\RankMath\\Helper', 'replace_vars')) {
                try {
                    $d = (string) \RankMath\Helper::replace_vars($d, $p);
                } catch (Throwable $e) {
                    $d = '';
                }
            }
            $d = trim(preg_replace('/%[a-z_]+(\([^)]*\))?%/i', '', $d));
        }
        return $d;
    }

    /** Best one-line description: Rank Math → TL;DR (posts) → excerpt/short description → content. */
    public static function description(WP_Post $p, $max = 180)
    {
        $d = self::rm_description($p);
        if ($d === '' && $p->post_type === 'post') {
            $d = (string) get_post_meta($p->ID, 'gl_tldr', true);
        }
        if ($d === '') {
            $d = $p->post_excerpt; // product short description lives here too
        }
        if (trim(wp_strip_all_tags($d)) === '') {
            $d = preg_replace('#<(script|style)[^>]*>.*?</\1>#si', '', (string) $p->post_content);
        }
        return self::short($d, $max);
    }

    /** Markdown twin URL: /a/b/ → /a/b.md, home → /index.md. */
    public static function md_url($url)
    {
        $url  = preg_replace('/[?#].*$/', '', (string) $url);
        $home = trailingslashit(home_url('/'));
        if (trailingslashit($url) === $home) {
            return $home . 'index.md';
        }
        return untrailingslashit($url) . '.md';
    }

    /** Rows of an ACF question/answer repeater (works without ACF too). */
    public static function faq_rows($post_id, $field = 'faq_list')
    {
        $rows = array();
        if (self::acf()) {
            $v = get_field($field, $post_id);
            if (is_array($v)) {
                foreach ($v as $r) {
                    if (is_array($r) && !empty($r['question']) && !empty($r['answer'])) {
                        $rows[] = array('q' => (string) $r['question'], 'a' => (string) $r['answer']);
                    }
                }
            }
            return $rows;
        }
        $n = (int) get_post_meta($post_id, $field, true);
        for ($i = 0; $i < $n; $i++) {
            $q = (string) get_post_meta($post_id, $field . '_' . $i . '_question', true);
            $a = (string) get_post_meta($post_id, $field . '_' . $i . '_answer', true);
            if ($q !== '' && $a !== '') {
                $rows[] = array('q' => $q, 'a' => $a);
            }
        }
        return $rows;
    }

    /** FAQ page groups: [ [title, rows[]] ] from ACF faq_cat → faq_list. */
    public static function faq_groups($page_id)
    {
        $groups = array();
        if (self::acf()) {
            $cats = get_field('faq_cat', $page_id);
            foreach ((array) $cats as $c) {
                $rows = array();
                foreach ((array) ($c['faq_list'] ?? array()) as $r) {
                    if (!empty($r['question']) && !empty($r['answer'])) {
                        $rows[] = array('q' => (string) $r['question'], 'a' => (string) $r['answer']);
                    }
                }
                if ($rows) {
                    $groups[] = array('title' => (string) ($c['title'] ?? ''), 'rows' => $rows);
                }
            }
            return $groups;
        }
        $n = (int) get_post_meta($page_id, 'faq_cat', true);
        for ($i = 0; $i < $n; $i++) {
            $rows = self::faq_rows($page_id, 'faq_cat_' . $i . '_faq_list');
            if ($rows) {
                $groups[] = array('title' => (string) get_post_meta($page_id, 'faq_cat_' . $i . '_title', true), 'rows' => $rows);
            }
        }
        return $groups;
    }

    /** The published page using a given template (first match), or null. */
    public static function page_by_template($template)
    {
        $ids = get_posts(array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
            'meta_value'     => $template, // phpcs:ignore WordPress.DB.SlowDBQuery
        ));
        return $ids ? get_post((int) $ids[0]) : null;
    }

    /** Store refund/returns policy page. */
    public static function refund_page()
    {
        $id = (int) get_option('woocommerce_refund_returns_page_id');
        $p  = $id ? get_post($id) : null;
        if (!$p || $p->post_status !== 'publish') {
            $p = get_page_by_path('refund_returns');
        }
        return ($p && $p->post_status === 'publish') ? $p : null;
    }

    /* =====================================================================
     * Storage
     * ================================================================== */

    public static function dir()
    {
        $up = wp_upload_dir(null, false);
        return trailingslashit($up['basedir']) . 'gilllda-ai';
    }

    public static function path($file)
    {
        return self::dir() . '/' . $file;
    }

    public static function ensure_dir()
    {
        $dir = self::dir();
        if (!is_dir($dir . '/md')) {
            wp_mkdir_p($dir . '/md');
        }
        if (!file_exists($dir . '/index.php')) {
            @file_put_contents($dir . '/index.php', "<?php\n// Silence is golden.\n"); // phpcs:ignore
            @file_put_contents($dir . '/md/index.php', "<?php\n// Silence is golden.\n"); // phpcs:ignore
            // Files are always served through PHP (correct headers, ETag); never directly.
            @file_put_contents($dir . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n"); // phpcs:ignore
        }
        return is_dir($dir) && wp_is_writable($dir);
    }

    /** Atomic write; skipped when unchanged (keeps Last-Modified/ETag stable for crawlers). */
    public static function write($file, $content)
    {
        if (is_readable($file) && md5_file($file) === md5($content)) {
            return true;
        }
        $tmp = $file . '.' . wp_generate_password(6, false) . '.tmp';
        if (@file_put_contents($tmp, $content, LOCK_EX) === false) { // phpcs:ignore
            return false;
        }
        if (!@rename($tmp, $file)) { // phpcs:ignore
            @unlink($tmp); // phpcs:ignore
            return false;
        }
        return true;
    }

    public static function builder()
    {
        require_once GAI_DIR . 'includes/class-gai-markdown.php';
        require_once GAI_DIR . 'includes/class-gai-builder.php';
        return new GAI_Builder(self::settings());
    }

    /* =====================================================================
     * Debounced regeneration
     * ================================================================== */

    /** Mark content as changed and schedule one rebuild DELAY seconds from now (once per request). */
    public static function touch()
    {
        if (self::$touched) {
            return;
        }
        self::$touched = true;
        update_option(self::OPT_CHANGED, time(), false);
        delete_transient('gai_excluded');
        $when = time() + self::DELAY;
        $next = wp_next_scheduled(self::HOOK_BUILD);
        if ($next && $next >= $when - 10) {
            return;
        }
        if ($next) {
            wp_unschedule_event($next, self::HOOK_BUILD);
        }
        wp_schedule_single_event($when, self::HOOK_BUILD);
    }

    private static function relevant_type($type)
    {
        return in_array($type, array('product', 'product_variation', 'post', 'page'), true) || in_array($type, self::types(), true);
    }

    public static function on_status($new, $old, $post)
    {
        if (($new === 'publish' || $old === 'publish') && $post instanceof WP_Post && self::relevant_type($post->post_type)
            && !wp_is_post_revision($post) && !wp_is_post_autosave($post)) {
            self::touch();
        }
    }

    public static function on_post_id($post_id)
    {
        $p = get_post($post_id);
        if ($p && self::relevant_type($p->post_type)) {
            self::touch();
        }
    }

    public static function on_meta($meta_id, $object_id, $key)
    {
        if (!self::$touched && in_array($key, self::WATCHED_META, true)) {
            $p = get_post($object_id);
            if ($p && in_array($p->post_status, array('publish', 'private'), true) && self::relevant_type($p->post_type)) {
                self::touch();
            }
        }
    }

    public static function on_term($term_id, $tt_id, $taxonomy)
    {
        if (in_array($taxonomy, array('product_cat', 'product_tag', 'pa_color', 'category', 'post_tag'), true)) {
            self::touch();
        }
    }

    public static function on_option($name)
    {
        if (in_array($name, array('blogname', 'blogdescription', 'woocommerce_currency', 'page_on_front', 'page_for_posts'), true)
            || preg_match('/^woocommerce_[a-z_]+_\d+_settings$/', (string) $name)) {
            self::touch();
        }
    }

    public static function on_acf_save($post_id)
    {
        if (is_string($post_id) && preg_match('/^options?$/', $post_id)) {
            self::touch();
        }
    }

    /** Build llms.txt + llms-full.txt (+ readiness audit) in the background. */
    public static function rebuild()
    {
        if (!add_option(self::OPT_LOCK, time(), '', false)) {
            if (time() - (int) get_option(self::OPT_LOCK) < 180) {
                return false;
            }
            update_option(self::OPT_LOCK, time(), false);
        }
        $t0 = microtime(true);
        if (function_exists('set_time_limit')) {
            @set_time_limit(180); // phpcs:ignore
        }
        wp_raise_memory_limit('admin');

        $error = '';
        $b     = null;
        $txt   = '';
        $full  = '';
        try {
            $b    = self::builder();
            $txt  = $b->llms_txt();
            $full = $b->llms_full();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $written = false;
        if ($error === '') {
            if (self::ensure_dir()) {
                $written = self::write(self::path('llms.txt'), $txt) && self::write(self::path('llms-full.txt'), $full);
            }
            if (!$written) {
                $error = sprintf('پوشه %s قابل نوشتن نیست؛ فایل‌ها هنگام درخواست در حافظه ساخته می‌شوند.', self::dir());
            }
            // Drop per-URL Markdown cached before the last content change.
            $changed = (int) get_option(self::OPT_CHANGED);
            foreach ((array) glob(self::dir() . '/md/*.md') as $f) {
                if ($f && filemtime($f) < $changed) {
                    @unlink($f); // phpcs:ignore
                }
            }
        }

        update_option(self::OPT_STATE, array(
            'generated' => time(),
            'duration'  => (int) round((microtime(true) - $t0) * 1000),
            'error'     => $error,
            'txt'       => array('bytes' => strlen($txt), 'words' => self::words($txt)),
            'full'      => array('bytes' => strlen($full), 'words' => self::words($full)),
            'counts'    => $b ? ($b->stats['counts'] ?? array()) : array(),
            'truncated' => $b ? (int) ($b->stats['truncated'] ?? 0) : 0,
            'skipped'   => $b ? (int) ($b->stats['skipped'] ?? 0) : 0,
            'version'   => GAI_VERSION,
        ), false);

        // Readiness audit piggybacks on the same background run.
        try {
            require_once GAI_DIR . 'includes/class-gai-audit.php';
            GAI_Audit::refresh();
        } catch (Throwable $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
        }

        wp_clear_scheduled_hook(self::HOOK_BUILD);
        delete_option(self::OPT_LOCK);
        return $written;
    }

    public static function daily()
    {
        GAI_Tracker::prune();
    }

    /* =====================================================================
     * Install / upgrade / activation
     * ================================================================== */

    public static function install()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table   = $wpdb->prefix . 'gai_hits';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  day date NOT NULL,
  kind varchar(8) NOT NULL DEFAULT 'bot',
  bot varchar(40) NOT NULL DEFAULT '',
  path varchar(190) NOT NULL DEFAULT '',
  hits int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY day_kind_bot_path (day,kind,bot,path),
  KEY kind_day (kind,day)
) {$charset};");

        if (get_option(self::OPT_SETTINGS) === false) {
            $s = self::defaults();
            require_once GAI_DIR . 'includes/class-gai-markdown.php';
            require_once GAI_DIR . 'includes/class-gai-builder.php';
            $s['intro'] = GAI_Builder::auto_intro();
            if (self::acf()) {
                $s['phone'] = is_string($v = get_field('phone', 'option')) ? sanitize_text_field($v) : '';
                $s['email'] = is_string($v = get_field('email', 'option')) ? sanitize_email($v) : '';
            }
            add_option(self::OPT_SETTINGS, $s, '', true);
            self::$settings = null;
        }
        if (!wp_next_scheduled(self::HOOK_DAILY)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::HOOK_DAILY);
        }
        update_option(self::OPT_VERSION, GAI_VERSION . '/' . self::DB_VERSION, true);
    }

    public static function maybe_upgrade()
    {
        if (get_option(self::OPT_VERSION) !== GAI_VERSION . '/' . self::DB_VERSION) {
            self::install();
            GAI_Router::add_rules();
            flush_rewrite_rules(false);
            self::touch();
        }
    }

    public static function activate()
    {
        self::install();
        GAI_Router::add_rules();
        flush_rewrite_rules(false);
        self::ensure_dir();
        wp_clear_scheduled_hook(self::HOOK_BUILD);
        wp_schedule_single_event(time() + 5, self::HOOK_BUILD);
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook(self::HOOK_BUILD);
        wp_clear_scheduled_hook(self::HOOK_DAILY);
        delete_option(self::OPT_LOCK);
        // Our rules were added on init during this request; drop the cache so they are rebuilt without us.
        delete_option('rewrite_rules');
    }
}
