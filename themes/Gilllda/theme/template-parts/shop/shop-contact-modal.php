<?php
$contactLinks = get_field('shop_contact_links', 'option');
?>
<div
        @keydown.escape.window="shopContact = false" id="searchModal"
        :class="shopContact ? '!z-50 !opacity-100' : ''"
        class="fixed <?= current_user_can('administrator') ? 'pt-8' : ''; ?> inset-0 flex justify-center z-[-1] bg-black/50 opacity-0 lg:items-center items-end backdrop-blur-sm transition-all duration-300"
        @click.self="shopContact = false">
    <div
            class="bg-gray-50 text-black w-full lg:max-w-96 p-5 lg:p-7 pt-9 rounded-lg flex flex-col gap-1 translate-y-full transition-all duration-300"
            :class="shopContact ? '!translate-y-0' : 'translate-y-full'"
    >
        <!-- Close Button -->
        <button @click="shopContact = false" aria-label="close shop contact modal" class=" text-black mb-2 absolute -top-4 cursor-pointer start-3 bg-gray-200 border border-gray-300 hover:bg-gray-300 transition-all p-2 rounded-sm">
            <?php
            $args = array(
                'size' => '20',
                'class' => '',
            );
            get_template_part('template-parts/svg/close', null, $args);
            ?>
        </button>
        <div class="mb-3 border-b border-black/10 flex justify-center">
            <p class="pb-2 border-b-2 border-secondary w-fit">برای سفارش با ما در ارتباط باشید!</p>
        </div>
        <?php foreach ($contactLinks as $i => $contactLink) : ?>
            <a target="_blank" aria-label="link to <?= $contactLink['link']['title'] ?? '' ?>"
               style="background: <?= !empty($contactLink['color']) ? $contactLink['color'] : 'rgb(0,0,0)'; ?>"
               class="p-5 transition-all group/add flex gap-x-2 justify-center text-xl font-bold border text-nowrap text-white rounded-xl text-center flex-grow hover:brightness-90"
               href="<?= $contactLink['link']['url'] ?? '' ?>">
                <?php
                $svg_args = array(
                    'size' => '15',
                    'class' => 'group-hover/add:delay-100 rotate-45 group-hover/add:rotate-0 text-white duration-300 translate-x-2 opacity-0 transition-all group-hover/add:opacity-100 group-hover/add:translate-x-0'
                );
                get_template_part('template-parts/svg/call-fill', null, $svg_args); ?>
                <span class="group-hover/add:-translate-x-0 font-bold text-sm transition-all duration-300 translate-x-3"><?= $contactLink['link']['title'] ?? '' ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>