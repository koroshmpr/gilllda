<?php
/**
 * The template for displaying comments
 */
if (post_password_required()) {
    return;
}
?>

<section id="comments" class="bg-primary">
    <div class="container grid relative pb-5 h-fit lg:grid-cols-12 gap-4 items-start my-8 py-10 p-5">

        <?php $bluebox_comment_count = get_comments_number(); ?>

        <div id="comment-form-wrapper"
             class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-24 max-lg:order-1 p-8 bg-white border border-white/25 rounded-2xl">
            <header class="mb-5">
                <h2 class="text-xl font-black text-primary flex items-center gap-2">
                    دیدگاه کاربران
                    <?php if ('0' < $bluebox_comment_count) : ?>
                        <span class="text-sm font-normal text-primary/70">(<?= number_format_i18n($bluebox_comment_count); ?>)</span>
                    <?php endif; ?>
                </h2>
                <p class="text-sm text-gray-700 mt-2">شما هم می‌توانید دیدگاه خود را درباره این مطلب ثبت کنید.</p>
            </header>

            <div id="custom-reply-ui"
                 class="hidden mb-4 bg-primary/10 border border-primary/15 p-3 rounded-xl flex items-center justify-between transition-all">
                <div class="text-sm text-primary flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    پاسخ به: <strong id="reply-to-name" class="font-black"></strong>
                </div>
                <button type="button" id="cancel-reply-btn"
                        class="text-primary/70 hover:text-white transition-colors bg-primary/10 hover:bg-white/20 rounded-full p-1"
                        aria-label="لغو پاسخ">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <?php
            $comment_args = array(
                'title_reply' => '',
                'title_reply_to' => '',
                'cancel_reply_link' => '',
                'label_submit' => __('ثبت دیدگاه', 'bluebox'),
                'class_submit' => 'w-full bg-primary text-white hover:bg-primary/90 font-bold py-3 px-4 rounded-xl transition-all cursor-pointer mt-4',
                'class_form' => 'flex flex-col gap-4',
                'comment_field' => '<div class="flex flex-col gap-2"><label for="comment" class="text-sm font-bold text-primary/50">دیدگاه شما</label><textarea id="comment" name="comment" cols="45" rows="5" aria-required="true" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 focus:border-gray-500 focus:ring-2 focus:ring-gray-500 outline-none transition-all resize-none"></textarea></div>',
                'must_log_in' => '<p class="must-log-in text-sm text-red-400">' . sprintf(__('شما باید <a href="%s" class="font-bold underline text-primary">وارد سیستم</a> شوید.', 'bluebox'), wp_login_url(apply_filters('the_permalink', get_permalink()))) . '</p>',
                'logged_in_as' => '<p class="logged-in-as text-sm text-primary/50 mb-4">' . sprintf(__('وارد شده به عنوان <a href="%1$s" class="text-primary font-bold">%2$s</a>. <a href="%3$s" class="text-red-300">خروج؟</a>', 'bluebox'), admin_url('profile.php'), $user_identity, wp_logout_url(apply_filters('the_permalink', get_permalink()))) . '</p>',
                'comment_notes_before' => '',
            );
            comment_form($comment_args);
            ?>
        </div>

        <div class="lg:col-span-7 xl:col-span-8 max-lg:order-2 overflow-hidden">
            <?php if (have_comments()) : ?>

                <div id="bluebox-comments-container">
                    <ol class="lg:space-y-4 max-lg:mt-6 pl-4 max-lg:flex max-lg:max-w-[100vw] max-lg:gap-4 max-lg:overflow-x-auto max-lg:snap-x max-lg:snap-mandatory pb-4"
                        id="bluebox-comments-list">
                        <?php
                        wp_list_comments(array(
                            'style' => 'ol',
                            'callback' => 'bluebox_html5_comment',
                            'short_ping' => true,
                            'per_page' => 10,
                            'max_depth' => 1, // جلوگیری از لود پاسخ‌ها
                            'avatar_size' => 48,
                        ));
                        ?>
                    </ol>
                </div>

            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-16 px-4 bg-white/5 rounded-2xl border border-white/20 border-dashed text-center">
                    <div class="bg-white/10 p-4 rounded-full shadow-sm mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <p class="text-gray-200 font-bold text-lg">هنوز دیدگاهی ثبت نشده است</p>
                    <p class="text-sm text-gray-300 mt-2">اولین نفری باشید که دیدگاه خود را برای این مطلب می‌نویسد!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div id="replies-modal"
     class="fixed inset-0 z-[999] flex items-end lg:items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm modal-overlay cursor-pointer"></div>

    <div class="bg-white w-full lg:w-[500px] max-h-[85vh] lg:max-h-[70vh] rounded-t-3xl lg:rounded-3xl relative z-10 flex flex-col transform translate-y-full lg:translate-y-10 lg:scale-95 transition-all duration-300"
         id="replies-modal-content">

        <header class="flex items-center justify-between p-5 border-b border-gray-100 shrink-0">
            <h3 class="font-black text-gray-800 text-lg flex items-center gap-2">
                پاسخ‌ها به <span id="modal-author-name" class="text-primary"></span>
            </h3>
            <button class="modal-close bg-gray-100 hover:bg-red-50 text-gray-500 hover:text-red-500 p-2 rounded-full transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </header>

        <div id="modal-replies-list" class="p-5 overflow-y-auto flex-grow flex flex-col gap-4">
            <div id="modal-loading" class="flex flex-col items-center justify-center py-10 hidden">
                <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none"
                     viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-500 mt-3 font-bold">در حال دریافت پاسخ‌ها...</span>
            </div>

            <div id="modal-replies-container" class="flex flex-col gap-4"></div>
        </div>
    </div>
</div>

<style>
    #bluebox-comments-list .children {
        margin-top: 1rem;
        padding-right: 1rem;
        border-right: 2px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        list-style: none;
    }

    #bluebox-comments-list .children li {
        width: 100% !important;
        flex-shrink: 0;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        // --- Modal Variables ---
        const modal = document.getElementById('replies-modal');
        const modalContent = document.getElementById('replies-modal-content');
        const modalLoading = document.getElementById('modal-loading');
        const modalRepliesContainer = document.getElementById('modal-replies-container');
        const modalAuthorName = document.getElementById('modal-author-name');

        function openModal() {
            modal.classList.remove('opacity-0', 'pointer-events-none');
            setTimeout(() => {
                modalContent.classList.remove('translate-y-full', 'lg:translate-y-10', 'lg:scale-95');
                modalContent.classList.add('translate-y-0', 'lg:translate-y-0', 'lg:scale-100');
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modalContent.classList.remove('translate-y-0', 'lg:translate-y-0', 'lg:scale-100');
            modalContent.classList.add('translate-y-full', 'lg:translate-y-10', 'lg:scale-95');
            setTimeout(() => {
                modal.classList.add('opacity-0', 'pointer-events-none');
                modalRepliesContainer.innerHTML = '';
            }, 300);
            document.body.style.overflow = '';
        }

        document.querySelectorAll('.modal-close, .modal-overlay').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });

        // --- Event Delegation ---
        document.addEventListener('click', function (e) {

            // 1. Reply Button (Scroll to Form)
            const replyBtn = e.target.closest('.custom-reply-action-btn');
            if (replyBtn) {
                e.preventDefault();

                const commentId = replyBtn.getAttribute('data-comment-id');
                const authorName = replyBtn.getAttribute('data-author');

                const parentInput = document.getElementById('comment_parent');
                if (parentInput) parentInput.value = commentId;

                document.getElementById('reply-to-name').innerText = authorName;
                document.getElementById('custom-reply-ui').classList.remove('hidden');

                const formWrapper = document.getElementById('comment-form-wrapper');
                const yOffset = -20;
                const y = formWrapper.getBoundingClientRect().top + window.pageYOffset + yOffset;
                window.scrollTo({top: y, behavior: 'smooth'});

                setTimeout(() => {
                    const commentArea = document.getElementById('comment');
                    if (commentArea) commentArea.focus();
                }, 300);
            }

            // 2. View Replies Button (Open Modal & AJAX)
            const viewRepliesBtn = e.target.closest('.view-replies-btn');
            if (viewRepliesBtn) {
                e.preventDefault();
                const commentId = viewRepliesBtn.getAttribute('data-comment-id');
                const author = viewRepliesBtn.getAttribute('data-author');

                modalAuthorName.innerText = author;
                modalRepliesContainer.innerHTML = '';
                modalLoading.classList.remove('hidden');
                openModal();

                const ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';
                const formData = new FormData();
                formData.append('action', 'fetch_comment_replies');
                formData.append('parent_id', commentId);

                fetch(ajaxUrl, {method: 'POST', body: formData})
                    .then(res => res.json())
                    .then(data => {
                        modalLoading.classList.add('hidden');
                        if (data.success) {
                            modalRepliesContainer.innerHTML = data.data;
                        } else {
                            modalRepliesContainer.innerHTML = '<p class="text-center text-sm text-red-500 py-5">خطا در دریافت پاسخ‌ها.</p>';
                        }
                    })
                    .catch(err => {
                        modalLoading.classList.add('hidden');
                        console.error(err);
                    });
            }
        });

        // 3. Cancel Reply
        const cancelBtn = document.getElementById('cancel-reply-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const parentInput = document.getElementById('comment_parent');
                if (parentInput) parentInput.value = '0';

                document.getElementById('custom-reply-ui').classList.add('hidden');
                document.getElementById('comment').value = '';
            });
        }
    });
</script>