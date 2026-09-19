<x-brand-layout title="إعداد الملف التعليمي للطفل — سنابل IQ">
    <main class="w-full min-h-screen bg-surface py-10 px-gutter">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <x-brand.logo variant="official" :show-tagline="true" />
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container font-label-md text-label-md font-semibold">
                    <x-brand.icon name="child_care" class="text-[18px]" />
                    إعداد ملف الطفل
                </span>
            </div>

            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-l from-primary-fixed/40 via-surface-container-lowest to-secondary-container/20 p-6 md:p-8 shadow-brand-card mb-8">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <img src="{{ asset('brand/mascot-sanbal.jpg') }}" alt="سنبل" class="w-28 h-28 rounded-full object-cover shadow-lg">
                    <div class="text-center md:text-right">
                        <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold">إعداد الملف التعليمي للطفل</h1>
                        <p class="font-body-md text-body-md text-on-surface-variant mt-2">
                            أخبرنا عن طفلك لنخصص له الدروس، المحطات التفاعلية، ولوحة المتابعة الأسبوعية.
                        </p>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-2xl bg-secondary-container/50 text-on-secondary-container p-4 font-body-sm text-body-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('onboarding.child.store') }}" class="bg-surface-container-lowest rounded-3xl p-6 md:p-8 shadow-brand-card space-y-6">
                @csrf

                <div>
                    <label for="name" class="font-label-md text-label-md font-semibold text-on-surface">اسم الطفل</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        dir="rtl"
                        lang="ar"
                        class="mt-2 block w-full rounded-2xl border-0 bg-surface-container-low py-3 px-4 font-body-md text-body-md text-on-surface shadow-sm focus:outline-none focus:ring-4 focus:ring-primary/15"
                        placeholder="مثال: سارة"
                    >
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="grade_level" class="font-label-md text-label-md font-semibold text-on-surface">الصف الدراسي</label>
                        <select
                            id="grade_level"
                            name="grade_level"
                            required
                            class="mt-2 block w-full rounded-2xl border-0 bg-surface-container-low py-3 px-4 font-body-md text-body-md text-on-surface shadow-sm focus:outline-none focus:ring-4 focus:ring-primary/15"
                        >
                            @foreach (range(1, 6) as $grade)
                                <option value="{{ $grade }}" @selected((int) old('grade_level', 1) === $grade)>
                                    الصف {{ $grade }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('grade_level')" class="mt-2" />
                    </div>

                    <div>
                        <label for="school_term" class="font-label-md text-label-md font-semibold text-on-surface">الفصل الدراسي</label>
                        <select
                            id="school_term"
                            name="school_term"
                            required
                            class="mt-2 block w-full rounded-2xl border-0 bg-surface-container-low py-3 px-4 font-body-md text-body-md text-on-surface shadow-sm focus:outline-none focus:ring-4 focus:ring-primary/15"
                        >
                            @foreach (range(1, 2) as $term)
                                <option value="{{ $term }}" @selected((int) old('school_term', 1) === $term)>
                                    الفصل {{ $term }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('school_term')" class="mt-2" />
                    </div>
                </div>

                <button type="submit" class="w-full py-3.5 rounded-full bg-primary hover:bg-primary-container text-on-primary font-title-md text-title-md font-bold flex items-center justify-center gap-2 shadow-brand-cta transition-all">
                    <span>متابعة إلى لوحة التعلم</span>
                    <x-brand.icon name="arrow_back" class="text-[20px]" />
                </button>
            </form>
        </div>
    </main>
</x-brand-layout>
