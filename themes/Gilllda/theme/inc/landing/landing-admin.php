<?php
/**
 * Product landing pages — admin UI.
 *
 * - «لندینگ محصول» meta box on the product editor (toggle, overrides, UTM links, stats)
 * - «لندینگ» column + quick on/off row action in the products list
 * - Products → «لندینگ‌ها» overview with stats for every landing
 *
 * @package bluebox
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Marketing channels offered in the link builder. */
function gl_lp_channels() {
    return [
        'instagram' => 'اینستاگرام (بیو/استوری)',
        'telegram'  => 'تلگرام',
        'whatsapp'  => 'واتساپ',
        'sms'       => 'پیامک',
        'google'    => 'گوگل ادز',
        'yektanet'  => 'یکتانت / تبلیغات',
        'influencer'=> 'اینفلوئنسر',
        'email'     => 'ایمیل',
    ];
}

function gl_lp_source_label($key) {
    $c = gl_lp_channels();
    return $c[$key] ?? ($key === 'direct' ? 'مستقیم / نامشخص' : $key);
}

/* -------------------------------------------------------------------------
 * Meta box
 * ---------------------------------------------------------------------- */

add_action('add_meta_boxes_product', function () {
    add_meta_box('gl-landing', '🚀 لندینگ محصول', 'gl_lp_render_metabox', 'product', 'normal', 'high');
});

function gl_lp_render_metabox($post) {
    $set   = gl_lp_settings($post->ID);
    $on    = gl_lp_enabled($post->ID);
    $url   = gl_lp_url($post->ID);
    $stats = gl_lp_stats($post->ID);
    wp_nonce_field('gl_lp_save', 'gl_lp_nonce');
    $f = function ($k) use ($set) {
        return esc_attr($set[$k]);
    };
    ?>
    <style>
        .gl-lp{--a:#9d0617;font-size:13px}
        .gl-lp .row{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin:12px 0}
        .gl-lp label{display:block;font-weight:600;margin-bottom:4px}
        .gl-lp input[type=text],.gl-lp input[type=url],.gl-lp input[type=datetime-local],.gl-lp textarea,.gl-lp select{width:100%}
        .gl-lp textarea{min-height:92px;font-family:inherit}
        .gl-lp .hint{color:#646970;font-size:12px;margin-top:3px}
        .gl-lp .switch{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;background:#f6f7f7;border:1px solid #dcdcde}
        .gl-lp .switch.on{background:#edfaef;border-color:#68de7c}
        .gl-lp .linkbox{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
        .gl-lp .linkbox code{direction:ltr;padding:6px 8px;border-radius:6px;background:#fff;border:1px solid #dcdcde;max-width:100%;overflow:auto;white-space:nowrap}
        .gl-lp table.st{border-collapse:collapse;width:100%;margin-top:8px}
        .gl-lp table.st th,.gl-lp table.st td{border-bottom:1px solid #f0f0f1;padding:6px 8px;text-align:right}
        .gl-lp details{margin-top:14px;border:1px solid #dcdcde;border-radius:10px;padding:10px 14px;background:#fff}
        .gl-lp summary{cursor:pointer;font-weight:600}
        .gl-lp .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-top:10px}
        .gl-lp .kpi{border:1px solid #dcdcde;border-radius:10px;padding:10px;background:#fff}
        .gl-lp .kpi b{display:block;font-size:20px;margin-top:4px}
    </style>
    <div class="gl-lp">
        <div class="switch <?= $on ? 'on' : ''; ?>">
            <label style="margin:0;display:flex;gap:8px;align-items:center">
                <input type="checkbox" name="gl_lp_on" value="yes" <?php checked($on); ?>>
                لندینگ این محصول فعال باشد
            </label>
            <span style="flex:1"></span>
            <a class="button" href="<?= esc_url($url); ?>" target="_blank" rel="noopener"><?= $on ? 'مشاهده لندینگ' : 'پیش‌نمایش (فقط مدیر)'; ?></a>
        </div>
        <p class="hint">لندینگ به‌طور خودکار از عکس‌ها، رنگ‌ها، قیمت، تخفیف و نظرات همین محصول ساخته می‌شود. فیلدهای زیر همه اختیاری‌اند و فقط برای شخصی‌سازی هستند. اگر لندینگ خاموش باشد، لینکش به صفحه‌ی خود محصول منتقل می‌شود.</p>

        <div class="linkbox">
            <strong>لینک لندینگ:</strong>
            <code id="gl-lp-url"><?= esc_html(urldecode($url)); ?></code>
            <button type="button" class="button gl-copy" data-copy="<?= esc_attr(urldecode($url)); ?>">کپی</button>
        </div>

        <details <?= $on ? 'open' : ''; ?>>
            <summary>لینک‌های آماده برای کمپین‌ها (UTM)</summary>
            <p class="hint">برای هر کانال، لینک مخصوص همان کانال را بگذارید تا بازدید و فروش هر کانال جدا شمرده شود.</p>
            <div class="row" style="align-items:end">
                <div><label>نام کمپین (اختیاری)</label><input type="text" id="gl-lp-campaign" placeholder="مثلا yalda-1405" dir="ltr"></div>
            </div>
            <table class="st" id="gl-lp-links">
                <?php foreach (gl_lp_channels() as $key => $label) : ?>
                    <tr data-src="<?= esc_attr($key); ?>">
                        <td style="width:170px"><?= esc_html($label); ?></td>
                        <td><code class="gl-lp-link" dir="ltr"></code></td>
                        <td style="width:60px"><button type="button" class="button button-small gl-copy">کپی</button></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </details>

        <details>
            <summary>محتوا و ظاهر (اختیاری)</summary>
            <div class="row">
                <div><label>تیتر اصلی</label><input type="text" name="gl_lp[headline]" value="<?= $f('headline'); ?>" placeholder="<?= esc_attr(get_the_title($post)); ?>"><div class="hint">خالی = نام محصول</div></div>
                <div><label>برچسب بالای تیتر</label><input type="text" name="gl_lp[eyebrow]" value="<?= $f('eyebrow'); ?>" placeholder="کالکشن پاییز"><div class="hint">خالی = دسته‌بندی محصول</div></div>
                <div><label>متن دکمه خرید</label><input type="text" name="gl_lp[cta]" value="<?= $f('cta'); ?>" placeholder="همین حالا سفارش بده"></div>
            </div>
            <div class="row">
                <div><label>زیرتیتر</label><textarea name="gl_lp[tagline]" placeholder="خالی = توضیح کوتاه محصول"><?= esc_textarea($set['tagline']); ?></textarea></div>
                <div><label>مزیت‌ها (هر خط یک مورد: عنوان | توضیح)</label><textarea name="gl_lp[benefits]" placeholder="جیب داخلی زیپ‌دار | گوشی و کارت‌ها همیشه امن و در دسترس"><?= esc_textarea($set['benefits']); ?></textarea><div class="hint">خالی = مزیت‌های پیش‌فرض گیلدا (ارسال، ضمانت بازگشت، کیفیت، مشاوره استایل)</div></div>
            </div>
            <div class="row">
                <div><label>رنگ اصلی صفحه</label><input type="text" name="gl_lp[accent]" value="<?= $f('accent'); ?>" placeholder="#9d0617" dir="ltr"><div class="hint">خالی = اولین رنگ محصول</div></div>
                <div><label>حالت</label>
                    <select name="gl_lp[mode]">
                        <option value="light" <?php selected($set['mode'], 'light'); ?>>روشن</option>
                        <option value="dark" <?php selected($set['mode'], 'dark'); ?>>تیره (لوکس)</option>
                    </select>
                </div>
                <div><label>ویدیو (لینک mp4، اختیاری)</label><input type="url" name="gl_lp[video]" value="<?= esc_attr($set['video']); ?>" dir="ltr" placeholder="https://…/video.mp4"></div>
            </div>
            <div class="row">
                <div><label>سؤالات متداول (هر خط: سؤال | جواب)</label><textarea name="gl_lp[faq]"><?= esc_textarea($set['faq']); ?></textarea><div class="hint">خالی = سؤالات پیش‌فرض (ابعاد، ارسال، تعویض، رنگ)</div></div>
                <div><label>تیتر بخش پایانی</label><input type="text" name="gl_lp[final_title]" value="<?= $f('final_title'); ?>"></div>
            </div>
        </details>

        <details>
            <summary>پیشنهاد ویژه و فوریت (اختیاری)</summary>
            <div class="row">
                <div><label>متن نشان پیشنهاد</label><input type="text" name="gl_lp[offer]" value="<?= $f('offer'); ?>" placeholder="ارسال رایگان فقط امروز"><div class="hint">خالی = درصد تخفیف (اگر محصول حراج باشد)</div></div>
                <div><label>پایان شمارش معکوس</label><input type="datetime-local" name="gl_lp[countdown]" value="<?= esc_attr($set['countdown'] ? str_replace(' ', 'T', $set['countdown']) : ''); ?>"><div class="hint">خالی = تاریخ پایان حراج محصول (اگر تنظیم شده باشد)</div></div>
                <div><label>کد تخفیف خودکار</label><input type="text" name="gl_lp[coupon]" value="<?= $f('coupon'); ?>" dir="ltr" placeholder="INSTA10"><div class="hint">با خرید از لندینگ، این کد خودکار روی سبد اعمال می‌شود. باید قبلاً در ووکامرس ← کوپن‌ها ساخته شده باشد.</div></div>
            </div>
        </details>

        <details <?= !empty($stats) ? 'open' : ''; ?>>
            <summary>آمار لندینگ</summary>
            <?php gl_lp_render_stats($stats); ?>
        </details>
    </div>
    <script>
        (function () {
            var base = <?= wp_json_encode(urldecode($url)); ?>;
            var camp = document.getElementById('gl-lp-campaign');
            function build() {
                document.querySelectorAll('#gl-lp-links tr').forEach(function (tr) {
                    var u = base + '?utm_source=' + tr.dataset.src + '&utm_medium=' + (tr.dataset.src === 'google' || tr.dataset.src === 'yektanet' ? 'cpc' : 'social') + (camp.value ? '&utm_campaign=' + encodeURIComponent(camp.value.trim()) : '');
                    tr.querySelector('.gl-lp-link').textContent = u;
                    tr.querySelector('.gl-copy').dataset.copy = u;
                });
            }
            camp && camp.addEventListener('input', build);
            build();
            document.querySelectorAll('.gl-lp .gl-copy').forEach(function (b) {
                b.addEventListener('click', function () {
                    navigator.clipboard.writeText(b.dataset.copy).then(function () {
                        var t = b.textContent; b.textContent = '✓ کپی شد'; setTimeout(function () { b.textContent = t; }, 1400);
                    });
                });
            });
        })();
    </script>
    <?php
}

function gl_lp_render_stats($stats) {
    if (empty($stats)) {
        echo '<p class="hint">هنوز آماری ثبت نشده است.</p>';
        return;
    }
    $t = function ($type) use ($stats) {
        return (int) ($stats[$type]['total'] ?? 0);
    };
    $views = $t('view');
    $orders = $t('order');
    $conv = $views ? round($orders / $views * 100, 1) : 0;
    echo '<div class="kpis">';
    foreach ([['بازدید', $views], ['کلیک خرید', $t('cta')], ['افزودن به سبد', $t('cart')], ['سفارش', $orders], ['نرخ تبدیل', $conv . '٪'], ['فروش (تومان)', number_format((float) ($stats['revenue']['total'] ?? 0))]] as $k) {
        echo '<div class="kpi">' . esc_html($k[0]) . '<b>' . esc_html($k[1]) . '</b></div>';
    }
    echo '</div>';

    $sources = [];
    foreach (['view', 'cta', 'cart', 'order', 'revenue'] as $type) {
        foreach (($stats[$type] ?? []) as $src => $n) {
            if ($src !== 'total') {
                $sources[$src][$type] = $n;
            }
        }
    }
    if ($sources) {
        echo '<table class="st"><tr><th>منبع</th><th>بازدید</th><th>کلیک خرید</th><th>سبد</th><th>سفارش</th><th>فروش</th></tr>';
        foreach ($sources as $src => $row) {
            echo '<tr><td>' . esc_html(gl_lp_source_label($src)) . '</td><td>' . (int) ($row['view'] ?? 0) . '</td><td>' . (int) ($row['cta'] ?? 0) . '</td><td>' . (int) ($row['cart'] ?? 0) . '</td><td>' . (int) ($row['order'] ?? 0) . '</td><td>' . esc_html(number_format((float) ($row['revenue'] ?? 0))) . '</td></tr>';
        }
        echo '</table>';
    }
}

add_action('save_post_product', function ($post_id) {
    if (!isset($_POST['gl_lp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gl_lp_nonce'])), 'gl_lp_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || !current_user_can('edit_post', $post_id)) {
        return;
    }
    update_post_meta($post_id, GL_LP_ON, !empty($_POST['gl_lp_on']) ? 'yes' : 'no');

    $in  = isset($_POST['gl_lp']) && is_array($_POST['gl_lp']) ? wp_unslash($_POST['gl_lp']) : [];
    $out = [];
    foreach (gl_lp_defaults() as $k => $def) {
        $v = $in[$k] ?? $def;
        switch ($k) {
            case 'tagline':
            case 'benefits':
            case 'faq':
                $out[$k] = sanitize_textarea_field($v);
                break;
            case 'accent':
                $out[$k] = sanitize_hex_color($v) ?: '';
                break;
            case 'mode':
                $out[$k] = $v === 'dark' ? 'dark' : 'light';
                break;
            case 'video':
                $out[$k] = esc_url_raw($v);
                break;
            case 'countdown':
                $out[$k] = preg_match('/^\d{4}-\d{2}-\d{2}T?\s?\d{2}:\d{2}/', $v) ? str_replace('T', ' ', substr($v, 0, 16)) : '';
                break;
            case 'coupon':
                $out[$k] = wc_format_coupon_code($v);
                break;
            default:
                $out[$k] = sanitize_text_field($v);
        }
    }
    update_post_meta($post_id, GL_LP_META, $out);
});

/* -------------------------------------------------------------------------
 * Products list: column + quick toggle
 * ---------------------------------------------------------------------- */

add_filter('manage_edit-product_columns', function ($cols) {
    $cols['gl_lp'] = 'لندینگ';
    return $cols;
}, 20);

add_action('manage_product_posts_custom_column', function ($col, $post_id) {
    if ($col !== 'gl_lp') {
        return;
    }
    $on = gl_lp_enabled($post_id);
    $toggle = wp_nonce_url(admin_url('admin-post.php?action=gl_lp_toggle&id=' . $post_id), 'gl_lp_toggle_' . $post_id);
    echo $on
        ? '<a href="' . esc_url(gl_lp_url($post_id)) . '" target="_blank" style="color:#008a20;font-weight:600">● فعال</a><br><a href="' . esc_url($toggle) . '" style="font-size:11px;color:#b32d2e">خاموش کن</a>'
        : '<span style="color:#8c8f94">خاموش</span><br><a href="' . esc_url($toggle) . '" style="font-size:11px">فعال کن</a>';
}, 10, 2);

add_action('admin_post_gl_lp_toggle', function () {
    $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    check_admin_referer('gl_lp_toggle_' . $id);
    if (!$id || !current_user_can('edit_post', $id)) {
        wp_die('دسترسی ندارید.');
    }
    update_post_meta($id, GL_LP_ON, gl_lp_enabled($id) ? 'no' : 'yes');
    wp_safe_redirect(wp_get_referer() ?: admin_url('edit.php?post_type=product'));
    exit;
});

/* -------------------------------------------------------------------------
 * Products → لندینگ‌ها
 * ---------------------------------------------------------------------- */

add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=product', 'لندینگ‌های محصولات', 'لندینگ‌ها', 'edit_products', 'gl-landings', 'gl_lp_render_overview');
});

function gl_lp_render_overview() {
    $ids = get_posts([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 200,
        'fields'         => 'ids',
        'meta_key'       => GL_LP_ON,
        'meta_value'     => 'yes',
    ]);
    ?>
    <div class="wrap">
        <h1>لندینگ‌های محصولات</h1>
        <p>برای فعال کردن لندینگ یک محصول، در لیست محصولات روی «فعال کن» در ستون لندینگ بزنید یا در صفحه ویرایش محصول، باکس «لندینگ محصول» را باز کنید.</p>
        <table class="widefat striped">
            <thead><tr><th>محصول</th><th>لینک</th><th>بازدید</th><th>کلیک خرید</th><th>سبد</th><th>سفارش</th><th>نرخ تبدیل</th><th>فروش (تومان)</th><th></th></tr></thead>
            <tbody>
            <?php if (!$ids) : ?>
                <tr><td colspan="9">هنوز هیچ لندینگی فعال نشده است.</td></tr>
            <?php endif; ?>
            <?php foreach ($ids as $id) :
                $s = gl_lp_stats($id);
                $v = (int) ($s['view']['total'] ?? 0);
                $o = (int) ($s['order']['total'] ?? 0); ?>
                <tr>
                    <td><strong><?= esc_html(get_the_title($id)); ?></strong></td>
                    <td><a href="<?= esc_url(gl_lp_url($id)); ?>" target="_blank" dir="ltr"><?= esc_html(urldecode(wp_make_link_relative(gl_lp_url($id)))); ?></a></td>
                    <td><?= $v; ?></td>
                    <td><?= (int) ($s['cta']['total'] ?? 0); ?></td>
                    <td><?= (int) ($s['cart']['total'] ?? 0); ?></td>
                    <td><?= $o; ?></td>
                    <td><?= $v ? esc_html(round($o / $v * 100, 1)) . '٪' : '—'; ?></td>
                    <td><?= esc_html(number_format((float) ($s['revenue']['total'] ?? 0))); ?></td>
                    <td><a class="button button-small" href="<?= esc_url(get_edit_post_link($id) . '#gl-landing'); ?>">تنظیمات و لینک‌ها</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
