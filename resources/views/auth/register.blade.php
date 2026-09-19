<x-brand-layout title="تسجيل ولي أمر جديد — سنابل IQ">
    <main class="w-full bg-surface min-h-screen flex items-center justify-center p-gutter">
        <div class="flex flex-col w-full max-w-6xl mx-auto py-6 px-4 md:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 pb-8">
                <x-brand.logo variant="official" :show-tagline="true" />
                <div class="flex items-center gap-3 bg-surface-container-low px-4 py-2 rounded-xl">
                    <x-brand.icon name="verified_user" class="text-secondary text-xl" />
                    <div class="text-right">
                        <span class="font-label-sm text-label-sm text-on-surface-variant block">البيانات مؤمنة ومتوافقة</span>
                        <span class="font-label-md text-label-md text-secondary font-semibold block">مع سياسات حماية بيانات الطلاب</span>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden bg-gradient-to-l from-surface-container-high via-surface-container to-surface-container-low p-6 md:p-8 rounded-3xl mb-8 shadow-brand-card">
                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-fixed text-on-primary-fixed font-label-md text-label-md mb-3">
                        <span class="w-2 h-2 rounded-full bg-primary-container animate-pulse"></span>
                        بوابة أولياء الأمور • تسجيل عائلة جديدة
                    </div>
                    <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold">إنشاء حساب ولي أمر جديد</h1>
                    <p class="font-body-md text-body-md text-on-surface-variant mt-1">خطوة واحدة تفصلك عن متابعة دقيقة لتحصيل أطفالك الأكاديمي.</p>
                </div>
                <div class="absolute -left-12 -bottom-12 w-48 h-48 rounded-full bg-primary-fixed/20 pointer-events-none blur-2xl"></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-7 bg-surface-container-lowest rounded-3xl p-6 md:p-8 shadow-brand-card">
                    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-space-md" x-data="{ showPassword: false }">
                        @csrf

                        <div class="flex flex-col gap-1.5">
                            <label for="name" class="font-label-md text-label-md font-semibold text-on-surface">الاسم الكامل لولي الأمر</label>
                            <div class="relative">
                                <div class="absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">
                                    <x-brand.icon name="person" class="text-[20px]" />
                                </div>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                                    class="w-full pr-11 pl-4 py-3 bg-surface-container-low rounded-2xl font-body-md text-body-md text-on-surface focus:outline-none focus:ring-4 focus:ring-primary/15 shadow-sm"
                                    placeholder="مثال: أحمد محمد">
                            </div>
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="font-label-md text-label-md font-semibold text-on-surface">البريد الإلكتروني</label>
                            <div class="relative">
                                <div class="absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">
                                    <x-brand.icon name="mail" class="text-[20px]" />
                                </div>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" dir="ltr"
                                    class="w-full pr-11 pl-4 py-3 bg-surface-container-low rounded-2xl font-body-md text-body-md text-left text-on-surface focus:outline-none focus:ring-4 focus:ring-primary/15 shadow-sm"
                                    placeholder="parent@email.com">
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
                            <div class="flex flex-col gap-1.5">
                                <label for="password" class="font-label-md text-label-md font-semibold text-on-surface">كلمة المرور</label>
                                <div class="relative">
                                    <div class="absolute right-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none">
                                        <x-brand.icon name="key" class="text-[20px]" />
                                    </div>
                                    <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required autocomplete="new-password"
                                        class="w-full pr-11 pl-11 py-3 bg-surface-container-low rounded-2xl font-body-md text-body-md text-on-surface focus:outline-none focus:ring-4 focus:ring-primary/15 shadow-sm">
                                    <button type="button" class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant" @click="showPassword = !showPassword">
                                        <span class="material-symbols-outlined text-[20px]" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                                    </button>
                                </div>
                                <x-input-error :messages="$errors->get('password')" class="mt-1" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="password_confirmation" class="font-label-md text-label-md font-semibold text-on-surface">تأكيد كلمة المرور</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                                    class="w-full px-4 py-3 bg-surface-container-low rounded-2xl font-body-md text-body-md text-on-surface focus:outline-none focus:ring-4 focus:ring-primary/15 shadow-sm">
                            </div>
                        </div>

                        <button type="submit" class="w-full mt-2 py-3.5 rounded-full bg-primary hover:bg-primary-container text-on-primary font-title-md text-title-md font-bold flex items-center justify-center gap-2 shadow-brand-cta transition-all">
                            <span>إنشاء الحساب والانتقال لإعداد ملف الطفل</span>
                            <x-brand.icon name="arrow_back" class="text-[20px]" />
                        </button>

                        <p class="text-center font-body-sm text-body-sm text-on-surface-variant">
                            لديك حساب بالفعل؟
                            <a href="{{ route('login') }}" class="text-secondary font-semibold hover:underline">تسجيل الدخول</a>
                        </p>
                    </form>
                </div>

                <aside class="lg:col-span-5 flex flex-col gap-4">
                    <div class="bg-surface-container-lowest rounded-3xl p-6 shadow-brand-card">
                        <img src="{{ asset('brand/mascot-sanbal.jpg') }}" alt="سنبل" class="w-24 h-24 rounded-full object-cover mx-auto shadow-md mb-4">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold text-center">سنبل بانتظار طفلك</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant text-center mt-2 leading-relaxed">
                            بعد التسجيل ستُوجَّه لإعداد الملف التعليمي للطفل ثم بوابة المتابعة الأسبوعية.
                        </p>
                    </div>
                    <div class="bg-secondary-container/40 rounded-3xl p-5">
                        <div class="flex items-start gap-3">
                            <x-brand.icon name="school" class="text-secondary text-[28px]" />
                            <div>
                                <p class="font-label-lg text-label-lg text-on-secondary-container font-bold">هل أنت معلّم أو مدير مدرسة؟</p>
                                <p class="font-body-sm text-body-sm text-on-secondary-container mt-1">استخدم بوابة الدخول الموحدة لحسابات الكادر والإدارة.</p>
                                <a href="{{ route('login') }}" class="inline-flex items-center gap-1 mt-3 font-label-md text-label-md text-secondary font-bold hover:underline">
                                    الانتقال لتسجيل الدخول
                                    <x-brand.icon name="arrow_back" class="text-[16px]" />
                                </a>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</x-brand-layout>
