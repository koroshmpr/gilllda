<?php
get_header();
wp_reset_postdata();
?>
	<section class="container relative max-lg:px-0 lg:grid items-start lg:grid-cols-12">
		<?php
		get_template_part('template-parts/product/single/product', 'gallery');
		get_template_part('template-parts/product/single/product', 'attribute');
		get_template_part('template-parts/product/single/product', 'sidebar');
		get_template_part('template-parts/product/single/product', 'content');
		?>
	</section>

<div id="comments-section"
		 class="pt-8 bg-white relative"
		 x-intersect:enter.margin.-15%.0px.-70%.0px="active = 'comments'">
		<?php
		if (comments_open() || get_comments_number()) {
			comments_template();
		}
		?>
	</div>
<?php
get_template_part('template-parts/product/single/mobile-fix-cta');
get_template_part('template-parts/product/single/product', 'notice');
get_template_part('template-parts/product/single/related-product');
get_template_part('template-parts/product/single/related-post');
get_template_part('template-parts/product/single/other-product');
 get_footer();
