<?php $term_id = $args['term_id'] ?? ''; ?>
<aside x-data="{open : false}" class="lg:col-span-4 flex flex-col justify-start relative xl:col-span-3">
    <?php get_template_part('template-parts/blog/category-sticky-button'); ?>
    <div
        class="flex flex-col transition-all -z-1 duration-300 backdrop-blur-sm fixed inset-0"
        :class="open ? '!z-[51] bg-black/20' : ''" @click="open = !open"></div>
    <div
        :class="open ? '!translate-y-0' : ''"
        class="lg:contents flex flex-col transition-all h-[40vh] bg-gray-50 translate-y-full duration-500 w-full fixed inset-x-0 bottom-0 z-[100]">
        <div class="lg:hidden flex text-lg border-b border-gray-200 justify-between items-center py-3 px-5 font-bold lg:pt-0">
            <button
                    aria-label="close category modal"
                    :class="intro ? '!translate-y-0' : ''"
                    class="p-2 transition-all border bg-gray-100 border-gray-200 rounded-lg aspect-square"
                    @click="open = !open">
                <?php
                $args = array(
                    'size' => '18',
                    'class' => '',
                );
                get_template_part('template-parts/svg/close', null, $args);
                ?>
            </button>
            <span>دسته بندی‌ها</span>

        </div>
        <?php
        $stickyClass = current_user_can('administrator') ? 'top-24' : 'top-18';
        ;
        $args = array(
            'class' => 'h-full max-lg:overflow-y-scroll lg:h-auto lg:sticky ' . $stickyClass,
            'currentId' => $term_id,
        );
        get_template_part('template-parts/global/category-dropdown-list', null, $args); ?>
    </div>
</aside>
<?php
//$args = array(
//        'term_id' => '',
//);
//get_template_part('template-parts/blog/sidebar',null,$args);
?>