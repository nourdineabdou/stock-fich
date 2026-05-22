<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('processing_order_id')
                ->nullable()
                ->after('client_id')
                ->constrained('processing_orders')
                ->nullOnDelete();

            $table->date('due_date')->nullable()->after('invoice_date');
            $table->decimal('tax_rate', 5, 2)->default(20)->after('total');
            $table->text('notes')->nullable()->after('tax_rate');

            $table->unique('processing_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['processing_order_id']);
            $table->dropConstrainedForeignId('processing_order_id');
            $table->dropColumn(['due_date', 'tax_rate', 'notes']);
        });
    }
};