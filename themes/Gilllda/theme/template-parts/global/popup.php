<?php
$popUp = get_field('popup', 'option');
$show = $popUp['show'];
if ($show) :
    ?>
    <div x-data="popupComponent()"
         @keydown.escape.window="closePopup()"
         id="popUp"
         :class="popup ? '!z-50 !opacity-100' : ''"
         @click.self="closePopup()"
         class="fixed inset-0 flex justify-center z-[-1] duration-300 opacity-0 bg-black/80 backdrop-blur-md items-center">
        <div class="absolute before:absolute scale-[102%] top-1/2 inset-x-1/2 translate-x-1/2 w-11/12 lg:w-[600px] -translate-y-1/2 aspect-3/4 md:aspect-video overflow-hidden rounded-lg before:rounded-lg before:bg-white/20 before:size-full before:scale-[97.5%] lg:after:w-2/3 after:w-full after:absolute after:start-1/2 after:origin-right after:top-1/2 after:animate-spin after:h-56 after:-translate-y-1/2 after:bg-gradient-to-b after:from-secondary animate-duration-4000"
             :class="popup ? 'delay-500 opacity-100' : 'opacity-0'"></div>
        <div class="relative w-11/12 lg:max-w-[595px] group translate-y-1/2 aspect-3/4 border border-white/20 md:aspect-video after:ease-linear flex flex-col rounded-lg ease-out overflow-hidden transition-all duration-500" :class="popup ? '!translate-y-0' : ''">
            <button @click="closePopup()" aria-label="close popup"
                    class="text-white absolute top-3 start-3 bg-secondary border border-white/30 shadow p-1.5 cursor-pointer hover:brightness-110 z-1 rounded-md">
                <?php get_template_part('template-parts/svg/close', null, ['size' => '16']); ?>
            </button>
            <picture>
                <source media="(min-width: 961px)" srcset="<?= $popUp['image']['desktop']['url'] ?? ''; ?>">
                <source media="(max-width: 960px)" srcset="<?= $popUp['image']['mobile']['url'] ?? $popUp['image']['desktop']['url'] ?? ''; ?>">
                <img width="600" height="400" class="lg:max-w-[600px] bg-white w-full group-hover:scale-110 transition-all duration-300 object-cover aspect-3/4 md:aspect-video" src="<?= esc_url($popUp['image']['desktop']['url'] ?? ''); ?>" alt="<?= esc_attr($popUp['image']['desktop']['title'] ?? ''); ?>">
            </picture>
            <a @click.self="closePopup()" class="absolute bottom-3 inset-x-3 border font-bold border-white/40 py-4 shadow text-center rounded-md text-lg text-white bg-secondary hover:brightness-125 transition-all" href="<?= esc_url($popUp['link']['url'] ?? '#'); ?>"><?= esc_html($popUp['link']['title'] ?? ''); ?></a>
        </div>
    </div>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('popupComponent', () => ({
                popup: false,
                shown: false,  // to avoid multiple triggers
                init() {
                    const lastClosed = this.getCookie('popupClosedAt');
                    if (lastClosed && (Date.now() - parseInt(lastClosed)) <= 2 * 60 * 60 * 1000) {
                        // Popup should NOT show yet, do nothing.
                        return;
                    }

                    // Otherwise set event listeners to show popup after scroll or timeout
                    const showPopup = () => {
                        if (this.shown) return;
                        this.shown = true;
                        this.popup = true;
                        // Remove event listener because popup is shown
                        window.removeEventListener('scroll', scrollHandler);
                        clearTimeout(timeoutId);
                    };

                    // Scroll handler
                    const scrollHandler = () => {
                        if (window.scrollY > 500) {
                            showPopup();
                        }
                    };

                    // Listen for scroll
                    window.addEventListener('scroll', scrollHandler, { passive: true });

                    // Timeout for 30 seconds
                    const timeoutId = setTimeout(() => {
                        showPopup();
                    }, 60000);
                },

                closePopup() {
                    this.popup = false;
                    this.setCookie('popupClosedAt', Date.now(), 2);
                },

                setCookie(name, value, hours) {
                    const d = new Date();
                    d.setTime(d.getTime() + (hours * 60 * 60 * 1000));
                    document.cookie = `${name}=${value};expires=${d.toUTCString()};path=/;SameSite=Lax`;
                },

                getCookie(name) {
                    const value = `; ${document.cookie}`;
                    const parts = value.split(`; ${name}=`);
                    if (parts.length === 2) return parts.pop().split(';').shift();
                    return null;
                }
            }))
        })
    </script>
<?php endif; ?>