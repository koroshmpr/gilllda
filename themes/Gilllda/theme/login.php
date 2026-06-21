<?php
/**
 * Template Name: Login
 */
get_header();
?>
    <section dir="rtl" class="flex items-center max-lg:px-5 justify-center min-h-[93.5vh] bg-primary py-10">
        <div x-data="otpAuth()" class="w-full container max-w-96 transition-all duration-500 p-5 bg-white rounded-2xl shadow-lg">
            <?php get_template_part('template-parts/global/logo', null, ['logoSize' => 'max-h-16 w-auto']); ?>
            <h1 class="text-2xl font-bold text-center text-gray-800 my-3"><?php the_title(); ?></h1>

            <template x-if="errorMessage">
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm text-center" x-text="errorMessage"></div>
            </template>

            <div class="relative min-h-64 mt-6 p-3 overflow-hidden">

                <div x-show="step === 1"
                     x-transition:enter="transition transform ease-out duration-300 delay-100"
                     x-transition:enter-start="-translate-x-full opacity-0"
                     x-transition:enter-end="translate-x-0 opacity-100"
                     x-transition:leave="transition transform ease-in duration-300 absolute w-full top-0 left-0"
                     x-transition:leave-start="translate-x-0 opacity-100"
                     x-transition:leave-end="translate-x-full opacity-0"
                     class="w-full">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">شماره موبایل</label>
                        <input type="tel"
                               x-model="phone"
                               @keydown.enter="sendOtp()"
                               placeholder="09123456789"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-left dir-ltr">
                    </div>
                    <button @click="sendOtp()" :disabled="isLoading" class="w-full bg-primary text-white font-bold p-4 rounded-lg hover:bg-primary/90 cursor-pointer transition disabled:opacity-50">
                        <span x-show="!isLoading">ارسال کد تایید</span>
                        <span x-show="isLoading">در حال ارسال...</span>
                    </button>

                    <div class="mt-6 border-t border-gray-200 pt-4 text-center">
                        <button @click="step = 4; errorMessage = ''" class="text-sm text-gray-600 hover:text-primary transition font-medium">
                            ورود با ایمیل و رمز عبور
                        </button>
                    </div>
                </div>

                <div x-show="step === 2" style="display: none;"
                     x-transition:enter="transition transform ease-out duration-300 delay-100"
                     x-transition:enter-start="-translate-x-full opacity-0"
                     x-transition:enter-end="translate-x-0 opacity-100"
                     x-transition:leave="transition transform ease-in duration-300 absolute w-full top-0 left-0"
                     x-transition:leave-start="translate-x-0 opacity-100"
                     x-transition:leave-end="translate-x-full opacity-0"
                     class="w-full">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">کد تایید پیامک شده</label>
                        <input type="text" x-model="otp" @keydown.enter="verifyOtp()" autocomplete="one-time-code" inputmode="numeric" placeholder="- - - -" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-center tracking-widest text-xl">
                    </div>
                    <button @click="verifyOtp()" :disabled="isLoading" class="w-full bg-blue-600 text-white font-bold p-4 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 mb-3">
                        <span x-show="!isLoading">تایید و ادامه</span>
                        <span x-show="isLoading">در حال بررسی...</span>
                    </button>
                    <div class="flex items-center justify-between mt-4 px-2 text-sm">
                        <button @click="sendOtp()" :disabled="timer > 0 || isLoading" class="text-blue-600 font-semibold hover:text-blue-800 transition disabled:text-gray-400 disabled:cursor-not-allowed">ارسال مجدد کد</button>
                        <span x-show="timer > 0" class="text-gray-500 font-medium tracking-wide dir-ltr" x-text="formattedTimer"></span>
                    </div>
                    <button @click="step = 1; clearInterval(intervalId);" class="w-fit px-3 py-1 bg-gray-100 mx-auto mt-4 flex gap-2 items-center text-sm text-gray-500 hover:text-gray-800 transition rounded">اصلاح شماره موبایل</button>
                </div>

                <div x-show="step === 3" style="display: none;"
                     x-transition:enter="transition transform ease-out duration-300 delay-100"
                     x-transition:enter-start="-translate-x-full opacity-0"
                     x-transition:enter-end="translate-x-0 opacity-100"
                     class="w-full">
                    <div class="space-y-4 mb-6">
                        <div><label class="block text-gray-700 text-sm font-bold mb-1">نام و نام خانوادگی</label><input type="text" x-model="name" @keydown.enter="registerUser()" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary"></div>
                        <div><label class="block text-gray-700 text-sm font-bold mb-1">نام کاربری (انگلیسی)</label><input type="text" x-model="username" @keydown.enter="registerUser()" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-left dir-ltr"></div>
                        <div><label class="block text-gray-700 text-sm font-bold mb-1">ایمیل</label><input type="email" x-model="email" @keydown.enter="registerUser()" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-left dir-ltr"></div>
                    </div>
                    <button @click="registerUser()" :disabled="isLoading" class="w-full bg-green-600 text-white font-bold p-4 rounded-lg hover:bg-green-700 transition disabled:opacity-50">
                        <span x-show="!isLoading">تکمیل ثبت‌نام</span><span x-show="isLoading">در حال ثبت...</span>
                    </button>
                </div>

                <div x-show="step === 4" style="display: none;"
                     x-transition:enter="transition transform ease-out duration-300 delay-100"
                     x-transition:enter-start="-translate-x-full opacity-0"
                     x-transition:enter-end="translate-x-0 opacity-100"
                     x-transition:leave="transition transform ease-in duration-300 absolute w-full top-0 left-0"
                     x-transition:leave-start="translate-x-0 opacity-100"
                     x-transition:leave-end="translate-x-full opacity-0"
                     class="w-full">
                    <div class="space-y-4 mb-6">
                        <div><label class="block text-gray-700 text-sm font-bold mb-1">ایمیل یا نام کاربری</label><input type="text" x-model="loginUsername" @keydown.enter="loginWithPassword()" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-left dir-ltr"></div>
                        <div><label class="block text-gray-700 text-sm font-bold mb-1">رمز عبور</label><input type="password" x-model="loginPassword" @keydown.enter="loginWithPassword()" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-left dir-ltr"></div>
                    </div>
                    <button @click="loginWithPassword()" :disabled="isLoading" class="w-full bg-gray-800 text-white font-bold p-4 rounded-lg hover:bg-gray-900 cursor-pointer transition disabled:opacity-50">
                        <span x-show="!isLoading">ورود</span><span x-show="isLoading">در حال بررسی...</span>
                    </button>
                    <div class="mt-5 flex flex-col gap-3 text-center">
                        <button @click="step = 5; errorMessage = ''" class="text-sm text-blue-600 hover:text-blue-800 transition font-medium">حساب کاربری ندارید؟ ثبت‌نام کنید</button>
                        <button @click="step = 1; errorMessage = ''" class="text-sm text-gray-500 hover:text-gray-800 transition">بازگشت به ورود با پیامک</button>
                    </div>
                </div>

                <div x-show="step === 5" style="display: none;"
                     x-transition:enter="transition transform ease-out duration-300 delay-100"
                     x-transition:enter-start="-translate-x-full opacity-0"
                     x-transition:enter-end="translate-x-0 opacity-100"
                     x-transition:leave="transition transform ease-in duration-300 absolute w-full top-0 left-0"
                     x-transition:leave-start="translate-x-0 opacity-100"
                     x-transition:leave-end="translate-x-full opacity-0"
                     class="w-full">
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">نام و نام خانوادگی</label>
                            <input type="text" x-model="name" @keydown.enter="registerWithPassword()" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">شماره موبایل</label>
                            <input type="tel" x-model="regPhone" @keydown.enter="registerWithPassword()" placeholder="09123456789" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-left dir-ltr">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">نام کاربری (انگلیسی)</label>
                            <input type="text" x-model="username" @keydown.enter="registerWithPassword()" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-left dir-ltr">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">ایمیل</label>
                            <input type="email" x-model="email" @keydown.enter="registerWithPassword()" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-left dir-ltr">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-1">رمز عبور</label>
                            <input type="password"
                                   x-model="regPassword"
                                   @input="evaluatePasswordStrength()"
                                   @keydown.enter="registerWithPassword()"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary text-left dir-ltr">

                            <div x-show="regPassword.length > 0" x-transition class="mt-2">
                                <div class="flex justify-between items-center mb-1 text-xs font-bold" :class="strengthTextColor">
                                    <span x-text="strengthText"></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 flex gap-1 overflow-hidden dir-ltr">
                                    <div class="h-1.5 transition-all duration-300 w-1/4" :class="passwordScore >= 1 ? strengthBgColor : 'bg-transparent'"></div>
                                    <div class="h-1.5 transition-all duration-300 w-1/4" :class="passwordScore >= 2 ? strengthBgColor : 'bg-transparent'"></div>
                                    <div class="h-1.5 transition-all duration-300 w-1/4" :class="passwordScore >= 3 ? strengthBgColor : 'bg-transparent'"></div>
                                    <div class="h-1.5 transition-all duration-300 w-1/4" :class="passwordScore >= 4 ? strengthBgColor : 'bg-transparent'"></div>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1 leading-tight">
                                    برای امنیت بیشتر از ترکیب حروف بزرگ، کوچک، اعداد و نمادها (!@#$) استفاده کنید. حداقل ۸ کاراکتر.
                                </p>
                            </div>
                        </div>

                    </div>
                    <button @click="registerWithPassword()" :disabled="isLoading" class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700 transition disabled:opacity-50 mt-2">
                        <span x-show="!isLoading">ثبت‌نام</span>
                        <span x-show="isLoading">در حال ثبت...</span>
                    </button>

                    <div class="mt-5 flex flex-col gap-3 text-center">
                        <button @click="step = 4; errorMessage = ''" class="text-sm text-blue-600 hover:text-blue-800 transition font-medium">قبلا ثبت‌نام کرده‌اید؟ وارد شوید</button>
                        <button @click="step = 1; errorMessage = ''" class="text-sm text-gray-500 hover:text-gray-800 transition">بازگشت به ورود با پیامک</button>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('otpAuth', () => ({
                step: 1,
                phone: '',
                regPhone: '',
                otp: '',
                name: '',
                username: '',
                email: '',
                loginUsername: '',
                loginPassword: '',
                regPassword: '',
                isLoading: false,
                errorMessage: '',
                timer: 0,
                intervalId: null,
                ajaxUrl: '/wp-admin/admin-ajax.php',

                // متغیرهای قدرت رمز عبور
                passwordScore: 0,
                strengthText: '',
                strengthTextColor: '',
                strengthBgColor: '',

                get formattedTimer() {
                    const minutes = Math.floor(this.timer / 60).toString().padStart(2, '0');
                    const seconds = (this.timer % 60).toString().padStart(2, '0');
                    return `${minutes}:${seconds}`;
                },

                startTimer(duration) {
                    this.timer = duration;
                    clearInterval(this.intervalId);
                    this.intervalId = setInterval(() => {
                        if (this.timer > 0) {
                            this.timer--;
                        } else {
                            clearInterval(this.intervalId);
                        }
                    }, 1000);
                },

                // تابع ارزیابی قدرت رمز عبور
                evaluatePasswordStrength() {
                    let score = 0;
                    let pass = this.regPassword;

                    if (!pass) {
                        this.passwordScore = 0;
                        return;
                    }

                    if (pass.length >= 8) score += 1;
                    if (/[a-z]/.test(pass) && /[A-Z]/.test(pass)) score += 1; // ترکیب حروف بزرگ و کوچک
                    if (/[0-9]/.test(pass)) score += 1; // داشتن عدد
                    if (/[^A-Za-z0-9]/.test(pass)) score += 1; // کاراکتر خاص

                    this.passwordScore = score;

                    if (score <= 1) {
                        this.strengthText = 'خیلی ضعیف';
                        this.strengthTextColor = 'text-red-600';
                        this.strengthBgColor = 'bg-red-500';
                    } else if (score === 2) {
                        this.strengthText = 'متوسط';
                        this.strengthTextColor = 'text-orange-500';
                        this.strengthBgColor = 'bg-orange-400';
                    } else if (score === 3) {
                        this.strengthText = 'خوب';
                        this.strengthTextColor = 'text-yellow-500';
                        this.strengthBgColor = 'bg-yellow-400';
                    } else if (score === 4) {
                        this.strengthText = 'بسیار قوی';
                        this.strengthTextColor = 'text-green-600';
                        this.strengthBgColor = 'bg-green-500';
                    }
                },

                 async sendRequest(action, data) {
                    this.isLoading = true;
                    this.errorMessage = '';
                    const formData = new FormData();
                    formData.append('action', action);
                    if (typeof jsData !== 'undefined' && jsData.nonce) {
                        formData.append('security', jsData.nonce);
                    }
                    for (const key in data) formData.append(key, data[key]);

                    try {
                        const response = await fetch(this.ajaxUrl, { method: 'POST', body: formData });
                        const result = await response.json();
                        this.isLoading = false;
                        return result;
                    } catch (error) {
                        this.isLoading = false;
                        this.errorMessage = 'خطای ارتباط با سرور';
                        return { success: false };
                    }
                },

                async sendOtp() {
                    if (!this.phone.match(/^09\d{9}$/)) {
                        this.errorMessage = 'شماره موبایل نامعتبر است.';
                        return;
                    }
                    const res = await this.sendRequest('kavenegar_send_otp', { phone: this.phone });
                    if (res.success) {
                        this.step = 2;
                        this.otp = '';
                        const countdown = (res.data && res.data.timer) ? parseInt(res.data.timer) : 60;
                        this.startTimer(countdown);
                    } else {
                        this.errorMessage = res.data.message || 'خطا در ارسال کد';
                    }
                },

                async verifyOtp() {
                    if (this.otp.length < 4) return;
                    const res = await this.sendRequest('kavenegar_verify_otp', { phone: this.phone, otp: this.otp });
                    if (res.success) {
                        clearInterval(this.intervalId);
                        if (res.data.action === 'logged_in') {
                            window.location.href = '/my-account';
                        } else if (res.data.action === 'needs_registration') {
                            this.step = 3;
                        }
                    } else {
                        this.errorMessage = res.data.message || 'کد وارد شده اشتباه است';
                    }
                },

                async registerUser() {
                    if (!this.name || !this.username || !this.email) {
                        this.errorMessage = 'لطفا تمام فیلدها را پر کنید.';
                        return;
                    }
                    const res = await this.sendRequest('kavenegar_register_user', {
                        phone: this.phone,
                        otp: this.otp,
                        name: this.name,
                        username: this.username,
                        email: this.email
                    });
                    if (res.success) {
                        window.location.href = '/my-account';
                    } else {
                        this.errorMessage = res.data.message || 'خطا در ثبت‌نام';
                    }
                },

                async loginWithPassword() {
                    if (!this.loginUsername || !this.loginPassword) {
                        this.errorMessage = 'لطفا نام کاربری و رمز عبور را وارد کنید.';
                        return;
                    }
                    const res = await this.sendRequest('classic_login', {
                        username: this.loginUsername,
                        password: this.loginPassword
                    });
                    if (res.success) {
                        window.location.href = '/my-account';
                    } else {
                        this.errorMessage = res.data.message || 'اطلاعات ورود اشتباه است.';
                    }
                },

                async registerWithPassword() {
                    if (!this.name || !this.username || !this.email || !this.regPassword || !this.regPhone) {
                        this.errorMessage = 'لطفا تمام فیلدها را پر کنید.';
                        return;
                    }

                    if (!this.regPhone.match(/^09\d{9}$/)) {
                        this.errorMessage = 'شماره موبایل نامعتبر است.';
                        return;
                    }

                    if (this.passwordScore < 2) {
                        this.errorMessage = 'رمز عبور شما بسیار ضعیف است. لطفا از ترکیب قوی‌تری استفاده کنید.';
                        return;
                    }

                    const res = await this.sendRequest('classic_register', {
                        name: this.name,
                        username: this.username,
                        email: this.email,
                        password: this.regPassword,
                        phone: this.regPhone // نام متغیر در سمت چپ باید phone باشد
                    });

                    if (res.success) {
                        window.location.href = '/my-account';
                    } else {
                        this.errorMessage = res.data.message || 'خطا در ثبت‌نام.';
                    }
                }
            }))
        })
    </script>
<?php
get_footer();
?>