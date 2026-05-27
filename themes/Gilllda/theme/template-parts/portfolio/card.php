<a class="relative overflow-hidden group" href="<?= get_the_permalink();?>">
	<img class="w-full object-cover aspect-video" height="250" src="<?= the_post_thumbnail_url(); ?>"
		 alt="image of the <?= get_the_title(); ?> post">
	<div class="lg:absolute inset-0 bg-foreground lg:bg-black/70 transition-all duration-500 lg:opacity-0 group-hover:opacity-100 flex justify-center items-center text-white max-lg:p-4 px-12">
		<p class="text-base lg:text-2xl text-center"><?= get_the_title(); ?></p>
	</div>
</a>
