<div
    x-data="{
        visible: localStorage.getItem('sanabel-push-dismissed') !== '1' && window.pushNotifications?.isSupported?.(),
        dismissed: false,
        enabling: false,
        async enablePush() {
            if (!window.pushNotifications?.enable) {
                return;
            }

            this.enabling = true;
            const enabled = await window.pushNotifications.enable();
            this.enabling = false;

            if (enabled) {
                this.visible = false;
                localStorage.setItem('sanabel-push-dismissed', '1');
            }
        },
        dismiss() {
            this.visible = false;
            this.dismissed = true;
            localStorage.setItem('sanabel-push-dismissed', '1');
        },
    }"
    x-show="visible && !dismissed"
    x-cloak
    class="border-b border-indigo-200 bg-indigo-50 dark:border-indigo-800 dark:bg-indigo-950/40"
>
    <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <p class="text-sm font-medium text-indigo-950 dark:text-indigo-100">
            فعّل إشعارات الهاتف لتصلك تحديثات أطفالك لحظة بلحظة
        </p>
        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="enablePush()"
                :disabled="enabling"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
            >
                <span x-show="!enabling">{{ __('Enable notifications') }}</span>
                <span x-show="enabling">{{ __('Enabling...') }}</span>
            </button>
            <button
                type="button"
                @click="dismiss()"
                class="inline-flex items-center rounded-md px-3 py-2 text-sm text-indigo-700 hover:text-indigo-900 dark:text-indigo-300 dark:hover:text-indigo-100"
            >
                {{ __('Not now') }}
            </button>
        </div>
    </div>
</div>
