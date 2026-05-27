<?php
$current_page = (get_query_var('paged')) ? get_query_var('paged') : 1;

if ($current_page == 1 && empty($_GET)) :

	get_template_part('template-parts/shop/shop-content');
	get_template_part('template-parts/global/faq-list');

endif;
?>
