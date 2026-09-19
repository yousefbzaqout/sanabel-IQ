<x-quiz-layout :title="$material->title">
    <livewire:student.quiz-runner
        :learning-material-id="$material->id"
        :student-id="$student->id"
    />
</x-quiz-layout>
