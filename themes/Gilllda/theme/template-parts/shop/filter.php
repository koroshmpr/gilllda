<?php
$weight_unit = get_option('woocommerce_weight_unit');
$category  = get_terms('product_cat', array('hide_empty' => true));
// Remove the "uncategorized" term if it exists
if (!empty($category) && !is_wp_error($category)) {
    $categories = array_filter($category, function($category) {
        return $category->slug !== 'uncategorized';  // exclude the default category slug
    });
}

/**
 * Reusable Filter Content
 * We use a variable to store the shared UI pieces (Categories and Sliders)
 */
$current_term_title = get_queried_object()->name;
ob_start(); ?>
<div class="mb-2">
	<h4 class="text-sm lg:text-md font-black text-gray-700 mt-0 mb-4">دسته‌بندی‌ها</h4>
	<div class="flex flex-wrap gap-2">
		<?php foreach($categories as $cat): ?>
			<button
				type="button"
				@click="toggleCategory('<?= $cat->slug ?>')"
				:class="filters.category.includes('<?= $cat->slug ?>') ? 'bg-primary text-white border-primary shadow-md shadow-primary/20' : 'bg-gray-50 text-gray-500 border-gray-200 hover:bg-primary/10 hover:border-primary/30'"
				class="py-3 px-2 flex-1 cursor-pointer text-nowrap rounded-xl border text-[11px] lg:text-xs font-bold transition-all <?= $current_term_title == $cat->name ? '!bg-primary text-white border-primary shadow-md shadow-primary/20' : ''; ;?>">
				<?= $cat->name ?>
			</button>
		<?php endforeach; ?>
	</div>
</div>

<div class="space-y-3">
	<div class="border-t border-gray-200 pt-2">
		<div class="flex justify-between items-center mb-2">
			<h4 class="text-sm font-black my-0 text-gray-700">حداقل وزن</h4>
			<span class="text-[10px] font-black px-2 py-1 bg-primary/5 rounded-lg text-primary" x-text="filters.min_weight + ' <?= $weight_unit ?>'"></span>
		</div>
		<input aria-label="change the weight filter" type="range" min="0" max="50" step="0.5" x-model="filters.min_weight" class="w-full h-1.5 bg-gray-200 hover:bg-gray-300 transition-all rounded-lg appearance-none cursor-pointer accent-primary">
	</div>

	<div class="border-t border-gray-200 pt-2">
		<div class="flex justify-between items-center mb-2">
			<h4 class="text-sm font-black text-gray-700">حداکثر قیمت</h4>
			<span class="text-[10px] font-black text-primary" x-text="formatPrice(filters.max_price)"></span>
		</div>
		<input aria-label="change the price filter"  type="range" min="0" max="10000000" step="100000" x-model="filters.max_price" class="w-full h-1.5 bg-gray-200 hover:bg-gray-300 transition-all rounded-lg appearance-none cursor-pointer accent-primary">
	</div>
</div>
<?php
$shared_filter_html = ob_get_clean();
?>

<aside
	x-data="productFilter"
	x-init="initFilters()"
	@open-filter.window="open = true"
	class="relative rtl" dir="rtl">

	<div class="hidden lg:block w-full p-6 bg-gray-100 border border-gray-200 rounded-lg sticky top-24">
		<div class="flex items-center justify-between border-b border-gray-200 mb-6">
			<span class="text-lg font-black text-gray-900 border-b-2 pb-3 border-primary leading-none">فیلترها</span>
			<button @click="resetFilters()" class="text-[10px] font-bold text-primary cursor-pointer hover:bg-primary py-1 px-2 transition-all rounded-lg hover:text-white">حذف همه</button>
		</div>

		<?= $shared_filter_html; ?>

		<button @click="applyFilters()" class="w-full py-4 cursor-pointer mt-8 bg-primary text-white rounded-2xl font-black shadow-lg shadow-primary/20 hover:brightness-125 hover:scale-[98%] transition-all">
			اعمال فیلترها
		</button>
	</div>

	<div x-show="open"
		 x-cloak
         @click.self="open = false"
		 class="fixed inset-0 z-[100] justify-end lg:hidden bg-black/50 backdrop-blur-sm flex flex-col" style="display: none;">
		<section
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-300 transform"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="h-fit relative bg-white">
            <div class="flex items-center justify-between px-5 h-16 border-b border-black/10 sticky top-0 z-10">
                <button @click="open = false" class="p-2 bg-gray-100 border border-gray-200 rounded-lg">
                    <?php
                    $args = array(
                        'size' => '18',
                        'class' => '',
                    );
                    get_template_part('template-parts/svg/close', null, $args);
                    ?>
                </button>
                <span class="border-b-2 mt-auto border-primary pb-4 text-base font-bold">فیلتر محصولات</span>
                <button @click="resetFilters()" class="text-xs font-bold text-primary">حذف همه</button>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <?= $shared_filter_html; ?>
            </div>

            <div class="p-2 sticky bottom-0 bg-white">
                <button @click="applyFilters()" class="w-full py-4 bg-primary text-white rounded-2xl font-black text-lg shadow-xl shadow-primary/20">مشاهده نتایج</button>
            </div>
        </section>
	</div>
</aside>

<script>
	document.addEventListener('alpine:init', () => {
		Alpine.data('productFilter', () => ({
			open: false,
			filters: { category: [], max_price: 10000000, min_weight: 0 },

			initFilters() {
				const params = new URLSearchParams(window.location.search);
				if (params.has('product_cat')) this.filters.category = params.get('product_cat').split(',');
				if (params.has('max_price')) this.filters.max_price = parseInt(params.get('max_price'));
				if (params.has('min_weight')) this.filters.min_weight = parseFloat(params.get('min_weight'));
			},

			toggleCategory(slug) {
				if (this.filters.category.includes(slug)) {
					this.filters.category = this.filters.category.filter(i => i !== slug);
				} else {
					this.filters.category.push(slug);
				}
			},

			formatPrice(val) { return new Intl.NumberFormat('fa-IR').format(val) + ' تومان'; },

			applyFilters() {
				const params = new URLSearchParams();
				if (this.filters.category.length > 0) params.set('product_cat', this.filters.category.join(','));
				if (this.filters.max_price < 10000000) params.set('max_price', this.filters.max_price);
				if (this.filters.min_weight > 0) params.set('min_weight', this.filters.min_weight);
				window.location.search = params.toString();
			},

			resetFilters() { window.location.href = window.location.pathname; }
		}))
	})
</script>
