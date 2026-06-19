<?php
global $woo_active;
$activeClass = 'max-lg:!bg-primary/15 border max-lg:!border-primary/20 max-lg:rounded-[20px] max-lg:!text-primary max-lg:[&>svg]:fill-primary';
?>

    <header id="header"
            :class="[scrolled ? 'lg:shadow-sm <?= current_user_can('administrator') ? '!lg:top-0' : ''; ?>' : '', scrollingDown ? 'max-lg:scale-85' : '']"
            class="fixed <?= current_user_can('administrator') ? 'lg:top-8' : 'lg:top-0'; ?> shadow-[0_-5px_5px_-3px_rgba(0,0,0,0.1)] max-lg:bottom-2 max-lg:inset-x-4 left-0 lg:w-full max-lg:rounded-3xl bg-white/90 backdrop-blur-sm max-lg:border border-gray-300 lg:bg-white transition-all duration-300 z-50">
        <nav class="container <?= is_admin() ? 'lg:pt-12' : '' ?> flex items-center lg:h-14 max-lg:p-[3px] lg:py-4 justify-between">
            <div class="flex items-center gap-5 max-lg:hidden">
                <?php get_template_part('template-parts/global/logo', null, [
                        'logoSize' => 'max-h-12 w-auto',
                            'width' => '80',
                            'height' => '47'
                ]);
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

            <div class="flex items-center max-lg:justify-between max-lg:w-full gap-1 lg:gap-3">
                <?php
                $baseClass = 'relative lg:bg-gray-50 justify-center lg:border border-gray-300 p-2 max-lg:py-3 max-lg:flex-1 hover:bg-primary hover:text-white flex items-center gap-3 cursor-pointer lg:rounded-sm transition-all duration-300';
                $baseSvgClass = 'max-lg:size-5.5 duration-300 transition-all';
                $svgSize = '18';

                if ($woo_active) :
                    $accClass = $baseClass . (is_account_page() || is_page('login') ? " $activeClass" : "");

                    get_template_part('template-parts/layout/my-account-button', null, [
                        'active' => is_account_page(),
                        'class' => $accClass,
                        'svgClass' => $baseSvgClass,
                        'svgSize' => $svgSize,
                    ]);
                    ?>
                    <a aria-label="go to cart" href="<?= wc_get_cart_url(); ?>"
                       class="<?= $baseClass; ?> <?= is_cart() ? $activeClass : ''; ?>">
                        <?php get_template_part('template-parts/svg/cart', null, ['size' => $svgSize, 'class' => $baseSvgClass]); ?>
                        <span class="absolute top-0 start-2 lg:start-0 lg:translate-x-1/2 lg:-translate-y-1/2 translate-y-1 bg-secondary/80 text-white flex leading-auto justify-center items-center pt-1 p-0.5 rounded-sm text-xs size-4">
                        <?= WC()->cart->get_cart_contents_count() ?? '0'; ?>
                    </span>
                    </a>
                <?php endif;

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
                    ?>
                    <a aria-label="go to home page" href="<?= home_url(); ?>"
                       class="<?= $baseClass; ?> lg:hidden <?= is_front_page() ? $activeClass : ''; ?>">
                        <?php get_template_part('template-parts/svg/home', null, ['size' => $svgSize, 'class' => $baseSvgClass]); ?>
                    </a>
                <?php endif;
                if ($woo_active) :
                    ?>
                    <a aria-label="go to compare page" href="<?= home_url('/compare'); ?>"
                       class="<?= $baseClass; ?> <?= is_page('compare') ? $activeClass : ''; ?>"
                       x-data="{ compareCount: 0 }"
                       x-init="compareCount = JSON.parse(localStorage.getItem('compare_products') || '[]').length;">
                        <?php get_template_part('template-parts/svg/compare', null, ['size' => $svgSize, 'class' => $baseSvgClass]); ?>
                        <span :class="compareCount === 0 ? 'hidden' : '!opacity-100'"
                              class="absolute top-0 start-2 lg:start-0 opacity-0 lg:translate-x-1/2 text-white lg:-translate-y-1/2 translate-y-1 bg-secondary/80 flex leading-auto justify-center items-center pt-1 p-0.5 rounded-sm text-xs size-4"
                              x-text="compareCount">
                    </span>
                    </a>
                <?php endif;
                if (!is_search()) : ?>
                    <button aria-label="open search modal" @click="searchOpen = true"
                            class="<?= $baseClass; ?> max-lg:hidden group">
                        <?php get_template_part('template-parts/svg/search', null, ['size' => $svgSize, 'class' => $baseSvgClass]); ?>
                    </button>
                <?php endif;
                if ($woo_active) : ?>
                    <button aria-label="open category list" @click="categoryOpen = true"
                            class="<?= $baseClass; ?> max-lg:hidden text-xs group">
                        <?php get_template_part('template-parts/svg/tag', null, ['size' => $svgSize, 'class' => $baseSvgClass]); ?>
                        <span class="max-lg:hidden">دسته بندی‌ها</span>
                    </button>
                    <a href="<?= home_url('/shop'); ?>" aria-label="go to shop page"
                       class="<?= $baseClass; ?> <?= is_shop() ? $activeClass : ''; ?> lg:hidden">
                        <?php get_template_part('template-parts/svg/shop', null, ['size' => $svgSize, 'class' => $baseSvgClass]); ?>
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
    <nav class="flex sticky lg:hidden top-0 z-50 bg-white ps-4 pl-1 justify-between items-center">
        <?php get_template_part('template-parts/global/logo', null, ['logoSize' => 'max-h-8 w-auto']); ?>

        <div class="flex items-center">
            <button class="<?= $mobileBaseClass; ?>"
                    :class="searchOpen ? 'max-lg:!bg-primary/10 max-lg:!text-primary is-active shadow-inner' : ''"
                    @click="searchOpen = true" aria-label="open search modal">
                <?php get_template_part('template-parts/svg/search', null, ['size' => 18, 'class' => $baseSvgClass]); ?>
            </button>

            <button class="<?= $mobileBaseClass; ?>"
                    :class="menuOpen ? 'max-lg:!bg-primary/10 max-lg:!text-primary is-active shadow-inner' : ''"
                    aria-label="open mobileMenu" @click="menuOpen = true">
                <?php get_template_part('template-parts/svg/menu', null, ['size' => 20, 'class' => $baseSvgClass]); ?>
            </button>
        </div>
    </nav>
<?php endif; ?>