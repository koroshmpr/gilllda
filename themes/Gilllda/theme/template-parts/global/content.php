<?php
$content = $args['content'] ?? '';
if ($content) :?>
    <section
        :class="showMore ? '' : 'pb-5'"
        class="border container max-lg:max-w-[95%] mt-5 relative border-black/10 rounded-sm pt-10"
        x-data="{showMore : true}">
        <article :class="showMore ? 'max-h-[150px] lg:max-h-[120px]' : ''"
                 class="prose prose-sm prose-h1:max-lg:text-2xl max-w-none h-fit duration-500 overflow-hidden text-justify transition-all leading-7">
            <?= $content; ?>
        </article>

        <div :class="showMore ? '' : 'hidden'"
             class="bg-gradient-to-t from-white from-30% inset-x-0 h-24 absolute bottom-0 w-full"></div>
        <button
            :class="showMore ? '-translate-y-1/2' : 'sticky bottom-32 lg:bottom-5'"
            @click="showMore = !showMore" aria-label="expand the content"
            class="bg-icon border flex items-center justify-center gap-1 text-sm z-0 border-icon transition-all cursor-pointer hover:brightness-105 rounded-sm px-16 lg:px-12 mx-auto py-2">
          <span x-show="!showMore">
             <?php get_template_part('template-parts/svg/close', null, ['class' => 'text-gray-700', 'size' => '15']); ?>
          </span>
            <span x-text="showMore ? 'بیشتر' : 'بستن'"></span>
        </button>
    </section>
<?php endif; ?>