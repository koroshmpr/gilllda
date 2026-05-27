<?php
$links = paginate_links(array(
    'type' => 'array',
    'prev_next' => false,
));
if ($links) : ?>

    <nav class="my-5 lg:col-span-12" aria-label="pagination">
        <ul class="pagination justify-center flex-wrap items-center flex gap-x-3 mb-0">
            <?php
            // get_previous_posts_link will return a string or void if no link is set.
            if ($prev_posts_link = get_previous_posts_link(__('<'))) :?>
                <li class="page-item prev">
                    <?= $prev_posts_link; ?>
                </li>
                <?php ;
            endif;
            ?>
            <li class="page-item">
                <?= join('</li><li class="page-item">', $links); ?>
            </li>
            <?php
            // get_next_posts_link will return a string or void if no link is set.
            if ($next_posts_link = get_next_posts_link(__('>'))) : ?>
                <li class="page-item next">
                    <?= $next_posts_link; ?>
                </li>
            <?php
            endif; ?>
        </ul>
    </nav>

<?php endif;
?>

<?php
//get_template_part('template-parts/global/pagination');
?>