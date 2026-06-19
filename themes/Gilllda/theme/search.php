<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package bluebox
 */
get_header();
?>
    <header class=" bg-primary/10 py-5">
        <div class="container max-w-content">
            <?php
            printf(
            /* translators: 1: search result title. 2: search term. */
                '<h1 class="text-xl">%1$s <span class="text-bold text-3xl">%2$s</span></h1>',
                esc_html__('جستجو برای : ', 'bluebox'),
                get_search_query()
            ); ?>
        </div>
    </header><!-- .page-header -->
    <form class="container max-lg:px-3 max-w-content py-3 sticky top-0 lg:top-22 z-10 bg-white" role="search"
          method="get" action="<?= home_url() ?>">
        <fieldset class="relative overflow-hidden">
            <label for="search-input" class="sr-only screen-reader-text">Search:</label>
            <input type="text" id="search-input"
                   class="w-full p-3 border border-gray-200 rounded-md"
                   name="s"
                   value="<?= get_search_query() ?>"
                   placeholder="جستجو برای">
            <input type="submit"
                   onclick="saveSearchToCookie()"
                   class="absolute rounded-l-md text-white cursor-pointer transition-all hover:bg-primary/90 px-4 left-0 inset-y-0 bg-primary/70"
                   value="جستجو">
            <a aria-label="search for <?= get_search_query() ?>" href="#" class="submit"></a>
        </fieldset>
    </form>
<?php
// خواندن کوکی‌ها از سمت سرور
$cookie_name = "recent_searches";
$recent_searches = isset($_COOKIE[$cookie_name]) ? json_decode(stripslashes($_COOKIE[$cookie_name]), true) : [];

if (!empty($recent_searches)):
    ?>
    <nav aria-label="recent searched list" class="flex container mb-6 mt-3 max-w-content items-center gap-2">
        <p class="text-black/50 text-xs text-nowrap">قبلا جستجو کرده‌اید : </p>
        <div class="overflow-x-scroll flex flex-nowrap  gap-2">
            <?php foreach ($recent_searches as $item): ?>
                <a aria-label="search for <?= esc_attr($item); ?>"
                   class="bg-gray-100 text-black/50 text-nowrap rounded-md lg:text-sm border border-gray-200 hover:bg-gray-200 hover:border-primary/30 px-3 py-0.5"
                   href="<?= home_url() ?>?s=<?= urlencode($item); ?>"><?= esc_html($item); ?></a>
            <?php endforeach; ?>
        </div>

    </nav>
<?php endif; ?>
    <section class="container max-w-content px-3 flex flex-col gap-3 my-3 lg:mb-8">
        <?php
        if (have_posts()) :
        get_template_part('template-parts/global/grid-button');
        ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3"
             :class="gridView === 'large' ? 'md:grid-cols-1 lg:!grid-cols-2 xl:!grid-cols-3' : 'md:grid-cols-2 lg:!grid-cols-3 xl:!grid-cols-4'">
            <?php
            global $wp_query; // Bring the main query object into scope

            while (have_posts()) :
                the_post();
                $current_post_type = get_post_type();

                if ($current_post_type == 'product') :
                    $args = [
                        'isArchive' => true,
                        'eager'     => $wp_query->current_post < 4 // Eager load the first 4 items in the grid
                    ];
                    get_template_part('template-parts/product/card/product-card', null, $args);
                endif;
            endwhile;
            ?>
        </div>
    </section>

    <?php get_template_part('template-parts/global/pagination');
    // Reset query
    wp_reset_postdata();
else : ?>
    <div class="flex my-24 flex-col gap-5 items-center">
        <?php
        $args = array(
            'size' => 200,
            'class' => 'opacity-10 animate-bounce' ?? ''
        );
        get_template_part('template-parts/svg/search', null, $args);
        ?>
        <p class="font-bold text-3xl opacity-20"> موردی یافت نشد!</p>
    </div>
<?php endif; ?>
    <script>
        function saveSearchToCookie() {
            const input = document.getElementById('search-input');
            const query = input.value.trim();

            if (query === "") return;

            // 1. دریافت کوکی فعلی
            let searches = [];
            const cookieName = "recent_searches";
            const cookieValue = `; ${document.cookie}`;
            const parts = cookieValue.split(`; ${cookieName}=`);

            if (parts.length === 2) {
                try {
                    // استخراج مقدار کوکی و رفع مشکل اسلش‌های اضافه شده توسط مرورگر
                    const rawValue = parts.pop().split(';').shift();
                    searches = JSON.parse(decodeURIComponent(rawValue.replace(/\\"/g, '"')));
                } catch (e) {
                    searches = [];
                }
            }

            // 2. مدیریت آرایه (حذف تکراری‌ها و اضافه کردن جدید در ابتدا)
            searches = searches.filter(item => item !== query); // حذف اگر از قبل بود
            searches.unshift(query); // اضافه کردن به ابتدای لیست

            // 3. محدود کردن به حداکثر 5 مورد
            if (searches.length > 5) {
                searches = searches.slice(0, 5);
            }

            // 4. ذخیره در کوکی (با تاریخ انقضای 30 روزه)

            // نکته: استفاده از JSON.stringify و سپس encodeURIComponent برای جلوگیری از خراب شدن کاراکترهای فارسی
            const jsonString = JSON.stringify(searches);
            const encodedString = encodeURIComponent(jsonString);
            document.cookie = `${cookieName}=${encodedString}; path=/; max-age=${60 * 60 * 24 * 30}`;
        }
    </script>
<?php get_footer();
