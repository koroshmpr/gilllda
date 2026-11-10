<?php
$type  = $args['type'] ?? '';
$class = $args['class'] ?? '';


// Only output the anchor tag if a URL actually exists
if ( ! empty( $type ) ) :
    ?>
    <a aria-label="back to <?= $type ?? ''; ?> page"
       class="flex justify-center bg-black/5 rounded-full items-center relative p-2 <?= esc_attr( $class ); ?>" href="/<?= $type; ?>" >
        <?php
        get_template_part( 'template-parts/svg/chevron-left', null, [
            'size' => 20,
        ] );
        ?>
    </a>
<?php endif; ?>