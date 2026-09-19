<nav class="fixed bottom-0 inset-x-0 z-40 md:hidden bg-surface-container-lowest/95 backdrop-blur-xl shadow-[0_-4px_24px_rgba(0,0,0,0.08)] py-space-xs px-space-md flex items-center justify-around" aria-label="تنقل الجوال">
    <a href="{{ route('student.dashboard') }}" @class([
        'flex flex-col items-center py-1 px-space-sm rounded-lg text-label-sm font-bold',
        'bg-primary-container text-on-primary-container font-extrabold shadow-md' => request()->routeIs('student.dashboard'),
        'text-on-surface-variant' => ! request()->routeIs('student.dashboard'),
    ])>
        <span class="material-symbols-outlined text-2xl">map</span>
        <span>الخريطة</span>
    </a>
    <a href="{{ route('student.activities.index') }}" @class([
        'flex flex-col items-center py-1 px-space-sm rounded-lg text-label-sm font-bold',
        'bg-primary-container text-on-primary-container font-extrabold shadow-md' => request()->routeIs('student.activities.*'),
        'text-on-surface-variant' => ! request()->routeIs('student.activities.*'),
    ])>
        <span class="material-symbols-outlined text-2xl">sports_esports</span>
        <span>الأنشطة</span>
    </a>
    <a href="{{ route('student.progress') }}" @class([
        'flex flex-col items-center py-1 px-space-sm rounded-lg text-label-sm font-bold',
        'bg-primary-container text-on-primary-container font-extrabold shadow-md' => request()->routeIs('student.progress'),
        'text-on-surface-variant' => ! request()->routeIs('student.progress'),
    ])>
        <span class="material-symbols-outlined text-2xl">trending_up</span>
        <span>تقدمي</span>
    </a>
    <a href="{{ route('student.badges') }}" @class([
        'flex flex-col items-center py-1 px-space-sm rounded-lg text-label-sm font-bold',
        'bg-primary-container text-on-primary-container font-extrabold shadow-md' => request()->routeIs('student.badges'),
        'text-on-surface-variant' => ! request()->routeIs('student.badges'),
    ])>
        <span class="material-symbols-outlined text-2xl">military_tech</span>
        <span>جوائزي</span>
    </a>
</nav>
