<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number')->index();
            $table->decimal('amount', 15, 2);
            $table->dateTime('paid_at');
            $table->enum('method', ['cash', 'transfer', 'cheque', 'mobile'])->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)->default(0)->after('total');
        });

        // Les factures deja marquees payees sont considerees encaissees en totalite
        DB::table('invoices')->where('status', 'paid')->update(['paid_amount' => DB::raw('total')]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $collect = Permission::firstOrCreate(['name' => 'invoices.collect', 'guard_name' => 'web']);

        foreach (['Super Admin', 'Direction'] as $roleName) {
            Role::where('name', $roleName)->first()?->givePermissionTo($collect);
        }

        $accountant = Role::firstOrCreate(['name' => 'Comptable', 'guard_name' => 'web']);
        $accountant->syncPermissions(collect([
            'clients.view', 'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.collect',
            'processing.view', 'stock.view', 'deliveries.view', 'reports.view',
        ])
            ->map(fn ($name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'])));
    }

    public function down(): void
    {
        Role::where('name', 'Comptable')->delete();
        Permission::where('name', 'invoices.collect')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('paid_amount'));
        Schema::dropIfExists('invoice_payments');
    }
};
