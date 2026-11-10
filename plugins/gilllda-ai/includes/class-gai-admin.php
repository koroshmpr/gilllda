<?php
/**
 * Admin UI (Persian, RTL): dashboard/status, AI bot & referral reports, content readiness audit, settings.
 * All actions go through admin-post.php with capability + nonce checks.
 *
 * @package GillldaAI
 */

if (!defined('ABSPATH')) {
    exit;
}

final class GAI_Admin
{
    const SLUG = 'gilllda-ai';
    const CAP  = 'manage_options';

    /** @var string */
    private static $hook = '';

    public static function boot()
    {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
        foreach (array('regenerate', 'audit', 'save', 'clear_log') as $a) {
            add_action('admin_post_gai_' . $a, array(__CLASS__, 'action_' . $a));
        }
    }

    public static function menu()
    {
        self::$hook = add_menu_page(
            'هوش مصنوعی گیلدا',
            'هوش مصنوعی گیلدا',
            self::CAP,
            self::SLUG,
            array(__CLASS__, 'render'),
            'dashicons-superhero-alt',
            81
        );
    }

    public static function assets($hook)
    {
        if ($hook !== self::$hook) {
            return;
        }
        wp_enqueue_style('gai-admin', GAI_URL . 'assets/admin.css', array(), GAI_VERSION);
    }

    private static function url($tab = 'dashboard', array $args = array())
    {
        return add_query_arg(array_merge(array('page' => self::SLUG, 'tab' => $tab), $args), admin_url('admin.php'));
    }

    private static function guard($action)
    {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('دسترسی ندارید.', 'gilllda-ai'), 403);
        }
        check_admin_referer($action);
    }

    private static function back($tab, $msg)
    {
        wp_safe_redirect(self::url($tab, array('gai_msg' => $msg)));
        exit;
    }

    /* =====================================================================
     * Actions
     * ================================================================== */

    public static function action_regenerate()
    {
        self::guard('gai_regenerate');
        delete_option(GAI_Core::OPT_LOCK);
        update_option(GAI_Core::OPT_CHANGED, time(), false);
        $ok = GAI_Core::rebuild();
        self::back('dashboard', $ok ? 'built' : 'build_err');
    }

    public static function action_audit()
    {
        self::guard('gai_audit');
        require_once GAI_DIR . 'includes/class-gai-audit.php';
        GAI_Audit::refresh();
        self::back('audit', 'audited');
    }

    public static function action_clear_log()
    {
        self::guard('gai_clear_log');
        global $wpdb;
        $wpdb->query('TRUNCATE TABLE ' . GAI_Tracker::table()); // phpcs:ignore
        self::back('reports', 'cleared');
    }

    public static function action_save()
    {
        self::guard('gai_save');
        $in = wp_unslash($_POST); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $d  = GAI_Core::defaults();
        $s  = GAI_Core::settings();

        foreach (array('llms_txt', 'llms_full', 'md', 'md_accept', 'md_head', 'robots', 'schema_org', 'schema_store', 'schema_speak', 'track', 'track_refs') as $k) {
            $s[$k] = empty($in[$k]) ? 0 : 1;
        }
        $types = array();
        foreach ((array) ($in['types'] ?? array()) as $pt) {
            $pt = sanitize_key($pt);
            if (post_type_exists($pt)) {
                $types[] = $pt;
            }
        }
        $s['types']       = $types;
        $s['exclude_ids'] = implode(', ', array_filter(array_map('absint', preg_split('/[\s,،]+/u', (string) ($in['exclude_ids'] ?? '')))));
        $s['full_cap_kb'] = min(20000, max(100, absint($in['full_cap_kb'] ?? $d['full_cap_kb'])));
        $s['retention']   = min(365, max(7, absint($in['retention'] ?? $d['retention'])));
        $s['intro']       = sanitize_textarea_field($in['intro'] ?? '');
        if (!empty($in['intro_auto'])) {
            require_once GAI_DIR . 'includes/class-gai-markdown.php';
            require_once GAI_DIR . 'includes/class-gai-builder.php';
            $s['intro'] = GAI_Builder::auto_intro();
        }
        $s['facts']        = sanitize_textarea_field($in['facts'] ?? '');
        $s['phone']        = sanitize_text_field($in['phone'] ?? '');
        $s['email']        = sanitize_email($in['email'] ?? '');
        $s['robots_extra'] = sanitize_textarea_field($in['robots_extra'] ?? '');
        $s['speakable']    = sanitize_textarea_field($in['speakable'] ?? '');
        $s['schema_faq']   = in_array($in['schema_faq'] ?? '', array('auto', 'off', 'force'), true) ? $in['schema_faq'] : 'auto';
        $bots = array();
        foreach (array_keys(GAI_Core::bots()) as $token) {
            $v              = $in['bots'][$token] ?? 'allow';
            $bots[$token]   = in_array($v, array('allow', 'disallow', 'inherit'), true) ? $v : 'allow';
        }
        $s['bots'] = $bots;

        GAI_Core::save_settings($s);
        GAI_Core::touch();
        self::back('settings', 'saved');
    }

    /* =====================================================================
     * Render
     * ================================================================== */

    public static function render()
    {
        if (!current_user_can(self::CAP)) {
            return;
        }
        $tab  = sanitize_key($_GET['tab'] ?? 'dashboard'); // phpcs:ignore WordPress.Security.NonceVerification
        $tabs = array('dashboard' => 'داشبورد و وضعیت', 'reports' => 'گزارش ربات‌ها و ارجاع‌ها', 'audit' => 'آمادگی محتوا', 'settings' => 'تنظیمات');
        if (!isset($tabs[$tab])) {
            $tab = 'dashboard';
        }
        echo '<div class="wrap gai-wrap"><h1 class="gai-title"><span class="dashicons dashicons-superhero-alt"></span> هوش مصنوعی گیلدا <small>نسخه ' . esc_html(GAI_VERSION) . '</small></h1>';
        self::notice();
        echo '<nav class="nav-tab-wrapper">';
        foreach ($tabs as $k => $label) {
            echo '<a class="nav-tab' . ($k === $tab ? ' nav-tab-active' : '') . '" href="' . esc_url(self::url($k)) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav><div class="gai-body">';
        call_user_func(array(__CLASS__, 'tab_' . $tab));
        echo '</div></div>';
    }

    private static function notice()
    {
        $msg  = sanitize_key($_GET['gai_msg'] ?? ''); // phpcs:ignore WordPress.Security.NonceVerification
        $map  = array(
            'built'     => array('success', 'فایل‌ها دوباره ساخته شدند.'),
            'build_err' => array('warning', 'ساخت انجام شد ولی ذخیره روی دیسک ممکن نبود؛ جزئیات در کارت وضعیت.'),
            'audited'   => array('success', 'ممیزی محتوا به‌روز شد.'),
            'saved'     => array('success', 'تنظیمات ذخیره شد. فایل‌ها تا یک دقیقه دیگر در پس‌زمینه بازسازی می‌شوند.'),
            'cleared'   => array('success', 'گزارش بازدیدها پاک شد.'),
        );
        if (isset($map[$msg])) {
            echo '<div class="notice notice-' . esc_attr($map[$msg][0]) . ' is-dismissible"><p>' . esc_html($map[$msg][1]) . '</p></div>';
        }
    }

    private static function button($action, $label, $tab, $class = 'button button-primary', $confirm = '')
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="gai-inline"' . ($confirm ? ' onsubmit="return confirm(\'' . esc_js($confirm) . '\')"' : '') . '>';
        wp_nonce_field('gai_' . $action);
        echo '<input type="hidden" name="action" value="gai_' . esc_attr($action) . '"><button class="' . esc_attr($class) . '">' . esc_html($label) . '</button></form>';
    }

    private static function n($n)
    {
        return number_format_i18n((float) $n);
    }

    /* ----------------------------- data ------------------------------ */

    private static function since($days)
    {
        return wp_date('Y-m-d', time() - ($days - 1) * DAY_IN_SECONDS);
    }

    private static function sum($kind, $days)
    {
        global $wpdb;
        $t = GAI_Tracker::table();
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(hits),0) FROM {$t} WHERE kind = %s AND day >= %s", $kind, self::since($days))); // phpcs:ignore
    }

    private static function series($kind, $days)
    {
        global $wpdb;
        $t    = GAI_Tracker::table();
        $rows = $wpdb->get_results($wpdb->prepare("SELECT day, SUM(hits) h FROM {$t} WHERE kind = %s AND day >= %s GROUP BY day", $kind, self::since($days)), ARRAY_A); // phpcs:ignore
        $map  = array();
        foreach ((array) $rows as $r) {
            $map[$r['day']] = (int) $r['h'];
        }
        $out = array();
        for ($i = $days - 1; $i >= 0; $i--) {
            $d       = wp_date('Y-m-d', time() - $i * DAY_IN_SECONDS);
            $out[$d] = $map[$d] ?? 0;
        }
        return $out;
    }

    private static function table_ok()
    {
        global $wpdb;
        $t = GAI_Tracker::table();
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) === $t; // phpcs:ignore
    }

    /** Inline SVG bar chart, one series. */
    private static function chart(array $series, $title, $unit)
    {
        $max   = max(1, max($series ?: array(0)));
        $n     = max(1, count($series));
        $w     = 720;
        $h     = 160;
        $pad   = 22;
        $slot  = $w / $n;
        $bw    = max(2, $slot - 2);
        $total = array_sum($series);
        echo '<figure class="gai-chart"><figcaption><strong>' . esc_html($title) . '</strong> <span>' . esc_html(self::n($total)) . ' ' . esc_html($unit) . ' · بیشینه روزانه ' . esc_html(self::n($max)) . '</span></figcaption>';
        echo '<svg viewBox="0 0 ' . (int) $w . ' ' . (int) ($h + $pad) . '" role="img" aria-label="' . esc_attr($title) . '" preserveAspectRatio="none">';
        echo '<line class="gai-grid" x1="0" x2="' . (int) $w . '" y1="0.5" y2="0.5"/><line class="gai-axis" x1="0" x2="' . (int) $w . '" y1="' . (int) $h . '" y2="' . (int) $h . '"/>';
        $i = 0;
        foreach ($series as $day => $v) {
            $x  = $i * $slot + 1;
            $bh = $v > 0 ? max(2, ($v / $max) * ($h - 4)) : 0;
            $label = wp_date('j F', strtotime($day . ' 12:00:00'));
            echo '<g class="gai-bar"><rect class="gai-hit" x="' . esc_attr(round($i * $slot, 2)) . '" y="0" width="' . esc_attr(round($slot, 2)) . '" height="' . (int) $h . '"/>';
            if ($bh > 0) {
                echo '<rect class="gai-fill" x="' . esc_attr(round($x, 2)) . '" y="' . esc_attr(round($h - $bh, 2)) . '" width="' . esc_attr(round($bw, 2)) . '" height="' . esc_attr(round($bh, 2)) . '" rx="2"/>';
            }
            echo '<title>' . esc_html($label . ': ' . self::n($v) . ' ' . $unit) . '</title></g>';
            if ($n <= 14 || $i % (int) ceil($n / 7) === 0) {
                echo '<text x="' . esc_attr(round($i * $slot + $slot / 2, 2)) . '" y="' . (int) ($h + 16) . '" text-anchor="middle">' . esc_html(wp_date('j/n', strtotime($day . ' 12:00:00'))) . '</text>';
            }
            $i++;
        }
        echo '</svg></figure>';
    }

    /* ----------------------------- tabs ------------------------------ */

    private static function tab_dashboard()
    {
        $state = GAI_Core::state();
        $s     = GAI_Core::settings();
        $audit = array();
        if (is_readable(GAI_DIR . 'includes/class-gai-audit.php')) {
            require_once GAI_DIR . 'includes/class-gai-audit.php';
            $audit = GAI_Audit::get();
        }
        $ok = self::table_ok();

        echo '<div class="gai-kpis">';
        $kpis = array(
            array('بازدید ربات‌های AI (۳۰ روز)', $ok ? self::n(self::sum('bot', 30)) : '—', self::url('reports')),
            array('ورودی از دستیارهای AI (۳۰ روز)', $ok ? self::n(self::sum('ref', 30)) : '—', self::url('reports')),
            array('دریافت llms.txt / .md (۳۰ روز)', $ok ? self::n(self::sum('file', 30)) : '—', self::url('reports')),
            array('امتیاز آمادگی محتوا', isset($audit['score']) ? self::n($audit['score']) . ' / ۱۰۰' : '—', self::url('audit')),
        );
        foreach ($kpis as $k) {
            echo '<a class="gai-kpi" href="' . esc_url($k[2]) . '"><span>' . esc_html($k[0]) . '</span><strong>' . esc_html($k[1]) . '</strong></a>';
        }
        echo '</div>';

        echo '<div class="gai-grid2">';
        // Status card.
        echo '<section class="gai-card"><h2>وضعیت فایل‌ها</h2>';
        $gen = (int) ($state['generated'] ?? 0);
        echo '<table class="widefat striped gai-kv"><tbody>';
        echo '<tr><th>آخرین ساخت</th><td>' . ($gen ? esc_html(wp_date('Y/m/d H:i', $gen)) . ' <span class="gai-muted">(' . esc_html(human_time_diff($gen)) . ' پیش، ' . esc_html(self::n($state['duration'] ?? 0)) . ' میلی‌ثانیه)</span>' : 'هنوز ساخته نشده') . '</td></tr>';
        $next = wp_next_scheduled(GAI_Core::HOOK_BUILD);
        echo '<tr><th>بازسازی بعدی</th><td>' . ($next ? esc_html(wp_date('H:i:s', $next)) . ' <span class="gai-muted">(در صف پس‌زمینه)</span>' : '<span class="gai-muted">در صف نیست؛ با هر تغییر محتوا خودکار زمان‌بندی می‌شود</span>') . '</td></tr>';
        foreach (array('llms.txt' => array('txt', 'llms_txt'), 'llms-full.txt' => array('full', 'llms_full')) as $file => $k) {
            $size = (int) ($state[$k[0]]['bytes'] ?? 0);
            $on   = !empty($s[$k[1]]);
            echo '<tr><th>' . esc_html($file) . '</th><td>' . ($on ? '<a href="' . esc_url(home_url('/' . $file)) . '" target="_blank" rel="noopener">' . esc_html(home_url('/' . $file)) . '</a> · ' . esc_html(size_format($size, 1)) . ' · ' . esc_html(self::n($state[$k[0]]['words'] ?? 0)) . ' کلمه' : '<span class="gai-muted">غیرفعال</span>') . '</td></tr>';
        }
        $c = (array) ($state['counts'] ?? array());
        echo '<tr><th>محتوای llms.txt</th><td>' . esc_html(sprintf('%s محصول · %s دسته · %s مقاله · %s صفحه', self::n($c['products'] ?? 0), self::n($c['categories'] ?? 0), self::n($c['posts'] ?? 0), self::n($c['pages'] ?? 0))) . '</td></tr>';
        if (!empty($state['truncated']) || !empty($state['skipped'])) {
            echo '<tr><th>سقف حجم</th><td>' . esc_html(sprintf('%s مورد کوتاه شد، %s مورد فقط به‌صورت پیوند آمد (سقف: %s کیلوبایت)', self::n($state['truncated'] ?? 0), self::n($state['skipped'] ?? 0), self::n($s['full_cap_kb']))) . '</td></tr>';
        }
        $sample = get_posts(array('post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids'));
        if ($s['md'] && $sample) {
            $md = GAI_Core::md_url(get_permalink($sample[0]));
            echo '<tr><th>نمونه Markdown</th><td><a href="' . esc_url($md) . '" target="_blank" rel="noopener">' . esc_html(rawurldecode($md)) . '</a></td></tr>';
        }
        echo '<tr><th>robots.txt</th><td><a href="' . esc_url(home_url('/robots.txt')) . '" target="_blank" rel="noopener">' . esc_html(home_url('/robots.txt')) . '</a></td></tr>';
        if (!empty($state['error'])) {
            echo '<tr><th>خطا</th><td class="gai-bad">' . esc_html($state['error']) . '</td></tr>';
        }
        echo '</tbody></table><p>';
        self::button('regenerate', 'ساخت دوباره همین حالا', 'dashboard');
        echo '</p></section>';

        // Validation + environment.
        echo '<section class="gai-card"><h2>اعتبارسنجی و سازگاری</h2><ul class="gai-checks">';
        $txt = is_readable(GAI_Core::path('llms.txt')) ? (string) file_get_contents(GAI_Core::path('llms.txt')) : ''; // phpcs:ignore
        require_once GAI_DIR . 'includes/class-gai-markdown.php';
        require_once GAI_DIR . 'includes/class-gai-builder.php';
        foreach (array_merge(GAI_Builder::validate($txt), self::environment()) as $r) {
            echo '<li class="gai-' . esc_attr($r[0]) . '"><span class="dashicons dashicons-' . ($r[0] === 'ok' ? 'yes-alt' : ($r[0] === 'error' ? 'dismiss' : ($r[0] === 'info' ? 'info-outline' : 'warning'))) . '"></span> ' . wp_kses($r[1], array('a' => array('href' => array(), 'target' => array()), 'code' => array())) . '</li>';
        }
        echo '</ul></section></div>';

        echo '<section class="gai-card"><h2>بخش افزوده‌شده به robots.txt</h2><p class="gai-muted">این بخش بعد از محتوای Rank Math (یا وردپرس) اضافه می‌شود و قوانین آن را بازنویسی نمی‌کند.</p><pre class="gai-pre" dir="ltr">' . esc_html(GAI_Robots::block(self::robots_base())) . '</pre></section>';
    }

    /** robots.txt content before our block (to preview mirrored rules). */
    private static function robots_base()
    {
        remove_filter('robots_txt', array('GAI_Robots', 'filter'), 100);
        $out = apply_filters('robots_txt', "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n", get_option('blog_public'));
        add_filter('robots_txt', array('GAI_Robots', 'filter'), 100, 2);
        return (string) $out;
    }

    /** Environment checks: [level, html]. */
    private static function environment()
    {
        $r = array();
        if (!get_option('permalink_structure')) {
            $r[] = array('error', 'پیوندهای یکتا «ساده» است؛ آدرس‌های .md کار نمی‌کنند. در تنظیمات › پیوندهای یکتا ساختار دیگری انتخاب کنید.');
        }
        if (!get_option('blog_public')) {
            $r[] = array('error', 'گزینه «جلوگیری از ایندکس موتورهای جستجو» فعال است؛ بخش robots.txt اضافه نمی‌شود.');
        }
        foreach (array('llms.txt', 'llms-full.txt', 'robots.txt') as $f) {
            if (file_exists(ABSPATH . $f)) {
                $r[] = array($f === 'robots.txt' ? 'warn' : 'error', 'فایل فیزیکی <code>' . esc_html($f) . '</code> در ریشه سایت هست و جای خروجی افزونه را می‌گیرد؛ آن را (از طریق FTP) حذف کنید.');
            }
        }
        if (GAI_Core::rank_math()) {
            $r[] = array('ok', 'Rank Math فعال است؛ توضیحات متا، noindex و اسکیمای آن مبنای کار است.');
            if (class_exists('\\RankMath\\Helper') && method_exists('\\RankMath\\Helper', 'is_module_active') && \RankMath\Helper::is_module_active('llms-txt')) {
                $r[] = array('warn', 'ماژول «LLMS Txt» رنک‌مث روشن است. این افزونه زودتر پاسخ می‌دهد، ولی برای جلوگیری از سردرگمی آن را در Rank Math › Dashboard › Modules خاموش کنید.');
            }
            $general = get_option('rank-math-options-general', array());
            if (is_array($general) && !empty($general['robots_txt_content'])) {
                $r[] = array('info', 'robots.txt از ویرایشگر Rank Math می‌آید؛ بخش ربات‌های AI در انتهای آن اضافه می‌شود (بدون تکرار).');
            }
        } else {
            $r[] = array('warn', 'Rank Math فعال نیست؛ اسکیمای تکمیلی اضافه نمی‌شود.');
        }
        if (!GAI_Core::wc()) {
            $r[] = array('warn', 'ووکامرس فعال نیست؛ محصولات در فایل‌ها نمی‌آیند.');
        }
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            $r[] = array('info', 'WP-Cron داخلی خاموش است؛ مطمئن شوید کران سرور wp-cron.php را اجرا می‌کند (در غیر این صورت فایل‌ها هنگام اولین درخواست ربات به‌روز می‌شوند).');
        }
        $dir = GAI_Core::dir();
        if (!wp_is_writable(is_dir($dir) ? $dir : dirname($dir))) {
            $r[] = array('error', 'پوشه <code>' . esc_html($dir) . '</code> قابل نوشتن نیست.');
        }
        if (!self::table_ok()) {
            $r[] = array('error', 'جدول گزارش‌ها ساخته نشده؛ افزونه را یک‌بار غیرفعال و دوباره فعال کنید.');
        }
        if (GAI_Core::get('schema_faq') === 'auto' && GAI_Schema::theme_prints_faq()) {
            $r[] = array('info', 'قالب خودش اسکیمای FAQPage را چاپ می‌کند؛ افزونه برای جلوگیری از تکرار FAQ اضافه نمی‌کند.');
        }
        return $r;
    }

    private static function tab_reports()
    {
        if (!self::table_ok()) {
            echo '<div class="notice notice-error inline"><p>جدول گزارش‌ها وجود ندارد؛ افزونه را یک‌بار غیرفعال و دوباره فعال کنید.</p></div>';
            return;
        }
        global $wpdb;
        $t     = GAI_Tracker::table();
        $days  = absint($_GET['days'] ?? 30); // phpcs:ignore WordPress.Security.NonceVerification
        $days  = in_array($days, array(7, 30, 90), true) ? $days : 30;
        $since = self::since($days);
        $bots  = GAI_Core::bots();

        echo '<div class="gai-toolbar"><span>بازه:</span>';
        foreach (array(7, 30, 90) as $d) {
            echo '<a class="button' . ($d === $days ? ' button-primary' : '') . '" href="' . esc_url(self::url('reports', array('days' => $d))) . '">' . esc_html(self::n($d)) . ' روز</a> ';
        }
        if (!GAI_Core::get('track')) {
            echo '<span class="gai-bad">ثبت بازدید ربات‌ها در تنظیمات خاموش است.</span>';
        }
        echo '</div>';

        echo '<div class="gai-grid2"><section class="gai-card">';
        self::chart(self::series('bot', $days), 'بازدید ربات‌های هوش مصنوعی در روز', 'درخواست');
        echo '</section><section class="gai-card">';
        self::chart(self::series('ref', $days), 'بازدیدکننده‌های ورودی از دستیارهای AI در روز', 'بازدید');
        echo '</section></div>';

        // Per bot.
        $rows = $wpdb->get_results($wpdb->prepare("SELECT bot, SUM(hits) h, MAX(day) last FROM {$t} WHERE kind = 'bot' AND day >= %s GROUP BY bot ORDER BY h DESC", $since), ARRAY_A); // phpcs:ignore
        $total = max(1, array_sum(array_map('intval', wp_list_pluck((array) $rows, 'h'))));
        $kinds = array();
        echo '<div class="gai-grid2"><section class="gai-card"><h2>ربات‌ها</h2><table class="widefat striped"><thead><tr><th>ربات</th><th>شرکت</th><th>نوع</th><th>درخواست</th><th>سهم</th><th>آخرین بازدید</th></tr></thead><tbody>';
        if (!$rows) {
            echo '<tr><td colspan="6" class="gai-muted">هنوز بازدیدی ثبت نشده است.</td></tr>';
        }
        foreach ((array) $rows as $r) {
            $b    = $bots[$r['bot']] ?? array('—', '', '', '');
            $kinds[$b[2]] = ($kinds[$b[2]] ?? 0) + (int) $r['h'];
            $pct  = round((int) $r['h'] / $total * 100);
            echo '<tr><td><code dir="ltr">' . esc_html($r['bot']) . '</code></td><td>' . esc_html($b[0]) . '</td><td>' . esc_html(GAI_Core::kind_label($b[2])) . '</td><td>' . esc_html(self::n($r['h'])) . '</td><td><span class="gai-meter"><i style="width:' . (int) $pct . '%"></i></span> ' . esc_html(self::n($pct)) . '٪</td><td>' . esc_html(wp_date('Y/m/d', strtotime($r['last'] . ' 12:00:00'))) . '</td></tr>';
        }
        echo '</tbody></table>';
        if ($kinds) {
            echo '<p class="gai-muted">';
            foreach ($kinds as $k => $v) {
                echo esc_html(GAI_Core::kind_label($k) . ': ' . self::n($v)) . ' &nbsp; ';
            }
            echo '</p>';
        }
        echo '</section>';

        // Files.
        $files = $wpdb->get_results($wpdb->prepare("SELECT path, bot, SUM(hits) h FROM {$t} WHERE kind = 'file' AND day >= %s GROUP BY path, bot ORDER BY h DESC LIMIT 40", $since), ARRAY_A); // phpcs:ignore
        echo '<section class="gai-card"><h2>دریافت فایل‌های AI</h2><table class="widefat striped"><thead><tr><th>فایل</th><th>درخواست‌کننده</th><th>تعداد</th></tr></thead><tbody>';
        if (!$files) {
            echo '<tr><td colspan="3" class="gai-muted">هنوز llms.txt یا نسخه Markdown دریافت نشده است.</td></tr>';
        }
        foreach ((array) $files as $r) {
            $label = $r['path'] === 'md' ? 'صفحه‌های .md' : $r['path'];
            echo '<tr><td><code dir="ltr">' . esc_html($label) . '</code></td><td>' . esc_html($r['bot'] === 'other' ? 'سایر (مرورگر/ابزار)' : $r['bot']) . '</td><td>' . esc_html(self::n($r['h'])) . '</td></tr>';
        }
        echo '</tbody></table></section></div>';

        // Top URLs.
        $urls = $wpdb->get_results($wpdb->prepare("SELECT path, SUM(hits) h, COUNT(DISTINCT bot) b, GROUP_CONCAT(DISTINCT bot ORDER BY bot SEPARATOR ', ') names FROM {$t} WHERE kind = 'bot' AND day >= %s GROUP BY path ORDER BY h DESC LIMIT 30", $since), ARRAY_A); // phpcs:ignore
        echo '<div class="gai-grid2"><section class="gai-card"><h2>پربازدیدترین آدرس‌ها برای ربات‌ها</h2><table class="widefat striped"><thead><tr><th>آدرس</th><th>درخواست</th><th>ربات‌ها</th></tr></thead><tbody>';
        if (!$urls) {
            echo '<tr><td colspan="3" class="gai-muted">—</td></tr>';
        }
        foreach ((array) $urls as $r) {
            $is_err = $r['path'][0] === '(';
            echo '<tr><td class="gai-path">' . ($is_err ? esc_html('خطای ' . trim($r['path'], '()') . ' (آدرس‌های نامعتبر)') : '<a href="' . esc_url(home_url($r['path'])) . '" target="_blank" rel="noopener" dir="ltr">' . esc_html($r['path']) . '</a>') . '</td><td>' . esc_html(self::n($r['h'])) . '</td><td dir="ltr" class="gai-small">' . esc_html($r['names']) . '</td></tr>';
        }
        echo '</tbody></table></section>';

        // Referrals.
        $src  = $wpdb->get_results($wpdb->prepare("SELECT bot, SUM(hits) h FROM {$t} WHERE kind = 'ref' AND day >= %s GROUP BY bot ORDER BY h DESC", $since), ARRAY_A); // phpcs:ignore
        $land = $wpdb->get_results($wpdb->prepare("SELECT bot, path, SUM(hits) h FROM {$t} WHERE kind = 'ref' AND day >= %s GROUP BY bot, path ORDER BY h DESC LIMIT 30", $since), ARRAY_A); // phpcs:ignore
        echo '<section class="gai-card"><h2>ورودی از دستیارهای هوش مصنوعی</h2><p class="gai-muted">بازدید انسان‌هایی که از ChatGPT، Perplexity، Gemini، Copilot، Claude و… (با Referer یا utm_source) وارد سایت شده‌اند.</p>';
        echo '<div class="gai-chips">';
        foreach ((array) $src as $r) {
            echo '<span class="gai-chip"><strong>' . esc_html($r['bot']) . '</strong> ' . esc_html(self::n($r['h'])) . '</span>';
        }
        if (!$src) {
            echo '<span class="gai-muted">هنوز ورودی ثبت نشده است.</span>';
        }
        echo '</div><table class="widefat striped"><thead><tr><th>منبع</th><th>صفحه ورود</th><th>بازدید</th></tr></thead><tbody>';
        foreach ((array) $land as $r) {
            echo '<tr><td>' . esc_html($r['bot']) . '</td><td class="gai-path"><a href="' . esc_url(home_url($r['path'])) . '" target="_blank" rel="noopener" dir="ltr">' . esc_html($r['path']) . '</a></td><td>' . esc_html(self::n($r['h'])) . '</td></tr>';
        }
        echo '</tbody></table></section></div>';

        echo '<p class="gai-muted">نگهداری داده: ' . esc_html(self::n(GAI_Core::get('retention'))) . ' روز. فقط نام ربات/منبع، مسیر صفحه و تعداد روزانه ذخیره می‌شود؛ IP و اطلاعات شخصی ذخیره نمی‌شود. ';
        self::button('clear_log', 'پاک کردن همه گزارش‌ها', 'reports', 'button-link-delete button-link', 'همه گزارش‌های بازدید پاک شود؟');
        echo '</p>';
    }

    private static function tab_audit()
    {
        require_once GAI_DIR . 'includes/class-gai-audit.php';
        $a = GAI_Audit::get();
        echo '<div class="gai-toolbar">';
        self::button('audit', 'اجرای دوباره ممیزی', 'audit');
        if (!empty($a['time'])) {
            echo ' <span class="gai-muted">آخرین ممیزی: ' . esc_html(wp_date('Y/m/d H:i', (int) $a['time'])) . ' (با هر بازسازی فایل‌ها خودکار به‌روز می‌شود)</span>';
        }
        echo '</div>';
        if (empty($a['rows'])) {
            echo '<p>هنوز ممیزی انجام نشده است.</p>';
            return;
        }
        $defs   = GAI_Audit::issues();
        $type   = sanitize_key($_GET['type'] ?? ''); // phpcs:ignore WordPress.Security.NonceVerification
        $issue  = sanitize_key($_GET['issue'] ?? ''); // phpcs:ignore WordPress.Security.NonceVerification
        $paged  = max(1, absint($_GET['paged'] ?? 1)); // phpcs:ignore WordPress.Security.NonceVerification
        $counts = array();
        $tcount = array();
        foreach ($a['rows'] as $r) {
            $tcount[$r['t']] = ($tcount[$r['t']] ?? 0) + 1;
            foreach (array_keys($r['i']) as $k) {
                $counts[$k] = ($counts[$k] ?? 0) + 1;
            }
        }
        $score = (int) $a['score'];
        $cls   = $score >= 80 ? 'good' : ($score >= 60 ? 'mid' : 'low');
        echo '<div class="gai-grid2"><section class="gai-card gai-score gai-' . esc_attr($cls) . '"><span>امتیاز کلی آمادگی برای پاسخ‌های هوش مصنوعی</span><strong>' . esc_html(self::n($score)) . '</strong><em>از ۱۰۰ — میانگین ' . esc_html(self::n(count($a['rows']))) . ' مورد منتشرشده (موارد noindex حساب نمی‌شوند)</em></section>';
        echo '<section class="gai-card"><h2>مشکلات پرتکرار</h2><div class="gai-chips">';
        arsort($counts);
        foreach ($counts as $k => $c) {
            $active = $issue === $k;
            echo '<a class="gai-chip' . ($active ? ' is-active' : '') . '" href="' . esc_url(self::url('audit', array('issue' => $active ? '' : $k, 'type' => $type))) . '">' . esc_html($defs[$k][0] ?? $k) . ' <strong>' . esc_html(self::n($c)) . '</strong></a>';
        }
        echo '</div></section></div>';

        $labels = array('product' => 'محصول', 'post' => 'مقاله', 'page' => 'برگه');
        echo '<div class="gai-toolbar"><a class="button' . ($type === '' ? ' button-primary' : '') . '" href="' . esc_url(self::url('audit', array('issue' => $issue))) . '">همه</a> ';
        foreach ($tcount as $t => $c) {
            echo '<a class="button' . ($type === $t ? ' button-primary' : '') . '" href="' . esc_url(self::url('audit', array('type' => $t, 'issue' => $issue))) . '">' . esc_html(($labels[$t] ?? $t) . ' (' . self::n($c) . ')') . '</a> ';
        }
        echo '</div>';

        $rows = array_values(array_filter($a['rows'], function ($r) use ($type, $issue) {
            return ($type === '' || $r['t'] === $type) && ($issue === '' || isset($r['i'][$issue]));
        }));
        $per   = 50;
        $pages = max(1, (int) ceil(count($rows) / $per));
        $rows  = array_slice($rows, ($paged - 1) * $per, $per);

        echo '<table class="widefat striped gai-audit"><thead><tr><th>عنوان</th><th>نوع</th><th>امتیاز</th><th>مشکلات</th><th>کلمات</th><th></th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $sc    = (int) $r['s'];
            $badge = !empty($r['x']) ? 'off' : ($sc >= 80 ? 'good' : ($sc >= 60 ? 'mid' : 'low'));
            echo '<tr><td><a href="' . esc_url(get_permalink($r['id'])) . '" target="_blank" rel="noopener">' . esc_html($r['ti'] ?: '#' . $r['id']) . '</a></td>';
            echo '<td>' . esc_html($labels[$r['t']] ?? $r['t']) . '</td>';
            echo '<td><span class="gai-badge gai-' . esc_attr($badge) . '">' . (!empty($r['x']) ? '—' : esc_html(self::n($sc))) . '</span></td><td>';
            if (!$r['i']) {
                echo '<span class="gai-ok">✓ کامل</span>';
            }
            foreach ($r['i'] as $k => $extra) {
                echo '<span class="gai-issue">' . esc_html(($defs[$k][0] ?? $k) . ($extra !== '' ? ' (' . $extra . ')' : '')) . '</span>';
            }
            echo '</td><td>' . esc_html(self::n($r['w'] ?? 0)) . '</td><td>';
            $edit = get_edit_post_link($r['id']);
            if ($edit) {
                echo '<a class="button button-small" href="' . esc_url($edit) . '">ویرایش</a>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
        if ($pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links(array( // phpcs:ignore WordPress.Security.EscapeOutput
                'base'    => add_query_arg('paged', '%#%', self::url('audit', array('type' => $type, 'issue' => $issue))),
                'format'  => '',
                'current' => $paged,
                'total'   => $pages,
            )) . '</div></div>';
        }
        echo '<p class="gai-muted">محتوای کم: کمتر از ' . esc_html(self::n(GAI_Audit::THIN_TEXT)) . ' کلمه برای مقاله/برگه و ' . esc_html(self::n(GAI_Audit::THIN_PRODUCT)) . ' کلمه برای محصول. برگه‌های قالب‌دار (درباره ما، تماس، سؤالات متداول) از بررسی متن معاف‌اند.</p>';
    }

    private static function check($name, $label, $s, $help = '')
    {
        echo '<label class="gai-check"><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked(!empty($s[$name]), true, false) . '> ' . esc_html($label) . '</label>' . ($help ? '<p class="description">' . esc_html($help) . '</p>' : '');
    }

    private static function tab_settings()
    {
        $s = GAI_Core::settings();
        require_once GAI_DIR . 'includes/class-gai-markdown.php';
        require_once GAI_DIR . 'includes/class-gai-builder.php';
        $probe = new GAI_Builder(array_merge($s, array('facts' => '')));
        $auto  = array();
        try {
            foreach ($probe->shipping_lines() as $l) {
                $auto[] = 'هزینه ارسال — ' . $l;
            }
            $auto = array_merge($auto, $probe->refund_facts());
        } catch (Throwable $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="gai-settings">';
        wp_nonce_field('gai_save');
        echo '<input type="hidden" name="action" value="gai_save">';

        echo '<section class="gai-card"><h2>خروجی‌ها</h2>';
        self::check('llms_txt', 'فعال‌سازی ‎/llms.txt‎ (فهرست خلاصه سایت برای مدل‌های زبانی)', $s);
        self::check('llms_full', 'فعال‌سازی ‎/llms-full.txt‎ (متن کامل)', $s);
        self::check('md', 'نسخه Markdown هر صفحه با ‎.md‎ یا ‎?format=md‎', $s);
        self::check('md_accept', 'پاسخ Markdown به درخواست‌هایی که ‎Accept: text/markdown‎ می‌فرستند', $s);
        self::check('md_head', 'افزودن ‎<link rel="alternate" type="text/markdown">‎ و هدر Link به صفحه‌ها', $s);
        echo '<h3>نوع محتوا</h3><p>';
        foreach (get_post_types(array('public' => true), 'objects') as $pt) {
            if (in_array($pt->name, array('attachment'), true)) {
                continue;
            }
            echo '<label class="gai-check gai-inline-check"><input type="checkbox" name="types[]" value="' . esc_attr($pt->name) . '" ' . checked(in_array($pt->name, $s['types'], true), true, false) . '> ' . esc_html($pt->labels->name) . '</label> ';
        }
        echo '</p><p><label>شناسه‌های مستثنا (با کاما)<br><input type="text" class="regular-text" dir="ltr" name="exclude_ids" value="' . esc_attr($s['exclude_ids']) . '"></label><br><span class="description">سبد خرید، تسویه حساب، حساب کاربری، ورود، مقایسه و برگه‌های noindex در Rank Math خودکار حذف می‌شوند.</span></p>';
        echo '<p><label>سقف حجم llms-full.txt (کیلوبایت)<br><input type="number" min="100" max="20000" step="50" name="full_cap_kb" value="' . esc_attr($s['full_cap_kb']) . '"></label></p></section>';

        echo '<section class="gai-card"><h2>معرفی فروشگاه</h2><p><label>متن معرفی (خلاصه بالای llms.txt)<br><textarea name="intro" rows="3" class="large-text">' . esc_textarea($s['intro']) . '</textarea></label></p>';
        echo '<label class="gai-check"><input type="checkbox" name="intro_auto" value="1"> ساخت خودکار از شعار سایت و برگه «درباره ما»</label><p class="description">پیشنهاد خودکار: ' . esc_html(GAI_Builder::auto_intro()) . '</p>';
        echo '<p><label>نکات کلیدی فروشگاه (هر خط یک مورد؛ خالی = تشخیص خودکار)<br><textarea name="facts" rows="5" class="large-text" placeholder="' . esc_attr(implode("\n", $auto)) . '">' . esc_textarea($s['facts']) . '</textarea></label></p>';
        echo '<div class="gai-auto"><strong>تشخیص خودکار فعلی</strong> (از مناطق ارسال ووکامرس و برگه شرایط بازگشت کالا):<ul>';
        foreach ($auto ?: array('چیزی پیدا نشد؛ برگه refund_returns و مناطق حمل‌ونقل را بررسی کنید یا نکات را دستی بنویسید.') as $l) {
            echo '<li>' . esc_html($l) . '</li>';
        }
        echo '</ul></div>';
        echo '<p><label>تلفن پشتیبانی<br><input type="text" dir="ltr" name="phone" value="' . esc_attr($s['phone']) . '"></label> &nbsp; <label>ایمیل<br><input type="email" dir="ltr" name="email" value="' . esc_attr($s['email']) . '"></label></p></section>';

        echo '<section class="gai-card"><h2>سیاست خزنده‌های هوش مصنوعی (robots.txt)</h2>';
        self::check('robots', 'افزودن بخش ربات‌های AI به robots.txt', $s, 'سبد خرید، تسویه، حساب کاربری، ورود، مقایسه و wp-admin همیشه برای ربات‌های مجاز بسته است.');
        echo '<table class="widefat striped gai-bots"><thead><tr><th>ربات</th><th>شرکت</th><th>کاربرد</th><th>اجازه</th><th>مسدود</th><th>بدون قانون</th></tr></thead><tbody>';
        foreach (GAI_Core::bots() as $token => $b) {
            $v = $s['bots'][$token] ?? 'allow';
            echo '<tr class="gai-kind-' . esc_attr($b[2]) . '"><td><code dir="ltr">' . esc_html($token) . '</code><br><span class="gai-tag">' . esc_html(GAI_Core::kind_label($b[2])) . '</span></td><td>' . esc_html($b[0]) . '</td><td>' . esc_html($b[1]) . '</td>';
            foreach (array('allow', 'disallow', 'inherit') as $opt) {
                echo '<td><input type="radio" name="bots[' . esc_attr($token) . ']" value="' . esc_attr($opt) . '" ' . checked($v, $opt, false) . ' aria-label="' . esc_attr($token . ' ' . $opt) . '"></td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table><p class="description">ربات‌های «جستجو/پاسخ» و «درخواست کاربر» باعث دیده‌شدن و لینک‌گرفتن در پاسخ‌ها می‌شوند؛ بستن آن‌ها یعنی حذف از ChatGPT Search، Perplexity و…. ربات‌های «آموزش مدل» فقط برای آموزش استفاده می‌شوند؛ تصمیم با شماست.</p>';
        echo '<p><label>مسیرهای اضافه برای Disallow (هر خط یک مسیر)<br><textarea name="robots_extra" rows="3" class="large-text" dir="ltr">' . esc_textarea($s['robots_extra']) . '</textarea></label></p></section>';

        echo '<section class="gai-card"><h2>داده ساختاریافته (هماهنگ با Rank Math)</h2>';
        echo '<p><label>FAQPage از فیلد faq_list: <select name="schema_faq">';
        foreach (array('auto' => 'خودکار (فقط اگر قالب یا Rank Math ندارند)', 'force' => 'همیشه (اگر Rank Math ندارد)', 'off' => 'خاموش') as $k => $l) {
            echo '<option value="' . esc_attr($k) . '" ' . selected($s['schema_faq'], $k, false) . '>' . esc_html($l) . '</option>';
        }
        echo '</select></label></p>';
        self::check('schema_org', 'تکمیل Organization: تلفن، ایمیل، ContactPoint، شبکه‌های اجتماعی (sameAs) و سیاست مرجوعی', $s);
        self::check('schema_store', 'نوع سازمان OnlineStore (به‌جای Organization عمومی)', $s);
        self::check('schema_speak', 'speakable روی مقاله‌ها (برای دستیارهای صوتی)', $s);
        echo '<p><label>انتخابگرهای CSS برای speakable (هر خط یکی)<br><textarea name="speakable" rows="3" class="large-text" dir="ltr">' . esc_textarea($s['speakable']) . '</textarea></label></p></section>';

        echo '<section class="gai-card"><h2>گزارش‌ها</h2>';
        self::check('track', 'ثبت بازدید ربات‌های AI و دریافت فایل‌ها', $s);
        self::check('track_refs', 'ثبت ورودی بازدیدکننده‌ها از دستیارهای AI', $s);
        echo '<p><label>مدت نگهداری (روز)<br><input type="number" min="7" max="365" name="retention" value="' . esc_attr($s['retention']) . '"></label></p></section>';

        submit_button('ذخیره تنظیمات');
        echo '</form>';
    }
}
