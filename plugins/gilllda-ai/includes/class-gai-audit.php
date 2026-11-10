<?php
/**
 * Content readiness audit for AI answers: per item score (0–100) and issues.
 * Computed in the background rebuild (or on demand from the admin page) and stored in one non-autoloaded option.
 *
 * @package GillldaAI
 */

if (!defined('ABSPATH')) {
    exit;
}

final class GAI_Audit
{
    const THIN_TEXT    = 300;
    const THIN_PRODUCT = 100;

    /** Issue key => [label, weight by type]. */
    public static function issues()
    {
        return array(
            'noindex' => array('noindex در Rank Math (در فایل‌های AI نمی‌آید)', array()),
            'desc'    => array('بدون توضیحات متا (Rank Math)', array('post' => 15, 'page' => 10, 'product' => 6)),
            'thumb'   => array('بدون تصویر شاخص', array('post' => 15, 'page' => 5, 'product' => 20)),
            'thin'    => array('محتوای کم', array('post' => 20, 'page' => 10, 'product' => 15)),
            'short'   => array('محصول بدون توضیح کوتاه', array('product' => 15)),
            'price'   => array('محصول بدون قیمت', array('product' => 15)),
            'tldr'    => array('بدون «خلاصه در ۳۰ ثانیه» (gl_tldr)', array('post' => 10)),
            'take'    => array('بدون «نکات کلیدی» (gl_takeaways)', array('post' => 5)),
            'faq'     => array('بدون پرسش‌های متداول', array('post' => 10, 'product' => 5)),
            'alt'     => array('تصویر بدون متن جایگزین (alt)', array('post' => 10, 'page' => 5, 'product' => 10)),
            'links'   => array('بدون لینک داخلی', array('post' => 10, 'page' => 5, 'product' => 5)),
        );
    }

    public static function get()
    {
        $a = get_option(GAI_Core::OPT_AUDIT, array());
        return is_array($a) ? $a : array();
    }

    public static function refresh()
    {
        $types = array_values(array_intersect(array('product', 'post', 'page'), GAI_Core::types()));
        $rows  = array();
        if ($types) {
            $posts = get_posts(array('post_type' => $types, 'post_status' => 'publish', 'posts_per_page' => 3000, 'no_found_rows' => true, 'suppress_filters' => true));
            update_meta_cache('post', wp_list_pluck($posts, 'ID'));
            $excluded = GAI_Core::excluded_ids();
            foreach ($posts as $p) {
                if ($p->post_password !== '' || in_array((int) $p->ID, $excluded, true)) {
                    continue;
                }
                $rows[] = self::check($p);
            }
        }
        usort($rows, function ($a, $b) {
            return $a['s'] <=> $b['s'];
        });
        $scored = array_filter($rows, function ($r) {
            return empty($r['x']);
        });
        $avg = $scored ? (int) round(array_sum(array_column($scored, 's')) / count($scored)) : 0;
        $data = array('time' => time(), 'score' => $avg, 'rows' => $rows);
        update_option(GAI_Core::OPT_AUDIT, $data, false);
        return $data;
    }

    /** @return array{id:int,t:string,ti:string,s:int,i:array,x?:int} */
    public static function check(WP_Post $p)
    {
        $type    = $p->post_type;
        $issues  = array();
        $content = (string) $p->post_content;
        $tpl     = $type === 'page' ? get_page_template_slug($p) : '';
        $custom  = $tpl !== '' && $tpl !== 'default'; // template-driven pages (ACF): skip body checks

        $noindex = GAI_Core::noindex($p->ID, $type);
        if ($noindex) {
            $issues['noindex'] = '';
        }
        if (trim((string) get_post_meta($p->ID, 'rank_math_description', true)) === '') {
            $issues['desc'] = '';
        }
        $thumb = (int) get_post_thumbnail_id($p);
        if (!$thumb && !$custom) {
            $issues['thumb'] = '';
        }

        $text  = $content . ($type === 'product' ? ' ' . $p->post_excerpt : '');
        $words = GAI_Core::words(strip_shortcodes($text));
        $min   = $type === 'product' ? self::THIN_PRODUCT : self::THIN_TEXT;
        if (!$custom && $words < $min) {
            $issues['thin'] = $words . ' کلمه';
        }

        if ($type === 'product') {
            if (trim(wp_strip_all_tags((string) $p->post_excerpt)) === '') {
                $issues['short'] = '';
            }
            if (GAI_Core::wc() && ($prod = wc_get_product($p->ID))) {
                $price = $prod->is_type('variable') ? $prod->get_variation_price('min') : $prod->get_price();
                if ($price === '' || $price === null) {
                    $issues['price'] = '';
                }
            }
        }
        if ($type === 'post') {
            if (trim((string) get_post_meta($p->ID, 'gl_tldr', true)) === '') {
                $issues['tldr'] = '';
            }
            if (trim((string) get_post_meta($p->ID, 'gl_takeaways', true)) === '') {
                $issues['take'] = '';
            }
        }
        if (in_array($type, array('post', 'product'), true) && !GAI_Core::faq_rows($p->ID)) {
            $issues['faq'] = '';
        }

        // Alt texts: content images + featured image + product gallery.
        $missing = 0;
        if (preg_match_all('/<img\b[^>]*>/i', $content, $m)) {
            foreach ($m[0] as $tag) {
                if (!preg_match('/\balt\s*=\s*(["\'])\s*[^"\'\s][^"\']*\1/i', $tag)) {
                    $missing++;
                }
            }
        }
        $imgs = $thumb ? array($thumb) : array();
        if ($type === 'product') {
            $imgs = array_merge($imgs, array_filter(array_map('intval', explode(',', (string) get_post_meta($p->ID, '_product_image_gallery', true)))));
        }
        foreach (array_unique($imgs) as $img) {
            if (trim((string) get_post_meta($img, '_wp_attachment_image_alt', true)) === '') {
                $missing++;
            }
        }
        if ($missing) {
            $issues['alt'] = $missing . ' تصویر';
        }

        if (!$custom && !self::has_internal_link($content)) {
            $issues['links'] = '';
        }

        $score = 100;
        $defs  = self::issues();
        foreach (array_keys($issues) as $k) {
            $score -= (int) ($defs[$k][1][$type] ?? 0);
        }
        $row = array('id' => (int) $p->ID, 't' => $type, 'ti' => wp_strip_all_tags(get_the_title($p)), 's' => max(0, $score), 'i' => $issues, 'w' => $words);
        if ($noindex) {
            $row['x'] = 1;
        }
        return $row;
    }

    private static function has_internal_link($html)
    {
        if (!preg_match_all('/<a\b[^>]*href\s*=\s*(["\'])([^"\']+)\1/i', (string) $html, $m)) {
            return false;
        }
        $host = wp_parse_url(home_url('/'), PHP_URL_HOST);
        foreach ($m[2] as $href) {
            $h = wp_parse_url($href, PHP_URL_HOST);
            if (($h === null && strpos($href, '/') === 0) || ($h && strcasecmp($h, $host) === 0)) {
                return true;
            }
        }
        return false;
    }
}
