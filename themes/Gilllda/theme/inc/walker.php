<?php
class Footer_Walker_Nav_Menu extends Walker_Nav_Menu
{
    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $classes = empty($item->classes) ? array() : (array)$item->classes;

        // Check if the menu item is the current page
        if (
            in_array('current_page_item', $classes) ||
            in_array('current_page_parent', $classes) ||
            in_array('current-page-ancestor', $classes)) {
            $classes[] = '!text-black before:scale-x-100'; // Add your custom active class
        }

        // Unique group class per menu item and depth, for scoped hover
        $group_class = 'group-depth' . $depth . '-item' . $item->ID;

        // Add different classes to <li> based on depth
        if ($depth === 0) {
            $classes[] = 'footer-menu-item depth-0 group/depth-0 before:absolute before:origin-right before:bg-secondary/50 before:h-0.5 before:w-full before:-bottom-1 before:duration-400 before:ease-in-out before:scale-x-0 hover:before:scale-100 before:transition-all';
        } elseif ($depth === 1) {
            $classes[] = 'footer-menu-item depth-1 border border-gray-100 group/depth-1 px-2 hover:bg-gray-50 hover:pl-1 transition-all';
        } elseif ($depth === 2) {
            $classes[] = 'footer-menu-item depth-2  group/depth-2';
        } else {
            $classes[] = 'footer-menu-item depth-' . $depth . ' ' . $group_class;
        }

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = ' class="' . esc_attr($class_names) . ' gap-1 flex items-center text-black/80 relative transition-all text-sm hover:text-black"';

        $output .= '<li' . $class_names . '>';

        // Prepare <a> classes based on depth
        $a_classes = 'transition ';
        if ($depth === 0) {
            $a_classes .= 'footer-link depth-0 text-base';
        } elseif ($depth === 1) {
            $a_classes .= 'footer-link depth-1 text-sm font-medium w-full p-2';
        } elseif ($depth === 2) {
            $a_classes .= 'footer-link depth-2 text-sm pl-6';
        } else {
            $a_classes .= 'footer-link depth-' . $depth;
        }

        $atts = array();
        $atts['href'] = !empty($item->url) ? $item->url : '';
        $atts['class'] = $a_classes;

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $attributes .= ' ' . $attr . '="' . esc_attr($value) . '"';
            }
        }

        $title = apply_filters('the_title', $item->title, $item->ID);
        $output .= '<a' . $attributes . '>' . $title . '</a>';
    }

    function start_lvl(&$output, $depth = 0, $args = null)
    {
        global $wp_query;

        $svg_icon_down = '<svg width="12" height="12" fill="currentColor" class="-rotate-90 transition-all inline-block group-hover/depth-0:rotate-90" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                    </svg>';
        $svg_icon = '<svg width="12" height="12" fill="currentColor" class="transition-all inline-block" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                    </svg>';

        if ($depth === 0) {
            $output .= $svg_icon_down;
            $output .= '<ul class="hidden group-hover/depth-0:flex absolute z-50 top-full right-0 pt-4 min-w-[200px] bg-gradient-to-t from-white from-90% border border-t-transparent border-gray-200 rounded-t-none rounded-md p-2 gap-1 flex-col">';
        } elseif ($depth === 1) {
            // Depth 2 submenu (opens to the right)
            $output .= $svg_icon;
            $output .= '<ul class="hidden group-hover/depth-1:flex absolute z-50 top-0 right-full ms-0.5 min-w-[200px] bg-white border border-gray-200 border-s-transparent rounded-l-md p-2 overflow-hidden gap-1 flex-col">';
        } else {
            // Depth 3+ fallback (opens to right)
            $output .= $svg_icon;
            $output .= '<ul class="hidden absolute z-50 top-0 left-full pl-4 min-w-[200px] bg-white border border-gray-200 rounded-md p-4 overflow-hidden shadow-lg gap-3 flex-col">';
        }
    }

    function end_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '</ul>';
    }
}
class Mobile_Walker_Nav_Menu extends Walker_Nav_Menu
{
    // Store current parent item ID so we can use it in start_lvl()
    private $current_parent_id = 0;

    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $classes = empty($item->classes) ? array() : (array)$item->classes;

        // Check if the menu item is the current page
        if (in_array('current_page_item', $classes) ||
            in_array('current_page_parent', $classes) ||
            in_array('current-page-ancestor', $classes)) {
            $classes[] = '!bg-primary/70 border-gray-300 text-white'; // Add your custom active class
        }

        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = 'text-black/80 bg-white border rounded-sm flex flex-col flex-1 border border-gray-200 ' . esc_attr($class_names) . ' text-sm hover:text-black';

        // Check if the item has children (to show accordion toggle)
        $has_children = !empty($args->walker->has_children) && $args->walker->has_children;

        // Unique Alpine.js state key per item ID
        $alpine_key = 'open' . $item->ID;

        // Store the current item ID if it has children, so start_lvl knows which to reference
        if ($has_children) {
            $this->current_parent_id = $item->ID;
            $output .= '<li class="' . $class_names . '" x-data="{ ' . $alpine_key . ': false }">';
        } else {
            $output .= '<li class="' . $class_names . '">';
        }

        // Anchor tag attributes
        $atts = array();
        $atts['href'] = !empty($item->url) ? $item->url : '#';
        if ($has_children) {
            $atts['@click.prevent'] = $alpine_key . ' = !' . $alpine_key;
            $atts['href'] = '#'; // Disable actual link for parents
            $atts['class'] = 'flex justify-between w-full cursor-pointer select-none';
        } else {
            $atts['class'] = 'flex justify-between w-full cursor-pointer select-none';
        }

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $attributes .= ' ' . $attr . '="' . esc_attr($value) . '"';
            }
        }

        // Arrow SVGs
        $svg_down = '
                    <svg width="20" height="15" 
                    fill="currentColor" 
                    class="h-auto -rotate-90 border border-b-current/5 px-3 flex gap-x-1 items-center transition-transform duration-300 box-content"
                    viewBox="0 0 16 16"
                    :class="{ \'rotate-90\': ' . $alpine_key . ' }">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                    </svg>';

        $svg_left = '
                    <svg width="20" height="15" fill="currentColor" class="box-content transition-all flex h-auto border border-current/5 px-3  gap-x-1 items-center" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                    </svg>';

        $svg = $has_children ? $svg_down : $svg_left;

        // Title with padding
        $title = '<p class="p-3 ps-5 max-lg:text-sm">' . apply_filters('the_title', $item->title, $item->ID) . '</p>';

        $output .= '<a' . $attributes . '>' . $title . $svg . '</a>';
    }

    function start_lvl(&$output, $depth = 0, $args = null)
    {
        // Use the stored current_parent_id to get Alpine state variable
        $alpine_key = 'open' . $this->current_parent_id;

        // Output submenu <ul> with x-show and Tailwind classes,
        // hidden by default, flex when Alpine state is true
        $output .= '<ul class="p-2 bg-gray-100 border-x border-gray-200 flex-col" x-show="' . $alpine_key . '" x-cloak>';
    }

    function end_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '</ul>';
    }
}