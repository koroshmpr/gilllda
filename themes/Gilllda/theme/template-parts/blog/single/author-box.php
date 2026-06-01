<?php
$author_id = get_post_field('post_author', get_the_ID());
?>
<section class="xl:w-3/4 border bg-primary text-white flex gap-x-5 gap-y-2 max-lg:flex-col max-lg:items-center  border-black/30 rounded-lg min-h-40 p-6">
        <?php $user_array_img = get_field('profile_image', 'user_' . $post->post_author);
        if ($user_array_img) : ?>
            <img width="70" class="bg-white aspect-square border rounded-full p-2 border-gray-400" src="<?php echo $user_array_img['url'] ?>"
                 alt="<?php echo $user_array_img['alt'] ?>">
        <?php else :
            $args = array(
                'size' => 100,
                'class' => 'bg-white text-black border rounded-full my-auto p-2 border-gray-400'
            );
            get_template_part('template-parts/svg/person', null, $args);
        endif; ?>
    <div class="flex-1 flex max-lg:flex-col gap-5 items-center justify-between">
       <div class="flex flex-col lg:w-2/3 max-lg:items-center gap-y-2">
           <p class="text-lg font-bold"><?= get_the_author_meta('display_name', get_queried_object()->post_author); ?></p>
           <p class="text-sm opacity-75 text-justify">
               <?= get_the_author_meta('description', get_queried_object()->post_author); ?>
           </p>
       </div>
        <a aria-label="like to author page" class="bg-white text-black flex text-nowrap max-lg:w-full justify-center items-center lg:hover:bg-gray-100 transition-all gap-x-2 rounded-lg px-4 py-3 border border-primary" href="<?=  get_author_posts_url($author_id); ?>">
            دیدن پروفایل نویسنده
        <?php
        $args = array(
            'size' => 20,
            'class' => ''
        );
        get_template_part('template-parts/svg/chevron-left', null, $args);
        ?>
        </a>
    </div>
</section>