<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_quiz_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_material_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('total_questions');
            $table->unsignedSmallInteger('correct_answers');
            $table->decimal('score_percentage', 5, 2);
            $table->unsignedInteger('xp_earned');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['student_id', 'learning_material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_quiz_attempts');
    }
};
