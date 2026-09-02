import Alpine from 'alpinejs';
import './echo';
import './push-notifications';
import { registerAudioStore } from './audio-narration';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    registerAudioStore(Alpine);
    Alpine.store('audio').init();
});

window.immersiveQuizFx = () => ({
    burstConfetti(count = 80) {
        const canvas = this.$refs.confetti;

        if (! canvas) {
            return;
        }

        const context = canvas.getContext('2d');

        if (! context) {
            return;
        }

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const pieces = Array.from({ length: count }, () => ({
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height * -0.3,
            r: 4 + Math.random() * 6,
            c: ['#34d399', '#fbbf24', '#60a5fa', '#f472b6', '#a78bfa'][Math.floor(Math.random() * 5)],
            vy: 2 + Math.random() * 4,
            vx: -2 + Math.random() * 4,
            life: 60 + Math.floor(Math.random() * 40),
        }));

        const tick = () => {
            context.clearRect(0, 0, canvas.width, canvas.height);

            pieces.forEach((piece) => {
                piece.x += piece.vx;
                piece.y += piece.vy;
                piece.life -= 1;
                context.fillStyle = piece.c;
                context.beginPath();
                context.arc(piece.x, piece.y, piece.r, 0, Math.PI * 2);
                context.fill();
            });

            if (pieces.some((piece) => piece.life > 0 && piece.y < canvas.height + 20)) {
                window.requestAnimationFrame(tick);
            } else {
                context.clearRect(0, 0, canvas.width, canvas.height);
            }
        };

        window.requestAnimationFrame(tick);
    },

    celebrationBlast() {
        this.burstConfetti(180);
        window.setTimeout(() => this.burstConfetti(120), 350);
        window.setTimeout(() => this.burstConfetti(90), 700);
    },
});

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
