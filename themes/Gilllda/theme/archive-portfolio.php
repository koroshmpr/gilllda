<?php
/* Template Name: archive portfolio */

get_header();

global $woo_active;

// Pagination setup
$paged = max(1, get_query_var('paged'));

?>

    <header class="py-3 lg:py-6 bg-primary/10">
        <h1 class="text-2xl lg:text-4xl text-center"><?php the_title(); ?></h1>
    </header>

<?php if ($woo_active): ?>

    <section class="bg-primary/5 py-5">
        <div class="flex flex-col container max-lg:px-0 gap-y-3 lg:gap-y-5">

            <?php
            // Step 1: Get all portfolio IDs
            $portfolio_posts = get_posts(array(
                'post_type' => 'portfolio',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
            ));

            $related_product_ids = array();

            // Step 2: Collect related product IDs from portfolios
            foreach ($portfolio_posts as $portfolio_id) {
                $related_products = get_field('related_product', $portfolio_id);
                if ($related_products) {
                    if (is_array($related_products)) {
                        foreach ($related_products as $rp) {
                            $related_product_ids[] = is_object($rp) ? $rp->ID : $rp;
                        }
                    } else {
                        $related_product_ids[] = is_object($related_products) ? $related_products->ID : $related_products;
                    }
                }
            }

            $related_product_ids = array_unique($related_product_ids);

            if (empty($related_product_ids)) {
                echo '<p class="text-center p-10 text-lg">هیچ محصول مرتبطی یافت نشد.</p>';
            } else {

                // Step 3: Query products with pagination, only related IDs
                $args = array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => 6, // adjust per page
                    'paged' => $paged,
                    'post__in' => $related_product_ids,
                    'orderby' => 'date',
                );

                $productQuery = new WP_Query($args);

                if ($productQuery->have_posts()):
                    while ($productQuery->have_posts()): $productQuery->the_post();
                        $product_id = get_the_ID();
                        $product_title = get_the_title();
                        $product_link = get_permalink();
                        $product_image = get_the_post_thumbnail_url($product_id, 'full');

                        // Get related portfolios for this product
                        $portfolioQuery = new WP_Query(array(
                            'post_type' => 'portfolio',
                            'posts_per_page' => 10,
                            'orderby' => 'rand',
                            'meta_query' => array(
                                array(
                                    'key' => 'related_product',
                                    'value' => $product_id,
                                    'compare' => 'LIKE',
                                )
                            )
                        ));

                        if ($portfolioQuery->have_posts()):
                            $portfolio_count = $portfolioQuery->found_posts;
                            ?>

                            <article
                                    class="flex relative md:rounded-2xl bg-icon/30 h-[30vh] xl:h-[40vh] border-y-2 md:border border-icon shadow-xs overflow-hidden">
                                <?php if ($portfolio_count > 4) : ?>
                                    <div class="absolute w-10 lg:w-14 inset-y-0 left-0 backdrop-blur-[1px] bg-gradient-to-r from-black/60 via-black/20 to-transparent z-1"></div>
                                    <div class="absolute w-10 lg:w-14 inset-y-0 right-1/3 backdrop-blur-[1px] lg:right-1/5 bg-gradient-to-l from-black/60 via-black/20 to-transparent z-1"></div>
                                <?php endif; ?>

                                <div class="basis-1/3 shadow-xs px-2 lg:p-4 bg-icon/50 hover:bg-icon/80 transition-all duration-300 shrink-0 lg:basis-1/5 overflow-hidden flex flex-col gap-2 justify-center group/item items-center">
                                    <img width="300" height="100"
                                         class="w-full object-cover transition-all rounded-sm lg:rounded-xl delay-200 duration-1000 group-hover/item:object-bottom aspect-3/4 sm:aspect-[4/3] lg:aspect-square"
                                         src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($product_title); ?>">
                                    <div class="flex flex-col gap-y-2 justify-between items-center w-full">
                                        <h2 class="text-sm lg:text-xl"><?php echo esc_html($product_title); ?></h2>
                                        <a class="max-lg:py-2 p-3 w-full justify-center relative flex items-center gap-x-3 text-primary font-bold hover:text-white overflow-hidden rounded-lg before:inset-0 group/related before:z-[0] before:transition-all before:duration-300 transition-all before:absolute before:translate-y-full hover:before:translate-y-0  before:bg-primary gap-1 border border-primary"
                                           href="<?php echo esc_url($product_link); ?>">
                                            <p class="z-0 max-lg:text-sm transition-all">سفارش</p>
                                            <div class="relative flex justify-center items-center">
                                                <?php
                                                $args = array(
                                                    'size' => '15',
                                                    'class' => 'z-0 rotate-180 absolute transition-all group-hover/related:opacity-0 duration-300',
                                                );
                                                get_template_part('template-parts/svg/arrow-right', null, $args);
                                                ?>
                                                <?php
                                                $args = array(
                                                    'size' => '15',
                                                    'class' => 'opacity-0 group-hover/related:opacity-100 absolute z-0 duration-300 rotate-45 group-hover/related:rotate-0 transition-all'
                                                );
                                                get_template_part('template-parts/svg/shop', null, $args);
                                                ?>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                <div class="flex-1 lg:basis-3/4 flex overflow-x-scroll relative">
                                    <?php
                                    while ($portfolioQuery->have_posts()): $portfolioQuery->the_post(); ?>
                                        <img class="aspect-square object-cover border-x-2 border-white w-44 md:w-56 transition-all ease-out duration-700 lg:min-w-[250px] hover:min-w-[500px]"
                                             width="200"
                                             height="200"
                                             src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'full')); ?>"
                                             alt="<?php echo esc_attr(get_the_title()); ?>">
                                    <?php endwhile;
                                    wp_reset_postdata();
                                    ?>
                                </div>
                            </article>

                        <?php
                        endif;

                    endwhile;

                    // Pagination
                    $big = 999999999; // need an unlikely integer

                    echo '<nav class="pagination-wrapper mt-10">';
                    echo paginate_links(array(
                        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format' => '?paged=%#%',
                        'current' => $paged,
                        'total' => $productQuery->max_num_pages,
                        'prev_text' => __('« قبلی'),
                        'next_text' => __('بعدی »'),
                        'type' => 'list',
                    ));
                    echo '</nav>';

                    wp_reset_postdata();

                else:
                    echo '<p class="text-center p-10 text-lg">هیچ محصول مرتبطی یافت نشد.</p>';
                endif;
            }
            ?>
        </div>
    </section>

<?php endif;

get_footer();