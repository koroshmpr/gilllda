<a class="navbar-brand flex justify-center items-center <?= $args['class'] ?? ''; ?>" href="<?= home_url() ?>">
	<?php
	$logoLink = $args['logoLink'] ?? 'site_logo';
    $logoImg = get_field( $logoLink , 'option');
    ?>
    <img width="<?= esc_attr($args['width'] ?? ($logoImg['width'] ?? 120)); ?>" height="<?= esc_attr($args['height'] ?? ($logoImg['height'] ?? 60)); ?>" class="<?= $args['logoSize'] ?? 'w-20' ?> object-cover"
		 src="<?= esc_url($logoImg['sizes']['small'] ?? $logoImg['sizes']['medium'] ?? $logoImg['url'] ?? '') ?>"
		 alt="<?= esc_attr(get_bloginfo('name')); ?>">
	<?php ?>
</a>
<?php
//$args = array(
//	'size' => '200',
//    'class' => '',
//    'logoLink' => '',
//    'logoSize' => 'w-20',
//    'height' => '40',
//);
//get_template_part('template-parts/global/logo', null, $args);
?>
