<?php
/**
 * First-visit splash (enabled by the «preloader» option in Site Settings).
 * Shown once per browser session, hidden on DOMContentLoaded (min 350 ms, max 1.2 s).
 * Never delays link clicks — page-to-page motion is handled by View Transitions (motion.css).
 */
$logo = function_exists('get_field') ? get_field('site_logo', 'option') : null;
?>
<div id="gl-splash" aria-hidden="true">
    <div class="gl-splash__mark">
        <?php if (!empty($logo['url'])) : ?>
            <img src="<?= esc_url($logo['sizes']['medium'] ?? $logo['url']); ?>" alt="" width="120" height="60" decoding="async">
        <?php endif; ?>
        <span class="gl-splash__bar"><span></span></span>
    </div>
</div>
<style>
    #gl-splash{position:fixed;inset:0;z-index:9999;display:grid;place-items:center;background:#fff;transition:opacity .45s ease,visibility .45s;}
    #gl-splash.is-done{opacity:0;visibility:hidden}
    .gl-splash__mark{display:grid;justify-items:center;gap:18px;animation:gl-splash-in .6s cubic-bezier(.2,.8,.2,1) both}
    .gl-splash__mark img{width:120px;height:auto}
    .gl-splash__bar{display:block;width:120px;height:3px;border-radius:3px;background:#eef0f3;overflow:hidden}
    .gl-splash__bar span{display:block;height:100%;width:40%;border-radius:3px;background:linear-gradient(90deg,#102542,#9d0617);animation:gl-splash-bar 1s ease-in-out infinite}
    @keyframes gl-splash-in{from{opacity:0;transform:scale(.92)}}
    @keyframes gl-splash-bar{0%{transform:translateX(160%)}100%{transform:translateX(-260%)}}
    @media (prefers-reduced-motion:reduce){.gl-splash__mark,.gl-splash__bar span{animation:none}}
</style>
<script>
    (function () {
        var s = document.getElementById('gl-splash');
        if (!s) return;
        var seen = false;
        try { seen = sessionStorage.getItem('gl_splash') === '1'; sessionStorage.setItem('gl_splash', '1'); } catch (e) {}
        if (seen) { s.remove(); return; }
        var t0 = Date.now();
        var done = function () {
            setTimeout(function () { s.classList.add('is-done'); setTimeout(function () { s.remove(); }, 500); }, Math.max(0, 350 - (Date.now() - t0)));
        };
        if (document.readyState !== 'loading') done(); else document.addEventListener('DOMContentLoaded', done);
        setTimeout(done, 1200);
        window.addEventListener('pageshow', function (e) { if (e.persisted) s.remove(); });
    })();
</script>
