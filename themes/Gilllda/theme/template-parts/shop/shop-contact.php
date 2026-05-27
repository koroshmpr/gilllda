<?php
$contactLinks = get_field('shop_contact_links', 'option');
$linkClass = 'bg-primary px-2 lg:px-4 py-2.5 lg:py-4 transition-all group/add cursor-pointer flex gap-x-2 justify-center text-xs lg:text-sm font-bold shadow-sm text-nowrap text-white rounded-lg text-center hover:brightness-90';
if ($contactLinks) :?>
    <nav class="max-lg:w-1/2 flex lg:flex-wrap gap-1 mb-2">
        <a target="_blank"
           class="<?= $linkClass; ?> flex-grow"
           href="<?= $contactLinks[0]['link']['url'] ?? '' ?>">
            <?php
            $svg_args = array(
                'size' => '15',
                'class' => 'group-hover/add:delay-100 rotate-45 group-hover/add:rotate-0 text-white duration-300 translate-x-2 opacity-0 transition-all group-hover/add:opacity-100 group-hover/add:translate-x-0'
            );
            get_template_part('template-parts/svg/call-fill', null, $svg_args); ?>
            <span class="group-hover/add:-translate-x-0 font-bold text-sm transition-all duration-300 translate-x-3"><?= $contactLinks[0]['link']['title'] ?? '' ?></span>
        </a>
        <button aria-label="open shop contact modal" type="button" @click="shopContact = true" class="<?= $linkClass; ?> w-10 lg:w-12">
            <?php
            $svg_args = array(
                'size' => '22',
                'class' => 'group-hover/add:-rotate-45 absolute group-hover/add:opacity-0 text-white duration-500 transition-all'
            );
            get_template_part('template-parts/svg/plus', null, $svg_args);
            $svg_args = array(
                'size' => '15',
                'class' => 'group-hover/add:delay-100 absolute rotate-45 group-hover/add:rotate-0 text-white duration-300 opacity-0 transition-all group-hover/add:opacity-100'
            );
            get_template_part('template-parts/svg/call-fill', null, $svg_args);
            ?>
        </button>
    </nav>
<?php endif;