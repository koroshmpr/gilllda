<?php
$textClass = $args['textClass'] ?? '';
?>
<button aria-label="add <?= $args['id'] ?? ''; ?> to compare list" title="اضافه کردن برای مقایسه"
        class="border border-gray-800 relative text-sm flex items-center gap-3 rounded-lg cursor-pointer transition-all duration-500 <?= $args['class'] ?? '' ; ?>"
        :class="compared ? '!bg-gray-800 text-white' : 'text-gray-800'"
        x-data="{
								id: <?= $args['id'] ?? '' ; ?>,
								compared: false,
								alert: false,

								init() {
									const items = JSON.parse(localStorage.getItem('compare_products')) || [];
									this.compared = items.some(item => item.id === this.id);
								},

								toggleCompare() {
									let items = JSON.parse(localStorage.getItem('compare_products')) || [];

									if (this.compared) {
										// Remove
										items = items.filter(item => item.id !== this.id);
										this.compared = false;
									} else {
										// Add
										items.push({ id: this.id });
										this.compared = true;
									}

									localStorage.setItem('compare_products', JSON.stringify(items));

									this.alert = true;
									setTimeout(() => this.alert = false, 1000);
								}
							}"
        @click="toggleCompare()"
>
    <?php
    $args = array(
        'size' => '15',
        'class' => ''
    );
    get_template_part('template-parts/svg/compare', null, $args);
    ?>
    <span class="<?= $textClass;?> max-lg:hidden text-xs" x-text="compared ? 'حذف از مقایسه' : 'افزودن به مقایسه'"> افزودن به مقایسه</span>

    <span
            x-cloak
            x-show="alert"
            class="absolute px-5 py-1 text-[10px] delay-200 text-nowrap text-black top-0 start-0 lg:start-1/2 translate-x-1/2 -translate-y-8 border border-black/10 bg-white"
    >
		<span x-text="compared ? 'اضافه شد!' : 'حذف شد'"></span>
	</span>
</button>
