<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_report_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('report_type');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->index(['parent_id', 'student_id', 'report_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_report_logs');
    }
};
