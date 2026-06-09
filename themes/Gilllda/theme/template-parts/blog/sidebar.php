<?php $term_id = $args['term_id'] ?? '';
$stickyClass = current_user_can('administrator') ? 'lg:top-24' : 'lg:top-18';
?>
<aside x-data="{open : false}" class="lg:col-span-4 flex flex-col justify-start relative xl:col-span-3">
    <?php get_template_part('template-parts/blog/category-sticky-button'); ?>
    <div
        class="flex flex-col transition-all -z-1 duration-300 backdrop-blur-sm fixed inset-0"
        :class="open ? '!z-[51] bg-black/20' : ''" @click="open = !open"></div>
    <div
        :class="open ? '!translate-y-0' : ''"
        class="flex flex-col transition-all max-lg:h-[40vh] lg:max-h-[70vh] overflow-y-scroll bg-gray-50 lg:p-4 lg:rounded-lg lg:border border-gray-100 max-lg:translate-y-full duration-500 w-full max-lg:fixed lg:sticky inset-x-0 max-lg:bottom-0 max-lg:z-[100] <?= $stickyClass; ?>">
        <div class="lg:hidden mb-3 flex text-lg border-b border-gray-200 justify-between items-center py-3 px-5 font-bold lg:pt-0">
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
        <div class="border-b max-lg:hidden border-primary/15 flex mb-3">
            <span class="border-b-2 w-fit pb-1">دسته بندی‌ها</span>
        </div>
        <?php
        $args = array(
            'class' => 'h-full lg:h-auto max-lg:px-5',
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