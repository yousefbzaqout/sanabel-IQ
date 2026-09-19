<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_materials', function (Blueprint $table): void {
            $table->string('audio_path')->nullable()->after('is_published');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->string('audio_path')->nullable()->after('order_column');
        });

        Schema::table('question_options', function (Blueprint $table): void {
            $table->string('audio_path')->nullable()->after('order_column');
        });
    }

    public function down(): void
    {
        Schema::table('learning_materials', function (Blueprint $table): void {
            $table->dropColumn('audio_path');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn('audio_path');
        });

        Schema::table('question_options', function (Blueprint $table): void {
            $table->dropColumn('audio_path');
        });
    }
};
