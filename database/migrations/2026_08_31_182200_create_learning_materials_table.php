<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('xp_reward')->default(50);
            $table->unsignedInteger('order_column')->default(1);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['subject_id', 'order_column']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_materials');
    }
};
