<aside class="lg:sticky lg:col-span-4 xl:col-span-3 order-2 <?= current_user_can('administrator') ? 'top-28' : 'top-20'; ?> grid gap-4 border border-black/15 bg-gray-100/50 lg:rounded-xl p-4">
    <?php
    get_template_part('template-parts/blog/single/post-information');
    get_template_part('template-parts/blog/single/toc');
    $args = array(
        'class' => 'max-lg:hidden'
    );
    get_template_part('template-parts/blog/single/related-product', null, $args);
    $args = array(
        'class' => 'max-lg:hidden',
        'linkClass' => 'border border-black/5 text-black/70 bg-white/10 hover:bg-white/75 p-2 rounded-sm transition-all'
    );
    get_template_part('template-parts/blog/single/share-button', null, $args); ?>
</aside>