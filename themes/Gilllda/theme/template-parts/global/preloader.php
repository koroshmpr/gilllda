<?php
/**
 * Template Part: Custom Page Transition Preloader
 * Requires Alpine.js and Tailwind CSS
 */
?>

<style>
    .dots {
        width: 13.4px;
        height: 13.4px;
        position: relative;
    }

    .dots::before,
    .dots::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: var(--color-primary);
    }

    .dots::before {
        box-shadow: -26.9px 0 var(--color-primary);
        animation: dots-dm1l1cmd 0.6s infinite linear;
    }

    .dots::after {
        transform: rotate(0deg) translateX(26.9px);
        animation: dots-dh1qq5md 0.6s infinite linear;
    }

    @keyframes dots-dm1l1cmd {
        100% {
            transform: translateX(26.9px);
        }
    }

    @keyframes dots-dh1qq5md {
        100% {
            transform: rotate(-180deg) translateX(26.9px);
        }
    }
</style>

<div id="alpine-preloader"
     x-data="pageTransition()"
     x-show="isLoading"
     x-transition.opacity.duration.500ms
     class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-white">

    <div class="dots"></div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pageTransition', () => ({
            isLoading: true, // Start visible when page loads

            init() {
                // 1. Fade out when the page fully loads
                window.addEventListener('load', () => {
                    setTimeout(() => {
                        this.isLoading = false;
                    }, 200); // Tiny delay so the user actually sees the logo
                });

                // 2. Fix the Safari/Chrome back-button cache issue (BFCache)
                window.addEventListener('pageshow', (event) => {
                    if (event.persisted) {
                        this.isLoading = false;
                    }
                });

                // 3. Intercept all link clicks globally to trigger exit animation
                document.addEventListener('click', (e) => {
                    const link = e.target.closest('a');

                    // Ignore if it's not a valid link
                    if (!link || !link.href) return;
                    if (link.target === '_blank') return;

                    // Ignore external links
                    if (link.hostname !== window.location.hostname) return;

                    // Ignore mailto: and tel: links
                    if (link.href.startsWith('mailto:') || link.href.startsWith('tel:')) return;

                    // Ignore anchor links on the same page (e.g., #details or #gallery)
                    const currentUrl = window.location.origin + window.location.pathname;
                    const linkUrl = link.origin + link.pathname;
                    if (link.hash && currentUrl === linkUrl) return;

                    // Ignore if user is opening in a new tab (Ctrl/Cmd + Click)
                    if (e.ctrlKey || e.metaKey || e.shiftKey) return;

                    e.preventDefault();
                    const targetUrl = link.href;

                    // Trigger Alpine's fade-in transition
                    this.isLoading = true;

                    // Wait for the x-transition duration (500ms) before navigating
                    setTimeout(() => {
                        window.location.href = targetUrl;
                    }, 500);
                });
            }
        }));
    });
</script>