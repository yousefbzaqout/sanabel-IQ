<x-quiz-layout :title="'أحسنت! — '.$material->title">
    <livewire:student.quiz-celebration
        :learning-material-id="$material->id"
        :student-id="$student->id"
    />
</x-quiz-layout>
