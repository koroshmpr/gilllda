<a class="navbar-brand flex justify-center items-center <?= $args['class'] ?? ''; ?>" href="<?= home_url() ?>">
	<?php
	$logoLink = $args['logoLink'] ?? 'site_logo';
    $logoImg = get_field( $logoLink , 'option');
    ?>
    <img width="<?= $args['size'] ?? $logoImg['width']; ?>" height="<?= $args['height'] ?? $logoImg['height']; ?>" class="<?= $args['logoSize'] ?? 'w-20' ?> object-cover"
		 src="<?= esc_url($logoImg['url']) ?>"
		 alt="<?= esc_attr($logoImg['title']) ?>">
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
