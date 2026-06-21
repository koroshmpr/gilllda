<?php
/**
 * Sticky WooCommerce notices for single product page
 */

$notices = wc_get_notices(); // ['error'=>[], 'success'=>[], 'notice'=>[]]

if ( empty( $notices['error'] ) && empty( $notices['success'] ) && empty( $notices['notice'] ) ) {
	return; // Nothing to show
}

/**
 * Recursively flatten an array to string
 */
function flatten_notice( $notice ) {
	if ( is_array( $notice ) ) {
		$notice = array_map( 'flatten_notice', $notice );
		return implode( ' ', $notice );
	}
	return (string) $notice;
}
?>

<div x-data="{ close: false }"
	 x-show="!close"
	 x-transition
	 class="fixed max-lg:top-12 lg:bottom-8 max-lg:inset-x-0 lg:end-8 w-full lg:w-96 max-w-xs z-50">

	<div class="bg-gray-50 border border-gray-300 flex flex-col gap-2 rounded-md shadow-lg pt-5 p-2 relative">

		<!-- Close button -->
		<button @click="close = true"
				class="absolute -top-2 start-2 lg:-start-2 p-1 cursor-pointer rounded-md bg-gray-100 border border-gray-200 hover:bg-gray-200 transition-all">
			<?php get_template_part('template-parts/svg/close', null, [
				'class' => 'text-gray-700',
				'size'  => '14'
			]); ?>
		</button>

		<!-- Loop through notices -->
		<?php foreach ( $notices as $type => $list ) : ?>
			<?php foreach ( $list as $notice ) : ?>
				<div class=" px-2 py-3 rounded flex justify-between items-center text-xs [&>a]:!bg-primary [&>a]:!text-white
                    <?= $type === 'error' ? 'bg-red-100/50 text-red-700' : '' ?>
                    <?= $type === 'success' ? 'bg-green-100 text-green-700' : '' ?>
                    <?= $type === 'notice' ? 'bg-yellow-100 text-yellow-700' : '' ?>">
					<?php
					// Flatten recursively and print safely with HTML
					echo wp_kses_post( flatten_notice( $notice ) );
					?>
				</div>
			<?php endforeach; ?>
		<?php endforeach; ?>

	</div>
</div>

<?php
// Clear notices so they don't repeat
wc_clear_notices();
?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var initNoticeJQuery = function () {
            jQuery(function ($) {
                $('form.variations_form').on('change', 'input[type=radio]', function () {
                    $(this).trigger('check_variations');
                });
            });
        };
        if (window.jQuery) {
            initNoticeJQuery();
        } else {
            var checkJQuery = setInterval(function () {
                if (window.jQuery) {
                    clearInterval(checkJQuery);
                    initNoticeJQuery();
                }
            }, 50);
        }
    });
</script>