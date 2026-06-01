<button
        aria-label="open category modal"
        :class="intro ? '!translate-y-0' : ''"
        class="lg:hidden flex items-center justify-center w-10 h-10 fixed translate-y-full duration-200 bg-gray-700 border border-white/20 rounded-sm z-[3] end-4 bottom-28 lg:bottom-4 p-2 transition-all aspect-square"
        @click="open = !open">
    <?php
    $args = array(
        'size' => '18',
        'class' => 'text-white',
    );
    get_template_part('template-parts/svg/tag', null, $args);
    ?>
</button>

<?php
//get_template_part('template-parts/blog/category-sticky-button');
?>