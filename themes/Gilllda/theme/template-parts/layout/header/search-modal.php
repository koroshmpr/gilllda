<div
        @keydown.escape.window="searchOpen = false" id="searchModal"
        :class="searchOpen ? '!z-50 !opacity-100' : ''"
        class="fixed <?= current_user_can('administrator') ? 'pt-8' : ''; ?> inset-0 flex justify-center z-[-1] bg-black/50 opacity-0 backdrop-blur-sm transition-all duration-300"
        @click.self="searchOpen = false"
>
    <div
            class="bg-gray-50 text-black w-full h-full transition-all duration-300 flex flex-col overflow-hidden"
            :class="searchOpen ? 'delay-100 !translate-y-0' : 'translate-y-5'"
    >
        <div class="container max-w-content max-lg:px-3 relative">
            <button @click="searchOpen = false" aria-label="close search modal" class="flex items-center justify-center absolute top-4 start-4 py-2 px-2 bg-gray-200 hover:bg-gray-300 cursor-pointer transition-all rounded-md text-black z-10">
                <?php
                $args = array(
                    'size' => '18',
                    'class' => '',
                );
                get_template_part('template-parts/svg/close', null, $args);
                ?>
            </button>

            <div class="w-full max-w-3xl mx-auto shrink-0 mt-16 mb-8 relative z-10">
                <form role="search" method="get" action="<?= home_url() ?>">
                    <fieldset class="relative overflow-hidden shadow-sm">
                        <label for="search-input" class="sr-only screen-reader-text">Search:</label>
                        <input type="text" id="search-input"
                               class="w-full p-4 border border-gray-200 rounded-lh text-lg focus:outline-none focus:ring-2 focus:ring-primary/50"
                               name="s"
                               autocomplete="off"
                               value="<?= get_search_query() ?>"
                               placeholder="جستجو برای..."
                               oninput="handleAjaxSearch(this.value)">

                        <input type="submit"
                               onclick="saveSearchToCookie()"
                               class="absolute rounded-l-md text-white cursor-pointer transition-all hover:bg-primary/90 px-8 left-0 inset-y-0 bg-primary/70"
                               value="جستجو">
                    </fieldset>
                </form>
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

            <div id="ajax-search-results" class="w-full mx-auto overflow-y-auto pb-10 flex-1 custom-scrollbar grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 content-start items-start">
            </div>
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
</div>