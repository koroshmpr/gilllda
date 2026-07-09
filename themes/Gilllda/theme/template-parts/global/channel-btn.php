<?php
$type  = $args['type'] ?? '';
$class = $args['class'] ?? '';

// Fetch the ACF group field
$channels = get_field('channels', 'option');

// Get the specific channel link array based on the passed type ('blog' or 'shop')
$link_data = $channels[$type] ?? false;

// Standard ACF Link fields use 'url'.
// (Fallback to 'link' just in case you manually created a sub-field named 'link')
$url = $link_data['url'] ?? ($link_data['link'] ?? '');

// Only output the anchor tag if a URL actually exists
if ( ! empty( $url ) ) :
    ?>
    <a aria-label="link to <?= $type ?? ''; ?> telegram channel" class="flex justify-center text-[#0088cc] items-center relative p-2 <?= esc_attr( $class ); ?>" href="<?= esc_url( $url ); ?>" target="_blank">
        <?php
        get_template_part( 'template-parts/svg/socials/telegram', null, [
                'size' => 25,
        ] );
        get_template_part( 'template-parts/svg/plus', null, [
            'size'  => 14,
            'class' => 'absolute box-content -top-0.5 -right-0.5 bg-primary text-white border border-white rounded-full p-[1px] shadow-sm'
        ] );
        ?>
    </a>
<?php endif; ?>