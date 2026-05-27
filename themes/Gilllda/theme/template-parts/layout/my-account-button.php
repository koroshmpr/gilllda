<div
	x-data="{ open: false }"
	@mouseenter="open = true"
	@mouseleave="open = false"
	class="relative inline-block text-left"
>
	<a
            aria-label="go to my account page"
		<?php if ( is_user_logged_in() ) : ?>
			href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"
		<?php else : ?>
			href="<?php echo esc_url( get_permalink( get_option('woocommerce_myaccount_page_id') ) ); ?>"
		<?php endif; ?>
		class="<?= $args['class'] ?? ''; ?> flex items-center transition-transform duration-200"
		:class="open ? 'scale-110' : ''"
	>
		<?php
		$svg_args = array( 'size' => $args['svgSize'] ?? '', 'class' => $args['svgClass'] ?? '' );
		get_template_part('template-parts/svg/person', null, $svg_args);
		?>
	</a>

	<?php if ( is_user_logged_in() ) : ?>
		<div
			x-show="open"
			x-cloak
			style="display: none"
			x-transition:enter="transition ease-out duration-200"
			x-transition:enter-start="opacity-0 scale-95 translate-y-1"
			x-transition:enter-end="opacity-100 scale-100 translate-y-0"
			x-transition:leave="transition ease-in duration-150"
			x-transition:leave-start="opacity-100 scale-100 translate-y-0"
			x-transition:leave-end="opacity-0 scale-95 translate-y-1"
			class="absolute right-0 mt-0 translate-x-1/2 w-52 origin-top-right rounded-md bg-white border border-gray-200 focus:outline-none z-[999] overflow-hidden"
		>

			<?php $current_user = wp_get_current_user(); ?>
			<div class="bg-gray-50 p-4 border-b border-gray-100">
				<p class="text-sm font-bold text-start text-gray-800 truncate"><?php echo esc_html( $current_user->display_name ); ?></p>
			</div>

			<nav>
				<ul class="list-none p-0 m-0 space-y-1">
					<?php
					$icons = array(
						'dashboard'       => 'dashicons-dashboard',
						'orders'          => 'dashicons-cart',
						'downloads'       => 'dashicons-download',
						'edit-address'    => 'dashicons-location',
						'payment-methods' => 'dashicons-id',
						'edit-account'    => 'dashicons-admin-users',
						'customer-logout' => 'dashicons-exit',
					);

					foreach ( wc_get_account_menu_items() as $endpoint => $label ) :
						$icon_class = isset( $icons[ $endpoint ] ) ? $icons[ $endpoint ] : 'dashicons-admin-generic';
						$url = esc_url( wc_get_account_endpoint_url( $endpoint ) );
						?>
						<li>
							<a href="<?php echo $url; ?>" class="group flex items-center px-3 py-2 text-xs text-gray-600 hover:bg-primary/10 hover:text-primary transition-all duration-200">
								<span class="dashicons <?php echo $icon_class; ?> ml-3 text-gray-400 group-hover:text-primary transition-colors"></span>
								<span class="font-medium text-sm"><?php echo esc_html( $label ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		</div>
	<?php endif; ?>
</div>
