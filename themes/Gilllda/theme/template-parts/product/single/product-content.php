<?php
global $product;

$args = array(
	'post_type' => 'portfolio',
    'post_per_page' => 10,
	'meta_query' => array(array('key' => 'related_product', 'value' => get_the_id(), 'compare' => 'like'))
);
$related_portfolio = new WP_Query($args);
$has_portfolio = $related_portfolio->have_posts();
?>

<div x-data="{ active: 'details' }"
	 class="lg:col-span-12 xl:col-span-9 z-0 bg-white flex flex-col lg:py-8 max-lg:px-3 lg:pe-8 rtl" dir="rtl">

	<nav class="sticky w-full <?= current_user_can('administrator') ? 'lg:top-22' : 'lg:top-14 '; ?> top-12 gap-x-2 flex border-b z-[2] bg-white border-black/10">
		<?php
		$btnClass = 'py-3 pl-2 text-sm cursor-pointer transition-all duration-300 font-bold text-nowrap';
		$titleClass = 'border-b-2 w-fit font-bold py-2 my-4 border-primary/70';
		$sectionCLass = 'bg-white border-b border-black/10 flex flex-col relative pb-5';
		?>

		<div
			class="absolute bottom-0 h-0.5 bg-primary transition-all duration-300 ease-in-out"
			:class="{
              'right-0 w-[55px]': active === 'details',
              'right-[65px] w-[90px]': active === 'gallery',
              '<?= $has_portfolio ? 'right-[165px]' : 'right-[65px]'; ?> w-[45px]': active === 'comments'
          }">
		</div>

		<button
			@click.prevent="document.getElementById('details').scrollIntoView({behavior: 'smooth'})"
			:class="active === 'details' ? 'text-primary' : 'text-gray-400 hover:text-gray-600'"
			class="<?= $btnClass; ?>">
			توضیحات
		</button>

		<?php if ($has_portfolio): ?>
			<button
				@click.prevent="document.getElementById('gallery').scrollIntoView({behavior: 'smooth'})"
				:class="active === 'gallery' ? 'text-primary' : 'text-gray-400 hover:text-gray-600'"
				class="<?= $btnClass; ?>">
				عکس‌های شما
			</button>
		<?php endif; ?>

		<button
			@click.prevent="document.getElementById('comments-section').scrollIntoView({behavior: 'smooth'})"
			:class="active === 'comments' ? 'text-primary' : 'text-gray-400 hover:text-gray-600'"
			class="<?= $btnClass; ?>">
			دیدگاه
		</button>
	</nav>
<!--    --><?php //wc_display_product_attributes($product);?>

	<div class="<?= $sectionCLass; ?>"
		 x-data="{showMore : true}"
		 id="details"
		 x-intersect:enter.margin.-15%.0px.-70%.0px="active = 'details'">
		<h2 class="<?= $titleClass; ?>">توضیحات</h2>
		<article :class="showMore ? 'max-h-[150px]' : ''"
				 class="prose prose-sm max-w-none h-fit duration-500 overflow-hidden text-justify transition-all leading-7">
			<?php the_content() ?>
		</article>

		<div :class="showMore ? '' : 'hidden'"
			 class="bg-gradient-to-t from-white/95 via-white/85 via-70% h-24 absolute bottom-0 w-full"></div>
		<button
			:class="showMore ? '-translate-y-1/2' : ' my-3 sticky bottom-32 lg:bottom-5'"
			@click="showMore = !showMore"
			class="bg-icon border flex items-center justify-center gap-1 text-sm z-0 border-icon transition-all cursor-pointer hover:brightness-105 rounded-sm px-16 lg:px-12 mx-auto py-1">
          <span x-show="!showMore">
             <?php get_template_part('template-parts/svg/close', null, ['class' => 'text-gray-700', 'size' => '15']); ?>
          </span>
			<span x-text="showMore ? 'بیشتر' : 'بستن'"></span>
		</button>
	</div>

	<?php if ($has_portfolio): ?>
		<div id="gallery"
			 x-intersect:enter.margin.-15%.0px.-70%.0px="active = 'gallery'"
			 class="<?= $sectionCLass; ?> py-6 overflow-hidden">
			<h2 class="<?= $titleClass; ?>">عکس‌های شما</h2>

			<div class="swiper post-slider w-full"
				 data-index="portfolios" data-perfix="portfolio" data-space="10"
				 data-perpage="5.5" data-mobile="2.2" data-laptop="4.2" data-tablet="3.2" data-autoplay="3000">
				<div class="swiper-wrapper">
					<?php while ($related_portfolio->have_posts()): $related_portfolio->the_post(); ?>
						<div class="swiper-slide overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
							<img class="object-cover w-full aspect-square"
								 src="<?= get_the_post_thumbnail_url(get_the_ID(), 'medium') ?>" alt="<?php the_title(); ?>"/>
						</div>
					<?php endwhile; wp_reset_postdata(); ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
        <?php
        $args = array(
            'title' => 'سوالات متداول'
        );
        get_template_part('template-parts/global/faq-list', null, $args);?>
    <div class="bg-white relative">
        <?php get_template_part('template-parts/blog/single/rating', null, ['class' => 'w-full max-lg:border-b-4 lg:border-s-4 max-lg:border-b-amber-400 lg:border-s-amber-400 rounded-lg flex max-lg:flex-col border-amber-400/30 ']); ?>
    </div>
</div>
