<?php
/**
 * Product landing page (served at /l/{product-slug}/ — see inc/landing/landing.php).
 * Standalone, conversion-focused and animated; every piece of content comes from the product.
 *
 * @package bluebox
 */

if (!defined('ABSPATH')) {
    exit;
}

the_post();
$product = wc_get_product(get_the_ID());
if (!$product) {
    wp_safe_redirect(home_url('/'));
    exit;
}
$d       = gl_lp_data($product);
$enabled = gl_lp_enabled($d['id']);
$images  = $d['image_ids'];
$hero_id = $images[0] ?? 0;
$source  = isset($_GET['utm_source']) ? gl_lp_clean_source(wp_unslash($_GET['utm_source'])) : 'direct';
$words   = preg_split('/\s+/u', trim($d['headline']));
$logo    = function_exists('get_field') ? get_field('site_logo', 'option') : null;
$phone   = function_exists('get_field') ? (get_field('phone', 'option') ?: '') : '';
$stars   = function ($r) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<svg class="lp-star' . ($r >= $i - 0.25 ? ' is-on' : '') . '" width="16" height="16" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9z"/></svg>';
    }
    return $out;
};
$icons = [
    '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
    '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
    '<path d="M12 3l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 15.4l-4.8 2.5.9-5.4L4.2 8.7l5.4-.8z"/>',
    '<path d="M4 5h16v11H8l-4 4z"/><path d="M8 9h8M8 12h5"/>',
    '<path d="M12 21s-7-4.5-7-11a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 6.5-7 11-7 11z"/>',
    '<path d="M5 13l4 4L19 7"/>',
];
$product_json = [
    'id'         => $d['id'],
    'name'       => $d['name'],
    'sku'        => $d['sku'],
    'type'       => $d['type'],
    'price'      => $d['price'],
    'regular'    => $d['regular'],
    'variations' => $d['variations'],
    'images'     => array_map(function ($img) {
        return [
            'id'     => (int) $img,
            'src'    => wp_get_attachment_image_url($img, 'large'),
            'srcset' => wp_get_attachment_image_srcset($img, 'large'),
        ];
    }, $images),
    'deadline'   => $d['deadline'] ? $d['deadline'] * 1000 : 0,
    'source'     => $source,
    'rest'       => esc_url_raw(rest_url('gilllda/v1/lp-event')),
    'track'      => $enabled,
];
?>
<!doctype html>
<html <?php language_attributes(); ?> class="lp-<?= esc_attr($d['mode']); ?>">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="<?= esc_attr($d['accent']); ?>">
    <?php if ($hero_id) : ?>
        <link rel="preload" as="image" href="<?= esc_url(wp_get_attachment_image_url($hero_id, 'large')); ?>" imagesrcset="<?= esc_attr(wp_get_attachment_image_srcset($hero_id, 'large')); ?>" imagesizes="(min-width: 1024px) 50vw, 100vw" fetchpriority="high">
    <?php endif; ?>
    <style>:root{--lp-accent:<?= esc_html($d['accent']); ?>}</style>
    <?php wp_head(); ?>
    <?php if (function_exists('get_field')) {
        echo get_field('header-scripts', 'option') ?: ''; // phpcs:ignore -- admin-managed tracking snippets
    } ?>
</head>
<body <?php body_class('lp'); ?>>
<?php wp_body_open(); ?>

<?php if (!$enabled) : ?>
    <div class="lp-preview-bar">پیش‌نمایش مدیر — این لندینگ هنوز فعال نیست و بازدیدکننده‌ها به صفحه‌ی محصول منتقل می‌شوند.</div>
<?php endif; ?>

<header class="lp-top" data-top>
    <div class="lp-wrap lp-top__in">
        <a class="lp-logo" href="<?= esc_url(home_url('/')); ?>" aria-label="<?= esc_attr(get_bloginfo('name')); ?>">
            <?php if (!empty($logo['url'])) : ?>
                <img src="<?= esc_url($logo['sizes']['medium'] ?? $logo['url']); ?>" alt="<?= esc_attr(get_bloginfo('name')); ?>" width="96" height="40" decoding="async">
            <?php else : ?>
                <span><?= esc_html(get_bloginfo('name')); ?></span>
            <?php endif; ?>
        </a>
        <nav class="lp-nav" aria-label="بخش‌های صفحه">
            <a href="#lp-features">ویژگی‌ها</a>
            <a href="#lp-gallery">گالری</a>
            <?php if ($d['reviews']) : ?><a href="#lp-reviews">نظرات</a><?php endif; ?>
            <a href="#lp-faq">سؤالات</a>
        </nav>
        <a class="lp-btn lp-btn--sm" href="#lp-buy" data-cta="top"><?= esc_html($d['cta']); ?></a>
    </div>
    <div class="lp-progress" aria-hidden="true"><span data-progress></span></div>
</header>

<main id="lp-main">
    <!-- ============ HERO ============ -->
    <section class="lp-hero" aria-labelledby="lp-title">
        <div class="lp-hero__bg" aria-hidden="true">
            <span class="lp-blob lp-blob--1"></span>
            <span class="lp-blob lp-blob--2"></span>
            <span class="lp-grain"></span>
        </div>
        <div class="lp-wrap lp-hero__grid">
            <div class="lp-hero__text">
                <p class="lp-eyebrow lp-in" style="--d:0">
                    <span class="lp-dot"></span><?= esc_html($d['eyebrow']); ?>
                    <?php if ($d['offer']) : ?><span class="lp-chip"><?= esc_html($d['offer']); ?></span><?php endif; ?>
                </p>
                <h1 id="lp-title" class="lp-title">
                    <?php foreach ($words as $i => $w) : ?><span class="lp-word" style="--i:<?= (int) $i; ?>"><span><?= esc_html($w); ?></span></span> <?php endforeach; ?>
                </h1>
                <?php if ($d['tagline']) : ?>
                    <p class="lp-lead lp-in" style="--d:3"><?= esc_html($d['tagline']); ?></p>
                <?php endif; ?>

                <div class="lp-price lp-in" style="--d:4">
                    <?php if ($d['regular'] > $d['price']) : ?>
                        <del><?= gl_fa_num(number_format($d['regular'])); ?></del>
                    <?php endif; ?>
                    <strong data-price><?= wp_kses_post(gl_lp_money($d['price'])); ?></strong>
                    <?php if ($d['type'] === 'variable' && count(array_unique(wp_list_pluck($d['variations'], 'price'))) > 1) : ?><span class="lp-muted">شروع قیمت</span><?php endif; ?>
                    <?php if ($d['discount']) : ?><span class="lp-off"><?= gl_fa_num($d['discount']); ?>٪</span><?php endif; ?>
                </div>

                <?php foreach ($d['options'] as $opt) :
                    if (!array_filter(wp_list_pluck($opt['values'], 'color'))) {
                        continue;
                    } ?>
                    <div class="lp-swatches lp-in" style="--d:5" aria-label="رنگ‌ها">
                        <?php foreach ($opt['values'] as $v) : ?>
                            <button type="button" class="lp-swatch" style="--c:<?= esc_attr($v['color'] ?: '#ddd'); ?>" data-pick="<?= esc_attr($opt['key']); ?>" data-value="<?= esc_attr($v['slug']); ?>" title="<?= esc_attr($v['name']); ?>"><span class="screen-reader-text"><?= esc_html($v['name']); ?></span></button>
                        <?php endforeach; ?>
                        <span class="lp-muted"><?= gl_fa_num(count($opt['values'])); ?> رنگ</span>
                    </div>
                    <?php break; endforeach; ?>

                <div class="lp-actions lp-in" style="--d:6">
                    <a class="lp-btn lp-btn--lg" href="#lp-buy" data-cta="hero" data-magnetic>
                        <?= esc_html($d['cta']); ?>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"/></svg>
                    </a>
                    <?php if ($d['rating_n']) : ?>
                        <a class="lp-rating" href="#lp-reviews"><span class="lp-stars"><?= $stars($d['rating']); // phpcs:ignore ?></span><span><?= gl_fa_num(number_format($d['rating'], 1)); ?> از <?= gl_fa_num($d['rating_n']); ?> نظر</span></a>
                    <?php endif; ?>
                </div>

                <?php if ($d['deadline']) : ?>
                    <div class="lp-countdown lp-in" style="--d:7" data-countdown aria-live="polite">
                        <span class="lp-muted">پیشنهاد تا پایان:</span>
                        <span class="lp-cd"><b data-cd="d">۰</b><small>روز</small></span>
                        <span class="lp-cd"><b data-cd="h">۰۰</b><small>ساعت</small></span>
                        <span class="lp-cd"><b data-cd="m">۰۰</b><small>دقیقه</small></span>
                        <span class="lp-cd"><b data-cd="s">۰۰</b><small>ثانیه</small></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="lp-hero__media" data-tilt>
                <div class="lp-ring" aria-hidden="true"></div>
                <?php if ($hero_id) {
                    echo wp_get_attachment_image($hero_id, 'large', false, [
                        'class'         => 'lp-hero__img',
                        'fetchpriority' => 'high',
                        'loading'       => 'eager',
                        'decoding'      => 'async',
                        'sizes'         => '(min-width: 1024px) 50vw, 100vw',
                        'alt'           => esc_attr($d['name']),
                        'data-hero'     => '1',
                    ]);
                } ?>
                <div class="lp-float lp-float--a"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><?= $icons[0]; // phpcs:ignore ?></svg>ارسال به سراسر ایران</div>
                <div class="lp-float lp-float--b"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><?= $icons[1]; // phpcs:ignore ?></svg>۷ روز ضمانت تعویض</div>
            </div>
        </div>
        <a class="lp-scroll" href="#lp-features" aria-label="ادامه"><span></span></a>
    </section>

    <!-- ============ MARQUEE ============ -->
    <div class="lp-marquee" aria-hidden="true">
        <div class="lp-marquee__track">
            <?php for ($r = 0; $r < 2; $r++) : foreach ($d['benefits'] as $b) : ?>
                <span><?= esc_html($b[0]); ?></span><i>✦</i>
            <?php endforeach; endfor; ?>
        </div>
    </div>

    <!-- ============ FEATURES ============ -->
    <section id="lp-features" class="lp-section" aria-labelledby="lp-features-title">
        <div class="lp-wrap">
            <p class="lp-kicker" data-reveal>چرا <?= esc_html($d['name']); ?>؟</p>
            <h2 id="lp-features-title" class="lp-h2" data-reveal>ساخته شده برای همراهی هر روزت</h2>
            <div class="lp-features">
                <?php foreach ($d['benefits'] as $i => $b) : ?>
                    <article class="lp-feature" data-reveal style="--d:<?= (int) $i; ?>">
                        <span class="lp-feature__icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icons[$i % count($icons)]; // phpcs:ignore ?></svg></span>
                        <h3><?= esc_html($b[0]); ?></h3>
                        <?php if ($b[1]) : ?><p><?= esc_html($b[1]); ?></p><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============ GALLERY ============ -->
    <?php if (count($images) > 1 || $d['video']) : ?>
        <section id="lp-gallery" class="lp-section lp-gallery" aria-labelledby="lp-gallery-title">
            <div class="lp-wrap">
                <p class="lp-kicker" data-reveal>از هر زاویه</p>
                <h2 id="lp-gallery-title" class="lp-h2" data-reveal>جزئیاتی که دیده می‌شود</h2>
            </div>
            <div class="lp-strip" data-strip tabindex="0" aria-label="گالری تصاویر">
                <?php if ($d['video']) : ?>
                    <figure class="lp-shot lp-shot--video">
                        <video src="<?= esc_url($d['video']); ?>" muted loop playsinline preload="none" data-autoplay <?= $hero_id ? 'poster="' . esc_url(wp_get_attachment_image_url($hero_id, 'large')) . '"' : ''; ?>></video>
                    </figure>
                <?php endif; ?>
                <?php foreach ($images as $i => $img) : ?>
                    <figure class="lp-shot" data-reveal>
                        <button type="button" class="lp-shot__btn" data-zoom="<?= (int) $i; ?>" aria-label="بزرگ‌نمایی تصویر <?= gl_fa_num($i + 1); ?>">
                            <?= wp_get_attachment_image($img, 'large', false, ['loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(min-width: 1024px) 34vw, 80vw', 'alt' => esc_attr($d['name'] . ' — تصویر ' . gl_fa_num($i + 1))]); ?>
                        </button>
                    </figure>
                <?php endforeach; ?>
            </div>
            <div class="lp-wrap lp-strip__nav">
                <button type="button" class="lp-round" data-strip-prev aria-label="قبلی"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></button>
                <button type="button" class="lp-round" data-strip-next aria-label="بعدی"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg></button>
            </div>
        </section>
    <?php endif; ?>

    <!-- ============ BUY / CONFIGURATOR ============ -->
    <section id="lp-buy" class="lp-section lp-buy" aria-labelledby="lp-buy-title">
        <div class="lp-wrap lp-buy__grid">
            <div class="lp-buy__media" data-reveal>
                <?php if ($hero_id) {
                    echo wp_get_attachment_image($hero_id, 'large', false, ['class' => 'lp-buy__img', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(min-width: 1024px) 40vw, 100vw', 'alt' => esc_attr($d['name']), 'data-buy-img' => '1']);
                } ?>
            </div>
            <form class="lp-buy__form" method="post" action="<?= esc_url(wc_get_checkout_url()); ?>" data-buy-form data-reveal>
                <p class="lp-kicker">انتخاب کن و سفارش بده</p>
                <h2 id="lp-buy-title" class="lp-h2"><?= esc_html($d['name']); ?></h2>

                <div class="lp-price lp-price--buy">
                    <del data-regular <?= $d['regular'] > $d['price'] ? '' : 'hidden'; ?>><?= gl_fa_num(number_format($d['regular'])); ?></del>
                    <strong data-price><?= wp_kses_post(gl_lp_money($d['price'])); ?></strong>
                </div>

                <?php foreach ($d['options'] as $opt) : ?>
                    <fieldset class="lp-opt">
                        <legend><?= esc_html($opt['label']); ?>: <b data-picked="<?= esc_attr($opt['key']); ?>">انتخاب کنید</b></legend>
                        <div class="lp-opt__list">
                            <?php foreach ($opt['values'] as $v) : ?>
                                <label class="lp-pill <?= $v['color'] ? 'lp-pill--color' : ''; ?>">
                                    <input type="radio" name="<?= esc_attr($opt['key']); ?>" value="<?= esc_attr($v['slug']); ?>" data-label="<?= esc_attr($v['name']); ?>" required>
                                    <?php if ($v['color']) : ?><span class="lp-pill__dot" style="--c:<?= esc_attr($v['color']); ?>"></span><?php endif; ?>
                                    <span><?= esc_html($v['name']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>

                <div class="lp-qty">
                    <span>تعداد</span>
                    <div class="lp-qty__box">
                        <button type="button" data-qty="1" aria-label="افزایش">+</button>
                        <input type="number" name="quantity" value="1" min="1" max="10" inputmode="numeric" aria-label="تعداد">
                        <button type="button" data-qty="-1" aria-label="کاهش">−</button>
                    </div>
                </div>

                <p class="lp-stock" data-stock role="status"><?= $d['in_stock'] ? '' : 'این محصول فعلاً ناموجود است.'; ?></p>

                <input type="hidden" name="add-to-cart" value="<?= (int) $d['id']; ?>">
                <input type="hidden" name="product_id" value="<?= (int) $d['id']; ?>">
                <?php if ($d['type'] === 'variable') : ?><input type="hidden" name="variation_id" value="" data-variation><?php endif; ?>
                <input type="hidden" name="gl_lp" value="<?= (int) $d['id']; ?>">
                <input type="hidden" name="gl_lp_source" value="<?= esc_attr($source); ?>">

                <button type="submit" class="lp-btn lp-btn--lg lp-btn--block" data-buy <?= $d['in_stock'] ? '' : 'disabled'; ?>>
                    <span data-buy-label><?= esc_html($d['cta']); ?></span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12zM6 6 5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
                </button>
                <ul class="lp-trust">
                    <li>پرداخت امن درگاه بانکی</li>
                    <li>۷ روز ضمانت تعویض</li>
                    <li>ارسال سریع</li>
                </ul>
            </form>
        </div>
    </section>

    <!-- ============ SPECS + STORY ============ -->
    <?php if ($d['specs'] || $d['description']) : ?>
        <section class="lp-section lp-story" aria-labelledby="lp-story-title">
            <div class="lp-wrap lp-story__grid">
                <div data-reveal>
                    <p class="lp-kicker">داستان این کیف</p>
                    <h2 id="lp-story-title" class="lp-h2">مشخصات و جزئیات</h2>
                    <?php if ($d['specs']) : ?>
                        <dl class="lp-specs">
                            <?php foreach ($d['specs'] as $s) : ?>
                                <div><dt><?= esc_html($s[0]); ?></dt><dd><?= esc_html($s[1]); ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                </div>
                <?php if ($d['description']) : ?>
                    <div class="lp-desc" data-reveal data-more>
                        <div class="lp-desc__body"><?= wp_kses_post(wpautop($d['description'])); ?></div>
                        <button type="button" class="lp-link" data-more-btn>ادامه مطلب</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- ============ REVIEWS ============ -->
    <?php if ($d['reviews']) : ?>
        <section id="lp-reviews" class="lp-section lp-reviews" aria-labelledby="lp-reviews-title">
            <div class="lp-wrap">
                <p class="lp-kicker" data-reveal>تجربه خریداران</p>
                <h2 id="lp-reviews-title" class="lp-h2" data-reveal>
                    <?= $d['rating_n'] ? gl_fa_num(number_format($d['rating'], 1)) . ' از ۵ — ' : ''; ?>از زبان مشتری‌های گیلدا
                </h2>
                <div class="lp-reviews__grid">
                    <?php foreach ($d['reviews'] as $i => $r) : ?>
                        <figure class="lp-review" data-reveal style="--d:<?= (int) $i; ?>">
                            <?php if ($r['rating']) : ?><span class="lp-stars"><?= $stars($r['rating']); // phpcs:ignore ?></span><?php endif; ?>
                            <blockquote><?= esc_html($r['text']); ?></blockquote>
                            <figcaption><b><?= esc_html($r['name']); ?></b><span><?= esc_html($r['date']); ?></span></figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ============ FAQ ============ -->
    <section id="lp-faq" class="lp-section lp-faq" aria-labelledby="lp-faq-title">
        <div class="lp-wrap lp-faq__grid">
            <div data-reveal>
                <p class="lp-kicker">قبل از خرید</p>
                <h2 id="lp-faq-title" class="lp-h2">سؤالاتی که معمولاً می‌پرسید</h2>
                <?php if ($phone) : ?>
                    <a class="lp-link" href="tel:<?= esc_attr(preg_replace('/[^\d+]/', '', $phone)); ?>">سؤال دیگری دارید؟ تماس بگیرید</a>
                <?php endif; ?>
            </div>
            <div class="lp-faq__list">
                <?php foreach ($d['faq'] as $i => $q) : ?>
                    <details class="lp-q" data-reveal <?= $i === 0 ? 'open' : ''; ?>>
                        <summary><?= esc_html($q[0]); ?><span aria-hidden="true"></span></summary>
                        <p><?= esc_html($q[1]); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============ FINAL CTA ============ -->
    <section class="lp-final" aria-labelledby="lp-final-title">
        <div class="lp-final__bg" aria-hidden="true"><span class="lp-blob lp-blob--3"></span></div>
        <div class="lp-wrap lp-final__in" data-reveal>
            <h2 id="lp-final-title"><?= esc_html($d['final_title']); ?></h2>
            <p><?= $d['offer'] ? esc_html($d['offer']) . ' — ' : ''; ?>موجودی هر رنگ محدود است.</p>
            <a class="lp-btn lp-btn--lg lp-btn--light" href="#lp-buy" data-cta="final" data-magnetic><?= esc_html($d['cta']); ?></a>
        </div>
    </section>

    <!-- ============ RELATED ============ -->
    <?php if ($d['related']) : ?>
        <section class="lp-section lp-related" aria-labelledby="lp-related-title">
            <div class="lp-wrap">
                <h2 id="lp-related-title" class="lp-h2 lp-h2--sm" data-reveal>شاید این‌ها را هم دوست داشته باشید</h2>
                <div class="lp-related__grid">
                    <?php foreach ($d['related'] as $i => $rid) :
                        $rp = wc_get_product($rid);
                        if (!$rp || !$rp->is_visible()) {
                            continue;
                        }
                        $rurl = gl_lp_enabled($rid) ? gl_lp_url($rid, $source !== 'direct' ? ['utm_source' => $source] : []) : get_permalink($rid); ?>
                        <a class="lp-card" href="<?= esc_url($rurl); ?>" data-reveal style="--d:<?= (int) $i; ?>">
                            <?= $rp->get_image('woocommerce_thumbnail', ['loading' => 'lazy', 'decoding' => 'async', 'alt' => esc_attr($rp->get_name())]); ?>
                            <span class="lp-card__name"><?= esc_html($rp->get_name()); ?></span>
                            <span class="lp-card__price"><?= wp_kses_post($rp->get_price_html()); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<footer class="lp-foot">
    <div class="lp-wrap lp-foot__in">
        <span>© <?= gl_fa_num(wp_date('Y')); ?> <?= esc_html(get_bloginfo('name')); ?></span>
        <a href="<?= esc_url($d['permalink']); ?>">صفحه کامل محصول</a>
        <a href="<?= esc_url(home_url('/')); ?>">فروشگاه</a>
    </div>
</footer>

<!-- Sticky buy bar (mobile + after hero) -->
<div class="lp-bar" data-bar aria-hidden="true">
    <div class="lp-bar__in">
        <?php if ($hero_id) {
            echo wp_get_attachment_image($hero_id, 'thumbnail', false, ['class' => 'lp-bar__img', 'loading' => 'lazy', 'alt' => '']);
        } ?>
        <div class="lp-bar__txt"><b><?= esc_html($d['name']); ?></b><span data-price><?= wp_kses_post(gl_lp_money($d['price'])); ?></span></div>
        <a class="lp-btn" href="#lp-buy" data-cta="bar" tabindex="-1">خرید</a>
    </div>
</div>

<dialog class="lp-zoom" data-zoom-dialog aria-label="تصویر بزرگ">
    <button type="button" class="lp-zoom__close" data-zoom-close aria-label="بستن">×</button>
    <img alt="<?= esc_attr($d['name']); ?>" data-zoom-img>
</dialog>

<script id="gl-lp-data" type="application/json"><?= wp_json_encode($product_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
<?php wp_footer(); ?>
</body>
</html>
