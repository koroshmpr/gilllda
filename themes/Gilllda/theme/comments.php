<?php
/**
 * The template for displaying comments
 *
 * This is the template that displays the area of the page that contains both
 * the current comments and the comment form.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bluebox
 */

/*
 * If the current post is protected by a password and the visitor has not yet
 * entered the password we will return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="grid relative pb-5 h-fit xl:grid-cols-2 gap-5 items-start">

	<?php
	if ( have_comments() ) :
		?>
	   <h2 class="border-b-2 w-fit border-primary/70 font-bold py-3 mb-3 xl:order-1">دیدگاه کاربران</h2>
		<p class="xl:order-3">
			<?php
			$bluebox_comment_count = get_comments_number();
			if ( '1' === $bluebox_comment_count ) {
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				printf(
					/* translators: 1: title. */
					esc_html__( 'یک دیدگاه برای &ldquo;%1$s&rdquo;', 'bluebox' ),
					get_the_title()
				);
				// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				printf(
					/* translators: 1: comment count number, 2: title. */
					esc_html( _nx( '%1$s دیدگاه برای &ldquo;%2$s&rdquo;', '%1$s دیدگاه برای &ldquo;%2$s&rdquo;', $bluebox_comment_count, 'comments title', 'bluebox' ) ),
					number_format_i18n( $bluebox_comment_count ),
					get_the_title()
				);
				// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</p>

		<?php the_comments_navigation(); ?>

		<ol class="max-lg:border-b border-black/5 pb-5 xl:order-4">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'callback'   => 'bluebox_html5_comment',
					'short_ping' => true,
				)
			);
			?>
		</ol>

		<?php
		the_comments_navigation();

		// If there are existing comments, but comments are closed, display a
		// message.
		if ( ! comments_open() ) :
			?>
			<p><?php esc_html_e( 'Comments are closed.', 'bluebox' ); ?></p>
			<?php
		endif;

	endif;

	comment_form();
	?>

</div><!-- #comments -->
