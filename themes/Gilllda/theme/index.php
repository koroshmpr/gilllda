<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no `home.php` file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bluebox
 */

// اصلاح مهم: دریافت ID صحیح حتی در صفحه بلاگ
$id = is_home() ? get_option('page_for_posts') : get_queried_object_id();

$current_page = (get_query_var('paged')) ? get_query_var('paged') : 1;

$args = array(
    'post_type' => 'page',
    'post_status' => 'publish',
    'post__in' => [$id]
);

// دریافت فیلدهای ACF از ID صحیح
$faq = get_field('faq_list', $id);
$pages = new WP_Query($args);
$content = $pages->posts[0]->post_content ?? '';

get_header();
?>

    <header class="container lg:px-0 border-b border-black/10">
        <h1 class="text-black text-3xl border-b-2 border-primary w-fit">
            <?php
            // نمایش داینامیک و اصولی عنوان بر اساس نوع صفحه
            if (is_home()) {
                echo single_post_title('', false); // عنوان برگه بلاگ
            } elseif (is_category() || is_tag() || is_tax()) {
                echo single_term_title('', false); // عنوان دسته‌بندی یا برچسب
            } elseif (is_search()) {
                echo 'نتایج جستجو برای: ' . get_search_query(); // عنوان صفحه جستجو
            } else {
                echo get_the_title($id); // عنوان برگه‌های عادی
            }
            ?>
        </h1>
    </header>

<?php
if ($current_page == 1 && empty($_GET)) :
    get_template_part('template-parts/blog/archive/must-visited-posts');
endif;

$args2 = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
    'paged' => $current_page,
);

$posts = new WP_Query($args2);
if ($posts->have_posts()) :
    ?>
    <article class=" container bg-white max-lg:px-2 grid lg:grid-cols-12 gap-2 lg:gap-4 pt-2">
        <?php get_template_part('template-parts/blog/sidebar'); ?>

        <section class="lg:col-span-8 xl:col-span-9 max-w-content flex flex-col gap-3 lg:mb-4">
            <?php get_template_part('template-parts/global/grid-button'); ?>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3"
                 :class="gridView === 'large' ? 'md:grid-cols-1 lg:!grid-cols-2 xl:!grid-cols-3' : 'grid-cols-2 lg:!grid-cols-3 xl:!grid-cols-4'">
                <?php
                while ($posts->have_posts()) :
                    $posts->the_post();

                    $args = array(
                        'class' => 'transition-all duration-500',
                        'eager' => $posts->current_post < 4 // Eager load the first 4 blog posts
                    );

                    get_template_part('template-parts/blog/archive-card', null, $args);
                endwhile;
                ?>
            </div>
        </section>

        <?php
        get_template_part('template-parts/global/pagination');
        // Reset query
        wp_reset_postdata(); // اضافه کردن این تابع برای جلوگیری از تداخل کوئری‌ها
        ?>
    </article>
<?php else : ?>
    <article class="container min-h-[30vh] flex flex-col justify-center items-center gap-4">
        <?php get_template_part('template-parts/svg/message', null, ['size' => 150, 'class' => 'opacity-10']); ?>
        <p class="text-4xl text-black/50">مقاله‌ای یافت نشد!</p>
    </article>
<?php endif;

if ($current_page == 1 && empty($_GET)) :
    $args = array(
        'content' => $content
    );
    get_template_part('template-parts/global/content', null, $args);

    if ($faq): ?>
        <section class="container max-lg:px-3 lg:mb-4">
            <?php
            $args = array(
                'items' => $faq
            );
            get_template_part('template-parts/global/faq-list', null, $args);
            ?>
        </section>
    <?php endif;
endif;
?>

<?php get_footer(); ?>