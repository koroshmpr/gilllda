<?php
global $woo_active;
?>
<header id="header"
        :class="[
<!--	scrollingDown ? '-translate-y-full' : (scrollingUp ? 'translate-y-0' : ''),-->
	 scrolled ? 'shadow-sm <?= current_user_can('administrator') ? '!lg:top-0' : ''; ?>' : '']"
        class="fixed <?= current_user_can('administrator') ? 'lg:top-8' : 'lg:top-0'; ?> max-lg:bottom-0 max-lg:border-t border-black/10 left-0 w-full bg-white transition-all duration-200 z-50"
>
    <nav
            class="container <?= is_admin() ? 'lg:pt-12' : '' ?> flex items-center lg:h-14 max-lg:px-2 lg:py-4 justify-between">
        <div class="flex items-center gap-5 max-lg:hidden">
            <!-- Logo -->
            <?php
            $args = array(
                'logoSize' => 'max-h-12 w-auto'
            );
            get_template_part('template-parts/global/logo', null, $args);
            ?>

            <!-- Desktop Menu -->
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
            $class = 'lg:bg-primary/5 justify-center lg:border border-primary/10 p-2 max-lg:py-4 hover:bg-primary/10 flex items-center gap-3 cursor-pointer hover:scale-105 transition-all';
            $svgClass = 'text-black/50 lg:text-black/70';
            $svgSize = '18';
            $args = array(
                'class' => $class,
                'svgClass' => $svgClass,
                'svgSize' => $svgSize,
            );
            if ($woo_active) :
                ?>
                <?php get_template_part('template-parts/layout/my-account-button', null, $args); ?>
                <a aria-label="go to cart" href="<?= wc_get_cart_url(); ?>" class="<?= $class; ?> relative">
                    <?php
                    $args = array(
                        'size' => $svgSize,
                        'class' => $svgClass,
                    );
                    get_template_part('template-parts/svg/cart', null, $args);
                    ?>
                    <span
                            class="absolute top-0 start-0 translate-x-1/2 lg:-translate-y-1/2 translate-y-1 bg-secondary/80 text-white flex leading-auto justify-center items-center pt-1 p-0.5 rounded-sm text-xs size-4">
					<?= WC()->cart->get_cart_contents_count() ?? '0'; ?>
				</span>
                </a>
            <?php endif;
            $showLogo = get_field('show_logo_in_navbar', 'option');
            if ($showLogo) :
                ?>
                <a aria-label="go to home page" href="<?= home_url(); ?>"
                   class="flex items-center justify-center p-1 scale-125 border border-secondary aspect-square lg:hidden bg-primary rounded-full mx-5 text-black/60">
                    <?php
                    $logo = get_field('footer_logo', 'option') ?? ''; ?>
                    <img width="45" height="45" class="object-fit" src="<?= $logo['url'] ?? ''; ?>" alt="<?= $logo['title'] ?? ''; ?>">
                </a>
            <?php else : ?>
                <a aria-label="go to home page" href="<?= home_url(); ?>"
                   class="<?= $class; ?>  lg:hidden bg-primary/15 border-x border-primary/20 px-4 mx-5 text-black/60">
                    <?php get_template_part('template-parts/svg/home', null, ['size' => '25']); ?>
                </a>
            <?php
            endif;
            if ($woo_active) : ?>
                <a aria-label="go to compare page" href="<?= home_url('/compare'); ?>" class="<?= $class; ?> relative"
                   x-data="{ compareCount: 0 }"
                   x-init="compareCount = JSON.parse(localStorage.getItem('compare_products') || '[]').length;"
                >
                    <?php
                    $args = array(
                        'size' => $svgSize,
                        'class' => $svgClass,
                    );
                    get_template_part('template-parts/svg/compare', null, $args);
                    ?>
                    <span
                            :class="compareCount === 0 ? 'hidden' : '!opacity-100'"
                            class="absolute top-0 start-0 opacity-0 translate-x-1/2 text-white lg:-translate-y-1/2 translate-y-1 bg-secondary/80 flex leading-auto justify-center items-center pt-1 p-0.5 rounded-sm text-xs size-4"
                            x-text="compareCount">
    			</span>
                </a>
            <?php endif; ?>
            <!-- Mobile Menu Button -->
            <?php if (!is_search()) : ?>
                <button aria-label="open search modal" @click="searchOpen = true" class="<?= $class; ?> max-lg:hidden">
                    <?php get_template_part('template-parts/svg/search', null, $args); ?>
                </button>
            <?php endif; ?>
            <?php if ($woo_active) : ?>
                <button aria-label="open category list" @click="categoryOpen = true"
                        class="<?= $class; ?> max-lg:hidden text-xs">
                    <?php get_template_part('template-parts/svg/tag', null, $args); ?>
                    <span class="max-lg:hidden">دسته بندی‌ها</span>
                </button>
            <?php endif; ?>
            <button aria-label="open mobileMenu" @click="menuOpen = true" class="<?= $class; ?> lg:hidden">
                <?php
                $args = array(
                    'size' => 20,
                    'class' => $svgClass,
                );
                get_template_part('template-parts/svg/menu', null, $args);
                ?>
            </button>
        </div>
    </nav>
</header>
