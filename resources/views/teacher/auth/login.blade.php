<x-brand-layout title="دخول المعلم — سنابل IQ">
    <main class="flex min-h-screen items-center justify-center bg-surface p-gutter">
        <div class="w-full max-w-md rounded-xl bg-surface-container-lowest p-space-lg shadow-md sm:p-space-xl">
            <div class="mb-space-lg flex flex-col items-center gap-space-sm text-center">
                <img src="{{ asset('brand/logo-official.svg') }}" alt="سنابل IQ" class="h-10 w-auto object-contain">
                <h1 class="font-headline-md text-headline-md font-bold text-on-surface">بوابة المعلم</h1>
                <p class="font-body-sm text-body-sm text-on-surface-variant">
                    سجّل دخولك لمتابعة صفّك وتعيين الدروس التفاعلية لطلاب المدرسة.
                </p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('teacher.login.store') }}" class="flex flex-col gap-space-md">
                @csrf

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="font-label-md text-label-md font-semibold text-on-surface">البريد الإلكتروني للمعلم</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        dir="ltr"
                        class="w-full rounded-lg bg-surface-container-low px-4 py-3 text-left font-body-md text-body-md text-on-surface shadow-sm focus:outline-none focus:ring-4 focus:ring-secondary/15"
                        placeholder="teacher@alamal.sanabel.test"
                    >
                    @error('email')
                        <p class="font-label-sm text-label-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password" class="font-label-md text-label-md font-semibold text-on-surface">كلمة المرور</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-lg bg-surface-container-low px-4 py-3 font-body-md text-body-md text-on-surface shadow-sm focus:outline-none focus:ring-4 focus:ring-secondary/15"
                    >
                    @error('password')
                        <p class="font-label-sm text-label-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <label class="inline-flex items-center gap-2 font-label-sm text-label-sm text-on-surface-variant">
                    <input type="checkbox" name="remember" class="rounded border-outline-variant text-secondary focus:ring-secondary/20">
                    تذكرني على هذا الجهاز
                </label>

                <button
                    type="submit"
                    class="mt-space-xs w-full rounded-full bg-secondary py-3.5 font-title-md text-title-md font-bold text-on-secondary shadow-brand-cta"
                >
                    دخول إلى صفّي
                </button>
            </form>

            <p class="mt-space-lg text-center font-label-sm text-label-sm text-on-surface-variant">
                ولي أمر أو طالب؟
                <a href="{{ route('login') }}" class="font-semibold text-secondary hover:underline">العودة لصفحة الدخول العامة</a>
            </p>
        </div>
    </main>
</x-brand-layout>
