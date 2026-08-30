<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('grade_level');
            $table->smallInteger('school_term');
            $table->integer('total_xp')->default(0);
            $table->integer('coins')->default(0);
            $table->integer('lives')->default(3);
            $table->string('avatar_path')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE students ADD CONSTRAINT students_grade_level_check CHECK (grade_level BETWEEN 1 AND 6)');
        DB::statement('ALTER TABLE students ADD CONSTRAINT students_school_term_check CHECK (school_term IN (1, 2))');
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
