<?php /* Template Name: contact us */
$form_id = get_field('gravity_form');
$textColor = 'text-white';
$fillColor = 'fill-white';
get_header(); ?>
        <div class="inset-y-0 bg-primary/15 absolute z-[0] w-full lg:w-8/12"></div>
        <section class="inset-y-0 left-0 absolute z-[0] w-full lg:w-4/12 border-s border-gray-400/75 max-lg:hidden">
            <img class="size-full object-cover opacity-25" src="<?php the_post_thumbnail_url('large'); ?>"
                 alt="<?php the_title_attribute(); ?>">
        </section>
        <section class="container max-lg:px-3 max-w-content z-[1] max-lg:pb-3 relative flex justify-start flex-wrap items-center">
            <div class="lg:ps-8 lg:pe-20 py-6 max-lg:px-2 lg:py-40 basis-full lg:basis-8/12">
                <header class="mb-8 border-s-4 border-white">
                    <h1 class="ps-4 font-bold relative before:absolute before:-start-1 before:h-7 before:animate-bounce before:top-2 before:w-1 before:bg-gradient-to-b before:via-primary text-3xl lg:text-5xl mb-2"><?php the_title() ?></h1>
                    <h2 class="text-base ps-4 opacity-40">هر سوالی دارین برامون بنویسین</h2>
                </header>
                <?php
                if ($form_id) {
                    // Display the Gravity Form using the ID
                    echo do_shortcode('[gravityform id="' . $form_id . '" title="false" ajax="true" description="false"]');
                } else {
                    echo '';
                } ?>
            </div>
            <div class="bg-gray-800 w-full mb-5 sticky <?= $textColor; ?> <?= current_user_can('administrator') ? 'top-24' : 'top-20'; ?> lg:-ms-4 px-5 py-16 lg:py-20 lg:px-12 lg:basis-4/12 flex flex-col gap-y-8 lg:items-start items-center">
                <?php $address = get_field('address', 'option');
                if ($address) :?>
                    <address class="flex gap-3 not-italic justify-center items-start lg:justify-start">
                        <?php
                        $args = array(
                            'size' => 25,
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
                    <a aria-label="call us"
                       class="flex gap-2 hover:text-warning transition-colors justify-start lg:justify-start"
                       href="mailto:<?= $email; ?>">
                        <?php get_template_part('template-parts/svg/envelope', null, $args); ?>
                        <?= $email; ?>
                    </a>
                <?php endif;
                $phone = get_field('phone', 'option');
                if ($phone) :?>
                    <a aria-label="call us"
                       class="flex gap-3 hover:text-warning transition-colors justify-start lg:justify-start"
                       href="tel:<?= $phone; ?>">
                        <?php get_template_part('template-parts/svg/call-fill', null, $args); ?>
                        <span class="ltr"><?= $phone; ?></span>
                    </a>
                <?php endif;
                ?>
            </div>
        </section>
<?php
$map = get_field('map', 'option');
if ($map) :
    ?>
    <section class="container mt-16 flex justify-center">
        <?= get_field('map', 'option') ?? ''; ?>
    </section>
<?php
endif;
get_footer();
