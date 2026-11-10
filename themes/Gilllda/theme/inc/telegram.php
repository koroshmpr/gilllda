<?php
/**
 * Telegram auto-publishing.
 *
 *   new blog post  → blog channel  (ACF option channels.blog, e.g. t.me/gillldactionary)
 *   new product    → shop channel  (ACF option channels.shop, e.g. t.me/gillldastore)
 *
 * Setup: Settings → «تلگرام گیلدا»: paste the bot token (the bot must be an admin of both
 * channels with "post messages" permission). Channel usernames are read from the existing
 * «channels» option; they can be overridden on the settings page.
 *
 * Sending runs from WP-Cron ~30 s after publishing (so the featured image & meta are saved and
 * the editor never waits on Telegram). Each post is sent once (meta gl_tg_sent); editors can
 * re-send from the post box or the «ارسال به تلگرام» row action.
 *
 * @package bluebox
 */

if (!defined('ABSPATH')) {
    exit;
}

const GL_TG_OPTION = 'gl_telegram';

function gl_tg_settings() {
    $s = get_option(GL_TG_OPTION, []);
    $s = is_array($s) ? $s : [];
    $channels = function_exists('get_field') ? (array) get_field('channels', 'option') : [];
    $from_url = function ($c) {
        $url = $c['url'] ?? ($c['link'] ?? '');
        return preg_match('~t\.me/([A-Za-z0-9_]{4,})~', (string) $url, $m) ? '@' . $m[1] : '';
    };
    return wp_parse_args($s, [
        'token'      => defined('GL_TELEGRAM_TOKEN') ? GL_TELEGRAM_TOKEN : '',
        'blog_chat'  => $from_url($channels['blog'] ?? []),
        'shop_chat'  => $from_url($channels['shop'] ?? []),
        'auto_post'  => 1,
        'auto_prod'  => 1,
        'api_base'   => 'https://api.telegram.org',
    ]);
}

function gl_tg_chat_for($post) {
    $s = gl_tg_settings();
    if ($post->post_type === 'post') {
        return $s['blog_chat'];
    }
    if ($post->post_type === 'product') {
        return $s['shop_chat'];
    }
    return '';
}

/** Which channel button the header shows on the current page: 'blog', 'shop' or ''. */
function gl_header_channel() {
    if (is_singular('post') || is_home() || is_category() || is_tag() || is_author()) {
        return 'blog';
    }
    if (function_exists('is_woocommerce') && (is_woocommerce() || is_front_page())) {
        return 'shop';
    }
    return '';
}

/* -------------------------------------------------------------------------
 * Message building
 * ---------------------------------------------------------------------- */

function gl_tg_esc($text) {
    return htmlspecialchars(html_entity_decode(wp_strip_all_tags((string) $text), ENT_QUOTES, 'UTF-8'), ENT_NOQUOTES, 'UTF-8');
}

function gl_tg_link($url) {
    return add_query_arg(['utm_source' => 'telegram', 'utm_medium' => 'channel'], $url);
}

/** @return array{caption:string, photo:string, button:string, url:string} */
function gl_tg_message($post) {
    $fa = function ($n) {
        return function_exists('gl_fa_num') ? gl_fa_num($n) : $n;
    };
    $photo = get_the_post_thumbnail_url($post, 'large') ?: '';
    $title = gl_tg_esc(get_the_title($post));

    if ($post->post_type === 'product' && function_exists('wc_get_product')) {
        $p   = wc_get_product($post->ID);
        $url = (function_exists('gl_lp_enabled') && gl_lp_enabled($post->ID)) ? gl_lp_url($post->ID) : get_permalink($post);
        $lines = ['🛍 <b>' . $title . '</b>', ''];
        $short = wp_trim_words(wp_strip_all_tags($p ? ($p->get_short_description() ?: $p->get_description()) : ''), 40);
        if ($short) {
            $lines[] = gl_tg_esc($short);
            $lines[] = '';
        }
        if ($p) {
            $price   = (float) ($p->is_type('variable') ? $p->get_variation_price('min', true) : wc_get_price_to_display($p));
            $regular = (float) ($p->is_type('variable') ? $p->get_variation_regular_price('min', true) : wc_get_price_to_display($p, ['price' => $p->get_regular_price()]));
            if ($price > 0) {
                $line = '💰 ' . $fa(number_format($price)) . ' تومان';
                if ($regular > $price) {
                    $line .= '  <s>' . $fa(number_format($regular)) . '</s>  🔥 ' . $fa((int) round(100 - $price / $regular * 100)) . '٪ تخفیف';
                }
                $lines[] = $line;
            }
            $colors = $p->get_attribute('pa_color');
            if ($colors) {
                $lines[] = '🎨 رنگ‌ها: ' . gl_tg_esc($colors);
            }
            $lines[] = '🚚 ارسال به سراسر ایران · ۷ روز ضمانت تعویض';
        }
        $lines[] = '';
        $lines[] = '#' . str_replace(' ', '_', gl_tg_esc(get_the_title($post))) . ' #کیف_زنانه #گیلدا';
        return ['caption' => implode("\n", $lines), 'photo' => $photo, 'button' => 'مشاهده و خرید 🛒', 'url' => gl_tg_link($url)];
    }

    // Blog post
    $summary = trim((string) get_post_meta($post->ID, 'gl_tldr', true)) ?: get_the_excerpt($post);
    $cat     = get_the_category($post->ID)[0] ?? null;
    $series  = function_exists('gl_series_info') ? gl_series_info($post->ID) : null;
    $lines   = ['📖 <b>' . $title . '</b>', ''];
    if ($series && $series['total'] > 1) {
        $lines[] = '🗂 پرونده «' . gl_tg_esc($series['name']) . '» — قسمت ' . $fa($series['number']);
        $lines[] = '';
    }
    $lines[] = gl_tg_esc(wp_trim_words($summary, 45));
    $takeaways = function_exists('gl_meta_lines') ? array_slice(gl_meta_lines($post->ID, 'gl_takeaways'), 0, 3) : [];
    if ($takeaways) {
        $lines[] = '';
        foreach ($takeaways as $t) {
            $lines[] = '✔️ ' . gl_tg_esc($t);
        }
    }
    $lines[] = '';
    $minutes = function_exists('gl_reading_minutes') ? gl_reading_minutes($post->ID) : 0;
    $lines[] = '⏱ ' . $fa($minutes) . ' دقیقه مطالعه' . ($cat ? '  ·  #' . str_replace(' ', '_', gl_tg_esc($cat->name)) : '') . ' #مجله_گیلدا';

    $caption = implode("\n", $lines);
    return ['caption' => $caption, 'photo' => $photo, 'button' => 'مطالعه کامل مقاله 📖', 'url' => gl_tg_link(get_permalink($post))];
}

/* -------------------------------------------------------------------------
 * Sending
 * ---------------------------------------------------------------------- */

/** @return true|WP_Error */
function gl_tg_send($post_id, $force = false) {
    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish' || !in_array($post->post_type, ['post', 'product'], true)) {
        return new WP_Error('gl_tg', 'نوشته منتشر نشده است.');
    }
    if (!$force && get_post_meta($post_id, 'gl_tg_sent', true)) {
        return true;
    }
    $s    = gl_tg_settings();
    $chat = gl_tg_chat_for($post);
    if (!$s['token'] || !$chat) {
        return new WP_Error('gl_tg', 'توکن ربات یا شناسه کانال تنظیم نشده است.');
    }

    $m       = gl_tg_message($post);
    $markup  = wp_json_encode(['inline_keyboard' => [[['text' => $m['button'], 'url' => $m['url']]]]]);
    $caption = $m['caption'];
    $method  = 'sendMessage';
    $body    = ['chat_id' => $chat, 'parse_mode' => 'HTML', 'reply_markup' => $markup];

    if ($m['photo'] && mb_strlen($caption) <= 1024) {
        $method          = 'sendPhoto';
        $body['photo']   = $m['photo'];
        $body['caption'] = $caption;
    } else {
        $body['text'] = ($m['photo'] ? '<a href="' . esc_url($m['photo']) . '">&#8205;</a>' : '') . $caption;
        $body['link_preview_options'] = wp_json_encode(['is_disabled' => false, 'show_above_text' => true]);
    }

    $res  = wp_remote_post(trailingslashit($s['api_base']) . 'bot' . $s['token'] . '/' . $method, ['timeout' => 20, 'body' => $body]);
    $data = is_wp_error($res) ? null : json_decode(wp_remote_retrieve_body($res), true);

    if (is_wp_error($res) || empty($data['ok'])) {
        $err = is_wp_error($res) ? $res->get_error_message() : ($data['description'] ?? 'unknown error');
        update_post_meta($post_id, 'gl_tg_error', sanitize_text_field($err));
        update_option('gl_telegram_last_error', sanitize_text_field(get_the_title($post) . ': ' . $err), false);
        return new WP_Error('gl_tg', $err);
    }
    update_post_meta($post_id, 'gl_tg_sent', ['id' => (int) ($data['result']['message_id'] ?? 0), 'chat' => $chat, 'time' => time()]);
    delete_post_meta($post_id, 'gl_tg_error');
    return true;
}

add_action('gl_tg_send_event', function ($post_id) {
    gl_tg_send((int) $post_id);
});

/** Queue on first publish (not on updates). */
add_action('transition_post_status', function ($new, $old, $post) {
    if ($new !== 'publish' || $old === 'publish' || !in_array($post->post_type, ['post', 'product'], true)) {
        return;
    }
    $s = gl_tg_settings();
    if (($post->post_type === 'post' && empty($s['auto_post'])) || ($post->post_type === 'product' && empty($s['auto_prod']))) {
        return;
    }
    if (get_post_meta($post->ID, 'gl_tg_skip', true) === '1' || get_post_meta($post->ID, 'gl_tg_sent', true)) {
        return;
    }
    if (!wp_next_scheduled('gl_tg_send_event', [$post->ID])) {
        wp_schedule_single_event(time() + 30, 'gl_tg_send_event', [$post->ID]);
    }
}, 10, 3);

/* -------------------------------------------------------------------------
 * Admin: settings page, post box, row action, bulk send
 * ---------------------------------------------------------------------- */

if (is_admin()) {
    add_action('admin_menu', function () {
        add_options_page('تلگرام گیلدا', 'تلگرام گیلدا', 'manage_options', 'gl-telegram', 'gl_tg_settings_page');
    });

    add_action('admin_post_gl_tg_save', function () {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی ندارید.');
        }
        check_admin_referer('gl_tg_save');
        $in  = wp_unslash($_POST);
        $old = get_option(GL_TG_OPTION, []);
        $chat = function ($v) {
            $v = trim((string) $v);
            return preg_match('/^(@[A-Za-z0-9_]{4,}|-?\d+)$/', $v) ? $v : '';
        };
        update_option(GL_TG_OPTION, [
            // empty token field = keep the saved one (it is never printed back)
            'token'     => trim((string) ($in['token'] ?? '')) !== '' ? sanitize_text_field($in['token']) : ($old['token'] ?? ''),
            'blog_chat' => $chat($in['blog_chat'] ?? ''),
            'shop_chat' => $chat($in['shop_chat'] ?? ''),
            'auto_post' => empty($in['auto_post']) ? 0 : 1,
            'auto_prod' => empty($in['auto_prod']) ? 0 : 1,
            'api_base'  => esc_url_raw($in['api_base'] ?? '') ?: 'https://api.telegram.org',
        ], false);
        $msg = 'saved';
        if (!empty($in['test'])) {
            $s   = gl_tg_settings();
            $res = wp_remote_get(trailingslashit($s['api_base']) . 'bot' . $s['token'] . '/getMe', ['timeout' => 15]);
            $d   = is_wp_error($res) ? null : json_decode(wp_remote_retrieve_body($res), true);
            $msg = !empty($d['ok']) ? 'ok:' . ($d['result']['username'] ?? '') : 'fail';
        }
        wp_safe_redirect(add_query_arg(['page' => 'gl-telegram', 'msg' => rawurlencode($msg)], admin_url('options-general.php')));
        exit;
    });

    add_action('admin_post_gl_tg_bulk', function () {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی ندارید.');
        }
        check_admin_referer('gl_tg_bulk');
        $type = ($_POST['type'] ?? '') === 'product' ? 'product' : 'post';
        $ids  = get_posts(['post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 30, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'ASC',
            'meta_query' => [['key' => 'gl_tg_sent', 'compare' => 'NOT EXISTS']]]);
        // One message every 4 s keeps us well under Telegram's per-channel limits.
        foreach ($ids as $i => $id) {
            wp_schedule_single_event(time() + 10 + $i * 4, 'gl_tg_send_event', [$id]);
        }
        wp_safe_redirect(add_query_arg(['page' => 'gl-telegram', 'msg' => 'queued:' . count($ids)], admin_url('options-general.php')));
        exit;
    });

    function gl_tg_settings_page() {
        $s   = gl_tg_settings();
        $msg = isset($_GET['msg']) ? sanitize_text_field(wp_unslash($_GET['msg'])) : '';
        $err = get_option('gl_telegram_last_error');
        $count = function ($type) {
            return count(get_posts(['post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => [['key' => 'gl_tg_sent', 'compare' => 'NOT EXISTS']]]));
        };
        ?>
        <div class="wrap">
            <h1>ارسال خودکار به تلگرام</h1>
            <?php if ($msg === 'saved') : ?><div class="notice notice-success"><p>ذخیره شد.</p></div><?php endif; ?>
            <?php if (strpos($msg, 'ok:') === 0) : ?><div class="notice notice-success"><p>اتصال برقرار است: ربات @<?= esc_html(substr($msg, 3)); ?></p></div><?php endif; ?>
            <?php if ($msg === 'fail') : ?><div class="notice notice-error"><p>اتصال به تلگرام ناموفق بود؛ توکن را بررسی کنید.</p></div><?php endif; ?>
            <?php if (strpos($msg, 'queued:') === 0) : ?><div class="notice notice-info"><p><?= esc_html(gl_fa_num(substr($msg, 7))); ?> مورد در صف ارسال قرار گرفت (هر ۴ ثانیه یکی).</p></div><?php endif; ?>
            <?php if ($err) : ?><div class="notice notice-warning"><p>آخرین خطا: <?= esc_html($err); ?></p></div><?php endif; ?>

            <form method="post" action="<?= esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('gl_tg_save'); ?>
                <input type="hidden" name="action" value="gl_tg_save">
                <table class="form-table" role="presentation">
                    <tr><th><label for="gl-tg-token">توکن ربات</label></th>
                        <td><input id="gl-tg-token" name="token" type="password" class="regular-text" dir="ltr" autocomplete="off" placeholder="<?= $s['token'] ? '•••••• (ذخیره شده — برای تغییر، جدید را وارد کنید)' : '123456:ABC-DEF…'; ?>">
                            <p class="description">از @BotFather بگیرید. ربات را در هر دو کانال ادمین کنید (با دسترسی ارسال پیام).</p></td></tr>
                    <tr><th><label for="gl-tg-blog">کانال مقالات</label></th>
                        <td><input id="gl-tg-blog" name="blog_chat" class="regular-text" dir="ltr" value="<?= esc_attr($s['blog_chat']); ?>" placeholder="@gillldactionary"></td></tr>
                    <tr><th><label for="gl-tg-shop">کانال فروشگاه</label></th>
                        <td><input id="gl-tg-shop" name="shop_chat" class="regular-text" dir="ltr" value="<?= esc_attr($s['shop_chat']); ?>" placeholder="@gillldastore"></td></tr>
                    <tr><th>ارسال خودکار</th>
                        <td><label><input type="checkbox" name="auto_post" value="1" <?php checked($s['auto_post']); ?>> مقاله‌ی تازه منتشرشده → کانال مقالات</label><br>
                            <label><input type="checkbox" name="auto_prod" value="1" <?php checked($s['auto_prod']); ?>> محصول تازه منتشرشده → کانال فروشگاه</label></td></tr>
                    <tr><th><label for="gl-tg-api">آدرس API</label></th>
                        <td><input id="gl-tg-api" name="api_base" class="regular-text" dir="ltr" value="<?= esc_attr($s['api_base']); ?>">
                            <p class="description">فقط اگر سرور به api.telegram.org دسترسی ندارد، آدرس یک رله (پراکسی) را بگذارید.</p></td></tr>
                </table>
                <p><button class="button button-primary">ذخیره</button> <button class="button" name="test" value="1">ذخیره و تست اتصال</button></p>
            </form>

            <hr>
            <h2>ارسال مطالب قبلی</h2>
            <p>مواردی که هنوز به تلگرام نرفته‌اند (حداکثر ۳۰ مورد در هر بار، به ترتیب قدیمی به جدید):</p>
            <form method="post" action="<?= esc_url(admin_url('admin-post.php')); ?>" style="display:flex;gap:8px">
                <?php wp_nonce_field('gl_tg_bulk'); ?>
                <input type="hidden" name="action" value="gl_tg_bulk">
                <button class="button" name="type" value="post">ارسال مقالات (<?= esc_html(gl_fa_num($count('post'))); ?>)</button>
                <button class="button" name="type" value="product">ارسال محصولات (<?= esc_html(gl_fa_num($count('product'))); ?>)</button>
            </form>
        </div>
        <?php
    }

    /* Post box */
    foreach (['post', 'product'] as $pt) {
        add_action('add_meta_boxes_' . $pt, function ($post) use ($pt) {
            add_meta_box('gl-tg', 'تلگرام', function ($post) use ($pt) {
                wp_nonce_field('gl_tg_box', 'gl_tg_nonce');
                $sent = get_post_meta($post->ID, 'gl_tg_sent', true);
                $err  = get_post_meta($post->ID, 'gl_tg_error', true);
                $chat = gl_tg_chat_for($post);
                echo '<p>کانال: <code dir="ltr">' . esc_html($chat ?: '—') . '</code></p>';
                if ($sent) {
                    echo '<p style="color:#008a20">✓ ارسال شده (' . esc_html(wp_date('Y-m-d H:i', (int) ($sent['time'] ?? 0))) . ')</p>';
                } elseif ($post->post_status === 'publish') {
                    echo '<p>هنوز ارسال نشده.</p>';
                }
                if ($err) {
                    echo '<p style="color:#b32d2e">خطا: ' . esc_html($err) . '</p>';
                }
                if (!$sent && $post->post_status !== 'publish') {
                    echo '<label><input type="checkbox" name="gl_tg_skip" value="1" ' . checked(get_post_meta($post->ID, 'gl_tg_skip', true), '1', false) . '> هنگام انتشار به تلگرام ارسال نشود</label>';
                }
                if ($post->post_status === 'publish') {
                    $url = wp_nonce_url(admin_url('admin-post.php?action=gl_tg_now&id=' . $post->ID), 'gl_tg_now_' . $post->ID);
                    echo '<p><a class="button" href="' . esc_url($url) . '">' . ($sent ? 'ارسال دوباره' : 'ارسال الان') . '</a></p>';
                }
            }, $pt, 'side');
        });
        if ($pt !== 'post') {
            continue; // products use post_row_actions too — registered once below
        }
        add_filter('post_row_actions', function ($actions, $post) {
            if (in_array($post->post_type, ['post', 'product'], true) && $post->post_status === 'publish' && current_user_can('edit_post', $post->ID)) {
                $url = wp_nonce_url(admin_url('admin-post.php?action=gl_tg_now&id=' . $post->ID), 'gl_tg_now_' . $post->ID);
                $actions['gl_tg'] = '<a href="' . esc_url($url) . '">ارسال به تلگرام</a>';
            }
            return $actions;
        }, 10, 2);
    }

    add_action('save_post', function ($post_id) {
        if (!isset($_POST['gl_tg_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gl_tg_nonce'])), 'gl_tg_box') || !current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['gl_tg_skip'])) {
            update_post_meta($post_id, 'gl_tg_skip', '1');
        } else {
            delete_post_meta($post_id, 'gl_tg_skip');
        }
    }, 5);

    add_action('admin_post_gl_tg_now', function () {
        $id = absint($_GET['id'] ?? 0);
        check_admin_referer('gl_tg_now_' . $id);
        if (!$id || !current_user_can('edit_post', $id)) {
            wp_die('دسترسی ندارید.');
        }
        $r = gl_tg_send($id, true);
        $back = wp_get_referer() ?: admin_url('edit.php');
        wp_safe_redirect(add_query_arg('gl_tg', is_wp_error($r) ? 'err' : 'ok', $back));
        exit;
    });

    add_action('admin_notices', function () {
        if (!isset($_GET['gl_tg'])) {
            return;
        }
        $ok = $_GET['gl_tg'] === 'ok';
        echo '<div class="notice notice-' . ($ok ? 'success' : 'error') . ' is-dismissible"><p>' . ($ok ? 'به تلگرام ارسال شد.' : 'ارسال به تلگرام ناموفق بود: ' . esc_html((string) get_option('gl_telegram_last_error'))) . '</p></div>';
    });
}
