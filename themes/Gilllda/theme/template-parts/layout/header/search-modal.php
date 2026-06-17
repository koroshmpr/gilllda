<section
        @keydown.escape.window="searchOpen = false" id="searchModal"
        :class="searchOpen ? '!z-50 !opacity-100' : 'transition-all duration-100 delay-75'"
        class="fixed <?= current_user_can('administrator') ? 'pt-8' : ''; ?> inset-0 bg-white flex justify-center z-[-1] opacity-0"
        @click.self="searchOpen = false"
>
    <div class="container max-w-content max-lg:px-3 relative text-black transition-all duration-200 opacity-0 flex flex-col overflow-hidden"
         :class="searchOpen ? 'delay-75 !translate-y-0 !opacity-100' : 'translate-y-1'">
            <div class="flex max-w-full gap-2 my-8">
             <button @click="searchOpen = false" aria-label="close search modal" class="flex items-center aspect-square justify-center p-2 px-4 bg-gray-50 border border-gray-200 hover:bg-gray-100 cursor-pointer transition-all rounded-md text-black z-10">
                 <?php
                 $args = array(
                     'size' => '20',
                     'class' => '',
                 );
                 get_template_part('template-parts/svg/close', null, $args);
                 ?>
             </button>

             <div class="flex-1 mx-auto lg:shrink-0 relative">
                 <form role="search" method="get" action="<?= home_url() ?>">
                     <fieldset class="relative flex">
                         <label for="search-input" class="sr-only screen-reader-text">Search:</label>
                         <input type="text" id="search-input"
                                class="w-full p-3 rounded-s-lg border bg-gray-50 border-gray-200 text-lg focus:border-gray-500"
                                name="s"
                                autocomplete="off"
                                value="<?= get_search_query() ?>"
                                placeholder="جستجو برای..."
                                oninput="handleAjaxSearch(this.value)">

                         <input type="submit"
                                onclick="saveSearchToCookie()"
                                class="text-white cursor-pointer rounded-e-lg transition-all hover:bg-primary/90 px-4 lg:px-8 left-0 inset-y-0 bg-primary/70"
                                value="جستجو">
                     </fieldset>
                 </form>
             </div>
         </div>
            <div id="search-loading" class="hidden text-sm text-gray-500 shrink-0">
                <div class="flex flex-col items-center justify-center py-16">
                    <?php
                    $args = array(
                        'class' => 'transition-all duration-300',
                        'size' => 40,
                    );
                    get_template_part('template-parts/svg/loader', null, $args); ?>
                    <span>در حال جستجو...</span>
                </div>
            </div>
            <div id="ajax-search-results"
                 class="w-full mx-auto overflow-y-auto pb-10 flex-1 custom-scrollbar grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 content-start items-start">
            </div>
    </div>

    <script>
        // 1. Existing Cookie Logic
        function saveSearchToCookie() {
            const input = document.getElementById('search-input');
            const query = input.value.trim();
            if (query === "") return;

            let searches = [];
            const cookieName = "recent_searches";
            const cookieValue = `; ${document.cookie}`;
            const parts = cookieValue.split(`; ${cookieName}=`);

            if (parts.length === 2) {
                try {
                    const rawValue = parts.pop().split(';').shift();
                    searches = JSON.parse(decodeURIComponent(rawValue.replace(/\\"/g, '"')));
                } catch (e) {
                    searches = [];
                }
            }

            searches = searches.filter(item => item !== query);
            searches.unshift(query);
            if (searches.length > 5) searches = searches.slice(0, 5);

            const jsonString = JSON.stringify(searches);
            const encodedString = encodeURIComponent(jsonString);
            document.cookie = `${cookieName}=${encodedString}; path=/; max-age=${60*60*24*30}`;
        }

        // 2. Existing AJAX & Debounce Logic
        let searchTimeout = null;

        function handleAjaxSearch(query) {
            const resultsContainer = document.getElementById('ajax-search-results');
            const loadingIndicator = document.getElementById('search-loading');

            clearTimeout(searchTimeout);

            if (query.trim().length < 3) {
                resultsContainer.innerHTML = '';
                loadingIndicator.classList.add('hidden');
                return;
            }

            resultsContainer.innerHTML = '';
            loadingIndicator.classList.remove('hidden');

            searchTimeout = setTimeout(() => {
                fetchSearchResults(query);
            }, 2000);
        }

        async function fetchSearchResults(query) {
            const resultsContainer = document.getElementById('ajax-search-results');
            const loadingIndicator = document.getElementById('search-loading');

            try {
                const formData = new URLSearchParams();
                formData.append('action', 'custom_ajax_search');
                formData.append('keyword', query);

                const response = await fetch('<?= admin_url("admin-ajax.php") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                });

                const result = await response.json();

                loadingIndicator.classList.add('hidden');

                if (result.success) {
                    resultsContainer.innerHTML = result.data;
                } else {
                    // Span handles full width of grid to center the error text
                    resultsContainer.innerHTML = '<span class="col-span-full text-center text-sm text-gray-500 py-8">خطایی رخ داد.</span>';
                }
            } catch (error) {
                console.error('Search error:', error);
                loadingIndicator.classList.add('hidden');
            }
        }
    </script>
</section>