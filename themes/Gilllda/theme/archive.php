<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bluebox
 */

get_header();
?>

	<header class="container max-w-content pb-8 pt-24">
		<h1 class="text-white text-3xl lg:text-8xl"><?php the_title() ?></h1>
	</header>
	<section class="container max-w-content grid lg:grid-cols-3 gap-8">
		<?php if ( have_posts() ) : ?>
			<?php
			// Start the Loop.
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content/content', 'excerpt' );

				// End the loop.
			endwhile;

			// Previous/next page navigation.
			bluebox_the_posts_navigation();

		else :

			// If no content, include the "No posts found" template.
			get_template_part( 'template-parts/content/content', 'none' );

		endif;
		?>
	</section><!-- #primary -->

<?php
get_footer();
