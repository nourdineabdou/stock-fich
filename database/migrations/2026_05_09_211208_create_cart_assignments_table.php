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
        Schema::create('cart_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('processing_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tunnel_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->enum('status', ['active', 'completed'])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_assignments');
    }
};
