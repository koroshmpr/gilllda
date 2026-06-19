<?php
/**
 * The template for displaying Author Archive pages.
 *
 * @package YourTheme
 */

get_header(); ?>

<?php
// Get Author Data
$author = get_queried_object();
$author_id = get_post_field('post_author', get_the_ID());
?>

<!-- Author Info -->
<header class="container max-w-content border px-6 py-12 lg:rounded-lg gap-6  mb-6 flex max-lg:flex-col items-center border-black/10">
    <?php
    $args = array(
        'size' => 100,
        'class' => 'bg-white border rounded-full my-auto p-2 border-gray-200'
    );
    get_template_part('template-parts/svg/person', null, $args);
    ?>
    <div class="flex flex-col gap-y-2 max-lg:items-center">
        <h1 class="text-black text-3xl pb-2 border-primary"><?= esc_html($author->display_name); ?></h1>
        <article class="text-justify opacity-75">
            <?= get_the_author_meta('description', get_queried_object()->post_author); ?>
        </article>
    </div>
</header>
<?php
if (have_posts()) :?>
    <section class="container border my-10 border-gray-300 rounded-lg">
        <h2 class="w-fit mx-auto px-2 text-lg -translate-y-1/2 bg-white">
            مقالات
            <span>(<?= count_user_posts($author_id); ?>)</span>
        </h2>
        <div class="grid md:grid-cols-3 lg:grid-cols-4 pb-4 xl:grid-cols-5 gap-4">
            <?php
            global $wp_query; // Bring the main query object into scope

            while (have_posts()) :
                the_post();

                // Create the args array to pass the eager flag
                $args = array(
                    'eager' => $wp_query->current_post < 4 // Eager load the first 4 blog posts
                );

                get_template_part('template-parts/blog/archive-card', null, $args);
            endwhile;
            get_template_part('template-parts/global/pagination');

            // Reset query
            wp_reset_postdata();
            ?>
        </div>
    </section>
<?php
endif;
get_footer(); ?>
