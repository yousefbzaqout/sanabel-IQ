<x-brand-layout title="استعادة كلمة المرور — سنابل IQ">
    <main class="w-full bg-surface min-h-screen flex items-center justify-center p-gutter">
        <div class="flex flex-col w-full max-w-4xl mx-auto py-space-xl px-margin-mobile md:px-margin">
            <div class="relative w-full overflow-hidden bg-surface-container-lowest rounded-xl shadow-xl p-space-lg md:p-8">
                <div class="absolute -top-24 -right-24 w-80 h-80 bg-primary-container/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col items-center text-center mb-space-xl">
                    <div class="relative flex items-center justify-center w-20 h-20 rounded-xl bg-gradient-to-tr from-primary-fixed to-primary-container shadow-md mb-space-md">
                        <x-brand.icon name="shield_person" class="text-4xl text-on-primary-fixed" filled />
                        <div class="absolute -bottom-1 -left-1 flex items-center justify-center w-7 h-7 rounded-full bg-secondary text-on-secondary shadow-sm">
                            <x-brand.icon name="lock_reset" class="text-sm" />
                        </div>
                    </div>
                    <x-brand.logo variant="official" class="mb-space-xs justify-center" />
                    <h1 class="font-headline-lg text-headline-lg text-on-surface mb-space-xs">استعادة كلمة المرور لحساب سنابل IQ</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant max-w-lg">
                        أدخل بريدك الإلكتروني المسجّل وسنرسل لك رابط إعادة التعيين الآمن فوراً.
                    </p>
                </div>

                <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
                    <div class="lg:col-span-5 flex flex-col gap-space-md bg-surface-container-low p-space-lg rounded-xl">
                        <div class="flex items-center justify-between pb-space-xs">
                            <span class="font-label-lg text-label-lg text-primary">تحديد الحساب</span>
                            <x-brand.icon name="verified_user" class="text-secondary text-base" />
                        </div>

                        <x-auth-session-status class="mb-2" :status="session('status')" />

                        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-space-md">
                            @csrf
                            <div class="flex flex-col gap-space-xs text-right">
                                <label class="font-label-lg text-label-lg text-on-surface font-semibold" for="email">البريد الإلكتروني</label>
                                <div class="relative flex items-center">
                                    <span class="absolute right-3 text-secondary pointer-events-none">
                                        <x-brand.icon name="mail_lock" class="text-xl" />
                                    </span>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value="{{ old('email') }}"
                                        required
                                        autofocus
                                        dir="ltr"
                                        class="w-full pl-4 pr-11 py-3 bg-surface-container-lowest text-on-surface font-body-md text-body-md rounded-lg shadow-sm placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary-container text-left transition-all"
                                        placeholder="name@school.edu"
                                    >
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-1" />
                            </div>

                            <button type="submit" class="w-full flex items-center justify-center gap-space-xs py-3.5 px-space-md bg-primary hover:bg-primary-container text-on-primary font-title-md text-title-md rounded-full shadow-md hover:shadow-lg transition-all">
                                <x-brand.icon name="send" class="text-xl" />
                                <span>إرسال رابط إعادة التعيين</span>
                            </button>
                        </form>

                        <a href="{{ route('login') }}" class="text-center font-label-md text-label-md text-secondary font-semibold hover:underline">
                            العودة لتسجيل الدخول
                        </a>
                    </div>

                    <div class="lg:col-span-7 flex flex-col gap-space-md bg-surface-container-lowest p-space-lg rounded-xl shadow-sm">
                        <div class="flex items-center gap-space-xs">
                            <span class="flex h-2.5 w-2.5 rounded-full bg-secondary animate-ping"></span>
                            <span class="font-label-lg text-label-lg text-secondary font-bold">إرشادات الأمان</span>
                        </div>
                        <ul class="flex flex-col gap-3 font-label-md text-label-md text-on-surface-variant list-none p-0 m-0 text-right">
                            <li class="flex items-start gap-2">
                                <x-brand.icon name="mark_email_unread" class="text-xs text-secondary mt-0.5" />
                                <span>لم يصلك الإشعار؟ تحقق من مجلد الرسائل غير المرغوب فيها (Spam).</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <x-brand.icon name="contact_support" class="text-xs text-primary mt-0.5" />
                                <span>تواصل مع إدارة مدرستك إذا فقدت الوصول إلى البريد المسجّل.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <x-brand.icon name="encrypted" class="text-xs text-secondary mt-0.5" />
                                <span>الرابط صالح لفترة محدودة ولا يُشارك مع أي طرف آخر.</span>
                            </li>
                        </ul>
                        <div class="mt-2 flex items-center gap-3 p-4 rounded-2xl bg-primary-fixed/30">
                            <img src="{{ asset('brand/mascot-sanbal.jpg') }}" alt="" class="w-12 h-12 rounded-full object-cover">
                            <p class="font-body-sm text-body-sm text-on-primary-fixed font-semibold">سنبل يقول: لا تقلق، سنعيدك للدروس بسرعة!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</x-brand-layout>
