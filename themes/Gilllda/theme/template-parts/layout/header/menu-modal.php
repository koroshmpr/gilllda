<?php
$showCategory = get_field('show_category_on_mobile_menu', 'option');
?>
<div
        @keydown.escape.window="menuOpen = false" id="mobileMenu"
        :class="menuOpen ? '!z-50 !opacity-100' : ''"
        class="fixed inset-0 flex justify-center z-[-1] bg-black/50 opacity-0 items-end lg:hidden backdrop-blur-sm transition-all duration-300"
        @click.self="menuOpen = false"
>
    <div x-data="{ activeTab: 'menu' }"
         class="bg-gray-50 text-black w-full max-w-sm p-4 py-8 h-[60vh] flex flex-col translate-y-full transition-all duration-300"
         :class="menuOpen ? 'delay-200 !translate-y-0' : 'translate-y-full'"
    >
        <!-- Close Button -->
        <button @click="menuOpen = false" aria-label="close menu" class=" text-black mb-2 absolute -top-4 start-3 bg-gray-100 border border-gray-200 p-2 rounded-sm">
            <?php
            $args = array(
                'size' => '20',
                'class' => '',
            );
            get_template_part('template-parts/svg/close', null, $args);
            ?>
        </button>
        <?php if ($showCategory): ?>
            <div class="flex items-center mb-2 bg-gray-100 border border-gray-200 p-1 rounded-xl">
                <button type="button" aria-label="show menu mobile tab" @click="activeTab = 'menu'"
                        :class="activeTab === 'menu' ? 'bg-white shadow-sm' : ''" class="flex-1 py-3 transition-all rounded-lg duration-300">منو سایت
                </button>
                <button type="button" aria-label="show category list on mobile menu" @click="activeTab = 'cats'"
                        :class="activeTab === 'cats' ? 'bg-white shadow-sm' : ''" class="flex-1 py-3 transition-all rounded-lg duration-300">دسته بندی محصولات
                </button>
            </div>
        <?php endif; ?>

        <!-- Mobile Menu Items -->
        <nav class="overflow-y-scroll h-full" x-show="activeTab === 'menu'">
            <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'menu-3',
                    'menu_id' => 'mobile-menu',
                    'menu_class' => 'grid gap-1 text-base text-center font-medium',
                    'show_submenus'  => true,
                    'walker' => new Mobile_Walker_Nav_Menu()
                )
            );
            ?>
        </nav>
        <?php if ($showCategory): ?>
            <div class="overflow-y-scroll h-full" x-show="activeTab === 'cats'">
                <?php get_template_part('template-parts/product/category-accordion-list',null,['listColor' => 'bg-white']); ?>
            </div>
        <?php endif; ?>

    </div>
</div>