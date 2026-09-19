<x-brand-layout title="تسجيل الدخول — سنابل IQ">
    <main
        class="w-full bg-surface min-h-screen flex items-center justify-center p-gutter"
        x-data="{
            role: @js(old('intended_role', in_array(request()->query('role'), ['parent', 'student'], true) ? request()->query('role') : 'parent')),
            showPassword: false,
            familyCode: @js(old('family_code', '')),
            children: [],
            selectedStudentId: @js(old('student_id')),
            pin: '',
            lookupError: '',
            lookupLoading: false,
            hints: {
                parent: 'حساب ولي الأمر والأسرة',
                student: 'حساب البطل الصغير (الطالب)'
            },
            async lookupFamily() {
                this.lookupError = '';
                this.children = [];
                this.selectedStudentId = null;
                this.pin = '';
                const code = (this.familyCode || '').toUpperCase().trim();
                if (code.length !== 6) {
                    this.lookupError = 'أدخل رمز العائلة المكوّن من 6 خانات.';
                    return;
                }
                this.lookupLoading = true;
                try {
                    const token = document.querySelector('meta[name=csrf-token]')?.content;
                    const response = await fetch(@js(route('login.child.lookup')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ family_code: code }),
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (! response.ok) {
                        this.lookupError = payload.errors?.family_code?.[0] || payload.message || 'تعذر العثور على العائلة.';
                        return;
                    }
                    this.familyCode = payload.family_code || code;
                    this.children = payload.children || [];
                } catch (error) {
                    this.lookupError = 'تعذر الاتصال. حاول مرة أخرى.';
                } finally {
                    this.lookupLoading = false;
                }
            },
            appendPin(digit) {
                if (this.pin.length >= 4) return;
                this.pin += String(digit);
            },
            clearPin() {
                this.pin = '';
            }
        }"
    >
        <div class="flex flex-col w-full">
            <div class="w-full max-w-5xl mx-auto my-auto flex flex-col lg:flex-row items-stretch justify-center gap-space-lg">
                <div class="hidden lg:flex lg:w-5/12 flex-col justify-between p-space-xl rounded-xl bg-surface-container-low text-on-surface relative overflow-hidden shadow-brand-card">
                    <div class="absolute -top-16 -right-16 w-56 h-56 bg-primary-container/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col gap-space-md">
                        <x-brand.logo variant="official" :show-tagline="true" class="mb-space-sm" />

                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-container font-label-md text-label-md text-secondary w-fit">
                            <x-brand.icon name="verified" class="text-[16px]" />
                            منظومة التعليم التفاعلي الذكي المعتمدة
                        </span>

                        <h2 class="font-headline-lg text-headline-lg text-on-surface mt-space-xs font-bold leading-snug">
                            بوابة الأسرة والأبطال الصغار
                        </h2>
                        <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
                            أولياء الأمور يتابعون الإنجاز، وكل طفل يدخل برمز العائلة ورمز PIN الخاص به.
                        </p>
                    </div>

                    <div class="relative z-10 flex flex-col gap-space-md mt-space-xl">
                        <div class="relative w-full h-44 rounded-xl overflow-hidden shadow-sm">
                            <div
                                class="w-full h-full bg-cover bg-center"
                                style="background-image: url('{{ asset('brand/classroom-hero.jpg') }}')"
                            ></div>
                            <div class="absolute inset-0 bg-gradient-to-t from-inverse-surface/80 via-inverse-surface/20 to-transparent flex items-end p-space-md">
                                <div class="flex items-center gap-space-sm">
                                    <x-brand.icon name="workspace_premium" class="text-primary-fixed-dim text-[20px]" />
                                    <p class="font-label-md text-label-md text-inverse-on-surface">نظام معتمد لتطوير نواتج التعلم</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-7/12 flex flex-col bg-surface-container-lowest rounded-xl shadow-md p-space-lg sm:p-space-xl relative">
                    <div class="w-full mb-space-md p-space-sm rounded-lg bg-surface-container-low flex flex-col sm:flex-row sm:items-center justify-between gap-space-xs">
                        <div class="flex items-center gap-space-sm">
                            <div class="w-9 h-9 rounded-lg bg-primary-fixed text-on-primary-fixed flex items-center justify-center shrink-0 shadow-sm">
                                <x-brand.icon name="login" class="text-[20px]" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-title-md text-title-md text-on-surface font-bold truncate">سنابل IQ — دخول الأسرة والطلاب</h3>
                                <p class="font-label-sm text-label-sm text-on-surface-variant">أولياء أمور • طلاب وأبناء</p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-space-lg">
                        <div class="flex items-center justify-between mb-space-xs">
                            <label class="font-label-sm text-label-sm text-on-surface-variant font-medium">اختر صفة الدخول:</label>
                            <span class="font-label-sm text-label-sm text-secondary font-semibold" x-text="hints[role]"></span>
                        </div>
                        <div class="grid grid-cols-2 p-1 rounded-full bg-surface-container gap-1" role="tablist" aria-label="اختيار دور الدخول">
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="role === 'parent'"
                                class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-full font-label-md text-label-md font-semibold transition-all duration-200"
                                :class="role === 'parent' ? 'bg-surface-container-lowest text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface'"
                                @click="role = 'parent'"
                            >
                                <span class="material-symbols-outlined text-[18px]" :class="role === 'parent' ? 'text-primary' : ''">family_restroom</span>
                                <span class="truncate">أولياء الأمور</span>
                            </button>
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="role === 'student'"
                                class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-full font-label-md text-label-md font-semibold transition-all duration-200"
                                :class="role === 'student' ? 'bg-surface-container-lowest text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface'"
                                @click="role = 'student'"
                            >
                                <span class="material-symbols-outlined text-[18px]" :class="role === 'student' ? 'text-primary' : ''">backpack</span>
                                <span class="truncate">الطلاب والأبناء</span>
                            </button>
                        </div>
                    </div>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    {{-- Parent email/password --}}
                    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-space-md" x-show="role === 'parent'" x-cloak>
                        @csrf
                        <input type="hidden" name="intended_role" value="parent">

                        <div class="flex flex-col gap-1.5">
                            <label class="font-label-md text-label-md font-semibold text-on-surface" for="email">البريد الإلكتروني لولي الأمر</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="username"
                                dir="ltr"
                                class="w-full px-4 py-3 bg-surface-container-low rounded-lg font-body-md text-body-md text-left text-on-surface focus:outline-none focus:ring-4 focus:ring-primary/15 shadow-sm"
                                placeholder="parent@sanabel.test"
                            />
                            <p class="font-label-sm text-label-sm text-on-surface-variant/80">استخدم البريد المسجل لمتابعة إنجازات أطفالك</p>
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="font-label-md text-label-md font-semibold text-on-surface" for="password">كلمة المرور</label>
                            <div class="relative flex items-center">
                                <input
                                    id="password"
                                    name="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    class="w-full px-4 py-3 bg-surface-container-low rounded-lg font-body-md text-body-md text-on-surface focus:outline-none focus:ring-4 focus:ring-primary/15 shadow-sm tracking-wider"
                                    placeholder="••••••••••••"
                                />
                                <button type="button" class="absolute left-3 p-1" @click="showPassword = !showPassword">
                                    <span class="material-symbols-outlined text-[20px]" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>

                        <button type="submit" class="w-full mt-space-xs py-3.5 px-space-lg rounded-full bg-primary text-on-primary font-title-md text-title-md font-bold shadow-brand-cta">
                            تسجيل الدخول إلى سنابل IQ
                        </button>
                    </form>

                    {{-- Student family code + PIN --}}
                    <div class="flex flex-col gap-space-md" x-show="role === 'student'" x-cloak>
                        <div class="flex flex-col gap-1.5">
                            <label class="font-label-md text-label-md font-semibold text-on-surface" for="family_code">رمز العائلة</label>
                            <div class="flex gap-2">
                                <input
                                    id="family_code"
                                    type="text"
                                    maxlength="6"
                                    dir="ltr"
                                    x-model="familyCode"
                                    @keyup.enter.prevent="lookupFamily()"
                                    class="flex-1 px-4 py-3 bg-surface-container-low rounded-lg font-body-md text-body-md text-left uppercase tracking-widest focus:outline-none focus:ring-4 focus:ring-primary/15 shadow-sm"
                                    placeholder="SNBL01"
                                />
                                <button
                                    type="button"
                                    class="px-4 rounded-full bg-secondary text-on-secondary font-label-md text-label-md font-bold"
                                    @click="lookupFamily()"
                                    :disabled="lookupLoading"
                                >
                                    <span x-text="lookupLoading ? '...' : 'عرض الأبناء'"></span>
                                </button>
                            </div>
                            <p class="font-label-sm text-label-sm text-on-surface-variant/80">سجّل دخولك لتبدأ المغامرة وجمع النقاط مع سنبل!</p>
                            <p class="font-label-sm text-label-sm text-error" x-show="lookupError" x-text="lookupError"></p>
                            <x-input-error :messages="$errors->get('family_code')" class="mt-1" />
                        </div>

                        <template x-if="children.length > 0">
                            <div class="space-y-space-sm">
                                <p class="font-label-md text-label-md font-semibold text-on-surface">اختر اسمك:</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <template x-for="child in children" :key="child.id">
                                        <button
                                            type="button"
                                            class="rounded-xl p-3 text-right border-2 transition"
                                            :class="selectedStudentId == child.id ? 'border-primary bg-primary-fixed/40' : 'border-transparent bg-surface-container-low'"
                                            @click="selectedStudentId = child.id; pin = ''"
                                            :disabled="! child.login_enabled"
                                        >
                                            <span class="font-title-md text-title-md font-bold block" x-text="child.name"></span>
                                            <span class="font-label-sm text-label-sm text-on-surface-variant" x-text="child.login_enabled ? ('الصف ' + child.grade_level) : 'الدخول غير مفعّل'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <form
                            method="POST"
                            action="{{ route('login.child') }}"
                            class="flex flex-col gap-space-md"
                            x-show="selectedStudentId"
                        >
                            @csrf
                            <input type="hidden" name="family_code" :value="familyCode">
                            <input type="hidden" name="student_id" :value="selectedStudentId">
                            <input type="hidden" name="pin" :value="pin">

                            <div>
                                <p class="font-label-md text-label-md font-semibold text-on-surface mb-2">أدخل رمز PIN (4 أرقام)</p>
                                <div class="flex justify-center gap-2 mb-3" dir="ltr">
                                    <template x-for="i in 4" :key="i">
                                        <span class="w-10 h-12 rounded-lg bg-surface-container flex items-center justify-center font-headline-md text-headline-md font-bold" x-text="pin[i-1] ? '•' : ''"></span>
                                    </template>
                                </div>
                                <div class="grid grid-cols-3 gap-2 max-w-xs mx-auto" dir="ltr">
                                    <template x-for="digit in [1,2,3,4,5,6,7,8,9]" :key="digit">
                                        <button type="button" class="py-3 rounded-xl bg-surface-container-low font-title-md text-title-md font-bold" @click="appendPin(digit)" x-text="digit"></button>
                                    </template>
                                    <button type="button" class="py-3 rounded-xl bg-surface-container font-label-md" @click="clearPin()">مسح</button>
                                    <button type="button" class="py-3 rounded-xl bg-surface-container-low font-title-md text-title-md font-bold" @click="appendPin(0)">0</button>
                                    <button type="submit" class="py-3 rounded-xl bg-primary text-on-primary font-label-md font-bold" :disabled="pin.length !== 4">دخول</button>
                                </div>
                                <x-input-error :messages="$errors->get('pin')" class="mt-2 text-center" />
                            </div>
                        </form>
                    </div>

                    <div class="mt-space-lg p-space-sm rounded-xl bg-primary-fixed/30 flex items-center justify-between gap-space-sm" x-show="role === 'parent'">
                        <a href="{{ route('register') }}" class="flex items-center gap-space-sm flex-1 min-w-0">
                            <img src="{{ asset('brand/mascot-sanbal.jpg') }}" alt="سنبل" class="w-10 h-10 rounded-full object-cover shadow-sm">
                            <div>
                                <p class="font-body-sm text-body-sm font-bold text-on-primary-fixed">ولي أمر جديد؟ أنشئ حساباً لطفلك</p>
                                <p class="font-label-sm text-label-sm text-on-primary-fixed-variant">ثم عيّن رمز PIN لكل ابن من بوابة الأسرة</p>
                            </div>
                        </a>
                    </div>

                    <p class="mt-space-md text-center font-label-sm text-label-sm text-on-surface-variant">
                        للكادر وإدارة المدرسة:
                        <a href="{{ url('/admin/login') }}" class="text-secondary font-semibold hover:underline">بوابة الإدارة</a>
                        <span class="mx-1">·</span>
                        <a href="{{ route('teacher.login') }}" class="text-secondary font-semibold hover:underline">بوابة المعلم</a>
                    </p>
                </div>
            </div>
        </div>
    </main>
</x-brand-layout>
