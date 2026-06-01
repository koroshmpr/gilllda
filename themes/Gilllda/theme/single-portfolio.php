<?php
///**
// * The template for displaying all single posts
// *
// * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
// *
// * @package bluebox
// */
//
//get_header();
//$info = get_field('info');
//$des = get_field('description');
//$embeds = get_field('ember_video');
//$galleries = get_field('gallery');
//$related = get_field('related_category');
//?>
<?php //get_template_part('template-parts/global/post-hero.php'); ?>
<?php //if ($info) :
//	?>
<!--	<section class="bg-foreground py-6 lg:py-20">-->
<!--		<div class="container max-w-content grid lg:grid-cols-3 lg:gap-x-16 gap-y-3">-->
<!--			--><?php
//			$boxClass = 'flex lg:grid gap-6 items-center text-center max-lg:border max-lg:border-title/10';
//			$titleCLass = 'max-lg:bg-title lg:text-title lg:border-b max-lg:basis-1/3 max-lg:border-e border-white/10 lg:pb-3 max-lg:p-2';
//			$subtitleCLass = 'text-white/70 max-lg:py-3';
//			?>
<!--			<div class="--><?php //= $boxClass; ?><!--">-->
<!--				<h2 class="--><?php //= $titleCLass ?><!--">برند</h2>-->
<!--				<p class="--><?php //= $subtitleCLass; ?><!--">--><?php //= $info['brand'] ?><!--</p>-->
<!--			</div>-->
<!--			<div class="--><?php //= $boxClass; ?><!--">-->
<!--				<h2 class="--><?php //= $titleCLass ?><!--">موضوع</h2>-->
<!--				<p class="--><?php //= $subtitleCLass; ?><!--">--><?php //= $info['subject'] ?><!--</p>-->
<!--			</div>-->
<!--			<div class="--><?php //= $boxClass; ?><!--">-->
<!--				<h2 class="--><?php //= $titleCLass ?><!--">تاریخ انتشار</h2>-->
<!--				<p class="--><?php //= $subtitleCLass; ?><!--">--><?php //= $info['date'] ?><!--</p>-->
<!--			</div>-->
<!--		</div>-->
<!--	</section>-->
<?php //endif; ?>
<?php //if ($des) : ?>
<!--	<article class="container py-8 leading-12 text-justify text-white/70">-->
<!--		--><?php //= $des; ?>
<!--	</article>-->
<?php //endif; ?>
<?php //if ($embeds): ?>
<!--	<section class="py-8 container grid --><?php //= count($embeds) > 1 ? 'lg:grid-cols-2' : ''; ?><!-- gap-3">-->
<!--		--><?php //foreach ($embeds as $embed):
//			echo $embed['video_iframe'];
//		endforeach; ?>
<!--	</section>-->
<?php //endif; ?>
<?php //if ($galleries): ?>
<!--	<section class="py-8 container grid lg:grid-cols-3 gap-3">-->
<!--		--><?php //foreach ($galleries as $gallery): ?>
<!--			<img class="w-full aspect-square object-cover" src="--><?php //= $gallery['url'] ?? '' ?><!--"-->
<!--				 alt="--><?php //= $gallery['title'] ?? '' ?><!--">-->
<!--		--><?php //endforeach; ?>
<!--	</section>-->
<?php //endif; ?>
<?php //if ($related):
//	$args = [
//		'post_type' => 'post', // Change if it's a custom post type
//		'orderby' => 'rand',
//		'posts_per_page' => 3, // Adjust the number of posts as needed
//		'tax_query' => [
//			[
//				'taxonomy' => 'category', // Ensure this matches the taxonomy used
//				'field' => 'term_id',
//				'terms' => $related, // Make sure this returns an ID or an array of IDs
//			],
//		],
//	];
//
//	$query = new WP_Query($args);
//	if ($query->have_posts()):?>
<!--		<section class="py-8 container">-->
<!--			<h6 class="pb-8 text-center text-white basis-full text-3xl fw-bold">مقالات مرتبط</h6>-->
<!--			<div class=" grid lg:grid-cols-3 gap-3">-->
<!--				--><?php //while ($query->have_posts()): $query->the_post();
//					get_template_part('template-parts/blog/card');
//				endwhile; ?>
<!--			</div>-->
<!--			--><?php
//			wp_reset_postdata(); // Reset the query
//			?>
<!--		</section>-->
<!--	--><?php //endif;
//endif; ?>
<?php //get_footer();
