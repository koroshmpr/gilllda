<?php
if (is_front_page()) return;
$currentColor = 'text-gray-700';
$slashClass = 'text-gray-400 px-1';
?>

<nav class="<?= $args['class'] ?? ''; ?> flex text-nowrap items-center text-gray-600 overflow-x-scroll text-sm gap-x-1 mb-1" aria-label="breadcrumb">
    <?php if (is_single()) : ?>
        <?php
        $post_type = get_post_type();
        $post_type_name = ($post_type === 'post') ? 'مقالات' : get_post_type_object($post_type)->labels->singular_name;
        $post_type_link = ($post_type === 'post') ? get_permalink(get_option('page_for_posts')) : get_post_type_archive_link($post_type);
        ?>

        <?php if ($post_type_link) : ?>
            <a href="<?= esc_url($post_type_link); ?>" class="text-gray-600 hover:text-gray-700">
                <?= esc_html($post_type_name); ?>
            </a>
        <?php else : ?>
            <span class="<?= $currentColor; ?>"><?= esc_html($post_type_name); ?></span>
        <?php endif; ?>

        <span class="<?= $slashClass; ?>">/</span>

        <?php
        $categories = get_the_category();
        if (!empty($categories)) :
            $category = $categories[0];
            ?>
            <a href="<?php echo get_category_link($category->term_id); ?>" class="text-gray-500 hover:text-gray-700">
                <?php echo esc_html($category->name); ?>
            </a>
            <span class="<?= $slashClass; ?>">/</span>
        <?php endif; ?>

        <span class="<?= $currentColor; ?>"><?php the_title(); ?></span>

    <?php elseif (is_category()) : ?>
        <span class="<?= $currentColor; ?>"><?php single_cat_title(); ?></span>

    <?php elseif (is_archive()) : ?>
        <span class="<?= $currentColor; ?>">
            <?= (get_post_type() === 'post') ? 'بلاگ' : post_type_archive_title('', false); ?>
        </span>

    <?php elseif (is_search()) : ?>
        <span class="<?= $currentColor; ?>">نتایج جستجو برای "<?php echo get_search_query(); ?>"</span>

    <?php elseif (is_404()) : ?>
        <span class="<?= $currentColor; ?>">صفحه پیدا نشد</span>
    <?php endif; ?>
</nav>

<?php
//$args = array(
//    'class' => ''
//);
//get_template_part('template-parts/svg/breadcrumb', null, $args);
?>
