<?php
$post_url = urlencode(get_permalink());
$post_title = urlencode(get_the_title());
$post_image = urlencode(get_the_post_thumbnail_url(null, 'full')); // Get featured image URL

$twitter_url = "https://twitter.com/intent/tweet?url={$post_url}&text={$post_title}";
$linkedin_url = "https://www.linkedin.com/shareArticle?mini=true&url={$post_url}&title={$post_title}";
$telegram_url = "https://t.me/share/url?url={$post_url}&text={$post_title}";
$pinterest_url = "https://pinterest.com/pin/create/button/?url={$post_url}&media={$post_image}&description={$post_title}";
$whatsapp_url = "https://api.whatsapp.com/send?text={$post_title}%20{$post_url}";

$linkClass = $args['linkClass'] ?? 'bg-white/20 p-2 rounded-sm transition-all';
$svgSize = '15';
$class = $args['class'] ?? '';
$args = array('size' => $svgSize);
?>

<div class="<?= $class; ?> flex gap-x-1 items-center justify-center w-fit mx-auto">
	<a href="<?= $twitter_url; ?>" target="_blank" class="<?= $linkClass; ?>"
	   aria-label="Share on Twitter" title="Share on Twitter">
		<?php get_template_part('template-parts/svg/socials/twitter', null , $args); ?>
	</a>
	<a href="<?= $linkedin_url; ?>" target="_blank" class="<?= $linkClass; ?>"
	   aria-label="Share on LinkedIn" title="Share on LinkedIn">
		<?php get_template_part('template-parts/svg/socials/linkedin', null , $args); ?>
	</a>
	<a href="<?= $telegram_url; ?>" target="_blank" class="<?= $linkClass; ?>"
	   aria-label="Share on Telegram" title="Share on Telegram">
		<?php get_template_part('template-parts/svg/socials/telegram', null , $args); ?>
	</a>
	<a href="<?= $pinterest_url; ?>" target="_blank" class="<?= $linkClass; ?>"
	   aria-label="Share on Pinterest" title="Share on Pinterest">
		<?php get_template_part('template-parts/svg/socials/pinterest', null , $args); ?>
	</a>
	<a href="<?= $whatsapp_url; ?>" target="_blank" class="<?= $linkClass; ?>"
	   aria-label="Share on WhatsApp" title="Share on WhatsApp">
		<?php get_template_part('template-parts/svg/socials/whatsapp', null , $args); ?>
	</a>
</div>
