<?php
/*
Plugin Name: Custom Table of Contents
Description: Automatically add semantic IDs to headings and generate a customizable table of contents shortcode.
Version: 1.1
Author: korosh mpr
*/
// Automatically add semantic IDs to headings

function auto_id_headings($content)
{
    // Match only h2 and h3 tags
    $content = preg_replace_callback('/(<h[2-3](.*?))>(.*?)(<\/h[2-3]>)/i', function ($matches) {
        $heading_text = strip_tags($matches[3]);
        $semantic_id = sanitize_title_with_dashes($heading_text); // Creates a URL-friendly ID
        $q = "'";

        if (!stripos($matches[0], 'id=')) {
            return $matches[1] . $matches[2] . ' x-intersect:enter.margin.-15%.0px.-70%.0px="active = '. $q . $semantic_id . $q. '"  id="' . $semantic_id . '">' . $matches[3] . $matches[4];
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
    echo '<ul class="list-none space-y-1 border-s-2 border-black/5">';
    foreach ($headings as $heading) : ?>
        <li class="border-s-2 transition-all duration-300 border-transparent ps-4 -ms-0.5" :class="active === '<?= esc_attr($heading['id']); ?>' ? '!border-primary' : 'border-transparent'">
            <button aria-label="link to <?= esc_attr($heading['name']); ?>"
                    class="<?= $textColor; ?> w-full text-start flex justify-start py-0.5 max-w-[90%] lg:text-sm text-xs cursor-pointer transition-all duration-300"
                    :class="active === '<?= esc_attr($heading['id']); ?>' ? 'text-primary font-bold' : 'text-gray-400 hover:text-gray-600'"
                    @click.prevent="document.getElementById('<?= esc_attr($heading['id']); ?>').scrollIntoView({ behavior: 'smooth' }); toc = false">
                <?= esc_html($heading['name']); ?>
            </button>
        </li>
    <?php endforeach;
    echo "</ul>";

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
