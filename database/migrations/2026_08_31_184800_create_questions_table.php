<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_material_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('prompt');
            $table->text('explanation')->nullable();
            $table->unsignedSmallInteger('points')->default(10);
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->index(['learning_material_id', 'order_column']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
