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
        Schema::create('freezing_cycles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tunnel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('processing_order_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->decimal('start_temperature', 8, 2)->nullable();
            $table->decimal('end_temperature', 8, 2)->nullable();
            $table->enum('status', ['running', 'completed', 'aborted'])->default('running');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freezing_cycles');
    }
};
