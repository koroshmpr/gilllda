<?php
$post_id = get_the_ID();
$total = get_post_meta($post_id, 'total_ratings', true);
?>
<section
        class="border bg-amber-400/5 justify-between items-center p-5 set custom-post-rating <?= $args['class'] ?? ''; ?>"
        id="<?= get_post_type();?>-rating">
    <div class="flex flex-col gap-y-1 justify-center">
        <span class="text-xl">چه امتیاری می‌دهید؟</span>
        <span class="text-xs flex gap-x-1 items-center max-lg:justify-center">
            <?php if ($total) : ?>
                تاکنون
                <p id="total-rating"
                   class="font-bold transition-all duration-300 text-sm text-amber-500"><?= get_post_meta($post_id, 'total_ratings', true); ?></p>
                نفر امتیاز داده‌اند!
            <?php else: ?>
                <span>اولین نفر باشید که امتیاز می‌دهید!</span>
            <?php endif; ?>
        </span>
    </div>
    <div class="flex gap-x-2 py-2 relative overflow-hidden items-center">
        <?php
        $btnClass = 'hover:scale-110 transition-all duration-300 flex flex-col gap-y-1 group group cursor-pointer text-xs text-black/50';
        $textCLass = 'group-hover:opacity-100 translate-y-2 group-hover:animate-bounce group-hover:translate-y-0 transition-all duration-300 opacity-0';
        $args = array(
            'class' => 'text-yellow-400 transition-all duration-300 -translate-y-3 group-hover:translate-y-0',
            'size' => 20,
        );
        $rating_value = get_post_meta($post_id, 'rating_value', true);
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating_value) :?>
                <button aria-label="add rating number <?= $i; ?>" class="custom-star-btn filled <?= $btnClass; ?>">
                    <span class="<?= $textCLass ?>"><?= $i; ?></span>
                    <?php get_template_part('template-parts/svg/star-fill', null, $args); ?>
                </button>
            <?php else : ?>
                <button class="custom-star-btn <?= $btnClass; ?> " aria-label="add rating number <?= $i; ?>">
                    <span class="<?= $textCLass ?>"><?= $i; ?></span>
                    <?php get_template_part('template-parts/svg/star', null, $args); ?>
                </button>
            <?php endif;
        }
        ?>
        <span id="rating-message"
              class="absolute translate-y-full select-none ease-out text-sm duration-500 flex inset-0 text-nowrap items-center justify-center backdrop-blur-[3px]">
            امتیاز با موفقیت ثبت شد!
        </span>
        <span id="pulseLoader"
              class="absolute translate-y-full ease-out duration-500 flex inset-0 text-nowrap items-center justify-center backdrop-blur-[3px]">
             <?php
             $args = array(
                 'class' => 'text-primary absolute scale-125 transition-all duration-300',
                 'size' => 40,
             );
             get_template_part('template-parts/svg/loader', null, $args); ?>
        </span>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.querySelector('.custom-post-rating');
            if (!container) return;
            const buttons = container.querySelectorAll('.custom-star-btn');
            const total = document.getElementById('total-rating');
            const pulseLoader = document.getElementById('pulseLoader');
            const ratingMessage = document.getElementById('rating-message');

            buttons.forEach((btn, index) => {
                btn.addEventListener('click', function () {
                    if (pulseLoader) {
                        pulseLoader.classList.remove('translate-y-full');
                    }

                    buttons.forEach((b, idx) => {
                        const path = b.querySelector('path');
                        if (!path) return;
                        if (idx <= index) {
                            path.setAttribute('d', 'M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z');
                        } else {
                            path.setAttribute('d', 'M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z');
                        }
                    });

                    const ratingValue = index + 1;

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'save_rating',
                            post_id: '<?php echo $post_id; ?>',
                            rating_value: ratingValue
                        })
                    });

                    setTimeout(function () {
                        if (pulseLoader) {
                            pulseLoader.classList.add('-translate-y-full');
                        }
                        if (ratingMessage) {
                            ratingMessage.classList.remove('translate-y-full');
                        }
                        if (total) {
                            var toNumber = Number(total.innerHTML);
                            total.innerText = toNumber + 1;
                            total.classList.add('scale-125', 'animate-bounce', 'mx-1');
                        }
                    }, 2000);

                    setTimeout(function () {
                        if (total) {
                            total.classList.remove('scale-125', 'animate-bounce', 'mx-1');
                        }
                    }, 4000);
                });
            });
        });
    </script>
</section>