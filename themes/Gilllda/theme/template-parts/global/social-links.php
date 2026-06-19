<?php
$class = $args['class'] ?? 'border-white/10 text-white hover:bg-white/5';
$socials = get_field('social', 'option');
if ($socials):
    foreach ($socials as $social):?>
        <a aria-label="<?= $social['name']; ?>" title="<?= $social['name']; ?>"
           class="p-4 hover:-translate-y-1 transition-all rounded-full border <?= $class; ?>"
           target="_blank"
           href="<?= $social['link']['url'] ?? ''; ?>">
            <?php
            $args = array(
                'size' => 20
            );
            get_template_part('template-parts/svg/socials/' . $social['name'], null, $args); ?>
        </a>
    <?php endforeach;
endif;
?>