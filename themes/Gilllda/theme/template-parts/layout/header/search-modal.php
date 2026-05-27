<div
        @keydown.escape.window="searchOpen = false" id="searchModal"
        :class="searchOpen ? '!z-50 !opacity-100' : ''"
        class="fixed <?= current_user_can('administrator') ? 'pt-8' : ''; ?> inset-0 flex justify-center z-[-1] bg-black/50 opacity-0 lg:items-start items-end backdrop-blur-sm transition-all duration-300"
        @click.self="searchOpen = false"
>
    <div
            class="bg-gray-50 text-black w-full max-w-sm px-2 lg:-translate-y-full translate-y-full transition-all duration-300"
            :class="searchOpen ? 'delay-200 !translate-y-0' : 'lg:-translate-y-full translate-y-full'"
    >
        <!-- Close Button -->
        <button @click="searchOpen = false" aria-label="close search modal" class="flex items-center justify-center absolute lg:top-full max-lg:bottom-full right-1/2 translate-x-1/2 py-2 px-5 bg-gray-50 hover:bg-gray-100 cursor-pointer transition-all max-lg:rounded-t-sm lg:rounded-b-sm text-black">
            <?php
            $args = array(
                'size' => '18',
                'class' => '',
            );
            get_template_part('template-parts/svg/close', null, $args);
            ?>
        </button>

        <!-- Mobile Menu Items -->
        <form class="container max-lg:px-2 max-w-content my-3" role="search" method="get" action="<?= home_url() ?>">
            <fieldset class="relative overflow-hidden">
                <label for="search-input" class="screen-reader-text">Search:</label>
                <input type="text" id="search-input"
                       class="w-full p-3 border border-gray-200 rounded-md"
                       name="s"
                       value="<?= get_search_query() ?>"
                       placeholder="جستجو برای">
                <input type="submit"
                       onclick="saveSearchToCookie()"
                       class="absolute rounded-l-md text-white cursor-pointer transition-all hover:bg-primary/90 px-4 left-0 inset-y-0 bg-primary/70"
                       value="جستجو">
                <a aria-label="go to search page" href="#go" class="submit"></a>
            </fieldset>
        </form>
    </div>
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
            document.cookie = `${cookieName}=${encodedString}; path=/; max-age=${60*60*24*30}`;
        }
    </script>
</div>