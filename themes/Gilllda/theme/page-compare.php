<?php
/**
 * Template Name: Compare Products
 */
get_header();
$trClass = 'flex flex-col w-fit shrink-0 min-w-20';
$thClass = 'border-b border-l border-black/5 px-1 text-base py-3 bg-primary/5 flex items-center justify-center font-thin text-black/50';
$tdClass = 'border-b border-l border-black/5 py-3 px-2 font-thin flex items-center justify-center';
?>

<section class="container max-lg:px-0 flex flex-col lg:gap-3 mb-4"
		 x-data="comparePage()"
		 x-init="load()">
	<header class="lg:col-span-12 max-lg:px-3 max-lg:pt-3 border-b border-black/10">
		<h1 class="text-black text-3xl pb-2 border-b-2 border-primary w-fit"><?php the_title(); ?></h1>
	</header>
	<!-- Loader -->
	<div
		x-show="loading"
		x-cloak
		class="flex justify-center items-center h-[46vh] w-full"
	>
		<div class="flex flex-col items-center gap-4">
			<div class="size-10 border-2 border-black/10 border-t-black rounded-full animate-spin"></div>
			<p class="text-gray-500">در حال بارگذاری...</p>
		</div>
	</div>

	<template x-if="!loading && products.length === 0">
		<div class="flex justify-center my-32 w-full flex-col h-[30vh] items-center gap-y-5">
            <?php
            $args = array(
                'size' => 150,
                'class' => 'text-gray-100',
            );
            get_template_part('template-parts/svg/compare', null, $args);
            ?>
			<p class="text-gray-500 text-2xl">محصولی در لیست وجود ندارد</p>
			<a aria-label="go to shop page" class="bg-icon/60 border border-icon hover:bg-icon hover:px-14 px-12 py-3 rounded-lg hover:font-bold hover:rounded-xl transition-all duration-300" href="<?= get_permalink( wc_get_page_id( 'shop' ) ); ?>">اضافه کنید</a>
		</div>
	</template>

	<table class="flex flex-wrap border my-6 border-black/5 w-full overflow-hidden"
		   x-show="!loading && products.length"
		   x-cloak>
		<thead>
		<tr class="<?= $trClass; ?>">
			<th class="<?= $thClass; ?>">-</th>
			<th class="<?= $thClass; ?> h-56">عکس</th>
			<th class="<?= $thClass; ?>">نام</th>
			<th class="<?= $thClass; ?>">وزن <span class="text-[8px] mt-2 ms-1 opacity-75">(کیلوگرم)</span></th>
			<th class="<?= $thClass; ?>">ابعاد</th>
			<th class="<?= $thClass; ?>">قیمت</th>
			<th class="<?= $thClass; ?>">لینک</th>
		</tr>
		</thead>
		<tbody class="flex-1 gap-1 flex flex-nowrap overflow-x-scroll">
		<template x-for="product in products" :key="product.id">
			<tr class="<?= $trClass; ?> bg-primary text-white">
				<td :id="product.id" class="<?= $tdClass; ?> !py-0 !px-0">
					<button
						class=" text-md size-full flex justify-center items-center hover:bg-red-700 transition-all group p-4 cursor-pointer"
						@click="remove(product.id)">
						<?php
						$args = array(
							'size' => '15',
							'class' => 'rotate-180 group-hover:scale-125 transition-all'
						);
						get_template_part('template-parts/svg/close',null,$args); ?>
					</button>
				</td>
				<td class="<?= $tdClass; ?> !py-0 !px-0">
					<img
						:src="product.image"
						:alt="product.name"
						class="w-56 aspect-square mx-auto object-cover"/>
				</td>
				<td class="<?= $tdClass; ?>"><p x-text="product.name"></p></td>
				<td class="<?= $tdClass; ?>" x-text="product.weight || '-'"></td>
				<td class="<?= $tdClass; ?>" x-html="product.dims || '-'"></td>
				<td class="<?= $tdClass; ?>">
					<p class="text-gray-300 flex text-xs flex-col leading-3" x-html="product.price"></p>
				</td>

				<td class="<?= $tdClass; ?> bg-primary/20 hover:brightness-125 transition-all">
					<a class="size-full flex justify-center group gap-x-1 text-sm p-0.5 text-center" :href="product.url">مشاهده
						<?php
						$args = array(
							'size' => '15',
							'class' => 'rotate-180 group-hover:-translate-x-1 transition-all'
						);
						get_template_part('template-parts/svg/chevron-right',null,$args); ?>
					</a>
				</td>
			</tr>
		</template>
		<tr class="<?= $trClass; ?> flex-1">
			<td class="<?= $tdClass; ?> h-12 !py-6"></td>
			<td class="<?= $tdClass; ?> h-56"></td>
			<td class="<?= $tdClass; ?> h-12 !py-6"></td>
			<td class="<?= $tdClass; ?> h-12 !py-6"></td>
			<td class="<?= $tdClass; ?> h-12 !py-6"></td>
			<td class="<?= $tdClass; ?> h-12 !py-6"></td>
			<td class="<?= $tdClass; ?> h-12 !py-6"></td>
		</tr>
		</tbody>
	</table>
</section>

<script>
	function comparePage() {
		return {
			products: [],
			loading: true,

			load() {
				const stored = JSON.parse(localStorage.getItem('compare_products')) || [];
				const ids = stored.map(p => p.id);

				if (!ids.length) {
					this.loading = false;
					this.products = [];
					return;
				}

				fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({
						action: 'get_compare_products',
						ids: ids
					})
				})
					.then(res => res.json())
					.then(data => {
						this.products = data;
					})
					.catch(() => {
						this.products = [];
					})
					.finally(() => {
						this.loading = false;
					});
			},

			remove(id) {
				this.products = this.products.filter(p => p.id !== id);

				const stored = JSON.parse(localStorage.getItem('compare_products')) || [];
				const updated = stored.filter(p => p.id !== id);

				localStorage.setItem('compare_products', JSON.stringify(updated));
			}
		}
	}
</script>



<?php get_footer(); ?>
