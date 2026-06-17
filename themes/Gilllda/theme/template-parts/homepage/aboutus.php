<?php $about = get_field('about_us'); ?>
<?php if ($about): ?>
    <section x-data="{
                 about: false,
                 progress: 0,
                 calculateScroll() {
                     let rect = this.$el.getBoundingClientRect();
                     let windowHeight = window.innerHeight;

                     let totalTravel = windowHeight;

                     // How far it has traveled so far
                     let scrolled = windowHeight - rect.top;

                     // Calculate base percentage
                     let percent = (scrolled / totalTravel) * 100;

                     // Multiply by 1.11 so it hits 100% when scroll is at 90%
                     let acceleratedPercent = percent * 0.8;

                     this.progress = Math.max(0, Math.min(100, acceleratedPercent));
                 }
             }"
             x-init="calculateScroll()"
             @scroll.window.passive="calculateScroll()"
             x-intersect:enter.margin.-15%.0px.-30%.0px="about = true"
             x-intersect:leave="about = false"
             :class="about ? 'max-lg:!bg-[20%]' : ''"
             class="relative w-full flex flex-col items-start justify-center transition-all duration-100 ease-linear lg:justify-start lg:bg-fixed bg-right lg:bg-center bg-cover bg-no-repeat my-8"
             style="background-image: url('<?= esc_url($about['image']['url'] ?? ''); ?>');"
             :style="`background-position: ${progress}%; background-image: url('<?= esc_url($about['image']['url'] ?? ''); ?>')`"
             dir="rtl">

        <div class="absolute inset-0 bg-black/60 lg:bg-black/10 transition-all duration-500"></div>

        <div class="container min-h-[70vh] lg:min-h-[100vh] relative z-10 mx-auto px-6 lg:px-12 py-16 flex items-center lg:items-start justify-center lg:justify-start h-full">

            <div :class="about ? 'lg:translate-y-6 scale-95' : ''"
                 class="bg-transparent lg:bg-white/20 lg:border-2 border-white/30 lg:backdrop-blur-lg lg:sticky lg:top-36 lg:shadow-2xl lg:w-3/5 xl:max-w-2/5 lg:p-10 rounded-2xl text-white flex flex-col gap-6 text-center lg:text-right transition-all ease-linear duration-100">

                <h2 class="text-xl lg:text-3xl font-black relative lg:text-primary pb-4 flex items-center justify-center lg:justify-start gap-3">
                    <svg class="w-8 h-8 lg:size-16 lg:text-primary" viewBox="0 0 16 16">
                        <defs>
                            <clipPath id="bag-fill-clip">
                                <rect x="0"
                                      :y="16 - (progress / 100) * 16"
                                      width="16"
                                      :height="(progress / 100) * 16"
                                      class="transition-all duration-75 ease-linear"></rect>
                            </clipPath>
                        </defs>

                        <g fill="currentColor" clip-path="url(#bag-fill-clip)">
                            <path d="M8 5.75c1.388 0 2.673.193 3.609.385a18 18 0 0 1 1.43.354l.112.034.002.001h.001a.5.5 0 0 1-.308.952l-.004-.002-.018-.005a17 17 0 0 0-1.417-.354A17.3 17.3 0 0 0 8 6.75a17.3 17.3 0 0 0-3.408.365 17 17 0 0 0-1.416.354l-.018.005-.003.001a.5.5 0 1 1-.308-.95A17.3 17.3 0 0 1 8 5.75"/>
                            <path d="M5.229 2.722c-.126.461-.19.945-.222 1.375-1.401.194-2.65.531-3.525 1.012C-.644 6.278.036 11.204.393 13.127a.954.954 0 0 0 .95.772h13.314a.954.954 0 0 0 .95-.772c.357-1.923 1.037-6.85-1.09-8.018-.873-.48-2.123-.818-3.524-1.012a7.4 7.4 0 0 0-.222-1.375c-.162-.593-.445-1.228-.971-1.622-1.115-.836-2.485-.836-3.6 0-.526.394-.81 1.03-.971 1.622M9.2 1.9c.26.195.466.57.606 1.085.088.322.142.667.173.998a23.3 23.3 0 0 0-3.958 0 6 6 0 0 1 .173-.998c.14-.515.346-.89.606-1.085.76-.57 1.64-.57 2.4 0M8 4.9c2.475 0 4.793.402 6.036 1.085.238.13.472.406.655.93.183.522.28 1.195.303 1.952.047 1.486-.189 3.088-.362 4.032H1.368c-.173-.944-.409-2.545-.362-4.032.024-.757.12-1.43.303-1.952.183-.524.417-.8.655-.93C3.207 5.302 5.525 4.9 8 4.9"/>
                        </g>
                    </svg>

                    <?= esc_html($about['title'] ?? 'به فروشگاه گیلدا خوش اومدین'); ?>

                    <span class="absolute bottom-0 right-0 lg:right-16 left-0 lg:left-auto w-16 h-1.5 bg-primary rounded-full mx-auto lg:mx-0"></span>
                </h2>

                <article class="text-sm lg:text-base leading-8 lg:leading-8 font-medium lg:font-normal text-gray-100 text-justify text-justify-last-center lg:text-justify-last-right">
                    <?= wp_kses_post($about['content'] ?? ''); ?>
                </article>

                <div class="mt-2 flex justify-center lg:justify-start">
                    <a href="<?= esc_url($about['button']['url'] ?? '#'); ?>"
                       class="inline-flex items-center justify-center px-8 py-5 lg:py-3 bg-primary lg:bg-gray-900 text-white rounded-xl font-bold text-sm hover:bg-white hover:text-primary lg:hover:bg-primary lg:hover:text-white transition-all duration-300 shadow-lg hover:shadow-xl w-full lg:w-fit border border-transparent hover:border-primary lg:hover:border-transparent">
                        <?= esc_html($about['button']['title'] ?? 'درباره ما'); ?>
                    </a>
                </div>

            </div>
        </div>
    </section>
<?php endif; ?>