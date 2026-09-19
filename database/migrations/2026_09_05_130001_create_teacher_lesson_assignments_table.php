<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_lesson_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->foreignId('teacher_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('interactive_lesson_id')
                ->constrained('interactive_lessons')
                ->cascadeOnDelete();
            $table->foreignId('student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->date('due_date')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'teacher_id']);
            $table->index(['tenant_id', 'interactive_lesson_id']);
            $table->index(['tenant_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_lesson_assignments');
    }
};
