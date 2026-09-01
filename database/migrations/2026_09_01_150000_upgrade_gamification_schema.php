<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('badges', function (Blueprint $table): void {
            $table->renameColumn('slug', 'code');
            $table->renameColumn('name', 'name_ar');
            $table->renameColumn('description', 'description_ar');
            $table->renameColumn('requirement_type', 'criteria_type');
            $table->renameColumn('requirement_value', 'criteria_value');
        });

        Schema::rename('student_badge', 'student_badges');

        Schema::create('student_streaks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('current_streak')->default(0);
            $table->unsignedSmallInteger('max_streak')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->timestamps();

            $table->unique('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_streaks');

        Schema::rename('student_badges', 'student_badge');

        Schema::table('badges', function (Blueprint $table): void {
            $table->renameColumn('code', 'slug');
            $table->renameColumn('name_ar', 'name');
            $table->renameColumn('description_ar', 'description');
            $table->renameColumn('criteria_type', 'requirement_type');
            $table->renameColumn('criteria_value', 'requirement_value');
        });
    }
};
