<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactive_lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_material_id')
                ->unique()
                ->constrained('learning_materials')
                ->cascadeOnDelete();
            $table->string('lesson_key', 64)->unique();
            $table->string('title', 255);
            $table->string('subtitle', 255)->nullable();
            $table->string('subject_code', 16);
            $table->smallInteger('grade_level')->default(1);
            $table->smallInteger('station_count')->default(6);
            $table->string('status', 32)->default('draft');
            $table->string('intro_audio_path', 255)->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();

            $table->index(['subject_code', 'grade_level']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactive_lessons');
    }
};
