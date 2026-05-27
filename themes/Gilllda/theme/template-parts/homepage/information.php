<?php $info = get_field('information');
if ($info): ?>
	<section class="relative flex flex-wrap justify-center lg:my-24 my-12">
		<img width="390" height="350" class="lg:basis-11/12 w-full lg:h-[650px] h-[350px] object-cover" src="<?= $info['image']['url'] ?? ''; ?>" alt="<?= $info['image']['url'] ?? ''; ?>">
		<div class="xl:w-2/5 w-11/12 bg-foreground p-6 lg:p-10 lg:-translate-y-1/2 -translate-y-24 flex flex-col gap-y-6 rounded-xl">
			<h2 class="text-white text-2xl max-lg:text-center pb-4 border-b border-white/30"><?= $info['title'] ?? '' ?></h2>
			<article class="text-white/70"><?= $info['content'] ?? '' ?></article>
		</div>
	</section>
<?php endif; ?>
