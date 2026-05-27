
<?php
$color = 'black';
$borderColor = $color == 'black' ? 'border-gray-200' : 'border-white/15';
$bgColor = $color == 'black' ? 'bg-white' : 'bg-gray-700';
$textColor = $color == 'black' ? 'text-black/85' : 'text-white/80';
?>
<div x-data="{ open: true }" class="<?= $bgColor; ?> border <?= $borderColor; ?> p-5 w-full min-w-1/4 <?= $textColor; ?>  mx-auto rounded-lg">
	<button @click="open = !open" class="text-base flex justify-between items-center cursor-pointer w-full">
		<span>فهرست مطالب این مقاله</span>
		<svg :class="open ? '' : 'rotate-180'" width="16" height="16" fill="currentColor"
			 class="bi bi-chevron-up transition-all" viewBox="0 0 16 16">
			<path fill-rule="evenodd"
				  d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708z"/>
		</svg>
	</button>
	<div :class="open ? ' max-h-[70vh] lg:max-h-[20vh] mt-3 pt-3 border-t' : 'max-h-0'"
		 class="duration-[300ms] <?= $borderColor; ?> transition-all overflow-y-scroll">
		<?php echo do_shortcode('[TOC levels="2,3"]'); ?>
	</div>
</div>
