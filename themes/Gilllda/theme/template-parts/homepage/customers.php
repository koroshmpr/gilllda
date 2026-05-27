<?php
$customersGroup = get_field('customers');
$customers = $customersGroup['customers'];

?>

<section class="bg-foreground py-10 lg:py-32">
	<div class="container max-w-content">
		<div class="relative flex justify-center lg:mb-24 mb-16">
			<h3 class="lg:text-4xl text-lg text-center font-bold text-white"><?= $customersGroup['title'] ?? ''; ?></h3>
			<span
				class="absolute inset-0 text-center -translate-y-full text-white/5 lg:text-8xl uppercase text-3xl"><?= $customersGroup['subtitle'] ?? ''; ?></span>
		</div>

		<?php if ($customers) : ?>
			<article
				class="grid lg:grid-cols-7 grid-cols-3 gap-8 max-lg:flex-wrap justify-center items-center text-white">
			<?php foreach ($customers as $customer):
					// Get the post object for the customer
					$customer_id = $customer->ID;

					// Check if the customer post has a thumbnail and display it
					if (has_post_thumbnail($customer_id)) : ?>
						<img src="<?php echo get_the_post_thumbnail_url($customer_id, 'large'); ?>"
							 alt="<?php echo get_the_title($customer_id); ?>"
							 class="object-cover w-full aspect-square">
					<?php endif;
					endforeach; ?>
			</article>
		<?php endif; ?>
	</div>
</section>
