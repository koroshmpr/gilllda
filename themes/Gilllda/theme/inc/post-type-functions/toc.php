<?php
/*
Plugin Name: Custom Table of Contents
Description: Automatically add semantic IDs to headings and generate a customizable table of contents shortcode.
Version: 1.3
Author: korosh mpr
*/

// Automatically add semantic IDs to headings
function auto_id_headings($content)
{
    // Match only h2 and h3 tags
    $content = preg_replace_callback('/(<h[2-3](.*?))>(.*?)(<\/h[2-3]>)/i', function ($matches) {
        $heading_text = strip_tags($matches[3]);

        // 1. Sanitize the title (removes bad punctuation, replaces spaces with dashes)
        // 2. Urldecode it (converts %d8%a2 back to readable Persian characters)
        $semantic_id = urldecode(sanitize_title_with_dashes($heading_text));

        // Fallback: If for some reason the ID becomes empty, create a random one
        if (empty($semantic_id)) {
            $semantic_id = 'heading-' . wp_generate_password(6, false);
        }

        $q = "'";

        if (!stripos($matches[0], 'id=')) {
            return $matches[1] . $matches[2] . ' x-intersect:enter.margin.-15%.0px.-70%.0px="active = '. $q . esc_js($semantic_id) . $q. '"  id="' . esc_attr($semantic_id) . '">' . $matches[3] . $matches[4];
        }
        return $matches[0];
    }, $content);

    return $content;
}

add_filter('the_content', 'auto_id_headings');

// Function to extract specific heading levels
function get_headings($content, $levels = [2])
{
    $headings = [];
    $tag_pattern = implode('|', array_map('intval', $levels));

    preg_match_all("/<h($tag_pattern)([^<]*)>(.*)<\/h[$tag_pattern]>/i", $content, $matches);

    for ($i = 0; $i < count($matches[1]); $i++) {
        $headings[$i]["tag"] = intval($matches[1][$i]);
        $att_string = $matches[2][$i];

        preg_match("/id=\"([^\"]*)\"/", $att_string, $id_matches);
        $headings[$i]["id"] = $id_matches[1] ?? '';
        $headings[$i]["name"] = strip_tags($matches[3][$i]);
    }

    return $headings;
}
// Generate the TOC
function get_toc($content, $levels = [2])
{
    $headings = get_headings($content, $levels);
    $color = 'black';
    $textColor = $color == 'black' ? 'text-black/60' : 'text-white/60';
    if (empty($headings)) return '';

    ob_start();
    ?>
    <ul class="list-none space-y-1 border-s-2 border-black/5"
        x-effect="if (active) {$nextTick(() => {
                    // 1. Use $el to only search inside THIS specific <ul> instance
                    let activeLi = null;const items = $el.querySelectorAll('li[data-toc-target]');
                    // 2. Loop through items to safely match URL-encoded Persian characters
                    for (let i = 0; i < items.length; i++) {
                        if (items[i].getAttribute('data-toc-target') === active) {
                            activeLi = items[i];
                            break;
                        }
                    }
                    if (activeLi) {
                        // Find the wrapper div that actually has the scrollbar
                        const scrollContainer = activeLi.closest('.overflow-y-scroll') || activeLi.closest('div');
                        if (scrollContainer) {
                            // Calculate precise positions regardless of DOM structure
                            const containerRect = scrollContainer.getBoundingClientRect();
                            const liRect = activeLi.getBoundingClientRect();
                            // Math to find the exact center offset
                            const scrollAmount = (liRect.top - containerRect.top) - (scrollContainer.clientHeight / 2) + (liRect.height / 2);
                            // Scroll the parent container smoothly
                            scrollContainer.scrollBy({ top: scrollAmount, behavior: 'smooth' });
                        }
                    }
                });
            }
        ">

        <?php foreach ($headings as $heading) : ?>
            <li data-toc-target="<?= esc_attr($heading['id']); ?>"
                class="border-s-2 transition-all duration-300 ps-4 -ms-0.5"
                :class="active === '<?= esc_attr($heading['id']); ?>' ? '!border-primary' : 'border-transparent'">

                <button aria-label="link to <?= esc_attr($heading['name']); ?>"
                        class="<?= $textColor; ?> w-full text-start flex justify-start py-0.5 max-w-[90%] lg:text-sm text-xs cursor-pointer transition-all duration-300"
                        :class="active === '<?= esc_attr($heading['id']); ?>' ? 'text-primary font-bold' : 'text-gray-400 hover:text-gray-600'"
                        @click.prevent="document.getElementById('<?= esc_attr($heading['id']); ?>').scrollIntoView({ behavior: 'smooth' }); toc = false">
                    <?= esc_html($heading['name']); ?>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    return ob_get_clean();
}

// TOC Shortcode with custom levels
function toc_shortcode($atts)
{
    $atts = shortcode_atts(['levels' => '2'], $atts);
    $levels = array_map('intval', explode(',', $atts['levels']));

    return get_toc(auto_id_headings(get_the_content()), $levels);
}

add_shortcode('TOC', 'toc_shortcode');