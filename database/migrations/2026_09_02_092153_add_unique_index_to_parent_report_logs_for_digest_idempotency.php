<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_report_logs', function (Blueprint $table): void {
            $table->unique(
                ['parent_id', 'student_id', 'report_type', 'start_date'],
                'parent_report_logs_digest_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('parent_report_logs', function (Blueprint $table): void {
            $table->dropUnique('parent_report_logs_digest_unique');
        });
    }
};
