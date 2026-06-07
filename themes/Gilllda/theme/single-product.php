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
<?php
get_template_part('template-parts/product/single/mobile-fix-cta');
get_template_part('template-parts/product/single/product', 'notice');
get_template_part('template-parts/product/single/related-product');
get_template_part('template-parts/product/single/related-post');
get_template_part('template-parts/product/single/other-product');
 get_footer();
