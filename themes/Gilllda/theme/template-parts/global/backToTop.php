<?php
global $woo_active
?>
    <button @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
            x-transition
            class="size-11 fixed <?= $woo_active &&  is_singular('product') ? 'hidden' : '';  ?> <?= $woo_active && ( is_shop() || is_product_category() ) ? 'bottom-30' : 'bottom-17'; ?> lg:bottom-4 bg-gray-100 border border-gray-200 hover:bg-gray-900 cursor-pointer justify-center transition-all duration-700 items-center flex text-primary rounded-full z-[5]"
            :class="scrollingUp && scrolled ? 'right-5' : '-right-full' "
            aria-label="Back to top">
        <svg width="18" height="18" fill="none" viewBox="0 0 16 16" stroke="currentColor">
            <path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708z"/>
        </svg>
    </button>
<?php
//get_template_part('template-parts/global/backToTop');
?>