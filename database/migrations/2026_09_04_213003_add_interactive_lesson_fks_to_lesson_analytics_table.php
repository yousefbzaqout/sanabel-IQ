<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_analytics', function (Blueprint $table): void {
            $table->foreignId('interactive_lesson_id')
                ->nullable()
                ->after('lesson_key')
                ->constrained('interactive_lessons')
                ->nullOnDelete();

            $table->foreignId('learning_material_id')
                ->nullable()
                ->after('interactive_lesson_id')
                ->constrained('learning_materials')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_analytics', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('interactive_lesson_id');
            $table->dropConstrainedForeignId('learning_material_id');
        });
    }
};
