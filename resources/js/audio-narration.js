const AUDIO_MUTE_KEY = 'sanabel_audio_muted';

/** @type {AudioContext | null} */
let sharedAudioContext = null;

/** @type {HTMLAudioElement | null} */
let activeServerAudio = null;

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

function logAudio(level, message, detail) {
    const payload = detail === undefined ? message : [message, detail];
    const fn = level === 'error' ? console.error : level === 'warn' ? console.warn : console.info;

    try {
        fn.call(console, '[sanabel-audio]', ...(Array.isArray(payload) ? payload : [payload]));
    } catch {
        // Never let logging break the app.
    }
}

function pickArabicVoice(voices) {
    const list = Array.isArray(voices) ? voices : [];

    const preferred = list.find((voice) =>
        /^ar(-|$)/i.test(voice.lang) && /SA|EG|AE|JO|LB|MA|DZ/i.test(voice.lang),
    );

    if (preferred) {
        return preferred;
    }

    return list.find((voice) => /^ar(-|$)/i.test(voice.lang)) ?? null;
}

function hasArabicVoice(voices) {
    return pickArabicVoice(voices) !== null;
}

function getSharedAudioContext() {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;

    if (! AudioContextClass) {
        return null;
    }

    if (! sharedAudioContext) {
        try {
            sharedAudioContext = new AudioContextClass();
        } catch (error) {
            logAudio('warn', 'AudioContext unavailable', error);

            return null;
        }
    }

    return sharedAudioContext;
}

async function resumeAudioContext() {
    const context = getSharedAudioContext();

    if (! context) {
        return null;
    }

    if (context.state === 'suspended') {
        try {
            await context.resume();
            logAudio('info', 'AudioContext resumed after user gesture');
        } catch (error) {
            logAudio('warn', 'AudioContext resume failed', error);
        }
    }

    return context;
}

function createTone(frequency, durationMs, type = 'sine', volume = 0.08) {
    const context = getSharedAudioContext();

    if (! context) {
        return;
    }

    void resumeAudioContext().then((ready) => {
        if (! ready) {
            return;
        }

        try {
            const oscillator = ready.createOscillator();
            const gain = ready.createGain();

            oscillator.type = type;
            oscillator.frequency.value = frequency;
            gain.gain.value = volume;

            oscillator.connect(gain);
            gain.connect(ready.destination);

            const now = ready.currentTime;
            gain.gain.setValueAtTime(volume, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + durationMs / 1000);

            oscillator.start(now);
            oscillator.stop(now + durationMs / 1000);
        } catch (error) {
            logAudio('warn', 'FX tone failed', error);
        }
    });
}

function normalizeSpeechText(text) {
    if (text === null || text === undefined) {
        return '';
    }

    return String(text).replace(/\s+/g, ' ').trim();
}

function stopServerAudio() {
    if (! activeServerAudio) {
        return;
    }

    try {
        activeServerAudio.pause();
        activeServerAudio.src = '';
    } catch {
        // ignore
    }

    activeServerAudio = null;
}

function buildTtsUrl(text) {
    const url = new URL('/student/tts', window.location.origin);
    url.searchParams.set('text', text);

    return url.toString();
}

export function registerAudioStore(Alpine) {
    if (! Alpine || typeof Alpine.store !== 'function') {
        logAudio('error', 'Alpine.store unavailable — audio store not registered');

        return;
    }

    try {
        const existing = Alpine.store('audio');

        if (existing && typeof existing.speak === 'function') {
            existing.init?.();

            return;
        }
    } catch {
        // Store not registered yet.
    }

    Alpine.store('audio', {
        muted: readMutedPreference(),
        speaking: false,
        voicesReady: false,
        unlocked: false,
        lastError: null,
        engine: 'none',

        init() {
            this.bindGestureUnlock();

            if (! ('speechSynthesis' in window)) {
                logAudio('warn', 'speechSynthesis API missing — server TTS will be used');

                return;
            }

            const refreshVoices = () => {
                try {
                    const voices = window.speechSynthesis.getVoices() || [];
                    this.voicesReady = voices.length > 0;

                    if (voices.length > 0 && ! hasArabicVoice(voices)) {
                        logAudio('warn', 'No Arabic system voice — using server TTS fallback', {
                            voiceCount: voices.length,
                        });
                    }
                } catch (error) {
                    logAudio('warn', 'getVoices failed', error);
                }
            };

            refreshVoices();
            window.speechSynthesis.onvoiceschanged = refreshVoices;
            window.setTimeout(refreshVoices, 0);
        },

        bindGestureUnlock() {
            if (typeof document === 'undefined' || this._gestureBound) {
                return;
            }

            this._gestureBound = true;

            const unlock = () => {
                void this.unlock();
            };

            document.addEventListener('pointerdown', unlock, { passive: true });
            document.addEventListener('keydown', unlock, { passive: true });
            document.addEventListener('touchstart', unlock, { passive: true });
        },

        async unlock() {
            await resumeAudioContext();

            if ('speechSynthesis' in window) {
                try {
                    window.speechSynthesis.getVoices();

                    if (window.speechSynthesis.paused) {
                        window.speechSynthesis.resume();
                    }
                } catch (error) {
                    logAudio('warn', 'speechSynthesis unlock poke failed', error);
                }
            }

            this.unlocked = true;
        },

        toggleMute() {
            this.muted = ! this.muted;
            writeMutedPreference(this.muted);

            if (this.muted) {
                this.stopAll();
            }

            void this.unlock();
        },

        stopAll() {
            stopServerAudio();

            if ('speechSynthesis' in window) {
                try {
                    window.speechSynthesis.cancel();
                } catch (error) {
                    logAudio('warn', 'cancel failed', error);
                }
            }

            this.speaking = false;
        },

        speak(text) {
            this.narrate({ text });
        },

        /**
         * Interactive UI path: try neural server briefly, then fall back to native Arabic TTS
         * so taps never wait on cold edge_tts synthesis.
         */
        speakFast(text) {
            this.narrate({ text, fast: true });
        },

        /**
         * Prefer a pre-rendered static MP3 when available; otherwise live TTS.
         *
         * @param {{ text?: string, url?: string|null, fast?: boolean }} payload
         */
        narrate(payload = {}) {
            const url = typeof payload?.url === 'string' && payload.url.trim() !== ''
                ? payload.url.trim()
                : null;
            const text = normalizeSpeechText(payload?.text ?? '');
            const fast = payload?.fast === true;

            if (url) {
                void this.playStatic(url, text);

                return;
            }

            if (this.muted) {
                logAudio('info', 'speak skipped — muted');

                return;
            }

            if (! text) {
                logAudio('warn', 'speak skipped — empty text');

                return;
            }

            void this.unlock().then(async () => {
                this.stopAll();

                // Prefer neural /student/tts (same engine as curriculum MP3s) over browser voices.
                const serverBudgetMs = fast ? 450 : 2500;
                const serverOk = await this.speakViaServer(text, serverBudgetMs);

                if (serverOk) {
                    return;
                }

                const voices = ('speechSynthesis' in window)
                    ? (window.speechSynthesis.getVoices() || [])
                    : [];

                if (hasArabicVoice(voices)) {
                    await this.speakNative(text, voices);
                }
            });
        },

        async playStatic(url, fallbackText = '') {
            if (this.muted) {
                logAudio('info', 'playStatic skipped — muted');

                return;
            }

            if (! url) {
                logAudio('warn', 'playStatic skipped — empty url');

                return;
            }

            // Don't await unlock when already unlocked — keeps speaker-button clicks instant.
            if (! this.unlocked) {
                await this.unlock();
            } else {
                void resumeAudioContext();
            }
            this.stopAll();

            try {
                const audio = new Audio(url);
                activeServerAudio = audio;
                this.engine = 'static';
                this.speaking = true;
                this.lastError = null;

                audio.onended = () => {
                    if (activeServerAudio === audio) {
                        activeServerAudio = null;
                    }
                    this.speaking = false;
                };

                audio.onerror = () => {
                    this.speaking = false;
                    this.lastError = 'static_audio_error';
                    logAudio('warn', 'static MP3 failed — falling back to live TTS', { url });
                    const text = normalizeSpeechText(fallbackText);
                    if (text) {
                        this.speakFast(text);
                    }
                };

                // Grade-1 pacing is baked into neural MP3s (TTS_RATE); keep pitch natural.
                if ('preservesPitch' in audio) {
                    audio.preservesPitch = true;
                }
                audio.playbackRate = 1;
                await audio.play();
                logAudio('info', 'static MP3 playing', { url, rate: audio.playbackRate, engine: this.engine });
            } catch (error) {
                this.speaking = false;
                this.lastError = 'static_play_blocked';
                logAudio('error', 'playStatic failed', error);
                const text = normalizeSpeechText(fallbackText);
                if (text) {
                    this.speakFast(text);
                }
            }
        },

        /**
         * @param {string} text
         * @param {SpeechSynthesisVoice[]} voices
         */
        speakNative(text, voices) {
            return new Promise((resolve) => {
                try {
                    if (window.speechSynthesis.paused) {
                        window.speechSynthesis.resume();
                    }

                    window.speechSynthesis.cancel();

                    const utterance = new SpeechSynthesisUtterance(text);
                    const voice = pickArabicVoice(voices);

                    utterance.lang = voice?.lang || 'ar-SA';
                    utterance.rate = 0.95;
                    utterance.pitch = 1.05;

                    if (voice) {
                        utterance.voice = voice;
                    }

                    const startedAt = performance.now();
                    let settled = false;

                    const finish = (ok) => {
                        if (settled) {
                            return;
                        }

                        settled = true;
                        this.speaking = false;
                        resolve(ok);
                    };

                    utterance.onstart = () => {
                        this.speaking = true;
                        this.engine = 'native';
                        this.lastError = null;
                    };

                    utterance.onend = () => {
                        const durationMs = performance.now() - startedAt;
                        // English-only engines often "succeed" in <150ms with silence for Arabic.
                        const ok = durationMs >= 250;
                        if (! ok) {
                            logAudio('warn', 'Native TTS ended too quickly — falling back to server', {
                                durationMs,
                            });
                            this.lastError = 'native_silent';
                        }
                        finish(ok);
                    };

                    utterance.onerror = (event) => {
                        if (event?.error !== 'interrupted' && event?.error !== 'canceled') {
                            logAudio('warn', 'native utterance error', event?.error || event);
                            this.lastError = event?.error || 'native_error';
                        }
                        finish(false);
                    };

                    window.setTimeout(() => {
                        try {
                            window.speechSynthesis.speak(utterance);
                            logAudio('info', 'native speak queued', { preview: text.slice(0, 48) });
                        } catch (error) {
                            this.lastError = 'native_throw';
                            logAudio('error', 'native speak threw', error);
                            finish(false);
                        }
                    }, 30);

                    // Safety timeout if the engine never callbacks.
                    window.setTimeout(() => finish(false), 8000);
                } catch (error) {
                    logAudio('error', 'speakNative failed', error);
                    resolve(false);
                }
            });
        },

        /**
         * @param {string} text
         * @param {number} [budgetMs] give up waiting for slow neural synthesis
         * @returns {Promise<boolean>} true when neural server audio started playing
         */
        async speakViaServer(text, budgetMs = 2500) {
            try {
                stopServerAudio();

                const audio = new Audio(buildTtsUrl(text));
                activeServerAudio = audio;
                this.engine = 'server';
                this.speaking = true;
                this.lastError = null;

                audio.onended = () => {
                    if (activeServerAudio === audio) {
                        activeServerAudio = null;
                    }
                    this.speaking = false;
                };

                const failed = await new Promise((resolve) => {
                    let settled = false;
                    const budget = window.setTimeout(() => {
                        if (settled) {
                            return;
                        }
                        settled = true;
                        this.speaking = false;
                        this.lastError = 'server_tts_slow';
                        logAudio('warn', 'server TTS exceeded budget — falling back', { budgetMs });
                        try {
                            audio.pause();
                            audio.src = '';
                        } catch {
                            // ignore
                        }
                        if (activeServerAudio === audio) {
                            activeServerAudio = null;
                        }
                        resolve(true);
                    }, Math.max(200, budgetMs));

                    audio.onerror = () => {
                        if (settled) {
                            return;
                        }
                        settled = true;
                        window.clearTimeout(budget);
                        this.speaking = false;
                        this.lastError = 'server_audio_error';
                        logAudio('error', 'server TTS audio element failed');
                        resolve(true);
                    };

                    void audio.play().then(() => {
                        if (settled) {
                            return;
                        }
                        settled = true;
                        window.clearTimeout(budget);
                        logAudio('info', 'server TTS playing', { preview: text.slice(0, 48) });
                        resolve(false);
                    }).catch((error) => {
                        if (settled) {
                            return;
                        }
                        settled = true;
                        window.clearTimeout(budget);
                        this.speaking = false;
                        this.lastError = 'server_play_blocked';
                        logAudio('error', 'server TTS play() failed', error);
                        resolve(true);
                    });
                });

                return ! failed;
            } catch (error) {
                this.speaking = false;
                this.lastError = 'server_play_blocked';
                logAudio('error', 'server TTS play() failed', error);

                return false;
            }
        },

        playFx(type) {
            if (this.muted) {
                return;
            }

            const run = () => {
                try {
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
                } catch (error) {
                    logAudio('warn', 'playFx failed', error);
                }
            };

            if (this.unlocked || getSharedAudioContext()?.state === 'running') {
                run();

                return;
            }

            void this.unlock().then(run);
        },
    });
}

export default registerAudioStore;
