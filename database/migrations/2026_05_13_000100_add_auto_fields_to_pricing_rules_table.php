<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->enum('charge_unit', ['kg', 'carton'])->default('kg')->after('billing_method');
            $table->enum('billing_frequency', ['day', 'week', 'month'])->default('week')->after('charge_unit');
            $table->boolean('auto_generate')->default(false)->after('billing_frequency');
            $table->unsignedTinyInteger('week_start_day')->default(1)->after('auto_generate');
            $table->unsignedTinyInteger('month_day')->default(1)->after('week_start_day');
            $table->unsignedSmallInteger('due_days')->default(7)->after('month_day');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropColumn([
                'charge_unit',
                'billing_frequency',
                'auto_generate',
                'week_start_day',
                'month_day',
                'due_days',
            ]);
        });
    }
};
