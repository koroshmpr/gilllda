<?php
// 1. Create the Shortcode for the Frontend
function render_inline_ad_shortcode($atts)
{
    $a = shortcode_atts(array(
        'link' => '#',
        'image' => '',
        'text' => 'مشاهده' // Default text
    ), $atts);

    ob_start();
    ?>
    <div class="not-prose relative my-5 w-full h-24 lg:h-32 rounded-xl overflow-hidden p-1 text-secondary drop-shadow-sm group">
        <!-- Spinning border animation (inherits your 'secondary' color automatically) -->
        <div class="absolute top-1/2 left-1/2 w-[200%] h-[200%] -translate-x-1/2 -translate-y-1/2 animate-[spin_2.5s_linear_infinite] bg-[conic-gradient(transparent_270deg,currentColor)]"></div>

        <!-- Inner container (masks the center, leaving a 4px animated border) -->
        <div class="relative w-full h-full bg-neutral-50 rounded-xl flex items-center justify-end overflow-hidden">
            <?php if ($a['image']): ?>
                <!-- Alt tag dynamically uses the button text for better accessibility and SEO -->
                <img src="<?= esc_url($a['image']); ?>" alt="<?= esc_attr($a['text'] ? $a['text'] : 'تبلیغ'); ?>" class="absolute inset-0 w-full h-full object-cover">
            <?php endif; ?>

            <a href="<?= esc_url($a['link']); ?>" target="_blank" rel="noopener nofollow"
               class="absolute w-fit max-lg:py-3 p-4 end-5 bg-secondary min-w-28 lg:min-w-32 text-white text-sm text-center rounded-xl font-semibold hover:brightness-125 hover:scale-105 transition-all duration-300 shadow-md">
                <?= esc_html($a['text']); ?>
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('inline_ad', 'render_inline_ad_shortcode');

// 2. Register the TinyMCE Button and Media Scripts
function add_inline_ad_tinymce_button()
{
    // Only run if user has permissions and rich editing is enabled
    if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) return;
    if (get_user_option('rich_editing') == 'true') {

        // Force WordPress to load the Media Uploader scripts
        wp_enqueue_media();

        add_filter('mce_external_plugins', 'load_inline_ad_tinymce_plugin');
        add_filter('mce_buttons', 'register_inline_ad_tinymce_button');
    }
}

add_action('admin_head', 'add_inline_ad_tinymce_button');

// Add the button to the array of editor buttons
function register_inline_ad_tinymce_button($buttons)
{
    array_push($buttons, 'inline_ad_button');
    return $buttons;
}

// Point to the JavaScript file
function load_inline_ad_tinymce_plugin($plugin_array)
{
    $plugin_array['inline_ad_button'] = get_template_directory_uri() . '/js/inline-ad-modal.js';
    return $plugin_array;
}