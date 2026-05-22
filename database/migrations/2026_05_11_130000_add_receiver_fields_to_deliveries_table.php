<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('receiver_name')->nullable()->after('notes');
            $table->string('receiver_id_number', 100)->nullable()->after('receiver_name');
            $table->string('receiver_phone', 50)->nullable()->after('receiver_id_number');
            $table->dateTime('validated_at')->nullable()->after('receiver_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['receiver_name', 'receiver_id_number', 'receiver_phone', 'validated_at']);
        });
    }
};
