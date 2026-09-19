import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import './echo';
import './push-notifications';
import { registerAudioStore } from './audio-narration';

window.Alpine = Alpine;
window.Livewire = Livewire;

// Register the audio store synchronously BEFORE Livewire/Alpine start so
// first-paint @click / $store.audio bindings never race alpine:init.
registerAudioStore(Alpine);

document.addEventListener('alpine:init', () => {
    registerAudioStore(Alpine);
    try {
        Alpine.store('audio')?.init?.();
    } catch (error) {
        console.error('[sanabel-audio] init during alpine:init failed', error);
    }
});

window.immersiveQuizFx = (config = {}) => ({
    questionId: Number(config.questionId) || 0,
    audioUrl: typeof config.audioUrl === 'string' ? config.audioUrl : null,
    prompt: typeof config.prompt === 'string' ? config.prompt : '',

    init() {
        // Autoplay only pre-rendered MP3s — never block navigation on live neural TTS.
        if (this.audioUrl) {
            this.$nextTick(() => {
                window.Alpine?.store?.('audio')?.playStatic?.(this.audioUrl, this.prompt);
            });
        }
    },

    prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    },

    burstConfetti(count = 80) {
        if (this.prefersReducedMotion()) {
            return;
        }

        const canvas = this.$refs.confetti;

        if (! canvas) {
            return;
        }

        const context = canvas.getContext('2d');

        if (! context) {
            return;
        }

        const cappedCount = Math.min(Math.max(0, count), 80);

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const pieces = Array.from({ length: cappedCount }, () => ({
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
        if (this.prefersReducedMotion()) {
            return;
        }

        this.burstConfetti(80);
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

window.letterRaaLesson = (introText, demoExplainText) => ({
    step: 1,
    hotspotOpened: false,
    revealed: null,
    letterGlow: false,
    introText,
    demoExplainText,

    boot() {
        this.$nextTick(() => {
            window.setTimeout(() => this.playIntro(), 450);
        });
    },

    goTo(next) {
        this.step = next;

        if (next === 3) {
            this.$nextTick(() => {
                window.setTimeout(() => this.playDemo(), 350);
            });
        }
    },

    audioStore() {
        try {
            return window.Alpine?.store?.('audio') ?? null;
        } catch {
            return null;
        }
    },

    playIntro() {
        const audio = this.audioStore();
        audio?.unlock?.();
        audio?.speak?.(this.introText);
    },

    revealCard(key, phrase) {
        this.revealed = key;
        const audio = this.audioStore();
        audio?.unlock?.();
        audio?.speak?.(phrase);
    },

    playDemo() {
        this.letterGlow = true;
        const audio = this.audioStore();
        audio?.unlock?.();
        audio?.speak?.(this.demoExplainText);
    },
});

window.integratedLetterRaaDemo = (config = {}) => ({
    diacriticTab: 'رَ',
    popped: {},
    expectedOrder: 1,
    builtWord: '',
    wordComplete: false,
    wordPieces: config.wordPieces || { 1: 'رَ', 2: 'مَ', 3: 'لْ' },
    sequenceLength: Number(config.sequenceLength) || 3,
    sequenceVoiceTarget: config.sequenceVoiceTarget || 'رَ',
    activePosition: null,
    tracing: false,
    traceProgress: 0,
    traceOffset: 100,
    traceDone: false,
    strokePoints: [],
    strokeResult: null,
    traceFeedback: '',
    listening: false,
    voiceResult: null,
    voiceFeedback: '',
    recognition: null,
    scratching: false,
    sandCleared: false,
    sandRevealed: false,
    demoGlow: false,
    traceCompleteAudio: config.traceCompleteAudio || '',
    demoAudio: config.demoAudio || '',
    sandStory: config.sandStory || '',
    wordCompleteAudio: config.wordCompleteAudio || '',

    audioStore() {
        try {
            return window.Alpine?.store?.('audio') ?? null;
        } catch {
            return null;
        }
    },

    speak(text) {
        const audio = this.audioStore();
        audio?.unlock?.();
        audio?.speak?.(text);
    },

    speakMascot(message) {
        if (! message) {
            return;
        }

        this.speak(message);
    },

    reportToLivewire(attempts, timeSpent, masteryScore) {
        if (typeof this.$wire?.reportPerformance === 'function') {
            this.$wire.reportPerformance(attempts, timeSpent, masteryScore);
        }
    },

    recordError(conceptKey, context = {}) {
        if (typeof this.$wire?.recordLessonError === 'function') {
            this.$wire.recordLessonError(conceptKey, context);
        }
    },

    playFx(type) {
        this.audioStore()?.playFx?.(type);
    },

    csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    },

    bootStation(station) {
        if (station === 5) {
            this.$nextTick(() => this.initSand());
        }
        if (station === 6) {
            window.setTimeout(() => this.playDemo(), 400);
        }
    },

    setDiacriticTab(glyph) {
        this.diacriticTab = glyph;
        this.voiceResult = null;
        this.voiceFeedback = '';
        this.speak(`حركة ${glyph}`);
    },

    async listenPronunciation(target) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (! SpeechRecognition) {
            this.voiceResult = 'retry';
            this.voiceFeedback = 'الميكروفون غير مدعوم في هذا المتصفح';

            return;
        }

        if (this.listening && this.recognition) {
            try {
                this.recognition.stop();
            } catch {
                // ignore
            }
            this.listening = false;

            return;
        }

        this.voiceResult = null;
        this.voiceFeedback = 'تحدث الآن…';
        this.listening = true;

        const recognition = new SpeechRecognition();
        this.recognition = recognition;
        recognition.lang = 'ar-SA';
        recognition.interimResults = false;
        recognition.maxAlternatives = 3;

        recognition.onresult = async (event) => {
            const transcript = Array.from(event.results?.[0] || [])
                .map((alt) => alt.transcript || '')
                .join(' ')
                .trim();

            await this.scorePronunciation(target || this.diacriticTab, transcript || '');
        };

        recognition.onerror = () => {
            this.listening = false;
            this.voiceResult = 'retry';
            this.voiceFeedback = 'لم أسمع جيداً — حاول مرة أخرى';
            this.playFx('retry');
        };

        recognition.onend = () => {
            this.listening = false;
        };

        try {
            recognition.start();
        } catch {
            this.listening = false;
            this.voiceFeedback = 'تعذّر فتح الميكروفون';
        }
    },

    async scorePronunciation(target, transcript) {
        try {
            const response = await fetch('/student/ai/pronunciation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify({ target, transcript }),
            });

            const payload = await response.json().catch(() => ({}));

            if (response.status === 429 || payload.error === 'too_many_requests') {
                this.voiceResult = 'retry';
                this.voiceFeedback = payload.message || 'لقد تجاوزت الحد المسموح. حاول بعد قليل.';
                this.playFx('retry');

                return;
            }

            if (! response.ok) {
                this.voiceResult = 'retry';
                this.voiceFeedback = payload.message || payload.feedback || 'حدث خطأ — حاول مجدداً';
                this.playFx('retry');

                return;
            }

            this.voiceResult = payload.result || 'retry';
            this.voiceFeedback = payload.feedback || '';

            if (this.voiceResult === 'match') {
                this.playFx('correct');
                this.speak('ممتاز! نطقك واضح');
                this.reportToLivewire(1, 5, 95);
            } else {
                this.playFx('retry');
                this.recordError('diacritic_confusion', {
                    expected: target || this.diacriticTab,
                    actual: transcript || '',
                    station: 1,
                });
                this.reportToLivewire(3, 20, 40);
            }
        } catch {
            this.voiceResult = 'retry';
            this.voiceFeedback = 'حدث خطأ — حاول مجدداً';
        }
    },

    popBubble(glyph, order) {
        if (this.popped[glyph]) {
            return;
        }

        if (order !== this.expectedOrder) {
            this.playFx('retry');
            this.speak('حاول مرة أخرى بالترتيب');
            this.recordError('bubble_sequence', { station: 2, expected: this.expectedOrder, actual: order });

            return;
        }

        this.popped[glyph] = true;
        this.builtWord += this.wordPieces[order] || glyph;
        this.expectedOrder += 1;
        this.playFx('click');
        this.speak(glyph);

        if (this.expectedOrder > this.sequenceLength) {
            this.wordComplete = true;
            this.playFx('correct');
            this.speak(this.wordCompleteAudio);
            this.reportToLivewire(1, 10, 90);
        }
    },

    activatePosition(id, audio) {
        this.activePosition = id;
        this.speak(audio);
    },

    svgPointFromEvent(event) {
        const svg = event.currentTarget?.querySelector?.('svg');
        if (! svg) {
            return null;
        }

        const rect = svg.getBoundingClientRect();
        const clientX = event.clientX ?? event.touches?.[0]?.clientX;
        const clientY = event.clientY ?? event.touches?.[0]?.clientY;

        if (clientX === undefined || clientY === undefined || rect.width === 0 || rect.height === 0) {
            return null;
        }

        return {
            x: ((clientX - rect.left) / rect.width) * 140,
            y: ((clientY - rect.top) / rect.height) * 140,
        };
    },

    startTrace(event) {
        if (this.traceDone) {
            return;
        }

        this.tracing = true;
        this.strokePoints = [];
        this.strokeResult = null;
        this.traceFeedback = '';
        this.moveTrace(event);
    },

    moveTrace(event) {
        if (! this.tracing || this.traceDone) {
            return;
        }

        const point = this.svgPointFromEvent(event);
        if (point) {
            this.strokePoints.push(point);
        }

        this.traceProgress = Math.min(100, this.traceProgress + 4);
        this.traceOffset = Math.max(0, 100 - this.traceProgress);

        if (this.traceProgress >= 100) {
            void this.completeTrace();
        }
    },

    endTrace() {
        this.tracing = false;
        if (! this.traceDone && this.strokePoints.length >= 4) {
            void this.completeTrace();
        }
    },

    async completeTrace() {
        if (this.traceDone) {
            return;
        }

        this.traceDone = true;
        this.tracing = false;
        this.traceOffset = 0;

        try {
            const response = await fetch('/student/ai/stroke', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify({ points: this.strokePoints }),
            });

            const payload = await response.json().catch(() => ({}));

            if (response.status === 429 || payload.error === 'too_many_requests') {
                this.strokeResult = 'retry';
                this.traceFeedback = payload.message || 'لقد تجاوزت الحد المسموح. حاول بعد قليل.';
                this.playFx('retry');
                this.traceDone = false;

                return;
            }

            if (! response.ok) {
                this.strokeResult = 'retry';
                this.traceFeedback = payload.message || payload.feedback || 'حدث خطأ — حاول مجدداً';
                this.playFx('retry');
                this.traceDone = false;

                return;
            }

            this.strokeResult = payload.result || 'retry';
            this.traceFeedback = payload.feedback || '';

            if (payload.result === 'match') {
                this.playFx('correct');
                this.speak(this.traceCompleteAudio || 'أحسنت! اتجاه الرسم صحيح');
                this.reportToLivewire(1, 8, 92);
            } else {
                this.playFx('retry');
                this.speak(payload.feedback || 'ابدأ من الأعلى وانزل بقوس حرف الراء');
                this.recordError('incomplete_trace', { station: 4, score: payload.score || 0 });
                this.reportToLivewire(3, 25, 45);
                // Allow another attempt after a soft fail.
                window.setTimeout(() => {
                    this.traceDone = false;
                    this.traceProgress = 0;
                    this.traceOffset = 100;
                    this.strokePoints = [];
                }, 1200);
            }
        } catch {
            this.strokeResult = 'retry';
            this.playFx('correct');
            this.speak(this.traceCompleteAudio);
        }
    },

    initSand() {
        const canvas = this.$refs.sandCanvas;
        if (! canvas) {
            return;
        }

        const rect = canvas.parentElement.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        canvas.width = Math.max(1, Math.floor(rect.width * dpr));
        canvas.height = Math.max(1, Math.floor(rect.height * dpr));
        canvas.style.width = `${rect.width}px`;
        canvas.style.height = `${rect.height}px`;

        const ctx = canvas.getContext('2d');
        if (! ctx) {
            return;
        }

        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        const gradient = ctx.createLinearGradient(0, 0, 0, rect.height);
        gradient.addColorStop(0, '#e7c37a');
        gradient.addColorStop(1, '#c4a052');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, rect.width, rect.height);

        // Speckle texture
        ctx.fillStyle = 'rgba(120, 90, 40, 0.18)';
        for (let i = 0; i < 180; i += 1) {
            ctx.beginPath();
            ctx.arc(Math.random() * rect.width, Math.random() * rect.height, Math.random() * 2.2, 0, Math.PI * 2);
            ctx.fill();
        }

        this.sandCleared = false;
        this.sandRevealed = false;
        this._sandClearedRatio = 0;
    },

    startScratch(event) {
        this.scratching = true;
        this.scratch(event);
    },

    scratch(event) {
        if (! this.scratching || this.sandCleared) {
            return;
        }

        const canvas = this.$refs.sandCanvas;
        if (! canvas) {
            return;
        }

        const ctx = canvas.getContext('2d');
        const rect = canvas.getBoundingClientRect();
        if (! ctx) {
            return;
        }

        const x = (event.clientX ?? event.touches?.[0]?.clientX) - rect.left;
        const y = (event.clientY ?? event.touches?.[0]?.clientY) - rect.top;

        ctx.globalCompositeOperation = 'destination-out';
        ctx.beginPath();
        ctx.arc(x, y, 28, 0, Math.PI * 2);
        ctx.fill();

        this._sandClearedRatio = Math.min(1, (this._sandClearedRatio || 0) + 0.02);

        if (! this.sandRevealed && this._sandClearedRatio > 0.12) {
            this.sandRevealed = true;
            this.speak(this.sandStory);
        }

        if (this._sandClearedRatio >= 0.55) {
            this.sandCleared = true;
            this.scratching = false;
            this.playFx('correct');
        }
    },

    endScratch() {
        this.scratching = false;
    },

    playDemo() {
        this.demoGlow = true;
        this.speak(this.demoAudio);
    },
});

window.interactiveLessonEngine = (config = {}) => ({
    diacriticTab: 'رَ',
    tracing: false,
    traceProgress: 0,
    traceOffset: 100,
    traceDone: false,
    stars: 0,
    found: {},
    demoGlow: false,
    completeAudio: config.completeAudio || '',
    demoAudio: config.demoAudio || '',

    audioStore() {
        try {
            return window.Alpine?.store?.('audio') ?? null;
        } catch {
            return null;
        }
    },

    speak(text) {
        const audio = this.audioStore();
        audio?.unlock?.();
        audio?.speak?.(text);
    },

    playFx(type) {
        this.audioStore()?.playFx?.(type);
    },

    setDiacriticTab(glyph) {
        this.diacriticTab = glyph;
        this.speak(`حركة ${glyph}`);
    },

    onStateChange(state) {
        if (state === 5 && ! this.demoGlow) {
            window.setTimeout(() => this.playDemo(), 400);
        }
    },

    startTrace(event) {
        if (this.traceDone) {
            return;
        }

        this.tracing = true;
        this.moveTrace(event);
    },

    moveTrace(event) {
        if (! this.tracing || this.traceDone) {
            return;
        }

        this.traceProgress = Math.min(100, this.traceProgress + 3.5);
        this.traceOffset = Math.max(0, 100 - this.traceProgress);

        if (this.traceProgress >= 100) {
            this.completeTrace();
        }
    },

    endTrace() {
        this.tracing = false;
    },

    completeTrace() {
        if (this.traceDone) {
            return;
        }

        this.traceDone = true;
        this.tracing = false;
        this.traceOffset = 0;
        this.playFx('correct');
        this.speak(this.completeAudio);
    },

    exploreItem(item) {
        if (! item || this.found[item.id]) {
            return;
        }

        this.speak(item.audio || item.label);

        if (item.correct) {
            this.found[item.id] = true;
            this.stars += 1;
            this.playFx('correct');
        } else {
            this.playFx('retry');
        }
    },

    playDemo() {
        this.demoGlow = true;
        this.speak(this.demoAudio);
    },
});

Livewire.start();

try {
    Alpine.store('audio')?.init?.();
} catch (error) {
    console.error('[sanabel-audio] post-start init failed', error);
}
