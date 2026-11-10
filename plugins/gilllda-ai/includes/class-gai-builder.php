<?php
/**
 * Content builders: /llms.txt, /llms-full.txt, per-URL Markdown documents and llms.txt validation.
 *
 * Loaded lazily (background rebuild, admin preview, .md request). Everything is read from the store's
 * own data — products, prices, colours, stock, shipping zones, the refund page — nothing is invented.
 *
 * @package GillldaAI
 */

if (!defined('ABSPATH')) {
    exit;
}

final class GAI_Builder
{
    const CAP_PRODUCT = 14000;
    const CAP_POST    = 40000;
    const CAP_PAGE    = 30000;

    /** @var array */
    private $s;

    /** @var array Build statistics. */
    public $stats = array('counts' => array(), 'truncated' => 0, 'skipped' => 0);

    /** @var array Per-run memo. */
    private $memo = array();

    public function __construct(array $settings)
    {
        $this->s = $settings;
    }

    /* =====================================================================
     * Small helpers
     * ================================================================== */

    private function h($level, $text)
    {
        return str_repeat('#', min(6, max(1, $level))) . ' ' . trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $text))) . "\n\n";
    }

    private static function title($p)
    {
        return trim(html_entity_decode(wp_strip_all_tags(get_the_title($p)), ENT_QUOTES, 'UTF-8'));
    }

    private function item($title, $url, $note = '')
    {
        $title = str_replace(array('[', ']'), array('(', ')'), trim(wp_strip_all_tags((string) $title)));
        $note  = trim(preg_replace('/\s+/u', ' ', (string) $note));
        return '- [' . $title . '](' . $url . ')' . ($note !== '' ? ': ' . $note : '') . "\n";
    }

    private function md($html, $shift = 0, $min = 1, $images = true)
    {
        return GAI_Markdown::convert((string) $html, array('shift' => $shift, 'min' => $min, 'images' => $images));
    }

    /** Post body as HTML: blocks rendered, shortcodes stripped (never executed — no side effects). */
    private function content_html($post)
    {
        $c = (string) ($post instanceof WP_Post ? $post->post_content : $post);
        if (trim($c) === '') {
            return '';
        }
        if (function_exists('has_blocks') && has_blocks($c)) {
            $c = do_blocks($c);
        }
        $c = strip_shortcodes($c);
        $c = preg_replace('/\[\/?[a-zA-Z][\w-]*(?:\s[^\]]*)?\]/u', '', $c); // unregistered shortcodes
        if (strpos($c, '<p') === false) {
            $c = wpautop($c);
        }
        return $c;
    }

    /** Cut Markdown to a byte budget at a paragraph boundary. */
    private function cap($md, $bytes, $url = '')
    {
        if (strlen($md) <= $bytes) {
            return $md;
        }
        $cut = substr($md, 0, $bytes);
        $pos = strrpos($cut, "\n\n");
        $cut = ($pos !== false && $pos > $bytes * 0.5) ? substr($cut, 0, $pos) : mb_strcut($md, 0, $bytes, 'UTF-8');
        if (substr_count($cut, '```') % 2) {
            $cut .= "\n```";
        }
        $this->stats['truncated']++;
        return rtrim($cut) . "\n\n*(ادامه در " . ($url ? '[صفحه اصلی](' . $url . ')' : 'سایت') . ')*';
    }

    private static function fa_to_en($s)
    {
        return strtr((string) $s, array('۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٬' => ',', '،' => ','));
    }

    private function count($key, $n = 1)
    {
        $this->stats['counts'][$key] = ($this->stats['counts'][$key] ?? 0) + $n;
    }

    /* =====================================================================
     * Store data
     * ================================================================== */

    public static function currency_label()
    {
        $c   = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'IRT';
        $map = array('IRT' => 'تومان', 'IRR' => 'ریال', 'IRHT' => 'هزار تومان', 'IRHR' => 'هزار ریال');
        return $map[$c] ?? $c;
    }

    public static function price($amount)
    {
        if ($amount === '' || $amount === null) {
            return '';
        }
        $amount = (float) $amount;
        $dec    = abs($amount - round($amount)) > 0.001 ? 2 : 0;
        return number_format($amount, $dec) . ' ' . self::currency_label();
    }

    /** Price summary: "1,250,000 تومان (قیمت قبل 1,500,000 تومان، 17٪ تخفیف)" or a range for variable products. */
    private function price_text($product)
    {
        if (!$product->is_purchasable() && $product->get_price() === '') {
            return 'قیمت: تماس بگیرید';
        }
        if ($product->is_type('variable')) {
            $min  = $product->get_variation_price('min', true);
            $max  = $product->get_variation_price('max', true);
            $rmin = $product->get_variation_regular_price('min', true);
            $txt  = ($min === $max || $max === '') ? self::price($min) : self::price($min) . ' تا ' . self::price($max);
            if ($product->is_on_sale() && (float) $rmin > (float) $min) {
                $txt .= ' (حراج؛ قیمت قبل از ' . self::price($rmin) . ')';
            }
            return $txt;
        }
        $price   = $product->get_price();
        $regular = $product->get_regular_price();
        $txt     = self::price($price);
        if ($product->is_on_sale() && (float) $regular > (float) $price && (float) $regular > 0) {
            $off  = (int) round((1 - (float) $price / (float) $regular) * 100);
            $txt .= ' (قیمت قبل ' . self::price($regular) . '، ' . $off . '٪ تخفیف)';
        }
        return $txt;
    }

    private function stock_label($product)
    {
        $st = $product->get_stock_status();
        return $st === 'outofstock' ? 'ناموجود' : ($st === 'onbackorder' ? 'پیش‌سفارش' : 'موجود');
    }

    private function colors($product)
    {
        $names = array();
        if (taxonomy_exists('pa_color') && function_exists('wc_get_product_terms')) {
            $names = wc_get_product_terms($product->get_id(), 'pa_color', array('fields' => 'names'));
        }
        if (!$names) {
            foreach (array('color', 'رنگ') as $a) {
                $v = $product->get_attribute($a);
                if ($v !== '') {
                    $names = array_map('trim', explode(',', $v));
                    break;
                }
            }
        }
        return array_values(array_filter(array_map('trim', (array) $names)));
    }

    /** Primary product category (Rank Math primary → first). */
    private function primary_term($post_id, $tax)
    {
        $primary = (int) get_post_meta($post_id, 'rank_math_primary_' . $tax, true);
        if ($primary && ($t = get_term($primary, $tax)) && !is_wp_error($t)) {
            return $t;
        }
        $terms = get_the_terms($post_id, $tax);
        return ($terms && !is_wp_error($terms)) ? $terms[0] : null;
    }

    /** All public products (WP_Post), memoised. */
    public function products()
    {
        if (isset($this->memo['products'])) {
            return $this->memo['products'];
        }
        $out = array();
        if (GAI_Core::wc() && in_array('product', GAI_Core::types(), true)) {
            $posts = get_posts(array('post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 2000, 'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'), 'no_found_rows' => true, 'suppress_filters' => true));
            update_meta_cache('post', wp_list_pluck($posts, 'ID'));
            foreach ($posts as $p) {
                if (GAI_Core::is_public($p)) {
                    $out[] = $p;
                }
            }
        }
        return $this->memo['products'] = $out;
    }

    /** Public product categories with at least one product. */
    public function categories()
    {
        if (isset($this->memo['cats'])) {
            return $this->memo['cats'];
        }
        $out = array();
        if (GAI_Core::wc() && taxonomy_exists('product_cat')) {
            $terms = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'name'));
            foreach (is_wp_error($terms) ? array() : $terms as $t) {
                if ($t->slug === 'uncategorized' || GAI_Core::term_noindex($t->term_id)) {
                    continue;
                }
                $out[] = $t;
            }
        }
        return $this->memo['cats'] = $out;
    }

    public function posts()
    {
        if (isset($this->memo['posts'])) {
            return $this->memo['posts'];
        }
        $out = array();
        if (in_array('post', GAI_Core::types(), true)) {
            $posts = get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 2000, 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true, 'suppress_filters' => true));
            update_meta_cache('post', wp_list_pluck($posts, 'ID'));
            foreach ($posts as $p) {
                if (GAI_Core::is_public($p)) {
                    $out[] = $p;
                }
            }
        }
        return $this->memo['posts'] = $out;
    }

    /** Public pages (excluding the front page, shop page and policy pages that get their own section). */
    public function pages()
    {
        if (isset($this->memo['pages'])) {
            return $this->memo['pages'];
        }
        $out  = array();
        $skip = array((int) get_option('page_on_front'));
        if (function_exists('wc_get_page_id')) {
            $skip[] = (int) wc_get_page_id('shop');
        }
        if (in_array('page', GAI_Core::types(), true)) {
            $pages = get_posts(array('post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 500, 'orderby' => 'menu_order title', 'order' => 'ASC', 'no_found_rows' => true, 'suppress_filters' => true));
            foreach ($pages as $p) {
                if (!in_array((int) $p->ID, $skip, true) && GAI_Core::is_public($p)) {
                    $out[] = $p;
                }
            }
        }
        foreach (GAI_Core::types() as $pt) {
            if (in_array($pt, array('post', 'page', 'product'), true)) {
                continue;
            }
            foreach (get_posts(array('post_type' => $pt, 'post_status' => 'publish', 'posts_per_page' => 500, 'no_found_rows' => true)) as $p) {
                if (GAI_Core::is_public($p)) {
                    $out[] = $p;
                }
            }
        }
        return $this->memo['pages'] = $out;
    }

    /** Policy pages: [role => WP_Post]. */
    private function policy_pages()
    {
        if (isset($this->memo['policy'])) {
            return $this->memo['policy'];
        }
        $out = array();
        if (($r = GAI_Core::refund_page()) && GAI_Core::is_public($r)) {
            $out['refund'] = $r;
        }
        $terms = (int) get_option('woocommerce_terms_page_id');
        if ($terms && ($p = get_post($terms)) && GAI_Core::is_public($p)) {
            $out['terms'] = $p;
        }
        $priv = (int) get_option('wp_page_for_privacy_policy');
        if ($priv && ($p = get_post($priv)) && GAI_Core::is_public($p)) {
            $out['privacy'] = $p;
        }
        foreach (array('shipping', 'shipping-policy', 'delivery', 'ersal') as $slug) {
            if (($p = get_page_by_path($slug)) && GAI_Core::is_public($p)) {
                $out['shipping'] = $p;
                break;
            }
        }
        return $this->memo['policy'] = $out;
    }

    private function policy_ids()
    {
        return array_map(function ($p) {
            return (int) $p->ID;
        }, $this->policy_pages());
    }

    /* =====================================================================
     * Policies & facts (read from the store, not hard-coded)
     * ================================================================== */

    /** Shipping methods per WooCommerce zone: [ "تهران: پیک — 200,000 تومان", ... ]. */
    public function shipping_lines()
    {
        if (isset($this->memo['ship'])) {
            return $this->memo['ship'];
        }
        $lines = array();
        if (GAI_Core::wc() && class_exists('WC_Shipping_Zones')) {
            $zones = WC_Shipping_Zones::get_zones();
            $zones[] = array('zone_name' => 'سایر مناطق', 'shipping_methods' => (new WC_Shipping_Zone(0))->get_shipping_methods(true));
            foreach ($zones as $z) {
                foreach ((array) ($z['shipping_methods'] ?? array()) as $m) {
                    if (!is_object($m) || (method_exists($m, 'is_enabled') && !$m->is_enabled())) {
                        continue;
                    }
                    $title = method_exists($m, 'get_title') ? $m->get_title() : '';
                    $note  = '';
                    if ($m->id === 'free_shipping') {
                        $min  = self::fa_to_en((string) $m->get_option('min_amount'));
                        $note = 'رایگان' . (is_numeric(str_replace(',', '', $min)) && (float) str_replace(',', '', $min) > 0 ? ' برای سفارش‌های بالای ' . self::price(str_replace(',', '', $min)) : '');
                    } else {
                        $cost = trim(str_replace(',', '', self::fa_to_en((string) $m->get_option('cost'))));
                        if ($cost !== '' && is_numeric($cost)) {
                            $note = (float) $cost > 0 ? self::price($cost) : 'رایگان';
                        }
                    }
                    $lines[] = trim($z['zone_name'] . ': ' . $title . ($note !== '' ? ' — ' . $note : ''));
                }
            }
        }
        return $this->memo['ship'] = array_values(array_unique($lines));
    }

    /** Key sentences from the refund/returns page (return window, shipping costs, conditions). */
    public function refund_facts()
    {
        if (isset($this->memo['refund'])) {
            return $this->memo['refund'];
        }
        $out  = array();
        $days = 0;
        $p    = GAI_Core::refund_page();
        if ($p) {
            $text  = html_entity_decode(wp_strip_all_tags(strip_shortcodes($this->content_html($p))), ENT_QUOTES, 'UTF-8');
            $parts = preg_split('/(?<=[.!؟?])\s+|\n+/u', $text);
            foreach ($parts as $sent) {
                $sent = trim(preg_replace('/\s+/u', ' ', $sent));
                if ($sent === '' || mb_strlen($sent) < 15) {
                    continue;
                }
                $is_return = preg_match('/(مرجوع|بازگشت|عودت|تعویض|ضمانت|استرداد)/u', $sent);
                $is_ship   = preg_match('/(ارسال|پست|پیک|تیپاکس)/u', $sent) && preg_match('/(تومان|ریال|رایگان)/u', $sent);
                $has_time  = preg_match('/(روز|ساعت|هفته)/u', $sent);
                if (($is_return && $has_time) || $is_ship) {
                    $out[] = GAI_Core::short($sent, 220);
                    if (!$days && $is_return && preg_match('/([0-9۰-۹]{1,2})\s*(?:\(\s*[^)]*\)\s*)?روز/u', $sent, $m)) {
                        $days = (int) self::fa_to_en($m[1]);
                    }
                    if (!$days && $is_return && preg_match('/(یک|دو|سه|چهار|پنج|شش|هفت|هشت|نه|ده|چهارده|پانزده|بیست|سی)\s+روز/u', $sent, $m)) {
                        $w    = array('یک' => 1, 'دو' => 2, 'سه' => 3, 'چهار' => 4, 'پنج' => 5, 'شش' => 6, 'هفت' => 7, 'هشت' => 8, 'نه' => 9, 'ده' => 10, 'چهارده' => 14, 'پانزده' => 15, 'بیست' => 20, 'سی' => 30);
                        $days = $w[$m[1]] ?? 0;
                    }
                }
                if (count($out) >= 6) {
                    break;
                }
            }
        }
        $this->memo['return_days'] = $days;
        return $this->memo['refund'] = array_values(array_unique($out));
    }

    /** Store facts shown at the top of llms.txt / llms-full.txt. Owner override wins. */
    public function facts()
    {
        if (isset($this->memo['facts'])) {
            return $this->memo['facts'];
        }
        $facts  = array();
        $custom = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $this->s['facts']))));
        if ($custom) {
            $facts = array_map(function ($l) {
                return ltrim($l, "-* \t");
            }, $custom);
        } else {
            foreach ($this->shipping_lines() as $l) {
                $facts[] = 'هزینه ارسال — ' . $l;
            }
            foreach ($this->refund_facts() as $l) {
                $facts[] = $l;
            }
        }
        $this->refund_facts(); // ensures return_days is detected even with custom facts
        $days = (int) ($this->memo['return_days'] ?? 0);
        update_option(GAI_Core::OPT_POLICY, array('return_days' => $days), true);
        return $this->memo['facts'] = $facts;
    }

    private function contact_line()
    {
        $bits  = array();
        $phone = trim((string) $this->s['phone']);
        $email = trim((string) $this->s['email']);
        if ($phone !== '') {
            $bits[] = 'تلفن: ' . $phone;
        }
        if ($email !== '') {
            $bits[] = 'ایمیل: ' . $email;
        }
        if (GAI_Core::acf() && is_string($addr = get_field('address', 'option')) && trim(wp_strip_all_tags($addr)) !== '') {
            $bits[] = 'نشانی: ' . GAI_Core::short($addr, 160);
        }
        return implode(' · ', $bits);
    }

    /** Smart default intro: tagline + about-page summary. */
    public static function auto_intro()
    {
        $name = trim(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $tag  = trim(wp_specialchars_decode(get_bloginfo('description'), ENT_QUOTES));
        $out  = $name . ($tag !== '' ? '؛ ' . $tag : '') . '.';
        $about = GAI_Core::page_by_template('about-us.php');
        if (!$about) {
            foreach (array('about-us', 'about', 'درباره-ما') as $slug) {
                if (($about = get_page_by_path($slug))) {
                    break;
                }
            }
        }
        $desc = '';
        if ($about) {
            $desc = GAI_Core::rm_description($about) ?: GAI_Core::short($about->post_excerpt ?: $about->post_content, 260);
        }
        if ($desc === '' && ($front = (int) get_option('page_on_front'))) {
            $fp   = get_post($front);
            $desc = $fp ? GAI_Core::rm_description($fp) : '';
        }
        if ($desc === '') {
            $desc = 'فروشگاه اینترنتی کیف زنانه با ارسال به سراسر ایران.';
        }
        return trim(preg_replace('/\s+/u', ' ', $out . ' ' . $desc));
    }

    public function summary()
    {
        $t = trim((string) $this->s['intro']);
        return preg_replace('/\s+/u', ' ', $t !== '' ? $t : self::auto_intro());
    }

    /** Overview bullets (no headings — allowed between the blockquote and the first H2). */
    private function overview()
    {
        $out      = '';
        $products = $this->products();
        if ($products && GAI_Core::wc()) {
            $min = null;
            $max = null;
            foreach ($products as $p) {
                $prod = wc_get_product($p->ID);
                if (!$prod) {
                    continue;
                }
                $lo = $prod->is_type('variable') ? $prod->get_variation_price('min', true) : $prod->get_price();
                $hi = $prod->is_type('variable') ? $prod->get_variation_price('max', true) : $prod->get_price();
                if ($lo !== '' && (float) $lo > 0) {
                    $min = $min === null ? (float) $lo : min($min, (float) $lo);
                }
                if ($hi !== '' && (float) $hi > 0) {
                    $max = $max === null ? (float) $hi : max($max, (float) $hi);
                }
            }
            $out .= '- محصولات: ' . count($products) . ' محصول در ' . count($this->categories()) . ' دسته' . ($min !== null ? '؛ بازه قیمت ' . self::price($min) . ' تا ' . self::price($max) : '') . "\n";
            $out .= '- زبان سایت: فارسی (fa-IR)؛ واحد قیمت‌ها: ' . self::currency_label() . "؛ ارسال به سراسر ایران\n";
        }
        foreach ($this->facts() as $f) {
            $out .= '- ' . $f . "\n";
        }
        if (($c = $this->contact_line()) !== '') {
            $out .= '- ' . $c . "\n";
        }
        $site = untrailingslashit(home_url('/'));
        if (!empty($this->s['md'])) {
            $out .= '- نسخه Markdown هر صفحه: «.md» را به انتهای آدرس اضافه کنید (مثلاً ' . $site . "/index.md)\n";
        }
        if (!empty($this->s['llms_full'])) {
            $out .= '- متن کامل فروشگاه برای مدل‌های زبانی: ' . $site . "/llms-full.txt\n";
        }
        return $out;
    }

    /* =====================================================================
     * Notes
     * ================================================================== */

    private function product_note(WP_Post $p)
    {
        $prod = GAI_Core::wc() ? wc_get_product($p->ID) : null;
        if (!$prod) {
            return GAI_Core::description($p, 120);
        }
        $bits = array($this->price_text($prod));
        if (($c = $this->colors($prod))) {
            $bits[] = 'رنگ‌ها: ' . implode('، ', $c);
        }
        if ($prod->get_stock_status() !== 'instock') {
            $bits[] = $this->stock_label($prod);
        }
        $d = GAI_Core::short($prod->get_short_description() ?: GAI_Core::description($p, 140), 110);
        return implode(' · ', $bits) . ($d !== '' ? ' — ' . $d : '');
    }

    private function series_name($post_id)
    {
        return trim((string) get_post_meta($post_id, 'gl_series', true));
    }

    /** Blog posts grouped: series (ordered by part) first, then by primary category. */
    private function blog_groups()
    {
        $series = array();
        $cats   = array();
        foreach ($this->posts() as $p) {
            $s = $this->series_name($p->ID);
            if ($s !== '') {
                $series[$s][] = $p;
                continue;
            }
            $t   = $this->primary_term($p->ID, 'category');
            $key = $t ? $t->name : 'مقالات';
            $cats[$key][] = $p;
        }
        foreach ($series as $name => $list) {
            usort($list, function ($a, $b) {
                $pa = (int) get_post_meta($a->ID, 'gl_series_part', true) ?: 999;
                $pb = (int) get_post_meta($b->ID, 'gl_series_part', true) ?: 999;
                return $pa <=> $pb ?: strcmp($a->post_date, $b->post_date);
            });
            $series[$name] = $list;
        }
        return array('series' => $series, 'cats' => $cats);
    }

    /* =====================================================================
     * /llms.txt
     * ================================================================== */

    public function llms_txt()
    {
        $name = trim(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)) ?: wp_parse_url(home_url(), PHP_URL_HOST);
        $out  = '# ' . $name . "\n\n> " . $this->summary() . "\n\n";
        $out .= $this->overview() . "\n";

        // Policies.
        $policy = $this->policy_pages();
        if ($policy) {
            $labels = array('refund' => 'شرایط بازگشت و مرجوعی کالا', 'shipping' => 'شیوه و هزینه ارسال', 'terms' => 'قوانین و شرایط خرید', 'privacy' => 'حریم خصوصی');
            $out   .= "## سیاست‌های فروشگاه (Store policies)\n\n";
            foreach ($policy as $role => $p) {
                $note = $role === 'refund' && ($f = $this->refund_facts()) ? implode(' ', array_slice($f, 0, 2)) : GAI_Core::description($p, 150);
                $out .= $this->item(self::title($p) ?: $labels[$role], get_permalink($p), GAI_Core::short($note, 220));
            }
            $out .= "\n";
        }

        // Categories.
        if (($cats = $this->categories())) {
            $out .= "## دسته‌بندی محصولات (Product categories)\n\n";
            foreach ($cats as $t) {
                $link = get_term_link($t);
                if (is_wp_error($link)) {
                    continue;
                }
                $desc = GAI_Core::short(get_term_meta($t->term_id, 'rank_math_description', true) ?: $t->description, 120);
                $out .= $this->item($t->name, $link, $t->count . ' محصول' . ($desc !== '' ? ' — ' . $desc : ''));
                $this->count('categories');
            }
            $out .= "\n";
        }

        // Products, grouped by primary category.
        $groups = array();
        foreach ($this->products() as $p) {
            $t = $this->primary_term($p->ID, 'product_cat');
            $groups[$t && $t->slug !== 'uncategorized' ? $t->name : 'سایر'][] = $p;
        }
        foreach ($groups as $cat => $list) {
            $out .= '## محصولات: ' . $cat . " (Products)\n\n";
            foreach ($list as $p) {
                $out .= $this->item(self::title($p), get_permalink($p), $this->product_note($p));
                $this->count('products');
            }
            $out .= "\n";
        }

        // Blog.
        $blog = $this->blog_groups();
        foreach ($blog['series'] as $series => $list) {
            $out .= '## مجله گیلدا — پرونده «' . $series . "» (Blog series)\n\n";
            foreach ($list as $p) {
                $part = (int) get_post_meta($p->ID, 'gl_series_part', true);
                $out .= $this->item(self::title($p), get_permalink($p), ($part ? 'قسمت ' . $part . ' — ' : '') . GAI_Core::description($p, 150));
                $this->count('posts');
            }
            $out .= "\n";
        }
        foreach ($blog['cats'] as $cat => $list) {
            $out .= '## مجله گیلدا — ' . $cat . " (Blog)\n\n";
            foreach ($list as $p) {
                $out .= $this->item(self::title($p), get_permalink($p), GAI_Core::description($p, 150));
                $this->count('posts');
            }
            $out .= "\n";
        }

        // Key pages.
        $pages = array_filter($this->pages(), function ($p) {
            return !in_array((int) $p->ID, $this->policy_ids(), true);
        });
        $main  = array();
        $minor = array();
        foreach ($pages as $p) {
            $tpl = get_page_template_slug($p);
            if (in_array($tpl, array('about-us.php', 'contact-us.php', 'faq.php'), true) || preg_match('/^(about|contact|faq)/', $p->post_name)) {
                $main[] = $p;
            } else {
                $minor[] = $p;
            }
        }
        $shop = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;
        $blog_page = (int) get_option('page_for_posts');
        if ($main || $shop > 0 || $blog_page) {
            $out .= "## صفحه‌های کلیدی (Key pages)\n\n";
            if ($shop > 0 && get_post_status($shop) === 'publish') {
                $out .= $this->item(self::title($shop) ?: 'فروشگاه', get_permalink($shop), 'فهرست همه محصولات با فیلتر رنگ، قیمت و دسته');
            }
            foreach ($main as $p) {
                $note = get_page_template_slug($p) === 'faq.php' ? 'پاسخ پرسش‌های رایج درباره سفارش، ارسال، پرداخت و مرجوعی' : GAI_Core::description($p, 150);
                $out .= $this->item(self::title($p), get_permalink($p), $note);
                $this->count('pages');
            }
            if ($blog_page && get_post_status($blog_page) === 'publish') {
                $out .= $this->item(self::title($blog_page) ?: 'مجله', get_permalink($blog_page), 'راهنمای خرید، نگهداری و استایل کیف زنانه');
            }
            $out .= "\n";
        }

        // Optional.
        $out .= "## Optional\n\n";
        if (!empty($this->s['llms_full'])) {
            $out .= $this->item('llms-full.txt', home_url('/llms-full.txt'), 'متن کامل محصولات، مقاله‌ها، سؤالات متداول و قوانین در یک فایل');
        }
        foreach ($minor as $p) {
            $out .= $this->item(self::title($p), get_permalink($p), GAI_Core::description($p, 120));
            $this->count('pages');
        }
        $sitemap = GAI_Core::rank_math() ? home_url('/sitemap_index.xml') : home_url('/wp-sitemap.xml');
        $out    .= $this->item('نقشه سایت (XML sitemap)', $sitemap, 'فهرست کامل آدرس‌های قابل ایندکس');

        return rtrim($out) . "\n";
    }

    /* =====================================================================
     * Documents
     * ================================================================== */

    /** Product as Markdown. $h = heading level of the product title. */
    public function product_md(WP_Post $p, $h = 1)
    {
        $url  = get_permalink($p);
        $prod = GAI_Core::wc() ? wc_get_product($p->ID) : null;
        $out  = $this->h($h, self::title($p));
        if (!$prod) {
            return $out . $this->md($this->content_html($p), $h, $h + 1);
        }
        if ($h === 1 && ($d = GAI_Core::description($p, 300)) !== '') {
            $out .= '> ' . $d . "\n\n";
        }
        $meta   = array();
        $meta[] = '- آدرس: ' . $url;
        $cats   = wc_get_product_terms($p->ID, 'product_cat', array('fields' => 'names'));
        if ($cats) {
            $meta[] = '- دسته: ' . implode('، ', $cats);
        }
        $meta[] = '- قیمت: ' . $this->price_text($prod);
        if (($c = $this->colors($prod))) {
            $meta[] = '- رنگ‌ها: ' . implode('، ', $c);
        }
        $meta[] = '- وضعیت موجودی: ' . $this->stock_label($prod);
        if ($prod->get_sku()) {
            $meta[] = '- کد کالا (SKU): ' . $prod->get_sku();
        }
        if (function_exists('wc_format_dimensions') && $prod->has_dimensions()) {
            $meta[] = '- ابعاد: ' . html_entity_decode(wp_strip_all_tags(wc_format_dimensions($prod->get_dimensions(false))), ENT_QUOTES, 'UTF-8');
        }
        if ($prod->has_weight()) {
            $meta[] = '- وزن: ' . $prod->get_weight() . ' ' . get_option('woocommerce_weight_unit');
        }
        foreach ($prod->get_attributes() as $attr) {
            if (!is_object($attr) || !$attr->get_visible()) {
                continue;
            }
            $name = wc_attribute_label($attr->get_name());
            if ($attr->get_name() === 'pa_color' || in_array($name, array('رنگ', 'color', 'Color'), true)) {
                continue;
            }
            $vals = $attr->is_taxonomy() ? wc_get_product_terms($p->ID, $attr->get_name(), array('fields' => 'names')) : $attr->get_options();
            if ($vals) {
                $meta[] = '- ' . $name . ': ' . implode('، ', array_map('wp_strip_all_tags', (array) $vals));
            }
        }
        if ($prod->get_review_count() > 0) {
            $meta[] = '- امتیاز خریداران: ' . round((float) $prod->get_average_rating(), 1) . ' از 5 (' . $prod->get_review_count() . ' نظر)';
        }
        $out .= implode("\n", $meta) . "\n\n";

        $short = $this->md($prod->get_short_description(), $h, $h + 1, false);
        if ($short !== '') {
            $out .= $short . "\n\n";
        }

        // Variations.
        if ($prod->is_type('variable')) {
            $rows = array();
            foreach ($prod->get_children() as $vid) {
                $v = wc_get_product($vid);
                if (!$v || $v->get_status() !== 'publish') {
                    continue;
                }
                $label = function_exists('wc_get_formatted_variation') ? wp_strip_all_tags(wc_get_formatted_variation($v, true, false, false)) : '';
                $price = self::price($v->get_price());
                if ($v->is_on_sale() && (float) $v->get_regular_price() > (float) $v->get_price()) {
                    $price .= ' (به‌جای ' . self::price($v->get_regular_price()) . ')';
                }
                $rows[] = '| ' . str_replace('|', '/', $label ?: '#' . $vid) . ' | ' . $price . ' | ' . $this->stock_label($v) . ' |';
            }
            if ($rows) {
                $out .= $this->h($h + 1, 'تنوع‌ها (رنگ / مدل)') . "| تنوع | قیمت | موجودی |\n| --- | --- | --- |\n" . implode("\n", $rows) . "\n\n";
            }
        }

        $desc = $this->md($this->content_html($p), $h, $h + 1);
        if ($desc !== '') {
            $out .= $this->h($h + 1, 'توضیحات') . $desc . "\n\n";
        }

        $faq = GAI_Core::faq_rows($p->ID);
        if (function_exists('gl_lp_enabled') && function_exists('gl_lp_settings') && gl_lp_enabled($p->ID)) {
            $set = gl_lp_settings($p->ID);
            foreach (preg_split('/\r\n|\r|\n/', (string) ($set['faq'] ?? '')) as $line) {
                $pair = array_map('trim', explode('|', $line, 2));
                if (count($pair) === 2 && $pair[0] !== '' && $pair[1] !== '') {
                    $faq[] = array('q' => $pair[0], 'a' => $pair[1]);
                }
            }
        }
        if ($faq) {
            $out .= $this->h($h + 1, 'پرسش‌های متداول') . $this->faq_md($faq) . "\n";
        }
        return rtrim($out) . "\n";
    }

    private function faq_md(array $rows)
    {
        $out = '';
        foreach ($rows as $r) {
            $a    = $this->md(wpautop((string) $r['a']), 0, 4, false);
            $out .= '**' . trim(wp_strip_all_tags($r['q'])) . "**\n\n" . $a . "\n\n";
        }
        return $out;
    }

    public function post_md(WP_Post $p, $h = 1)
    {
        $url  = get_permalink($p);
        $out  = $this->h($h, self::title($p));
        $tldr = trim((string) get_post_meta($p->ID, 'gl_tldr', true));
        $lead = $tldr !== '' ? $tldr : GAI_Core::description($p, 300);
        if ($lead !== '') {
            $out .= '> ' . preg_replace('/\s+/u', ' ', $lead) . "\n\n";
        }
        $meta   = array('- آدرس: ' . $url);
        $meta[] = '- تاریخ انتشار: ' . get_the_date('Y-m-d', $p) . ' · به‌روزرسانی: ' . get_the_modified_date('Y-m-d', $p);
        $cats   = get_the_category($p->ID);
        if ($cats) {
            $meta[] = '- دسته: ' . implode('، ', wp_list_pluck($cats, 'name'));
        }
        $series = $this->series_name($p->ID);
        if ($series !== '') {
            $part  = (int) get_post_meta($p->ID, 'gl_series_part', true);
            $info  = function_exists('gl_series_info') ? gl_series_info($p->ID) : null;
            $total = is_array($info) ? (int) ($info['total'] ?? 0) : 0;
            $meta[] = '- پرونده: «' . $series . '»' . ($part ? ' — قسمت ' . $part . ($total ? ' از ' . $total : '') : '');
        }
        $out .= implode("\n", $meta) . "\n\n";

        $take = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) get_post_meta($p->ID, 'gl_takeaways', true)))));
        if ($take) {
            $out .= $this->h($h + 1, 'نکات کلیدی') . '- ' . implode("\n- ", $take) . "\n\n";
        }
        $out .= $this->md($this->content_html($p), $h - 1, $h + 1) . "\n\n";

        $faq = GAI_Core::faq_rows($p->ID);
        if ($faq) {
            $out .= $this->h($h + 1, 'پرسش‌های متداول') . $this->faq_md($faq);
        }
        // Products linked from the article (ACF relationship).
        if (GAI_Core::acf() && GAI_Core::wc()) {
            $rel  = (array) get_field('related_product', $p->ID);
            $list = '';
            foreach ($rel as $r) {
                $rp = $r instanceof WP_Post ? $r : get_post((int) $r);
                if ($rp && GAI_Core::is_public($rp)) {
                    $list .= $this->item(self::title($rp), get_permalink($rp), $this->product_note($rp));
                }
            }
            if ($list !== '') {
                $out .= $this->h($h + 1, 'محصولات معرفی‌شده در این مقاله') . $list . "\n";
            }
        }
        return rtrim($out) . "\n";
    }

    public function page_md(WP_Post $p, $h = 1)
    {
        $out = $this->h($h, self::title($p));
        $tpl = get_page_template_slug($p);
        if ($tpl === 'faq.php') {
            $intro = trim($this->md($this->content_html($p), $h - 1, $h + 1));
            $out  .= $intro !== '' ? $intro . "\n\n" : '';
            foreach (GAI_Core::faq_groups($p->ID) as $g) {
                $out .= ($g['title'] !== '' ? $this->h($h + 1, $g['title']) : '') . $this->faq_md($g['rows']);
            }
            return rtrim($out) . "\n";
        }
        if ($h === 1 && ($d = GAI_Core::rm_description($p)) !== '') {
            $out .= '> ' . $d . "\n\n";
        }
        $body = $this->md($this->content_html($p), $h - 1, $h + 1);
        $out .= $body !== '' ? $body . "\n\n" : '';
        if ($tpl === 'contact-us.php' && ($c = $this->contact_line()) !== '') {
            $out .= $this->h($h + 1, 'راه‌های تماس') . '- ' . str_replace(' · ', "\n- ", $c) . "\n\n";
        }
        if ($p->ID === (int) (GAI_Core::refund_page() ? GAI_Core::refund_page()->ID : 0) && ($ship = $this->shipping_lines())) {
            $out .= $this->h($h + 1, 'هزینه‌های ارسال (از تنظیمات فروشگاه)') . '- ' . implode("\n- ", $ship) . "\n\n";
        }
        $faq = GAI_Core::faq_rows($p->ID);
        if ($faq) {
            $out .= $this->h($h + 1, 'پرسش‌های متداول') . $this->faq_md($faq);
        }
        return rtrim($out) . "\n";
    }

    /** Standalone Markdown document for one public post/page/product. */
    public function single_doc(WP_Post $p)
    {
        switch ($p->post_type) {
            case 'product':
                $body = $this->cap($this->product_md($p, 1), self::CAP_PRODUCT * 3, get_permalink($p));
                break;
            case 'post':
                $body = $this->post_md($p, 1);
                break;
            default:
                $body = $this->page_md($p, 1);
        }
        $name = trim(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        return rtrim($body) . "\n\n---\n\nمنبع: " . get_permalink($p) . ' · ' . $name . ' · فهرست کامل: ' . home_url('/llms.txt') . "\n";
    }

    /** Markdown for list pages: home, shop, product category, blog home, blog category. */
    public function archive_doc()
    {
        if (is_front_page()) {
            return array('body' => $this->llms_txt(), 'canonical' => home_url('/'), 'modified' => (int) get_option(GAI_Core::OPT_CHANGED));
        }
        $mod = (int) get_option(GAI_Core::OPT_CHANGED);
        if ((function_exists('is_shop') && is_shop()) || is_tax('product_cat')) {
            $term  = is_tax('product_cat') ? get_queried_object() : null;
            if ($term && GAI_Core::term_noindex($term->term_id)) {
                return null;
            }
            $title = $term ? $term->name : self::title((int) wc_get_page_id('shop'));
            $body  = '# ' . $title . "\n\n";
            if ($term && ($d = trim(wp_strip_all_tags($term->description))) !== '') {
                $body .= '> ' . GAI_Core::short($d, 400) . "\n\n";
            }
            if (!$term) {
                $body .= "## دسته‌ها\n\n";
                foreach ($this->categories() as $t) {
                    $l = get_term_link($t);
                    $body .= is_wp_error($l) ? '' : $this->item($t->name, $l, $t->count . ' محصول');
                }
                $body .= "\n## محصولات\n\n";
            }
            foreach ($this->products() as $p) {
                if ($term && !has_term($term->term_id, 'product_cat', $p)) {
                    continue;
                }
                $body .= $this->item(self::title($p), get_permalink($p), $this->product_note($p));
            }
            $canon = $term ? get_term_link($term) : get_permalink((int) wc_get_page_id('shop'));
            return array('body' => $body, 'canonical' => is_wp_error($canon) ? home_url('/') : $canon, 'modified' => $mod);
        }
        if (is_category()) {
            $term = get_queried_object();
            $body = '# ' . $term->name . "\n\n";
            if (($d = trim(wp_strip_all_tags($term->description))) !== '') {
                $body .= '> ' . GAI_Core::short($d, 400) . "\n\n";
            }
            foreach ($this->posts() as $p) {
                if (has_category($term->term_id, $p)) {
                    $body .= $this->item(self::title($p), get_permalink($p), GAI_Core::description($p, 150));
                }
            }
            return array('body' => $body, 'canonical' => get_term_link($term), 'modified' => $mod);
        }
        if (is_home()) {
            $pid  = (int) get_option('page_for_posts');
            $body = '# ' . ($pid ? self::title($pid) : 'مجله') . "\n\n";
            $blog = $this->blog_groups();
            foreach ($blog['series'] as $s => $list) {
                $body .= '## پرونده «' . $s . "»\n\n";
                foreach ($list as $p) {
                    $body .= $this->item(self::title($p), get_permalink($p), GAI_Core::description($p, 150));
                }
                $body .= "\n";
            }
            foreach ($blog['cats'] as $c => $list) {
                $body .= '## ' . $c . "\n\n";
                foreach ($list as $p) {
                    $body .= $this->item(self::title($p), get_permalink($p), GAI_Core::description($p, 150));
                }
                $body .= "\n";
            }
            return array('body' => $body, 'canonical' => $pid ? get_permalink($pid) : home_url('/'), 'modified' => $mod);
        }
        return null;
    }

    /* =====================================================================
     * /llms-full.txt
     * ================================================================== */

    public function llms_full()
    {
        $budget = max(100, (int) $this->s['full_cap_kb']) * 1024;
        $name   = trim(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $head   = '# ' . $name . " — متن کامل برای مدل‌های زبانی\n\n> " . $this->summary() . "\n\n" . $this->overview() . "\n";
        $head  .= 'این فایل به‌طور خودکار از محتوای منتشرشده سایت ساخته می‌شود؛ قیمت و موجودی لحظه‌ای را در صفحه هر محصول ببینید. آخرین به‌روزرسانی: ' . wp_date('Y-m-d H:i') . "\n\n";

        $parts   = array($head);
        $used    = strlen($head);
        $skipped = array();

        $add = function ($section, $chunks) use (&$parts, &$used, &$skipped, $budget) {
            if (!$chunks) {
                return;
            }
            $h = '## ' . $section . "\n\n";
            if ($used + strlen($h) > $budget) {
                foreach ($chunks as $c) {
                    $skipped[] = $c;
                }
                return;
            }
            $parts[] = $h;
            $used   += strlen($h);
            foreach ($chunks as $c) {
                $body = $c['body'];
                if ($used + strlen($body) > $budget) {
                    $skipped[] = $c;
                    continue;
                }
                $parts[] = rtrim($body) . "\n\n";
                $used   += strlen($body) + 2;
            }
        };

        // 1. Policies.
        $chunks = array();
        foreach ($this->policy_pages() as $p) {
            $chunks[] = array('title' => self::title($p), 'url' => get_permalink($p), 'body' => $this->cap($this->page_md($p, 3), self::CAP_PAGE, get_permalink($p)) . "\nمنبع: " . get_permalink($p) . "\n");
        }
        if (!isset($this->policy_pages()['refund']) && ($ship = $this->shipping_lines())) {
            $chunks[] = array('title' => 'ارسال', 'url' => home_url('/'), 'body' => "### هزینه‌های ارسال\n\n- " . implode("\n- ", $ship) . "\n");
        }
        $add('سیاست‌ها و قوانین فروشگاه (Policies)', $chunks);

        // 2. FAQ page.
        $faq_page = GAI_Core::page_by_template('faq.php');
        if ($faq_page && GAI_Core::is_public($faq_page)) {
            $add('پرسش‌های متداول (FAQ)', array(array('title' => self::title($faq_page), 'url' => get_permalink($faq_page), 'body' => $this->page_md($faq_page, 3) . "\nمنبع: " . get_permalink($faq_page) . "\n")));
            $this->count('full_faq');
        }

        // 3. Products.
        $chunks = array();
        foreach ($this->products() as $p) {
            $chunks[] = array('title' => self::title($p), 'url' => get_permalink($p), 'body' => $this->cap($this->product_md($p, 3), self::CAP_PRODUCT, get_permalink($p)));
            $this->count('full_products');
        }
        $add('محصولات (Products)', $chunks);

        // 4. Blog.
        $chunks = array();
        foreach ($this->posts() as $p) {
            $chunks[] = array('title' => self::title($p), 'url' => get_permalink($p), 'body' => $this->cap($this->post_md($p, 3), self::CAP_POST, get_permalink($p)));
            $this->count('full_posts');
        }
        $add('مقاله‌های مجله (Blog articles)', $chunks);

        // 5. Other pages.
        $chunks = array();
        $skip   = $this->policy_ids();
        if ($faq_page) {
            $skip[] = (int) $faq_page->ID;
        }
        foreach ($this->pages() as $p) {
            if (in_array((int) $p->ID, $skip, true)) {
                continue;
            }
            $body = $this->page_md($p, 3);
            if (GAI_Core::words($body) < 15) {
                continue; // layout-only pages (built from ACF/templates) add nothing
            }
            $chunks[] = array('title' => self::title($p), 'url' => get_permalink($p), 'body' => $this->cap($body, self::CAP_PAGE, get_permalink($p)) . "\nمنبع: " . get_permalink($p) . "\n");
            $this->count('full_pages');
        }
        $add('صفحه‌ها (Pages)', $chunks);

        if ($skipped) {
            $this->stats['skipped'] = count($skipped);
            $tail = "## موارد خارج از سقف حجم\n\nاین موارد به‌دلیل سقف حجم فایل فقط به‌صورت پیوند آمده‌اند (نسخه Markdown هر کدام با افزودن .md در دسترس است):\n\n";
            foreach ($skipped as $c) {
                $tail .= $this->item($c['title'], $c['url']);
            }
            $parts[] = $tail;
        }
        return GAI_Markdown::tidy(implode('', $parts)) . "\n";
    }

    /* =====================================================================
     * Validation (admin status card)
     * ================================================================== */

    /** @return array list of [level ok|warn|error, message]. */
    public static function validate($txt)
    {
        $r     = array();
        $txt   = (string) $txt;
        $lines = preg_split('/\r\n|\r|\n/', $txt);
        if ($txt === '') {
            return array(array('error', 'llms.txt هنوز ساخته نشده است.'));
        }
        $h1 = preg_grep('/^# /', $lines);
        $r[] = (count($h1) === 1 && strpos(ltrim($txt), '# ') === 0)
            ? array('ok', 'با یک تیتر H1 (نام سایت) شروع می‌شود.')
            : array('error', 'باید دقیقاً یک H1 داشته باشد و با آن شروع شود (' . count($h1) . ' مورد).');

        $first = '';
        foreach ($lines as $i => $l) {
            if ($i > 0 && trim($l) !== '') {
                $first = $l;
                break;
            }
        }
        $r[] = strpos($first, '> ') === 0 ? array('ok', 'خلاصه (blockquote) بلافاصله بعد از H1 آمده است.') : array('warn', 'خلاصه blockquote بعد از H1 پیدا نشد.');

        $section  = '';
        $bad      = array();
        $deep     = 0;
        $links    = array();
        $sections = array();
        foreach ($lines as $n => $l) {
            if (preg_match('/^(#{2,6}) (.+)$/', $l, $m)) {
                if (strlen($m[1]) > 2) {
                    $deep++;
                }
                $section    = trim($m[2]);
                $sections[] = $section;
                continue;
            }
            if ($section === '' || trim($l) === '') {
                continue;
            }
            if (!preg_match('/^- \[[^\]]+\]\(([^)\s]+)\)(: .+)?$/u', $l, $m)) {
                $bad[] = $n + 1;
                continue;
            }
            $links[] = $m[1];
        }
        $r[] = $bad
            ? array('warn', 'خط(های) ' . implode('، ', array_slice($bad, 0, 10)) . ' قالب «- [عنوان](آدرس): توضیح» را ندارند.')
            : array('ok', count($sections) . ' بخش H2 و ' . count($links) . ' پیوند با قالب استاندارد.');
        if ($deep) {
            $r[] = array('warn', $deep . ' تیتر H3 یا عمیق‌تر هست؛ استاندارد فقط H2 را برای بخش‌ها تعریف می‌کند.');
        }
        $r[] = in_array('Optional', $sections, true) ? array('ok', 'بخش «## Optional» وجود دارد.') : array('warn', 'بخش «## Optional» وجود ندارد.');
        $dups = array_filter(array_count_values($links), function ($c) {
            return $c > 1;
        });
        $r[] = $dups ? array('warn', count($dups) . ' پیوند تکراری: ' . implode(' ، ', array_slice(array_keys($dups), 0, 3))) : array('ok', 'پیوند تکراری ندارد.');
        $host    = wp_parse_url(home_url('/'), PHP_URL_HOST);
        $foreign = 0;
        $http    = 0;
        foreach ($links as $u) {
            if (wp_parse_url($u, PHP_URL_HOST) !== $host) {
                $foreign++;
            }
            if (stripos($u, 'http://') === 0) {
                $http++;
            }
        }
        if ($foreign) {
            $r[] = array('warn', $foreign . ' پیوند به دامنه دیگر اشاره می‌کند.');
        }
        if ($http) {
            $r[] = array('warn', $http . ' پیوند با http (بدون s) ساخته شده؛ «نشانی سایت» در تنظیمات وردپرس را بررسی کنید.');
        }
        $size = strlen($txt);
        $r[]  = $size > 100000 ? array('warn', 'حجم ' . size_format($size, 1) . ' است؛ llms.txt بهتر است موجز بماند (زیر ۱۰۰ کیلوبایت).') : array('ok', 'حجم مناسب: ' . size_format($size, 1));
        return $r;
    }
}
