jQuery(function($) {
    var $form = $('form.variations_form');
    var $priceContainer = $form.find('.woocommerce-variation-price');

    // Parse URL search params
    var urlParams = new URLSearchParams(window.location.search);

    // Pre-select radios based on URL params
    $form.find('input.variation-input').each(function() {
        var $input = $(this);
        var attrName = $input.data('attribute_name'); // e.g. attribute_pa_color
        var paramValue = urlParams.get(attrName);

        if (paramValue && $input.val() === paramValue) {
            $input.prop('checked', true);
        }
    });

    // Function to update variation price and variation_id input
    function updateVariation() {
        var selectedAttributes = {};

        $form.find('input.variation-input:checked').each(function () {
            var attrName = $(this).data('attribute_name');
            var attrValue = $(this).val();
            selectedAttributes[attrName] = attrValue;
        });

        var variations = $form.data('product_variations');

        if (!variations) {
            $priceContainer.hide();
            return;
        }

        var match = variations.find(function (variation) {
            for (var key in selectedAttributes) {
                if (variation.attributes[key] !== selectedAttributes[key]) {
                    return false;
                }
            }
            return true;
        });

        if (match) {
            if (match.price_html) {
                $priceContainer.html(match.price_html).fadeIn();
            } else {
                $priceContainer.hide();
            }
            $form.find('input.variation_id').val(match.variation_id).trigger('change');
        } else {
            $priceContainer.hide();
            $form.find('input.variation_id').val(0).trigger('change');
        }
    }

    // Bind change event to inputs
    $form.on('change', 'input[type=radio]', function () {
        updateVariation();
    });

    // Trigger update on page load if any attribute is preselected
    if ($form.find('input.variation-input:checked').length) {
        updateVariation();
    }
});