<?php $about = get_field('about_us'); ?>
<section class="container my-6 lg:mt-24 flex max-lg:flex-col relative justify-start lg:justify-center items-start gap-x-12 gap-y-8 h-[50vh] max-lg:w-11/12 lg:h-[80vh]">
	<div
		:class="scrollingDown ? 'lg:translate-y-6 scale-95' : (scrollingUp ? '' : '')"
		class="text-white lg:text-black h-fit lg:basis-2/5 2xl:basis-1/4 relative z-[1] lg:sticky top-36 duration-700 transition-all flex flex-col gap-y-2">
		<h2 class="lg:text-3xl  lg:border-s-4  border-primary/30 lg:ps-4 mb-5 text-2xl"><?= $about['title'] ?? ''; ?></h2>
		<article class="text-justify max-lg:text-sm text-white lg:text-black/80 leading-7">
			<?= $about['content'] ?? ''; ?>
		</article>
		<a class="bg-primary text-white px-5 py-4 lg:py-2 rounded-sm xl:w-fit text-center hover:border-s-8 border-icon hover:pr-7 ease-in-out transition-all origin-right" href="<?= $about['button']['url'] ?? ''; ?>">
			<?= $about['button']['title'] ?? ''; ?>
		</a>
	</div>
	<div :class="scrollingDown ? 'lg:scale-95' : (scrollingUp ? '' : '')"
		 class="lg:basis-3/5 2xl:basis-2/5 basis-full lg:p-3 border border-black/10 shadow-md max-lg:absolute max-lg:brightness-80 bg-white h-full inset-0 z-[0] bg-cover bg-left transition-all duration-700">
		<img class="size-full object-cover" src="<?= $about['image']['url'] ?? ''; ?>" alt="<?= $about['image']['title'] ?? ''; ?>">
	</div>
</section>
