<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('logo_path')->nullable()->after('domain');
            $table->string('primary_color', 32)->nullable()->after('logo_path');
            $table->string('contact_email')->nullable()->after('primary_color');
            $table->string('contact_phone', 64)->nullable()->after('contact_email');
            $table->string('subscription_plan', 64)->default('standard')->after('contact_phone');
            $table->unsignedInteger('seat_limit')->default(50)->after('subscription_plan');
            $table->jsonb('settings')->nullable()->after('seat_limit');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'logo_path',
                'primary_color',
                'contact_email',
                'contact_phone',
                'subscription_plan',
                'seat_limit',
                'settings',
            ]);
        });
    }
};
