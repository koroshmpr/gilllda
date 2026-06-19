<?php
get_header();

$term = get_queried_object();

$acf_term_id = $term->taxonomy . '_' . $term->term_id;

$content = get_field('content', $acf_term_id);
?>
    <article class="container bg-white grid lg:grid-cols-12 gap-4 max-lg:px-3 pt-2">
        <header class="lg:col-span-12 border-b border-black/10">
            <h1 class="text-black text-3xl border-b-2 pb-3 border-primary w-fit">
                <?= esc_html(single_term_title('', false)); ?>
            </h1>
        </header>

        <?php if (have_posts()) : ?>

            <?php
            $args = array(
                'term_id' => $term->term_id,
            );
            get_template_part('template-parts/blog/sidebar', null, $args);
            ?>

            <section class="lg:col-span-8 xl:col-span-9 max-w-content flex flex-col gap-3 mb-8">

                <?php get_template_part('template-parts/global/grid-button'); ?>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3"
                     :class="gridView === 'large' ? 'md:grid-cols-1 lg:!grid-cols-2 xl:!grid-cols-3' : 'grid-cols-2 lg:!grid-cols-3 xl:!grid-cols-4'">
                    <?php
                    global $wp_query; // Bring the main query object into scope

                    while (have_posts()) :
                        the_post();

                        $args = array(
                            'class' => 'transition-all duration-500',
                            'eager' => $wp_query->current_post < 4 // Eager load the first 4 blog posts
                        );

                        get_template_part('template-parts/blog/archive-card', null, $args);
                    endwhile;
                    ?>
                </div>
            </section>

            <?php
            get_template_part('template-parts/global/pagination');
            // Reset query
            wp_reset_postdata();
            ?>

        <?php endif; ?>
    </article>

<?php

if ($content) :
    $args = array(
        'id'    => $acf_term_id,
        'class' => 'container max-w-content my-3'
    );
    get_template_part('template-parts/shop/shop-content', null, $args);
endif;

get_footer();