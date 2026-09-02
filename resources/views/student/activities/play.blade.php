<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $activity->title }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $student->name }} — {{ __('Up to') }} {{ $activity->xp_reward }} XP
            </p>
        </div>
    </x-slot>

    @php
        $questions = $activity->payload['questions'] ?? [];
    @endphp

    <div
        class="py-12"
        x-data="activityQuiz({
            questions: @js($questions),
            submitUrl: @js(route('student.activities.submit', $activity)),
            csrfToken: @js(csrf_token()),
            dashboardUrl: @js(route('student.activities.index')),
        })"
    >
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <template x-if="!completed">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        <div>
                            <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                                <span x-text="progressLabel"></span>
                                <span x-text="`${Math.round(((currentIndex + 1) / questions.length) * 100)}%`"></span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                <div
                                    class="h-full bg-indigo-600 transition-all duration-300"
                                    :style="`width: ${((currentIndex + 1) / questions.length) * 100}%`"
                                ></div>
                            </div>
                        </div>

                        <div>
                            <p class="text-lg font-medium text-gray-900 dark:text-gray-100" x-text="currentQuestion.question"></p>
                        </div>

                        <div class="grid gap-3">
                            <template x-for="(option, optionIndex) in currentQuestion.options" :key="optionIndex">
                                <button
                                    type="button"
                                    class="w-full rounded-lg border px-4 py-3 text-start text-sm font-medium transition"
                                    :class="selectedIndex === optionIndex
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200'
                                        : 'border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 hover:border-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-900'"
                                    @click="selectOption(optionIndex)"
                                    x-text="option"
                                ></button>
                            </template>
                        </div>

                        <div class="flex justify-between gap-3">
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 disabled:opacity-50"
                                @click="previousQuestion"
                                :disabled="currentIndex === 0"
                            >
                                {{ __('Previous') }}
                            </button>
                            <button
                                type="button"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                                @click="nextOrSubmit"
                                :disabled="selectedIndex === null || submitting"
                                x-text="currentIndex === questions.length - 1 ? '{{ __('Finish') }}' : '{{ __('Next') }}'"
                            ></button>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="completed && result">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-indigo-200 dark:border-indigo-800">
                    <div class="p-8 text-center space-y-6">
                        <div class="text-5xl">🎉</div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('Great job!') }}</h3>
                            <p class="mt-2 text-gray-600 dark:text-gray-400">{{ __('Activity completed successfully.') }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Score') }}</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="`${result.score}/${result.total_questions}`"></div>
                            </div>
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Percentage') }}</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="`${result.percentage}%`"></div>
                            </div>
                            <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-4">
                                <div class="text-sm text-amber-700 dark:text-amber-300">{{ __('XP Earned') }}</div>
                                <div class="text-2xl font-bold text-amber-800 dark:text-amber-200" x-text="`${result.xp_earned} XP`"></div>
                            </div>
                        </div>

                        <div class="text-start space-y-3">
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Answer Review') }}</h4>
                            <template x-for="(item, index) in result.feedback" :key="index">
                                <div
                                    class="rounded-lg border p-4"
                                    :class="item.is_correct
                                        ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20'
                                        : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20'"
                                >
                                    <p class="font-medium text-gray-900 dark:text-gray-100" x-text="item.question"></p>
                                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300" x-text="item.explanation"></p>
                                </div>
                            </template>
                        </div>

                        <a
                            :href="dashboardUrl"
                            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            {{ __('Return to Activities') }}
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('activityQuiz', ({ questions, submitUrl, csrfToken, dashboardUrl }) => ({
                questions,
                submitUrl,
                csrfToken,
                dashboardUrl,
                currentIndex: 0,
                answers: Array(questions.length).fill(null),
                selectedIndex: null,
                submitting: false,
                completed: false,
                result: null,
                get currentQuestion() {
                    return this.questions[this.currentIndex] ?? { question: '', options: [] };
                },
                get progressLabel() {
                    return `{{ __('Question') }} ${this.currentIndex + 1} {{ __('of') }} ${this.questions.length}`;
                },
                selectOption(optionIndex) {
                    this.selectedIndex = optionIndex;
                },
                previousQuestion() {
                    if (this.currentIndex === 0) {
                        return;
                    }

                    this.currentIndex--;
                    this.selectedIndex = this.answers[this.currentIndex];
                },
                nextOrSubmit() {
                    if (this.selectedIndex === null) {
                        return;
                    }

                    this.answers[this.currentIndex] = this.selectedIndex;

                    if (this.currentIndex < this.questions.length - 1) {
                        this.currentIndex++;
                        this.selectedIndex = this.answers[this.currentIndex];
                        return;
                    }

                    this.submitAnswers();
                },
                async submitAnswers() {
                    this.submitting = true;

                    try {
                        const response = await fetch(this.submitUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({ answers: this.answers }),
                        });

                        if (!response.ok) {
                            throw new Error('Submission failed');
                        }

                        this.result = await response.json();
                        this.completed = true;
                    } catch (error) {
                        alert(@js(__('Something went wrong while submitting your answers. Please try again.')));
                    } finally {
                        this.submitting = false;
                    }
                },
            }));
        });
    </script>
</x-app-layout>
