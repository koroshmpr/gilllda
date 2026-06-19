<?php
/* Template Name: Contact Us - Editorial Style */
get_header();
$form_id = get_field('gravity_form');
?>

    <!-- 1. Immersive Hero Section -->
    <section class="relative w-full h-[55vh] min-h-[450px] flex items-center justify-center overflow-hidden bg-gray-100">
        <!-- Background Image -->
        <img src="<?php the_post_thumbnail_url('2048x2048'); ?>"
             alt="<?php the_title_attribute(); ?>"
             class="absolute inset-0 w-full h-full object-cover object-center opacity-90"
             fetchpriority="high"
             decoding="async">

        <!-- Soft Fade Overlay (Blends into the page background below) -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/10 to-[#fafafa]"></div>

        <!-- Header Text -->
        <div class="relative z-10 text-center px-4 mt-[-8vh]">
            <h1 class="text-4xl md:text-6xl font-extrabold text-white tracking-tight mb-4 drop-shadow-lg">
                <?php the_title() ?>
            </h1>
            <p class="text-lg md:text-xl text-white/90 font-medium tracking-wide drop-shadow-md">
                همراه همیشگی استایل شما
            </p>
        </div>
    </section>

    <!-- 2. Floating Contact Card -->
    <section class="relative z-20 container mx-auto px-4 max-w-6xl -mt-32 mb-24">
        <div class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-3xl overflow-hidden border border-white">
            <div class="grid lg:grid-cols-2">

                <!-- Right Side (Form Area - RTL) -->
                <div class="p-8 md:p-12 lg:p-16 order-2 lg:order-1 bg-white">
                    <div class="mb-10 border-b border-gray-100 pb-6">
                        <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-3">برای ما بنویسید</h2>
                        <p class="text-base text-gray-500">پاسخگوی سوالات، پیشنهادات و نظرات شما هستیم.</p>
                    </div>

                    <div class="form-wrapper">
                        <?php
                        if ($form_id) {
                            echo do_shortcode('[gravityform id="' . esc_attr($form_id) . '" title="false" ajax="true" description="false"]');
                        }
                        ?>
                    </div>
                </div>

                <!-- Left Side (Contact Info Area - RTL) -->
                <div class="bg-gray-50 p-8 md:p-12 lg:p-16 order-1 lg:order-2 flex flex-col justify-center relative overflow-hidden">

                    <!-- Subtle Monogram Watermark -->
                    <div class="absolute -end-8 -top-12 text-[18rem] font-serif font-bold text-gray-200/40 select-none pointer-events-none">
                        G L
                    </div>

                    <div class="relative z-10 space-y-10">
                        <?php $address = get_field('address', 'option'); if ($address) : ?>
                            <div class="flex items-start gap-5 group">
                                <div class="p-4 bg-white rounded-2xl shadow-sm text-gray-800 group-hover:bg-gray-900 group-hover:text-white transition-all duration-300">
                                    <?php get_template_part('template-parts/svg/map', null, ['size' => 24, 'class' => 'fill-current']); ?>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-400 mb-2">آدرس دفتر</h3>
                                    <address class="not-italic text-gray-900 text-base font-medium leading-relaxed"><?= esc_html($address); ?></address>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php $email = get_field('email', 'option'); if ($email) : ?>
                            <div class="flex items-start gap-5 group">
                                <div class="p-4 bg-white rounded-2xl shadow-sm text-gray-800 group-hover:bg-gray-900 group-hover:text-white transition-all duration-300">
                                    <?php get_template_part('template-parts/svg/envelope', null, ['size' => 24, 'class' => 'fill-current']); ?>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-400 mb-2">ایمیل ارتباطی</h3>
                                    <a href="mailto:<?= esc_attr($email); ?>" class="text-gray-900 text-base font-medium hover:text-gray-500 transition-colors"><?= esc_html($email); ?></a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php $phone = get_field('phone', 'option'); if ($phone) : ?>
                            <div class="flex items-start gap-5 group">
                                <div class="p-4 bg-white rounded-2xl shadow-sm text-gray-800 group-hover:bg-gray-900 group-hover:text-white transition-all duration-300">
                                    <?php get_template_part('template-parts/svg/call-fill', null, ['size' => 24, 'class' => 'fill-current']); ?>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-400 mb-2">تلفن تماس</h3>
                                    <a href="tel:<?= esc_attr($phone); ?>" class="text-gray-900 text-base font-medium hover:text-gray-500 transition-colors ltr inline-block"><?= esc_html($phone); ?></a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Social Links Divider -->
                    <div class="relative z-10 mt-12 pt-8 border-t border-gray-200">
                        <p class="text-sm font-bold text-gray-500 mb-5">ما را در شبکه‌های اجتماعی دنبال کنید</p>
                        <nav aria-label="social network links" class="flex gap-1">
                            <?php get_template_part('template-parts/global/social-links', null , ['class' => 'border-primary/10 text-white hover:bg-primary/90 bg-primary']); ?>
                        </nav>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 3. Full Bleed Map -->
<?php
$map = get_field('map', 'option');
if ($map) :
    ?>
    <section class="w-full h-[400px] relative grayscale hover:grayscale-0 transition-all duration-1000 overflow-hidden">
        <?= $map; ?>
    </section>
<?php
endif;
get_footer();
?>