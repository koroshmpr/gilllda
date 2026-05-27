<?php
$current_category = get_queried_object();
$current_category_id = $current_category->term_id;
$current_parent_category_id = $current_category->parent;
$listClass = $args['listColor'] ?? 'bg-gray-50';
$parent_categories = get_categories(array(
    'taxonomy' => 'product_cat',
    'orderby' => 'name',
    'parent' => 0,
    'pad_counts' => true,
    'hierarchical' => true,
    'hide_empty' => true,
    'exclude' => '16',
));

if ($parent_categories) : ?>
    <nav x-data="{activeItem: null}" class="flex flex-col gap-y-1">
        <?php foreach ($parent_categories as $parent_cat) :
            $thumbnail_id = get_term_meta($parent_cat->term_id, 'thumbnail_id', true);
            $id = $parent_cat->term_id;
            $parent_cat_link = get_term_link($parent_cat); // Get the link for the parent category

            // Get all subcategories of the current parent category
            $subcategories = get_categories(array(
                'taxonomy' => 'product_cat',
                'orderby' => 'name',
                'parent' => $parent_cat->term_id,
                'hide_empty' => false,
            ));
            ?>
            <div
                <?php if ($subcategories) : ?>
                    @click="activeItem = <?= $id; ?>"
                <?php endif; ?>
                    class="border border-gray-200 hover:bg-gray-100 transition-all overflow-hidden rounded-sm <?= $listClass; ?> <?= $current_category_id === $id ? '!bg-primary/70 text-white' : ''; ?>">
                <div class="flex justify-between cursor-pointer">
                    <button class="p-3 flex justify-content-between max-lg:text-sm" type="button">
                        <?= $parent_cat->name; ?>
                    </button>
                    <?php if ($subcategories) : ?>
                        <div :class="activeItem === <?= $id; ?> ? 'hidden' : 'block'"
                             class="flex items-center border border-current/5 px-3 transition-all hover:brightness-125">
                            <?php
                            $args = array(
                                'size' => 20,
                                'class' => '-rotate-90'
                            );
                            get_template_part('template-parts/svg/chevron-left', null, $args);
                            ?>
                        </div>
                        <a class="border border-current/5 px-3 flex gap-x-1 items-center transition-all hover:brightness-125"
                           :class="activeItem === <?= $id; ?> ? 'block' : 'hidden'"
                           href="<?= $parent_cat_link; ?>"
                           aria-label="link to product category <?= $parent_cat->name; ?>">
                            <?php
                            $args = array(
                                'size' => 20,
                            );
                            get_template_part('template-parts/svg/chevron-left', null, $args);
                            ?>
                        </a>
                    <?php else : ?>
                        <a class="hover:bg-gray-200 border border-current/5 px-3 flex gap-x-1 items-center transition-all"
                           href="<?= $parent_cat_link; ?>"
                           aria-label="link to product category <?= $parent_cat->name; ?>">
                            <?php
                            $args = array(
                                'size' => 20,
                            );
                            get_template_part('template-parts/svg/chevron-left', null, $args);
                            ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php if ($subcategories) : ?>
                    <ul class="transition-all duration-300 border-s-4 divide-y divide-gray-300 border-gray-400 flex flex-col text-sm"
                        :class="activeItem === <?= $id; ?> ? 'grid-rows-[1fr] opacity-100 border-t border-gray-200' : 'grid-rows-[0fr] max-h-0 opacity-0'">
                        <?php foreach ($subcategories as $subcategory) :
                            ?>
                            <li>
                                <?php if ($current_category_id === $subcategory->term_id) : ?>
                                    <p class="p-2 transition-all group flex justify-between items-center bg-primary/70 text-white ">
                                        <?= $subcategory->name;
                                        $args = array(
                                            'size' => 12,
                                            'class' => 'p-2 box-content'
                                        );
                                        get_template_part('template-parts/svg/chevron-left', null, $args);
                                        ?>
                                    </p>

                                <?php
                                else :?>
                                    <a class="p-2 transition-all hover:border-primary group bg-gray-200 text-black flex justify-between items-center"
                                       href="<?= get_term_link($subcategory) ?>">
                                        <?= $subcategory->name;
                                        $args = array(
                                            'size' => 12,
                                            'class' => 'group-hover:-translate-x-1 p-2 box-content transition-all group-hover:scale-110'
                                        );
                                        get_template_part('template-parts/svg/chevron-left', null, $args);
                                        ?>
                                    </a>
                                <?php endif;
                                // Get sub-subcategories (depth more than 2)
                                $sub_subcategories = get_categories(array(
                                    'taxonomy' => 'product_cat',
                                    'orderby' => 'name',
                                    'parent' => $subcategory->term_id,
                                    'hide_empty' => false,
                                ));

                                if ($sub_subcategories) :
                                    echo '<ul>';
                                    foreach ($sub_subcategories as $sub_subcategory) {
                                        // Add 'text-success' class if this is the current category
                                        $sub_sub_cat_class = ($sub_subcategory->term_id == $current_category_id) ? ' text-success' : '';
                                        echo '<li class="ms-3"><a class="text-dark text-opacity-75' . $sub_sub_cat_class . '" href="' . get_term_link($sub_subcategory) . '">' . $sub_subcategory->name . '</a></li>';
                                    }
                                    echo '</ul>';
                                endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php wp_reset_postdata(); // Reset Query
                ?>
            </div>
        <?php endforeach; ?>
    </nav>
<?php
else :
    echo '<p class="text-center opacity-75 text-2xl ">دسته بندی وجود ندارد!</p>';
endif; ?>