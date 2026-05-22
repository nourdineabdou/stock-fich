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
        Schema::create('storage_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_stock_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cold_room_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['in', 'out', 'transfer'])->default('in');
            $table->integer('cartons_count')->default(0);
            $table->decimal('weight_kg', 15, 2)->default(0);
            $table->string('reference')->nullable();
            $table->dateTime('entry_date');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_entries');
    }
};
