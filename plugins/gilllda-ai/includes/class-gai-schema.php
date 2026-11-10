<?php
/**
 * Structured-data gaps, filled through Rank Math's own graph (`rank_math/json_ld`) so nothing is duplicated:
 *  - FAQPage from ACF faq_list — only when neither Rank Math nor the theme already outputs one
 *    (the Gilllda theme prints FAQPage itself in template-parts/global/faq-list.php and faq.php → "auto" skips).
 *  - Organization → OnlineStore enrichment (contactPoint, telephone, email, sameAs, return policy) — missing keys only.
 *  - speakable on blog articles.
 * Runs at priority 120, after the theme's own fixes (theme/inc/seo.php at 99), and never touches Product/Article naming.
 *
 * @package GillldaAI
 */

if (!defined('ABSPATH')) {
    exit;
}

final class GAI_Schema
{
    public static function boot()
    {
        add_filter('rank_math/json_ld', array(__CLASS__, 'filter'), 120, 2);
    }

    private static function has_type(array $data, $type)
    {
        foreach ($data as $e) {
            if (is_array($e) && !empty($e['@type']) && in_array($type, (array) $e['@type'], true)) {
                return true;
            }
        }
        return false;
    }

    /** Does the active theme print its own FAQPage for faq_list? (cached per theme version) */
    public static function theme_prints_faq()
    {
        $theme = wp_get_theme();
        $key   = 'gai_theme_faq_' . md5($theme->get_stylesheet() . $theme->get('Version'));
        $v     = get_transient($key);
        if ($v === false) {
            $v = 'no';
            foreach (array('template-parts/global/faq-list.php', 'faq.php', 'single.php') as $f) {
                $file = locate_template($f);
                if ($file && is_readable($file) && strpos((string) file_get_contents($file), 'FAQPage') !== false) { // phpcs:ignore
                    $v = 'yes';
                    break;
                }
            }
            set_transient($key, $v, DAY_IN_SECONDS);
        }
        return $v === 'yes';
    }

    public static function filter($data, $jsonld = null)
    {
        if (!is_array($data) || is_admin()) {
            return $data;
        }
        $s = GAI_Core::settings();
        try {
            if (is_singular()) {
                $data = self::faq($data, $s);
            }
            if (!empty($s['schema_org'])) {
                $data = self::organization($data, $s);
            }
            if (!empty($s['schema_speak']) && is_singular('post')) {
                $data = self::speakable($data, $s);
            }
        } catch (Throwable $e) {
            return $data; // never break the page's schema
        }
        return $data;
    }

    private static function faq(array $data, array $s)
    {
        $mode = $s['schema_faq'] ?? 'auto';
        if ($mode === 'off' || self::has_type($data, 'FAQPage')) {
            return $data;
        }
        $id = get_queried_object_id();
        // The theme prints FAQPage itself on blog posts (faq-list.php) and the FAQ template; products/pages are ours.
        if ($mode === 'auto' && self::theme_prints_faq() && (is_singular('post') || get_page_template_slug($id) === 'faq.php')) {
            return $data;
        }
        $rows = GAI_Core::faq_rows($id);
        if (!$rows && get_page_template_slug($id) === 'faq.php') {
            foreach (GAI_Core::faq_groups($id) as $g) {
                $rows = array_merge($rows, $g['rows']);
            }
        }
        if (!$rows) {
            return $data;
        }
        $main = array();
        foreach ($rows as $r) {
            $main[] = array(
                '@type'          => 'Question',
                'name'           => wp_strip_all_tags($r['q']),
                'acceptedAnswer' => array('@type' => 'Answer', 'text' => trim(wp_strip_all_tags($r['a']))),
            );
        }
        $data['GillldaFAQPage'] = array(
            '@type'      => 'FAQPage',
            '@id'        => get_permalink($id) . '#faq',
            'mainEntity' => $main,
        );
        return $data;
    }

    private static function sameas()
    {
        $urls = array();
        if (GAI_Core::acf()) {
            foreach ((array) get_field('social', 'option') as $row) {
                $u = is_array($row) ? ($row['link']['url'] ?? ($row['link'] ?? '')) : '';
                if (is_string($u) && preg_match('#^https?://#i', $u)) {
                    $urls[] = esc_url_raw($u);
                }
            }
            foreach ((array) get_field('channels', 'option') as $row) {
                $u = is_array($row) ? ($row['url'] ?? ($row['link'] ?? '')) : '';
                if (is_string($u) && preg_match('#^https?://#i', $u)) {
                    $urls[] = esc_url_raw($u);
                }
            }
        }
        return array_values(array_unique($urls));
    }

    private static function organization(array $data, array $s)
    {
        foreach ($data as $k => $e) {
            if (!is_array($e) || empty($e['@type'])) {
                continue;
            }
            $types = (array) $e['@type'];
            if (!array_intersect($types, array('Organization', 'OnlineStore', 'OnlineBusiness', 'Store', 'LocalBusiness'))) {
                continue;
            }
            if (!empty($s['schema_store']) && $types === array('Organization')) {
                $data[$k]['@type'] = 'OnlineStore';
            }
            $phone = trim((string) $s['phone']);
            $email = trim((string) $s['email']);
            if ($phone !== '' && empty($e['telephone'])) {
                $data[$k]['telephone'] = $phone;
            }
            if ($email !== '' && empty($e['email'])) {
                $data[$k]['email'] = $email;
            }
            if (($phone !== '' || $email !== '') && empty($e['contactPoint'])) {
                $cp = array('@type' => 'ContactPoint', 'contactType' => 'customer service', 'areaServed' => 'IR', 'availableLanguage' => array('fa'));
                if ($phone !== '') {
                    $cp['telephone'] = $phone;
                }
                if ($email !== '') {
                    $cp['email'] = $email;
                }
                $data[$k]['contactPoint'] = $cp;
            }
            $same = self::sameas();
            if ($same) {
                $data[$k]['sameAs'] = array_values(array_unique(array_merge((array) ($e['sameAs'] ?? array()), $same)));
            }
            if (empty($e['hasMerchantReturnPolicy'])) {
                $policy = get_option(GAI_Core::OPT_POLICY, array()); // tiny autoloaded option written by the builder
                $days   = (int) (is_array($policy) ? ($policy['return_days'] ?? 0) : 0);
                if ($days > 0) {
                    $data[$k]['hasMerchantReturnPolicy'] = array(
                        '@type'                => 'MerchantReturnPolicy',
                        'applicableCountry'    => 'IR',
                        'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                        'merchantReturnDays'   => $days,
                        'returnMethod'         => 'https://schema.org/ReturnByMail',
                    );
                    if (($r = GAI_Core::refund_page())) {
                        $data[$k]['hasMerchantReturnPolicy']['merchantReturnLink'] = get_permalink($r);
                    }
                }
            }
            break; // one publisher entity
        }
        return $data;
    }

    private static function speakable(array $data, array $s)
    {
        $sel = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $s['speakable']))));
        if (!$sel) {
            return $data;
        }
        foreach ($data as $k => $e) {
            if (is_array($e) && !empty($e['@type']) && array_intersect((array) $e['@type'], array('Article', 'BlogPosting', 'NewsArticle')) && empty($e['speakable'])) {
                $data[$k]['speakable'] = array('@type' => 'SpeakableSpecification', 'cssSelector' => $sel);
            }
        }
        return $data;
    }
}
