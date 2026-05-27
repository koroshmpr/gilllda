<?php
/* Template Name: content */
get_header();?>
<article class="container max-w-content prose prose-sm prose-h1:max-lg:text-2xl  h-fit duration-500 overflow-hidden text-justify transition-all leading-7 my-5">
    <h1><?php the_title();?></h1>
    <?php the_content(); ?>
</article>
<?php
get_footer();
