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
        Schema::create('cold_rooms', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->enum('type', [
                'tunnel',
                'storage',
                'processing',
                'reception'
            ]);

            $table->decimal('temperature', 8, 2)->nullable();

            $table->decimal('capacity_kg', 15, 2)->nullable();

            $table->text('description')->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cold_rooms');
    }
};
