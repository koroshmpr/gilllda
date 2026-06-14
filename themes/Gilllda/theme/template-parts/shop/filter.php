<?php
$weight_unit = get_option('woocommerce_weight_unit');
$category    = get_terms('product_cat', array('hide_empty' => true));

// Remove the "uncategorized" term if it exists
if (!empty($category) && !is_wp_error($category)) {
    $categories = array_filter($category, function($cat) {
        return $cat->slug !== 'uncategorized';
    });
} else {
    $categories = [];
}

// Safely check if we are on a category page
$queried_object = get_queried_object();
$current_term_title = ($queried_object instanceof WP_Term) ? $queried_object->name : '';

// Fetch all WooCommerce Attributes dynamically (Color, Size, etc.)
$available_attributes = [];
if (function_exists('wc_get_attribute_taxonomies')) {
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    if ($attribute_taxonomies) {
        foreach ($attribute_taxonomies as $tax) {
            $taxonomy_name = wc_attribute_taxonomy_name($tax->attribute_name); // This returns 'pa_color'
            $terms = get_terms(array(
                'taxonomy'   => $taxonomy_name,
                'hide_empty' => true,
            ));
            if (!empty($terms) && !is_wp_error($terms)) {
                $available_attributes[] = array(
                    'name'     => $tax->attribute_label,
                    'taxonomy' => $taxonomy_name,       // e.g., 'pa_color'
                    'terms'    => $terms,
                );
            }
        }
    }
}

/**
 * Reusable Filter Content
 */
ob_start(); ?>
<div class="mb-2">
    <p class="text-sm lg:text-md font-black text-gray-700 mt-0 mb-4">دسته‌بندی‌ها</p>
    <div class="flex flex-wrap gap-2">
        <?php foreach($categories as $cat): ?>
            <button
                    type="button"
                    @click="toggleCategory('<?= esc_js($cat->slug) ?>')"
                    :class="filters.category.includes('<?= esc_js($cat->slug) ?>') ? 'bg-primary text-white border-primary shadow-md shadow-primary/20' : 'bg-gray-50 text-gray-500 border-gray-200 hover:bg-primary/10 hover:border-primary/30'"
                    class="py-3 px-2 flex-1 cursor-pointer text-nowrap rounded-xl border text-[11px] lg:text-xs font-bold transition-all <?= ($current_term_title === $cat->name) ? '!bg-primary text-white border-primary shadow-md shadow-primary/20' : ''; ?>">
                <?= esc_html($cat->name); ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<div class="space-y-4 mt-6">

    <!-- Dynamic WooCommerce Attributes (Color, Size, etc.) -->
    <?php foreach ($available_attributes as $attr): ?>
        <div class="border-t border-gray-200 pt-4">
            <p class="text-sm font-black text-gray-700 m-0 mb-3"><?= esc_html($attr['name']) ?></p>
            <div class="flex flex-wrap gap-2">
                <?php foreach($attr['terms'] as $term): ?>
                    <button
                            type="button"
                            @click="toggleAttribute('<?= esc_js($attr['taxonomy']) ?>', '<?= esc_js($term->slug) ?>')"
                            :class="filters.attributes['<?= esc_js($attr['taxonomy']) ?>'].includes('<?= esc_js($term->slug) ?>') ? 'bg-primary text-white border-primary shadow-md shadow-primary/20' : 'bg-gray-50 text-gray-500 border-gray-200 hover:bg-primary/10 hover:border-primary/30'"
                            class="py-2 px-3 flex-1 lg:flex-none cursor-pointer text-nowrap rounded-xl border text-[11px] font-bold transition-all text-center">
                        <?= esc_html($term->name); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- In Stock Toggle -->
    <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
        <p class="text-sm font-black text-gray-700 m-0">فقط کالاهای موجود</p>
        <button type="button"
                aria-label="add filter to show in stock products"
                @click="filters.in_stock = !filters.in_stock"
                :class="filters.in_stock ? 'bg-primary' : 'bg-gray-200'"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
            <span :class="filters.in_stock ? '-translate-x-5' : 'translate-x-0'"
                  class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
        </button>
    </div>

    <!-- On Sale Toggle -->
    <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
        <p class="text-sm font-black text-gray-700 m-0">فقط کالاهای تخفیف‌دار</p>
        <button type="button"
                aria-label="add filter to show on sale products"
                @click="filters.on_sale = !filters.on_sale"
                :class="filters.on_sale ? 'bg-primary' : 'bg-gray-200'"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
            <span :class="filters.on_sale ? '-translate-x-5' : 'translate-x-0'"
                  class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
        </button>
    </div>

    <!-- Minimum Weight -->
    <div class="border-t border-gray-200 pt-4">
        <div class="flex justify-between items-center mb-3">
            <p class="text-sm font-black my-0 text-gray-700">حداقل وزن</p>
            <span class="text-[10px] font-black px-2 py-1 bg-primary/5 rounded-lg text-primary" x-text="filters.min_weight + ' <?= esc_js($weight_unit) ?>'"></span>
        </div>
        <input aria-label="change the weight filter" type="range" min="0" max="50" step="0.5" x-model="filters.min_weight" class="w-full h-1.5 bg-gray-200 hover:bg-gray-300 transition-all rounded-lg appearance-none cursor-pointer accent-primary">
    </div>

    <!-- Maximum Price -->
    <div class="border-t border-gray-200 pt-4">
        <div class="flex justify-between items-center mb-3">
            <p class="text-sm font-black text-gray-700 m-0">حداکثر قیمت</p>
            <span class="text-[10px] font-black text-primary bg-primary/5 px-2 py-1 rounded-lg" x-text="formatPrice(filters.max_price)"></span>
        </div>
        <input aria-label="change the price filter" type="range" min="0" max="10000000" step="100000" x-model="filters.max_price" class="w-full h-1.5 bg-gray-200 hover:bg-gray-300 transition-all rounded-lg appearance-none cursor-pointer accent-primary">
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

    <!-- Desktop Sidebar -->
    <div class="hidden lg:block w-full p-6 bg-gray-100 border border-gray-200 rounded-2xl sticky top-24">
        <div class="flex items-center justify-between border-b border-gray-200 mb-6 pb-3">
            <span class="text-lg font-black text-gray-900 pb-2 border-b-2 border-primary leading-none translate-y-[13px]">فیلترها</span>
            <button @click="resetFilters()" class="text-xs font-bold text-gray-500 cursor-pointer hover:bg-red-50 hover:text-red-500 py-1.5 px-3 transition-all rounded-lg">حذف همه</button>
        </div>

        <?= $shared_filter_html; ?>

        <button @click="applyFilters()" class="w-full py-3 cursor-pointer mt-8 bg-primary text-white rounded-xl font-black shadow-lg shadow-primary/20 hover:brightness-110 hover:-translate-y-0.5 transition-all">
            اعمال فیلترها
        </button>
    </div>

    <!-- Mobile Drawer -->
    <div x-show="open"
         x-cloak
         @click.self="open = false"
         class="fixed inset-0 z-[100] justify-end lg:hidden bg-black/60 backdrop-blur-sm flex flex-col" style="display: none;">
        <section
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-300 transform"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="h-[85vh] mt-auto relative bg-white rounded-t-3xl overflow-hidden flex flex-col">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-white z-10">
                <span class="text-base font-black text-gray-800">فیلتر محصولات</span>
                <div class="flex gap-3">
                    <button @click="resetFilters()" class="text-xs font-bold text-gray-500 hover:text-red-500">حذف همه</button>
                    <button @click="open = false" class="p-1.5 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 transition-colors">
                        <?php get_template_part('template-parts/svg/close', null, ['size' => '18']); ?>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <?= $shared_filter_html; ?>
            </div>

            <div class="p-4 bg-white border-t border-gray-100 shadow-[0_-10px_20px_rgba(0,0,0,0.05)]">
                <button @click="applyFilters()" class="w-full py-3.5 bg-primary text-white rounded-xl font-black text-base shadow-lg shadow-primary/20">مشاهده نتایج</button>
            </div>
        </section>
    </div>
</aside>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productFilter', () => ({
            open: false,
            filters: {
                category: [],
                max_price: 10000000,
                min_weight: 0,
                in_stock: false,
                on_sale: false,
                // Setup empty arrays using the taxonomy name (e.g. 'pa_color')
                attributes: {
                    <?php foreach($available_attributes as $attr): ?>
                    '<?= esc_js($attr['taxonomy']) ?>': [],
                    <?php endforeach; ?>
                }
            },

            initFilters() {
                const params = new URLSearchParams(window.location.search);

                if (params.has('product_cat')) this.filters.category = params.get('product_cat').split(',');
                if (params.has('max_price')) this.filters.max_price = parseInt(params.get('max_price'));
                if (params.has('min_weight')) this.filters.min_weight = parseFloat(params.get('min_weight'));
                if (params.has('in_stock')) this.filters.in_stock = params.get('in_stock') === 'true';
                if (params.has('on_sale')) this.filters.on_sale = params.get('on_sale') === 'true';

                // Check URL for pa_ attributes (e.g., ?pa_color=red,blue)
                for (const [key, value] of params.entries()) {
                    if (key.startsWith('pa_')) {
                        if (this.filters.attributes[key] !== undefined) {
                            this.filters.attributes[key] = value.split(',');
                        }
                    }
                }
            },

            toggleCategory(slug) {
                if (this.filters.category.includes(slug)) {
                    this.filters.category = this.filters.category.filter(i => i !== slug);
                } else {
                    this.filters.category.push(slug);
                }
            },

            toggleAttribute(attrTax, termSlug) {
                if (this.filters.attributes[attrTax].includes(termSlug)) {
                    this.filters.attributes[attrTax] = this.filters.attributes[attrTax].filter(i => i !== termSlug);
                } else {
                    this.filters.attributes[attrTax].push(termSlug);
                }
            },

            formatPrice(val) {
                return new Intl.NumberFormat('fa-IR').format(val) + ' تومان';
            },

            applyFilters() {
                const params = new URLSearchParams(window.location.search);

                if (this.filters.category.length > 0) params.set('product_cat', this.filters.category.join(','));
                else params.delete('product_cat');

                if (this.filters.max_price < 10000000) params.set('max_price', this.filters.max_price); else params.delete('max_price');
                if (this.filters.min_weight > 0) params.set('min_weight', this.filters.min_weight); else params.delete('min_weight');
                if (this.filters.in_stock) params.set('in_stock', 'true'); else params.delete('in_stock');
                if (this.filters.on_sale) params.set('on_sale', 'true'); else params.delete('on_sale');

                // Clear old pa_ parameters
                const keysToDelete = [];
                for (const key of params.keys()) {
                    if (key.startsWith('pa_')) keysToDelete.push(key);
                }
                keysToDelete.forEach(k => params.delete(k));

                // Apply new pa_ attributes
                for (const [attrTax, terms] of Object.entries(this.filters.attributes)) {
                    if (terms.length > 0) {
                        params.set(attrTax, terms.join(','));
                    }
                }

                window.location.search = params.toString();
            },

            resetFilters() {
                window.location.href = window.location.pathname;
            }
        }))
    })
</script>