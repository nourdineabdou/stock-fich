<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$rooms = \App\Models\ColdRoom::query()->where('active', true)->orderBy('id')->get(['id','name']);
echo "COLD_ROOMS\n";
foreach ($rooms as $r) { echo $r->id . '|' . $r->name . "\n"; }

$pkg = \App\Models\PackagingOrder::query()->where('reference','PKG-20260512-IHVF')->first(['id','reference','processing_order_id']);
if (!$pkg) { echo "PACKAGE_NOT_FOUND\n"; exit(0); }

echo "PACKAGE\n";
echo $pkg->id . '|' . $pkg->reference . '|' . ($pkg->processing_order_id ?? 'NULL') . "\n";

echo "ITEMS\n";
foreach (\App\Models\PackagingItem::query()->where('packaging_order_id',$pkg->id)->get(['id','cold_room_id']) as $i) {
  echo $i->id . '|' . ($i->cold_room_id ?? 'NULL') . "\n";
}

echo "BATCHES\n";
foreach (\App\Models\ClientStockBatch::query()->where('packaging_order_id',$pkg->id)->get(['id','cold_room_id']) as $b) {
  echo $b->id . '|' . ($b->cold_room_id ?? 'NULL') . "\n";
}

echo "ENTRIES\n";
$batchIds = \App\Models\ClientStockBatch::query()->where('packaging_order_id',$pkg->id)->pluck('id');
foreach (\App\Models\StorageEntry::query()->whereIn('client_stock_batch_id',$batchIds)->get(['id','client_stock_batch_id','cold_room_id','type']) as $e) {
  echo $e->id . '|' . $e->client_stock_batch_id . '|' . ($e->cold_room_id ?? 'NULL') . '|' . $e->type . "\n";
}
