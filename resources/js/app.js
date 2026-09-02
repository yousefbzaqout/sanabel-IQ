import Alpine from 'alpinejs';
import './echo';
import './push-notifications';

window.Alpine = Alpine;

window.parentRealtime = ({ parentId, initialUnreadCount }) => ({
    parentId,
    unreadCount: initialUnreadCount,
    toasts: [],
    toastSequence: 0,

    init() {
        window.addEventListener('parent-unread-count-updated', (event) => {
            if (typeof event.detail?.count === 'number') {
                this.unreadCount = event.detail.count;
            } else {
                this.unreadCount += 1;
            }
        });

        if (typeof window.Echo === 'undefined') {
            return;
        }

        window.Echo.private(`parent.${this.parentId}`)
            .listen('.activity.completed', (payload) => {
                this.pushToast(
                    `🎉 أكمل ${payload.child_name} نشاط ${payload.activity_title} وحصل على ${payload.xp_earned} XP!`,
                    'success',
                );
                this.incrementUnreadCount();
            })
            .listen('.goal.achieved', (payload) => {
                const subjectSuffix = payload.subject ? ` في ${payload.subject}` : '';

                this.pushToast(
                    `🏆 ${payload.child_name} حقق هدف التعلم${subjectSuffix}!`,
                    'celebration',
                );
                this.incrementUnreadCount();
            });
    },

    incrementUnreadCount() {
        this.unreadCount += 1;
        window.dispatchEvent(new CustomEvent('parent-unread-count-updated', {
            detail: { count: this.unreadCount },
        }));
    },

    pushToast(message, tone = 'success') {
        const id = ++this.toastSequence;
        this.toasts.push({ id, message, tone, visible: true });

        window.setTimeout(() => {
            const toast = this.toasts.find((item) => item.id === id);

            if (toast) {
                toast.visible = false;
            }
        }, 6000);

        window.setTimeout(() => {
            this.toasts = this.toasts.filter((item) => item.id !== id);
        }, 6500);
    },
});

Alpine.start();
