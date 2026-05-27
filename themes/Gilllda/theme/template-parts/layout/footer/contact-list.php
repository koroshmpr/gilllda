<?php
$fillColor = 'fill-white/80';
$contactClass = 'ps-2 lg:ps-4';
$iconTransitionClass = 'flex before:duration-200 justify-center items-center group-hover:before:scale-100 before:transition-all before:scale-0 before:size-[33px] before:rounded-full relative before:absolute before:top-1/2 before:translate-x-1/2  before:-translate-y-1/2 before:right-1/2 before:bg-white/20';
$columnClass = 'max-lg:my-5 flex lg:border-s border-white/10  flex-col max-lg:items-center gap-y-4 text-white/70';
$titleCLass = 'lg:bg-gradient-to-l select-none from-white/5 py-0.5 text-xl lg:text-2xl lg:ps-5 w-full lg:border-s-2 max-lg:text-white/85 border-white/75 text-white-75'
?>
<nav class=" <?= $columnClass; ?>">
    <p class="<?= $titleCLass; ?> max-lg:text-center">
        راه‌های ارتباطی
    </p>
    <?php $address = get_field('address', 'option');
    if ($address) :?>
        <address class="flex gap-3 not-italic items-center <?= $contactClass; ?>">
            <?php
            $args = array(
                'size' => 20,
                'class' => $fillColor
            );
            get_template_part('template-parts/svg/map', null, $args);
            ?>
            <?= get_field('address', 'option'); ?>
        </address>
    <?php endif; ?>
    <?php
    $email = get_field('email', 'option');
    if ($email) :?>
        <a aria-label="email link"
           class="flex gap-3 hover:text-warning group transition-colors justify-start <?= $contactClass; ?>"
           href="mailto:<?= $email; ?>">
            <span class="<?= $iconTransitionClass; ?>">
                <?php get_template_part('template-parts/svg/envelope', null, $args); ?>
            </span>
            <span class="group-hover:!text-icon">
                <?= $email; ?>
            </span>
        </a>
    <?php endif;
    $phone = get_field('phone', 'option');
    if ($phone) :
        ?>
        <a aria-label="call us"
           class="flex gap-3 group transition-colors justify-start <?= $contactClass; ?>"
           href="tel:<?= $phone; ?>">
            <span class="<?= $iconTransitionClass; ?>">
                <?php get_template_part('template-parts/svg/call-fill', null, $args); ?>
            </span>
            <span class="ltr group-hover:!text-icon"><?= $phone; ?></span>
        </a>
    <?php endif;
    ?>
</nav>