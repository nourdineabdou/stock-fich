<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FishTypeController;
use App\Http\Controllers\CartonTypeController;
use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\ProcessingOrderController;
use App\Http\Controllers\TunnelController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ColdRoomController;
use App\Http\Controllers\PackagingOrderController;
use App\Http\Controllers\ClientStockBatchController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\StorageBillingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RoleController;
use App\Http\Middleware\CheckRoutePermission;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

// Presentation de l'application (francais / arabe) : publique, sans connexion ni permission
Route::view('/presentation', 'presentation.index')->name('presentation');

Route::middleware(['auth', 'verified', CheckRoutePermission::class])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Clients
    Route::resource('clients', ClientController::class);

    // Fish Types
    Route::resource('fish-types', FishTypeController::class);

    // Carton Types
    Route::resource('carton-types', CartonTypeController::class);

    // Arrivals
    Route::resource('arrivals', ArrivalController::class);

    // Processing Orders
    Route::resource('processing', ProcessingOrderController::class);
    Route::post('processing/{processing}/assign-carts', [ProcessingOrderController::class, 'assignCarts'])->name('processing.assign-carts');
    Route::post('processing/{processing}/prices', [ProcessingOrderController::class, 'savePrices'])->name('processing.prices');
    Route::post('processing/{processing}/release-tunnels', [ProcessingOrderController::class, 'releaseFromTunnels'])->name('processing.release-tunnels');
    Route::post('processing/{processing}/place-tunnels', [ProcessingOrderController::class, 'placeInTunnels'])->name('processing.place-tunnels');
    Route::post('processing/{processing}/cancel-carts', [ProcessingOrderController::class, 'cancelCarts'])->name('processing.cancel-carts');
    Route::post('processing/{processing}/distribution', [ProcessingOrderController::class, 'saveDistribution'])->name('processing.distribution');
    Route::post('processing/{processing}/distribution/suggest', [ProcessingOrderController::class, 'suggestDistribution'])->name('processing.distribution.suggest');
    Route::get('processing/{processing}/client-pdf', [ProcessingOrderController::class, 'clientPdf'])->name('processing.client-pdf');
    Route::get('processing/{processing}/worker-sheet-pdf', [ProcessingOrderController::class, 'workerSheetPdf'])->name('processing.worker-sheet-pdf');

    // Cold Rooms
    Route::resource('cold-rooms', ColdRoomController::class);

    // Tunnels
    Route::resource('tunnels', TunnelController::class);
    Route::post('tunnels/{tunnel}/release-assignment/{assignment}', [TunnelController::class, 'releaseAssignment'])->name('tunnels.release-assignment');
    Route::post('tunnels/{tunnel}/release-multiple-assignments', [TunnelController::class, 'releaseMultipleAssignments'])->name('tunnels.release-multiple-assignments');

    // Carts
    Route::resource('carts', CartController::class);

    // Packaging
    Route::resource('packaging', PackagingOrderController::class);
    Route::get('packaging/{packaging}/print', [PackagingOrderController::class, 'printSheet'])->name('packaging.print');
    Route::post('packaging/{packaging}/finalize', [PackagingOrderController::class, 'finalize'])->name('packaging.finalize');

    // Stock
    Route::resource('stock', ClientStockBatchController::class)
        ->parameters(['stock' => 'clientStockBatch'])
        ->only(['index', 'create', 'store']);
    Route::get('stock/{clientStockBatch}/receipt', [ClientStockBatchController::class, 'entryReceiptPdf'])
        ->whereNumber('clientStockBatch')
        ->name('stock.receipt');
    Route::post('stock/{clientStockBatch}/transfer', [ClientStockBatchController::class, 'transfer'])
        ->whereNumber('clientStockBatch')
        ->name('stock.transfer');
    Route::get('stock/entry/{reference}', [ClientStockBatchController::class, 'entryShow'])->name('stock.entry.show');
    Route::get('stock/transactions', [ClientStockBatchController::class, 'transactionReport'])->name('stock.transactions');
    Route::get('stock/transactions/excel', [ClientStockBatchController::class, 'transactionReportExcel'])->name('stock.transactions.excel');
    Route::get('stock/export/pdf', [ClientStockBatchController::class, 'stockListPdf'])->name('stock.export.pdf');
    Route::get('stock/export/excel', [ClientStockBatchController::class, 'stockListExcel'])->name('stock.export.excel');
    Route::get('stock/client/{client}/statement', [ClientStockBatchController::class, 'clientStatement'])->name('stock.client-statement');
    Route::get('stock/client/{client}/statement/pdf', [ClientStockBatchController::class, 'clientStatementPdf'])->name('stock.client-statement.pdf');
    Route::get('stock/client/{client}/statement/excel', [ClientStockBatchController::class, 'clientStatementExcel'])->name('stock.client-statement.excel');
    Route::get('stock/{clientStockBatch}', [ClientStockBatchController::class, 'show'])
        ->whereNumber('clientStockBatch')
        ->name('stock.show');
    Route::delete('stock/{clientStockBatch}', [ClientStockBatchController::class, 'destroy'])
        ->whereNumber('clientStockBatch')
        ->name('stock.destroy');
    Route::get('deliveries/client-stock', [DeliveryController::class, 'getClientStock'])->name('deliveries.client-stock');

    // Deliveries
    Route::resource('deliveries', DeliveryController::class);
    Route::post('deliveries/{delivery}/validate', [DeliveryController::class, 'validateDelivery'])->name('deliveries.validate');
    Route::get('deliveries/{delivery}/pdf', [DeliveryController::class, 'pdf'])->name('deliveries.pdf');

    // Invoices (Facturation Traitement)
    Route::get('invoices/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::post('clients/{client}/invoices/storage-generate', [InvoiceController::class, 'generateStorageInvoice'])->name('invoices.storage-generate');
    Route::post('clients/{client}/invoices/storage-config', [InvoiceController::class, 'saveStorageBillingConfig'])->name('invoices.storage-config');
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::patch('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');

    // Encaissements (role Comptable)
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');
    Route::post('storage-billing/clients/{client}/collect', [PaymentController::class, 'storeForClient'])->name('storage-billing.collect');
    Route::get('payments/receipt/{receipt}', [PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    // Storage Billing (Facturation Congélation)
    Route::get('storage-billing', [StorageBillingController::class, 'index'])->name('storage-billing.index');
    Route::post('storage-billing/clients/{client}/generate', [StorageBillingController::class, 'generate'])->name('storage-billing.generate');
    Route::post('storage-billing/clients/{client}/generate-week', [StorageBillingController::class, 'generateWeek'])->name('storage-billing.generate-week');
    Route::post('storage-billing/clients/{client}/generate-unbilled', [StorageBillingController::class, 'generateUnbilled'])->name('storage-billing.generate-unbilled');
    Route::patch('storage-billing/invoices/{invoice}/pay', [StorageBillingController::class, 'pay'])->name('storage-billing.pay');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

    // Users
    Route::resource('users', UserController::class)->except(['show']);

    // Roles et permissions (une permission par action)
    Route::resource('roles', RoleController::class)->except(['show']);
});

require __DIR__.'/auth.php';
