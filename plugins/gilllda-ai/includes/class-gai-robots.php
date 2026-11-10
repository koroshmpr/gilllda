<?php
/**
 * AI crawler policy in robots.txt.
 *
 * Hooks the core `robots_txt` filter late (after Rank Math, which replaces the whole content at priority 10
 * when its robots.txt editor is used) and Rank Math's `rank_math/frontend/robots_txt` too. A BEGIN/END
 * marker guarantees the block is appended exactly once, whichever filter runs.
 *
 * A named User-agent group replaces the `*` group for that bot, so allowed AI bots get the `*` Disallow
 * rules mirrored plus the store's private paths (cart/checkout/account/admin).
 *
 * @package GillldaAI
 */

if (!defined('ABSPATH')) {
    exit;
}

final class GAI_Robots
{
    const BEGIN = '# BEGIN Gilllda AI Visibility';
    const END   = '# END Gilllda AI Visibility';

    public static function boot()
    {
        add_filter('robots_txt', array(__CLASS__, 'filter'), 100, 2);
        add_filter('rank_math/frontend/robots_txt', array(__CLASS__, 'filter'), 100, 1);
    }

    public static function filter($output, $public = 1)
    {
        $output = (string) $output;
        if ((string) $public === '0' || !get_option('blog_public') || strpos($output, self::BEGIN) !== false) {
            return $output;
        }
        $block = self::block($output);
        if ($block === '') {
            return $output;
        }
        $output = rtrim($output) . "\n\n" . $block;
        if (!preg_match('/^\s*sitemap\s*:/im', $output)) {
            $output .= "\nSitemap: " . self::sitemap_url() . "\n";
        }
        return $output;
    }

    private static function sitemap_url()
    {
        if (GAI_Core::rank_math() && class_exists('\\RankMath\\Helper') && method_exists('\\RankMath\\Helper', 'is_module_active') && \RankMath\Helper::is_module_active('sitemap')) {
            return home_url('/sitemap_index.xml');
        }
        return home_url('/wp-sitemap.xml');
    }

    /** Path of a page relative to the site root ('' when unknown). */
    private static function page_path($id)
    {
        if ($id <= 0 || get_post_status($id) !== 'publish') {
            return '';
        }
        $path = (string) wp_parse_url(get_permalink($id), PHP_URL_PATH);
        return $path !== '' && $path !== '/' ? trailingslashit($path) : '';
    }

    /** Private paths every AI bot must skip. */
    public static function private_paths()
    {
        $paths = array();
        if (function_exists('wc_get_page_id')) {
            foreach (array('cart', 'checkout', 'myaccount') as $p) {
                $paths[] = self::page_path((int) wc_get_page_id($p));
            }
        }
        foreach (array('login', 'compare') as $slug) {
            $p = get_page_by_path($slug);
            if ($p) {
                $paths[] = self::page_path((int) $p->ID);
            }
        }
        $base    = rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
        $paths[] = $base . '/wp-admin/';
        $paths[] = $base . '/wp-login.php';
        $paths[] = '/*?add-to-cart=';
        $paths[] = '/*&add-to-cart=';
        $paths[] = '/*?remove_item=';
        $paths[] = '/*?s=';
        foreach (preg_split('/\r\n|\r|\n/', (string) GAI_Core::get('robots_extra')) as $l) {
            $l = trim($l);
            if ($l !== '' && ($l[0] === '/' || $l[0] === '*')) {
                $paths[] = $l;
            }
        }
        return array_values(array_unique(array_filter($paths)));
    }

    /** Disallow lines of the existing `User-agent: *` group. */
    private static function star_disallows($robots)
    {
        $rules   = array();
        $in_star = false;
        $seen    = false;
        foreach (preg_split('/\r\n|\r|\n/', (string) $robots) as $line) {
            $l = trim(preg_replace('/#.*$/', '', $line));
            if ($l === '') {
                continue;
            }
            if (preg_match('/^user-agent\s*:\s*(.+)$/i', $l, $m)) {
                if ($seen) {
                    $in_star = false;
                    $seen    = false;
                }
                $in_star = $in_star || trim($m[1]) === '*';
                continue;
            }
            if (preg_match('/^(allow|disallow)\s*:\s*(.*)$/i', $l, $m)) {
                $seen = true;
                if ($in_star && trim($m[2]) !== '' && trim($m[2]) !== '/') {
                    $rules[] = array(ucfirst(strtolower($m[1])), trim($m[2]));
                }
            }
        }
        return $rules;
    }

    public static function block($robots = '')
    {
        if (!GAI_Core::get('robots')) {
            return '';
        }
        $policy = (array) GAI_Core::get('bots');
        $allow  = array();
        $deny   = array();
        foreach (array_keys(GAI_Core::bots()) as $ua) {
            $p = $policy[$ua] ?? 'allow';
            if ($p === 'disallow') {
                $deny[] = $ua;
            } elseif ($p === 'allow') {
                $allow[] = $ua;
            }
        }
        $site = untrailingslashit(home_url('/'));
        $out  = self::BEGIN . "\n";
        if (GAI_Core::get('llms_txt')) {
            $out .= '# LLMs: ' . $site . "/llms.txt\n";
        }
        if (GAI_Core::get('llms_full')) {
            $out .= '# LLMs-full: ' . $site . "/llms-full.txt\n";
        }
        if (GAI_Core::get('md')) {
            $out .= "# Markdown version of any page: append .md to its URL\n";
        }
        if ($allow) {
            $lines = array();
            foreach (self::star_disallows($robots) as $r) {
                $lines[$r[0] . ': ' . $r[1]] = 1;
            }
            foreach (self::private_paths() as $p) {
                $lines['Disallow: ' . $p] = 1;
            }
            $out .= "\n";
            foreach ($allow as $ua) {
                $out .= 'User-agent: ' . $ua . "\n";
            }
            $out .= "Allow: /\n" . implode("\n", array_keys($lines)) . "\n";
        }
        if ($deny) {
            $out .= "\n";
            foreach ($deny as $ua) {
                $out .= 'User-agent: ' . $ua . "\n";
            }
            $out .= "Disallow: /\n";
        }
        return $out . self::END . "\n";
    }
}
