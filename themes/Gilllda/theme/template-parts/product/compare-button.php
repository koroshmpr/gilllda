<?php
/**
 * Compare toggle (product card + single product).
 *
 * @var array $args { id: int, class?: string, textClass?: string }
 *   textClass — classes for the text label (pass 'hidden' for an icon-only button).
 *
 * State lives in Alpine.store('compare') (javascript/compare.js); the markup is identical
 * for every visitor so it is safe in full-page cache.
 */
$gl_cmp_id = absint($args['id'] ?? 0);
if (!$gl_cmp_id) {
    return;
}
$gl_cmp_product = function_exists('wc_get_product') ? wc_get_product($gl_cmp_id) : null;
$gl_cmp_name    = $gl_cmp_product ? $gl_cmp_product->get_name() : get_the_title($gl_cmp_id);
$gl_cmp_img     = $gl_cmp_product && $gl_cmp_product->get_image_id() ? (string) wp_get_attachment_image_url($gl_cmp_product->get_image_id(), 'thumbnail') : '';
$gl_cmp_text    = $args['textClass'] ?? '';
$gl_cmp_data    = ['id' => $gl_cmp_id, 'name' => $gl_cmp_name, 'img' => $gl_cmp_img];
?>
<button type="button"
        x-data="glCompareBtn(<?php echo esc_attr(wp_json_encode($gl_cmp_data)); ?>)"
        @click.prevent.stop="toggle()"
        aria-pressed="false" :aria-pressed="on ? 'true' : 'false'"
        aria-label="<?php echo esc_attr('افزودن ' . $gl_cmp_name . ' به مقایسه'); ?>"
        :aria-label="(on ? 'حذف ' : 'افزودن ') + p.name + (on ? ' از مقایسه' : ' به مقایسه')"
        class="gl-cmp group/cmp relative inline-flex cursor-pointer items-center justify-center gap-2 rounded-full border border-gray-300 bg-white/80 text-sm text-gray-700 transition-all duration-300 after:absolute after:-inset-1.5 after:content-[''] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/50 <?php echo esc_attr($args['class'] ?? ''); ?>"
        :class="on ? '!bg-primary !text-white !border-primary !opacity-100 !translate-y-0' : ''">
    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path x-show="!on" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
        <path x-show="on" style="display:none" d="M4.5 12.75l6 6 9-13.5"/>
    </svg>
    <span class="<?php echo esc_attr($gl_cmp_text); ?> text-xs" x-text="on ? 'در لیست مقایسه' : 'افزودن به مقایسه'">افزودن به مقایسه</span>
    <span aria-hidden="true"
          class="pointer-events-none absolute end-0 top-full z-10 mt-2 whitespace-nowrap rounded-full bg-gray-900 px-3 py-1 text-[11px] font-medium text-white opacity-0 shadow-lg transition duration-200 max-lg:hidden group-hover/cmp:opacity-100 group-focus-visible/cmp:opacity-100"
          x-text="on ? 'حذف از مقایسه' : 'افزودن به مقایسه'">افزودن به مقایسه</span>
</button>
