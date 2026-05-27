<?php
global $woo_active
?>
    <button @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
            x-transition
            class="w-10 h-10 fixed <?= $woo_active && ( is_singular('product') || is_shop() || is_product_category() ) ? 'bottom-30' : 'bottom-16'; ?> lg:bottom-4 bg-gray-700 hover:bg-gray-900 cursor-pointer justify-center border border-white/20 transition-all duration-700 items-center flex text-white rounded-sm z-[5]"
            :class="scrolled ? 'right-4' : '-right-full' "
            aria-label="Back to top">
        <svg width="16" height="16" fill="none" viewBox="0 0 16 16" stroke="currentColor">
            <path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708z"/>
        </svg>
    </button>
<?php
//get_template_part('template-parts/global/backToTop');
?>