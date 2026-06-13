<?php
global $woo_active, $wp;

// Safely check current pages context for active states
$is_woo_account = $woo_active && function_exists('is_account_page') && is_account_page();
$is_woo_cart = $woo_active && function_exists('is_cart') && is_cart();
$is_home = is_front_page() || is_home();

// Safely check if we are on the compare page
$current_url = home_url(add_query_arg(array(), $wp->request));
$is_compare = (strpos($current_url, '/compare') !== false);
$is_shop = (strpos($current_url, '/shop') !== false);

// 🔴 اضافه شدن پیشوند max-lg: تا این استایل‌ها فقط در موبایل کار کنند
$active_wrap_class = 'max-lg:!bg-primary/10 max-lg:rounded-3xl max-lg:!text-primary is-active';
?>

    <header id="header"
            :class="[scrolled ? 'lg:shadow-sm <?= current_user_can('administrator') ? '!lg:top-0' : ''; ?>' : '']"
            class="fixed <?= current_user_can('administrator') ? 'lg:top-8' : 'lg:top-0'; ?> shadow-[0_-5px_10px_-3px_rgba(0,0,0,0.1)] max-lg:bottom-2 max-lg:inset-x-2 left-0 lg:w-full max-lg:rounded-3xl bg-white/90 backdrop-blur-[2px] max-lg:border border-gray-200 lg:bg-white transition-all duration-200 z-50">
        <nav class="container <?= is_admin() ? 'lg:pt-12' : '' ?> flex items-center lg:h-14 max-lg:p-0.5 lg:py-4 justify-between">

            <div class="flex items-center gap-5 max-lg:hidden">
                <?php
                get_template_part('template-parts/global/logo', null, ['logoSize' => 'max-h-12 w-auto']);
                ?>

                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'menu-1',
                        'menu_id' => 'primary-menu',
                        'menu_class' => 'max-lg:hidden flex gap-x-3 justify-center lg:justify-start',
                        'show_submenus' => true,
                        'walker' => new Footer_Walker_Nav_Menu(),
                    )
                );
                ?>
            </div>

            <div class="flex items-center max-lg:justify-between max-lg:w-full lg:gap-3">
                <?php
                $baseClass = 'lg:bg-primary/5 justify-center lg:border border-primary/10 p-2 max-lg:py-3.5 max-lg:px-6 hover:bg-primary/10 flex items-center gap-3 cursor-pointer hover:scale-105 transition-all';
                $baseSvgClass = 'text-black/50 lg:text-black/70 transition-colors';
                $svgSize = '20';

                // کلاس کمکی برای SVGهای زیرمجموعه Alpine (فقط در موبایل رنگ می‌گیرند)
                $alpineSvgClass = $baseSvgClass . ' group-[.is-active]:max-lg:!text-primary';

                if ($woo_active) :
                    // --- My Account Button ---
                    $accClass = $baseClass . ($is_woo_account ? " $active_wrap_class" : "");

                    get_template_part('template-parts/layout/my-account-button', null, [
                        'class' => $accClass,
                        'svgClass' => $baseSvgClass,
                        'svgSize' => $svgSize,
                    ]);
                    ?>

                    <?php
                    $cartClass = $baseClass . ' relative' . ($is_woo_cart ? " $active_wrap_class" : "");
                    ?>
                    <a aria-label="go to cart" href="<?= wc_get_cart_url(); ?>" class="<?= $cartClass; ?>">
                        <?php get_template_part('template-parts/svg/cart', null, ['size' => $svgSize, 'class' => $baseSvgClass]); ?>
                        <span class="absolute top-0 start-0 lg:translate-x-1/2 lg:-translate-y-1/2 translate-y-1 bg-secondary/80 text-white flex leading-auto justify-center items-center pt-1 p-0.5 rounded-sm text-xs size-4">
                        <?= WC()->cart->get_cart_contents_count() ?? '0'; ?>
                    </span>
                    </a>
                <?php endif; ?>

                <?php
                $showLogo = get_field('show_logo_in_navbar', 'option');
                if ($showLogo) :
                    $logoClass = 'relative z-10 flex items-center justify-center aspect-square lg:hidden bg-primary rounded-2xl p-1 mx-2 border border-secondary transition-all' . ($is_home ? ' ring-2 ring-primary/60 shadow-lg scale-105' : '');
                    ?>
                    <a aria-label="go to home page" href="<?= home_url(); ?>" class="<?= $logoClass; ?>">
                        <?php $logo = get_field('footer_logo', 'option') ?? ''; ?>
                        <img fetchpriority="high" decoding="sync" width="<?= esc_attr($logo['width'] ?? '98') ?>"
                             height="<?= esc_attr($logo['height'] ?? '59') ?>" class="w-12 h-12 object-contain"
                             src="<?= esc_url($logo['url'] ?? '') ?>"
                             alt="<?= esc_attr($logo['title'] ?? get_bloginfo('name')) ?>">
                    </a>
                <?php else :
                    $homeIconClass = $baseClass . ' lg:hidden px-4 text-black/60' . ($is_home ? " $active_wrap_class" : "");
                    ?>
                    <a aria-label="go to home page" href="<?= home_url(); ?>" class="<?= $homeIconClass; ?>">
                        <?php get_template_part('template-parts/svg/home', null, ['size' => $svgSize, 'class' => $baseSvgClass]); ?>
                    </a>
                <?php endif; ?>

                <?php if ($woo_active) :
                    $compareClass = $baseClass . ' relative' . ($is_compare ? " $active_wrap_class" : "");
                    ?>
                    <a aria-label="go to compare page" href="<?= home_url('/compare'); ?>" class="<?= $compareClass; ?>"
                       x-data="{ compareCount: 0 }"
                       x-init="compareCount = JSON.parse(localStorage.getItem('compare_products') || '[]').length;">
                        <?php get_template_part('template-parts/svg/compare', null, ['size' => $svgSize, 'class' => $baseSvgClass]); ?>
                        <span :class="compareCount === 0 ? 'hidden' : '!opacity-100'"
                              class="absolute top-0 start-0 opacity-0 lg:translate-x-1/2 text-white lg:-translate-y-1/2 translate-y-1 bg-secondary/80 flex leading-auto justify-center items-center pt-1 p-0.5 rounded-sm text-xs size-4"
                              x-text="compareCount">
                    </span>
                    </a>
                <?php endif; ?>

                <?php if (!is_search()) : ?>
                    <button aria-label="open search modal" @click="searchOpen = true"
                            class="<?= $baseClass; ?> max-lg:hidden group"
                            :class="searchOpen ? '<?= $active_wrap_class; ?>' : ''">
                        <?php get_template_part('template-parts/svg/search', null, ['size' => $svgSize, 'class' => $alpineSvgClass]); ?>
                    </button>
                <?php endif; ?>

                <?php if ($woo_active) : ?>
                    <button aria-label="open category list" @click="categoryOpen = true"
                            class="<?= $baseClass; ?> max-lg:hidden text-xs group"
                            :class="categoryOpen ? '<?= $active_wrap_class; ?>' : ''">
                        <?php get_template_part('template-parts/svg/tag', null, ['size' => $svgSize, 'class' => $alpineSvgClass]); ?>
                        <span class="max-lg:hidden">دسته بندی‌ها</span>
                    </button>
                    <?php
                        $shopClass = $baseClass . ' relative' . ($is_shop ? " $active_wrap_class" : "");
                    ?>
                    <a href="<?= home_url('/shop'); ?>" aria-label="go to shop page" class="<?= $shopClass; ?> lg:hidden group">
                        <?php get_template_part('template-parts/svg/shop', null, ['size' => $svgSize, 'class' => $alpineSvgClass]); ?>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

<?php
// --- Mobile Top Header ---
if (wp_is_mobile()):
    $mobileBaseClass = 'justify-center flex items-center px-3 py-4 shrink-0 cursor-pointer transition-all group';
    ?>
    <nav class="flex sticky top-0 z-50 bg-white ps-4 pl-1 justify-between items-center">
        <?php get_template_part('template-parts/global/logo', null, ['logoSize' => 'max-h-8 w-auto']); ?>

        <div class="flex items-center">
            <button class="<?= $mobileBaseClass; ?>"
                    :class="searchOpen ? 'max-lg:!bg-primary/10 max-lg:!text-primary is-active shadow-inner' : ''"
                    @click="searchOpen = true" aria-label="open search modal">
                <?php get_template_part('template-parts/svg/search', null, ['size' => 18, 'class' => $alpineSvgClass]); ?>
            </button>

            <button class="<?= $mobileBaseClass; ?>"
                    :class="menuOpen ? 'max-lg:!bg-primary/10 max-lg:!text-primary is-active shadow-inner' : ''"
                    aria-label="open mobileMenu" @click="menuOpen = true">
                <?php get_template_part('template-parts/svg/menu', null, ['size' => 20, 'class' => $alpineSvgClass]); ?>
            </button>
        </div>
    </nav>
<?php endif; ?>