<div @keydown.escape.window="categoryOpen = false" id="categoryModal"
     :class="categoryOpen ? '!z-50 !opacity-100' : ''"
     class="fixed inset-0 flex justify-center z-[-1] bg-black/50 opacity-0 items-center backdrop-blur-sm transition-all duration-300"
     @click.self="categoryOpen = false">
    <nav x-data="{activeItem: null}"
         class="bg-white w-11/12 p-6 lg:w-1/3 relative rounded-sm lg:rounded-lg duration-300 transition-all"
         :class="categoryOpen ? 'opacity-100' : 'delay-200 translate-y-12 opacity-0'">
        <button @click="categoryOpen = false" aria-label="close search modal"
                class="border border-black/5 flex items-center justify-center absolute top-3 right-4 p-2 bg-gray-50 hover:bg-gray-100 cursor-pointer transition-all max-lg:rounded-t-sm lg:rounded-b-sm text-black">
            <?php
            $args = array(
                'size' => '18',
                'class' => '',
            );
            get_template_part('template-parts/svg/close', null, $args);
            ?>
        </button>
        <div class="border-b border-gray-200 flex justify-center items-center mb-3">
            <p class="text-center border-b-2 border-primary pb-2 text-2xl">دسته بندی محصولات</p>
        </div>
        <?php
        $args = array(
                'class' => 'max-h-2/4 overflow-y-scroll'
        );
        get_template_part('template-parts/product/category-accordion-list', null, $args); ?>
    </nav>
</div>
