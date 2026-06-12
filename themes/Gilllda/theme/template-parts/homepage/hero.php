<?php
$hero = get_field('hero');
$image = $hero['image'];
?>
<section class="relative overflow-hidden lg:h-[85vh] h-[55vh] lg:pb-16">
	<div class="absolute bottom-0 inset-x-0 h-1/3 lg:h-2/3 bg-gradient-to-t from-white from-20% via-white/30 via-40% z-1"></div>
	<div class="container max-lg:w-full h-full relative">
        <picture>
            <source media="(min-width: 961px)" srcset="<?= $image['desktop']['url'] ?? ''; ?>">
            <source media="(max-width: 960px)" srcset="<?= $image['mobile']['url'] ?? $image['desktop']['url'] ?? ''; ?>">
            <img width="390" height="374" fetchpriority="high" loading="eager"
                 :class="scrollingDown ? 'scale-95' : (scrollingUp ? 'lg:scale-105 lg:mt-5' : '')"
                 class="absolute inset-0 w-full lg:h-[80vh] select-none h-[55vh] transition-transform border border-y-0 border-black/5 lg:px-5 pb-0 duration-700 object-cover"
                 src="<?= $image['desktop']['url'] ?? ''; ?>" alt="<?= $image['desktop']['title'] ?? ''; ?>">
        </picture>
		<div :class="scrollingDown ? 'lg:-translate-y-24 -translate-y-8 scale-85' : (scrollingUp ? 'scale-105' : '')"
			 class="absolute duration-700 text-white transition-all bottom-20 lg:bottom-0 z-[2] w-full inset-x-0 flex flex-col items-center">
			<div class="flex ltr items-center text-nowrap">
				<p class="lg:text-9xl !leading-0 tracking-[-5px] lg:tracking-[-10px] me-2 lg:me-5 stroke-3 text-secondary font-bold stroke-white text-6xl"><?= $hero['title'] ?? ''; ?></p>
				<p class="!leading-0 lg:text-5xl font-thin pb-2 lg:pb-4 text-xl text-primary/50 stroke-white border-b border-white"><?= $hero['adjectives'] ?? ''; ?></p>
			</div>
			<h1 class="lg:text-4xl bg-gradient-to-t from-primary/60 border border-t-0 p-3 pb-1 border-white/50 xl:me-32 text-2xl"><?= $hero['subtitle'] ?? ''; ?></h1>
			<a href="<?= $hero['button']['url'] ?? ''; ?>" aria-label="link to <?= $hero['button']['title'] ?? ''; ?>"
			   class="px-5 font-bold flex gap-2 items-center group/add transition-all ease-in-out mt-10 lg:mt-4 bg-secondary rounded-sm lg:-mb-10 hover:brightness-125 hover:rounded-sm hover:ring-1 border shadow-lg backdrop-blur-sm ring-white duration-700 hover:scale-110 py-3"
			   :class="scrollingDown ? 'translate-y-12 px-24 py-4 lg:py-8 text-2xl' : (scrollingUp ? '' : '')">
				<?php
				$args = array(
					'size' => '28',
					'class' => 'group-hover/add:delay-100 pb-1 text-white duration-700 rotate-45 group-hover/add:rotate-0 translate-x-2 opacity-0 transition-all group-hover/add:opacity-100 group-hover/add:translate-x-0'
				);
				get_template_part('template-parts/svg/shop',null,$args);
				?>
				<span class="group-hover/add:-translate-x-0 transition-all duration-700 translate-x-4"><?= $hero['button']['title'] ?? ''; ?></span>
			</a>
		</div>
	</div>
</section>
