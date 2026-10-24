<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storage_entries', function (Blueprint $table) {
            $table->foreignId('packaging_order_id')
                ->nullable()
                ->after('id')
                ->constrained('packaging_orders')
                ->cascadeOnDelete();
        });

        Schema::table('client_stock_batches', function (Blueprint $table) {
            $table->foreignId('packaging_order_id')
                ->nullable()
                ->after('id')
                ->constrained('packaging_orders')
                ->cascadeOnDelete();
        });

        DB::statement(
            'UPDATE storage_entries se
             INNER JOIN packaging_orders po ON po.reference = se.reference
             SET se.packaging_order_id = po.id
             WHERE se.packaging_order_id IS NULL'
        );

        DB::statement(
            'UPDATE client_stock_batches b
             INNER JOIN storage_entries se ON se.client_stock_batch_id = b.id
             INNER JOIN packaging_orders po ON po.reference = se.reference
             SET b.packaging_order_id = po.id
             WHERE b.packaging_order_id IS NULL'
        );

        $duplicateProcessingOrderIds = DB::table('packaging_orders')
            ->select('processing_order_id')
            ->groupBy('processing_order_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('processing_order_id');

        foreach ($duplicateProcessingOrderIds as $processingOrderId) {
            $orderIds = DB::table('packaging_orders')
                ->where('processing_order_id', $processingOrderId)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $keepOrderId = array_shift($orderIds);

            if (!empty($orderIds)) {
                DB::table('packaging_orders')
                    ->whereIn('id', $orderIds)
                    ->delete();
            }
        }

        Schema::table('packaging_orders', function (Blueprint $table) {
            $table->unique('processing_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('packaging_orders', function (Blueprint $table) {
            $table->dropForeign(['processing_order_id']);
        });

        Schema::table('packaging_orders', function (Blueprint $table) {
            $table->dropUnique(['processing_order_id']);
        });

        Schema::table('packaging_orders', function (Blueprint $table) {
            $table->foreign('processing_order_id')
                ->references('id')
                ->on('processing_orders')
                ->cascadeOnDelete();
        });

        Schema::table('client_stock_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('packaging_order_id');
        });

        Schema::table('storage_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('packaging_order_id');
        });
    }
};
