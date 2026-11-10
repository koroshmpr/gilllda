<?php
get_header();

$term = get_queried_object();

$acf_term_id = $term->taxonomy . '_' . $term->term_id;

$content = get_field('content', $acf_term_id);
?>
<?php get_template_part('template-parts/blog/mag-header', null, [
        'title'   => single_term_title('', false),
        'desc'    => wp_strip_all_tags(term_description($term)),
        'current' => $term->term_id,
    ]); ?>
    <section class="container px-4 pb-14 pt-6 lg:px-0">
        <?php if (have_posts()) : global $wp_query; ?>
            <div class="grid gap-x-6 gap-y-12 md:grid-cols-2 lg:grid-cols-3">
                <?php while (have_posts()) : the_post();
                    get_template_part('template-parts/blog/mag-card', null, ['d' => $wp_query->current_post % 3, 'eager' => $wp_query->current_post < 3]);
                endwhile; ?>
            </div>
            <?php get_template_part('template-parts/global/pagination'); ?>
        <?php else : ?>
            <p class="py-20 text-center text-2xl text-gray-400">هنوز مقاله‌ای در این دسته منتشر نشده است.</p>
        <?php endif; ?>
    </section>

<?php

if ($content) :
    $args = array(
        'id'    => $acf_term_id,
        'class' => 'container max-w-content my-3'
    );
    get_template_part('template-parts/shop/shop-content', null, $args);
endif;

get_footer();