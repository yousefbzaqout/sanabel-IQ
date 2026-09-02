const AUDIO_MUTE_KEY = 'sanabel_audio_muted';

function readMutedPreference() {
    try {
        return window.localStorage.getItem(AUDIO_MUTE_KEY) === '1';
    } catch {
        return false;
    }
}

function writeMutedPreference(muted) {
    try {
        window.localStorage.setItem(AUDIO_MUTE_KEY, muted ? '1' : '0');
    } catch {
        // Ignore storage failures (private mode, etc.).
    }
}

function pickArabicVoice(voices) {
    const preferred = voices.find((voice) =>
        /^ar(-|$)/i.test(voice.lang) && /SA|EG|AE|JO|LB/i.test(voice.lang),
    );

    if (preferred) {
        return preferred;
    }

    return voices.find((voice) => /^ar(-|$)/i.test(voice.lang)) ?? null;
}

function createTone(frequency, durationMs, type = 'sine', volume = 0.08) {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;

    if (!AudioContextClass) {
        return;
    }

    const context = new AudioContextClass();
    const oscillator = context.createOscillator();
    const gain = context.createGain();

    oscillator.type = type;
    oscillator.frequency.value = frequency;
    gain.gain.value = volume;

    oscillator.connect(gain);
    gain.connect(context.destination);

    const now = context.currentTime;
    gain.gain.setValueAtTime(volume, now);
    gain.gain.exponentialRampToValueAtTime(0.001, now + durationMs / 1000);

    oscillator.start(now);
    oscillator.stop(now + durationMs / 1000);

    oscillator.onended = () => {
        context.close().catch(() => {});
    };
}

export function registerAudioStore(Alpine) {
    Alpine.store('audio', {
        muted: readMutedPreference(),
        speaking: false,
        voicesReady: false,

        init() {
            if (!('speechSynthesis' in window)) {
                return;
            }

            const markReady = () => {
                this.voicesReady = true;
            };

            markReady();
            window.speechSynthesis.onvoiceschanged = markReady;
        },

        toggleMute() {
            this.muted = !this.muted;
            writeMutedPreference(this.muted);

            if (this.muted && 'speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                this.speaking = false;
            }
        },

        speak(text) {
            if (this.muted || !text || !('speechSynthesis' in window)) {
                return;
            }

            window.speechSynthesis.cancel();

            const utterance = new SpeechSynthesisUtterance(String(text));
            utterance.lang = 'ar-SA';
            utterance.rate = 0.95;
            utterance.pitch = 1.05;

            const voice = pickArabicVoice(window.speechSynthesis.getVoices());

            if (voice) {
                utterance.voice = voice;
                utterance.lang = voice.lang || 'ar-SA';
            }

            utterance.onstart = () => {
                this.speaking = true;
            };
            utterance.onend = () => {
                this.speaking = false;
            };
            utterance.onerror = () => {
                this.speaking = false;
            };

            window.speechSynthesis.speak(utterance);
        },

        playFx(type) {
            if (this.muted) {
                return;
            }

            if (type === 'click') {
                createTone(880, 90, 'triangle', 0.05);
                return;
            }

            if (type === 'correct' || type === 'fanfare') {
                createTone(523.25, 120, 'sine', 0.07);
                window.setTimeout(() => createTone(659.25, 140, 'sine', 0.07), 100);
                window.setTimeout(() => createTone(783.99, 220, 'sine', 0.08), 220);
                return;
            }

            if (type === 'retry' || type === 'encouraging') {
                createTone(392, 160, 'sine', 0.05);
                window.setTimeout(() => createTone(349.23, 200, 'triangle', 0.04), 140);
            }
        },
    });
}

export default registerAudioStore;
