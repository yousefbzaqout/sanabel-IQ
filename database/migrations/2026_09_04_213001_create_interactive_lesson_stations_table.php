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
        Schema::create('interactive_lesson_stations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('interactive_lesson_id')
                ->constrained('interactive_lessons')
                ->cascadeOnDelete();
            $table->smallInteger('station_number');
            $table->string('station_type', 32);
            $table->text('title')->nullable();
            $table->text('instructions')->nullable();
            $table->text('sonbol_prompt')->nullable();
            $table->jsonb('config')->default('{}');
            $table->jsonb('assets')->nullable();
            $table->boolean('is_skippable')->default(false);
            $table->integer('order_column')->default(0);
            $table->timestamps();

            $table->unique(['interactive_lesson_id', 'station_number']);
            $table->index(['interactive_lesson_id', 'order_column']);
            $table->index('station_type');
        });

        DB::statement('
            ALTER TABLE interactive_lesson_stations
            ADD CONSTRAINT interactive_lesson_stations_station_number_range
            CHECK (station_number BETWEEN 1 AND 6)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('interactive_lesson_stations');
    }
};
