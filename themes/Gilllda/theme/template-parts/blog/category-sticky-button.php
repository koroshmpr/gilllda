<button
        aria-label="open category modal"
        :class="intro ? '!translate-y-0' : ''"
        class="lg:hidden flex items-center justify-center size-11 fixed translate-y-full duration-200 bg-gray-50 border border-gray-200 rounded-full z-[3] end-5 bottom-17 p-2 transition-all aspect-square"
        @click="open = !open">
    <?php
    $args = array(
        'size' => '20',
        'class' => 'text-primary',
    );
    get_template_part('template-parts/svg/tag', null, $args);
    ?>
</button>

<?php
//get_template_part('template-parts/blog/category-sticky-button');
?>