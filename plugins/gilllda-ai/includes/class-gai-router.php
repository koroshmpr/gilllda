<?php
/**
 * Routing and delivery: /llms.txt, /llms-full.txt, {permalink}.md, ?format=md, Accept: text/markdown,
 * plus <link rel="alternate" type="text/markdown"> and the matching Link header on HTML pages.
 *
 * Normal page view cost: one parse_url + a few string compares on wp_loaded, and one cheap
 * check in template_redirect / wp_head.
 *
 * @package GillldaAI
 */

if (!defined('ABSPATH')) {
    exit;
}

final class GAI_Router
{
    /** @var false|string false | 'md' (explicit .md / ?format=md) | 'accept' (content negotiation). */
    private static $md = false;

    /** @var string|null File requested by path (llms.txt / llms-full.txt). */
    private static $file = null;

    public static function boot()
    {
        add_action('init', array(__CLASS__, 'add_rules'));
        add_filter('query_vars', array(__CLASS__, 'query_vars'));
        add_action('wp_loaded', array(__CLASS__, 'detect'), 0);
        add_action('parse_request', array(__CLASS__, 'parse_request'), 0);
        add_action('template_redirect', array(__CLASS__, 'template_redirect'), 0);
        add_action('wp_head', array(__CLASS__, 'head_link'), 3);
    }

    public static function add_rules()
    {
        add_rewrite_rule('^llms\.txt$', 'index.php?gai_file=llms', 'top');
        add_rewrite_rule('^llms-full\.txt$', 'index.php?gai_file=full', 'top');
    }

    public static function query_vars($vars)
    {
        $vars[] = 'gai_file';
        return $vars;
    }

    public static function is_md_request()
    {
        return (bool) self::$md;
    }

    private static function base_path()
    {
        return rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
    }

    /** Current request path relative to the site root, without query string. */
    public static function rel_path()
    {
        $path = (string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $base = self::base_path();
        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }
        return $path === '' ? '/' : $path;
    }

    /** wp_loaded: detect our URLs before WordPress parses the request. */
    public static function detect()
    {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('WP_CLI') && WP_CLI)) {
            return;
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'GET' && $method !== 'HEAD') {
            return;
        }
        $rel = self::rel_path();
        if ($rel === '/llms.txt' || $rel === '/llms-full.txt') {
            self::$file = $rel === '/llms.txt' ? 'llms' : 'full';
            return;
        }
        if (!GAI_Core::get('md')) {
            return;
        }
        if (substr($rel, -3) === '.md' && strlen($rel) > 3) {
            $uri   = (string) ($_SERVER['REQUEST_URI'] ?? '');
            $query = (string) wp_parse_url($uri, PHP_URL_QUERY);
            $new   = preg_replace('#(/index(\.html?)?)?\.md$#', '', $rel);
            $_SERVER['REQUEST_URI'] = self::base_path() . ($new === '' ? '/' : $new . '/') . ($query !== '' ? '?' . $query : '');
            self::$md = 'md';
        } elseif (isset($_GET['format']) && $_GET['format'] === 'md') { // phpcs:ignore WordPress.Security.NonceVerification
            self::$md = 'md';
        } elseif (GAI_Core::get('md_accept') && self::accept_prefers_markdown()) {
            self::$md = 'accept';
        }
        if (self::$md) {
            add_filter('redirect_canonical', '__return_false', 99);
        }
    }

    /** Accept header lists text/markdown before (or instead of) text/html. */
    private static function accept_prefers_markdown()
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        if ($accept === '' || strpos($accept, 'text/markdown') === false) {
            return false;
        }
        $html = strpos($accept, 'text/html');
        return $html === false || strpos($accept, 'text/markdown') < $html;
    }

    /** parse_request: serve llms files before the main query, canonical redirects and other plugins (Rank Math's llms.txt). */
    public static function parse_request($wp)
    {
        $file = self::$file;
        if (!$file && !empty($wp->query_vars['gai_file'])) {
            $file = $wp->query_vars['gai_file'] === 'full' ? 'full' : 'llms';
        }
        if (!$file) {
            return;
        }
        $enabled = $file === 'full' ? GAI_Core::get('llms_full') : GAI_Core::get('llms_txt');
        if (!$enabled) {
            if ($file === 'llms') {
                return; // let WordPress (or Rank Math's own llms.txt) handle it
            }
            self::plain_error(404, "Not found\n");
        }
        GAI_Tracker::note_file($file === 'full' ? 'llms-full.txt' : 'llms.txt');
        self::serve_file($file === 'full' ? 'llms-full.txt' : 'llms.txt');
    }

    private static function plain_error($code, $body)
    {
        status_header($code);
        nocache_headers();
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex');
        echo $body; // phpcs:ignore WordPress.Security.EscapeOutput
        exit;
    }

    /** True when the client already has this version. */
    private static function not_modified($etag, $mtime)
    {
        $inm = trim(wp_unslash((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')));
        if ($inm !== '') {
            if ($inm === '*') {
                return true;
            }
            return in_array($etag, array_map('trim', explode(',', str_replace('W/', '', $inm))), true);
        }
        $ims = (string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '');
        return $mtime && $ims !== '' && ($t = strtotime($ims)) && $t >= $mtime;
    }

    /**
     * Send a text response with validators and exit.
     *
     * @param string $body      Body, or a file path when $is_file.
     * @param array  $extra     Extra headers.
     */
    private static function send($body, $type, $mtime, $etag, array $extra = array(), $is_file = false)
    {
        if (function_exists('header_remove')) {
            header_remove('Expires');
            header_remove('Pragma');
            header_remove('X-Pingback');
            header_remove('Link');
            header_remove('Set-Cookie');
        }
        status_header(200);
        header('Content-Type: ' . $type . '; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=3600, stale-while-revalidate=86400');
        header('Access-Control-Allow-Origin: *');
        header('ETag: ' . $etag);
        if ($mtime) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        }
        foreach ($extra as $h) {
            header($h, false);
        }
        if (self::not_modified($etag, $mtime)) {
            status_header(304);
            exit;
        }
        $len = $is_file ? (int) filesize($body) : strlen($body);
        header('Content-Length: ' . $len);
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
            if ($is_file) {
                readfile($body); // phpcs:ignore
            } else {
                echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- plain text / markdown response
            }
        }
        exit;
    }

    private static function serve_file($name)
    {
        $path  = GAI_Core::path($name);
        $stale = !is_readable($path);
        if (!$stale) {
            // WP-Cron not running: rebuild inline once the event is 10 min overdue.
            $next  = wp_next_scheduled(GAI_Core::HOOK_BUILD);
            $stale = $next && $next < time() - 600;
        }
        if ($stale) {
            GAI_Core::rebuild();
            clearstatcache(true, $path);
        }
        $extra = array('X-Robots-Tag: noindex', 'Link: <' . esc_url_raw(home_url('/')) . '>; rel="describes"');
        if (is_readable($path)) {
            $mtime = (int) filemtime($path);
            self::send($path, 'text/plain', $mtime, sprintf('"%x-%x"', $mtime, filesize($path)), $extra, true);
        }
        $b    = GAI_Core::builder();
        $body = $name === 'llms.txt' ? $b->llms_txt() : $b->llms_full();
        self::send($body, 'text/plain', 0, '"' . md5($body) . '"', $extra);
    }

    /** template_redirect: Markdown twin of the resolved page; Link header on HTML pages. */
    public static function template_redirect()
    {
        if (!self::$md) {
            self::link_header();
            return;
        }
        $doc = is_404() ? null : self::current_doc();
        if (!$doc) {
            if (self::$md === 'accept') {
                return; // content negotiation only: fall back to HTML silently
            }
            status_header(404);
            nocache_headers();
            header('Content-Type: text/markdown; charset=utf-8');
            header('X-Robots-Tag: noindex');
            echo "# 404\n\nاین آدرس نسخه Markdown ندارد. فهرست صفحه‌ها: " . esc_url_raw(home_url('/llms.txt')) . "\n"; // phpcs:ignore
            exit;
        }
        GAI_Tracker::note_file('md');
        $extra = array('X-Robots-Tag: noindex', 'Link: <' . esc_url_raw($doc['canonical']) . '>; rel="canonical"');
        if (self::$md === 'accept') {
            $extra[] = 'Vary: Accept';
        }
        self::send($doc['body'], 'text/markdown', (int) $doc['modified'], '"' . md5($doc['body']) . '"', $extra);
    }

    /** Markdown for the current query; single documents are cached on disk until the next content change. */
    private static function current_doc()
    {
        if (is_front_page() && !is_paged()) {
            return GAI_Core::builder()->archive_doc(); // home → the llms.txt overview
        }
        if (is_singular()) {
            $p = get_queried_object();
            if (!($p instanceof WP_Post) || !GAI_Core::is_public($p)) {
                return null;
            }
            $modified = max((int) strtotime($p->post_modified_gmt . ' UTC'), (int) get_option(GAI_Core::OPT_CHANGED));
            $file     = GAI_Core::path('md/' . (int) $p->ID . '.md');
            if (is_readable($file) && filemtime($file) >= $modified) {
                $body = (string) file_get_contents($file); // phpcs:ignore
            } else {
                $body = GAI_Core::builder()->single_doc($p);
                if (GAI_Core::ensure_dir()) {
                    GAI_Core::write($file, $body);
                }
            }
            return array('body' => $body, 'canonical' => get_permalink($p), 'modified' => $modified);
        }
        if (is_paged()) {
            return null;
        }
        return GAI_Core::builder()->archive_doc();
    }

    /** URL of the Markdown twin for the current HTML page, or ''. */
    private static function current_md_url()
    {
        static $url = null;
        if ($url !== null) {
            return $url;
        }
        $url = '';
        if (!GAI_Core::get('md') || is_paged() || is_404() || is_search() || (function_exists('gl_is_landing') && gl_is_landing())) {
            return $url;
        }
        if (is_front_page()) {
            $url = GAI_Core::md_url(home_url('/'));
        } elseif (is_singular()) {
            $p = get_queried_object();
            if ($p instanceof WP_Post && GAI_Core::is_public($p)) {
                $url = GAI_Core::md_url(get_permalink($p));
            }
        } elseif ((function_exists('is_shop') && is_shop()) || is_tax('product_cat') || is_category() || (is_home() && get_option('page_for_posts'))) {
            $l = is_home() ? get_permalink((int) get_option('page_for_posts')) : (is_category() || is_tax() ? get_term_link(get_queried_object()) : get_permalink(wc_get_page_id('shop')));
            $url = ($l && !is_wp_error($l)) ? GAI_Core::md_url($l) : '';
        }
        return $url;
    }

    private static function link_header()
    {
        if (!GAI_Core::get('md_head') || headers_sent()) {
            return;
        }
        $url = self::current_md_url();
        if ($url) {
            header('Link: <' . esc_url_raw($url) . '>; rel="alternate"; type="text/markdown"', false);
        }
    }

    public static function head_link()
    {
        if (!GAI_Core::get('md_head')) {
            return;
        }
        $url = self::current_md_url();
        if ($url) {
            echo '<link rel="alternate" type="text/markdown" title="Markdown" href="' . esc_url($url) . '">' . "\n";
        }
    }
}
