<?php /* Template Name: form */
$form_id = get_field('gravity_form');
get_header(); ?>
    <section class="container max-w-[700px] p-5 lg:p-7 mb-1 lg:my-5 bg-primary lg:rounded-2xl text-white">
        <header class="border-b border-white/10 mb-4 flex justify-center">
            <h1 class="border-b-2 text-xl bg-gradient-to-t from-secondary/15 px-4 border-secondary/80 pb-2"><?php the_title(); ?></h1>
        </header>
        <article
                class="entry-content prose prose-neutral text-white/70 prose-img:mx-auto text-justify prose-strong:text-white prose-headings:text-white prose-a:no-underline prose-a:text-icon mb-7 border-b border-white/15 pb-4">
            <?php the_content(); ?>
        </article>
        <?php if ($form_id) :
            echo do_shortcode('[gravityform id="' . $form_id . '" title="false" ajax="true" description="false"]');
        endif ?>
    </section>
    <style>
        .gform_fields {
            gap: 12px !important;

            input, textarea {
                padding: 20px 8px !important;
                border-radius: 7px !important;
            }
            select {
                border-radius: 7px !important;
            }
        }
    </style>
<?php
$utm = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
global $woo_active;
if ($woo_active && $utm) :
    $product = wc_get_product($utm);
    if ($product) :
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const productName = document.querySelector('.product_name input');
                const productSku = document.querySelector('.product_sku input');
                const productLink = document.querySelector('.product_link input');
                const userName = document.querySelector('.user_name_field input');
                const userEmail= document.querySelector('.user_email_field input');
                const addCLass = function (element) {
                    element.classList.add('!bg-secondary', '!text-white');
                    setTimeout(function () {
                        element.classList.remove('!bg-secondary', '!text-white');
                    }, 500);
                }
                addCLass(productName);
                addCLass(productSku);
                productName.value = '<?= $product->name; ?>'
                productLink.value = '<?= get_permalink($utm); ?>'
                productSku.value = '<?= $product->sku; ?>'
                <?php if( is_user_logged_in() ):?>
                    userName.value = '<?= wp_get_current_user()->display_name; ?>';
                    userEmail.value = '<?= wp_get_current_user()->user_email; ?>';
                <?php endif;?>
            })
        </script>
    <?php
    endif;
endif;
get_footer();
