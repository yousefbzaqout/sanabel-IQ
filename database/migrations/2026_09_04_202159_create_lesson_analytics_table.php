<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_analytics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('lesson_key', 64)->default('letter-raa');
            $table->unsignedTinyInteger('station')->nullable();
            $table->string('concept_key', 64);
            $table->string('event_type', 32);
            $table->unsignedInteger('error_count')->default(1);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'concept_key']);
            $table->index(['student_id', 'lesson_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_analytics');
    }
};
