<?php

namespace Database\Seeders;

use App\Models\Arrival;
use App\Models\ArrivalItem;
use App\Models\Cart;
use App\Models\CartAssignment;
use App\Models\CartonType;
use App\Models\Client;
use App\Models\ClientStockBatch;
use App\Models\ColdRoom;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\FishType;
use App\Models\FreezingCycle;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PackagingItem;
use App\Models\PackagingOrder;
use App\Models\ProcessingItem;
use App\Models\ProcessingOrder;
use App\Models\StorageEntry;
use App\Models\Tunnel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    // Caches
    private array $clients    = [];
    private array $fish       = [];
    private array $cartons    = [];
    private array $coldRooms  = [];
    private array $tunnels    = [];
    private array $carts      = [];
    private int   $seqInvoice = 1;
    private int   $seqDelivery= 1;

    public function run(): void
    {
        $this->loadCaches();
        $this->seedArrivalsAndProcessing();
        $this->seedDirectStock();
        $this->seedDeliveries();
        $this->seedInvoices();
        $this->command->info('✅ Données de test complètes générées.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function loadCaches(): void
    {
        Client::all()->each(fn($c) => $this->clients[$c->name] = $c);
        FishType::all()->each(fn($f) => $this->fish[$f->name] = $f);
        CartonType::all()->each(fn($c) => $this->cartons[$c->name] = $c);
        ColdRoom::all()->each(fn($r) => $this->coldRooms[$r->name] = $r);
        Tunnel::all()->each(fn($t) => $this->tunnels[$t->name] = $t);
        Cart::all()->each(fn($c) => $this->carts[$c->code] = $c);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ARRIVAGES → TRAITEMENT → CARTONNAGE → STOCK
    // ─────────────────────────────────────────────────────────────────────────
    private function seedArrivalsAndProcessing(): void
    {
        $scenarios = [
            // ── Scénario 1 : Chario — Sardines + Maquereaux — Terminé ──────────
            [
                'client'       => 'Chario SARL',
                'arrival_date' => now()->subDays(75),
                'prefix'       => 'ARR',
                'num'          => '2026001',
                'status'       => 'completed',
                'gross_weight' => 12500.00,
                'net_weight'   => 11800.00,
                'notes'        => 'Débarquement port Casablanca. Qualité A.',
                'items'        => [
                    ['fish' => 'Sardine',   'weight_kg' => 7200.00, 'quantity' => 720],
                    ['fish' => 'Maquereau', 'weight_kg' => 4600.00, 'quantity' => 460],
                ],
                'processing'   => [
                    'prefix'         => 'TR',
                    'num'            => '2026001',
                    'start_date'     => now()->subDays(74),
                    'end_date'       => now()->subDays(72),
                    'billing_method' => 'kg',
                    'price'          => 0.85,
                    'status'         => 'completed',
                    'notes'          => 'Traitement complet. Rendement excellent.',
                    'items'          => [
                        ['fish' => 'Sardine',   'input' => 7200.0, 'output' => 6120.0, 'waste' => 720.0,  'returned' => 360.0, 'ppu' => 0.80],
                        ['fish' => 'Maquereau', 'input' => 4600.0, 'output' => 3910.0, 'waste' => 460.0,  'returned' => 230.0, 'ppu' => 0.90],
                    ],
                    'packaging'      => [
                        'prefix' => 'COND', 'num' => '2026001',
                        'status' => 'completed',
                        'items'  => [
                            ['fish' => 'Sardine',   'carton' => 'Carton 25 kg Vrac',    'qty' => 240, 'wpc' => 25.0, 'cold_room' => 'Chambre Froide CF-01'],
                            ['fish' => 'Maquereau', 'carton' => 'Carton 20 kg Premium', 'qty' => 190, 'wpc' => 20.0, 'cold_room' => 'Chambre Froide CF-01'],
                        ],
                    ],
                    'tunnel'   => 'Tunnel A — Surgélation Rapide',
                    'carts_ok' => ['CHA-001', 'CHA-002', 'CHA-003'],
                ],
            ],

            // ── Scénario 2 : Kilian — Merlu + Crevettes — Terminé ────────────
            [
                'client'       => 'Kilian Pêche & Distribution',
                'arrival_date' => now()->subDays(60),
                'prefix'       => 'ARR',
                'num'          => '2026002',
                'status'       => 'completed',
                'gross_weight' => 8200.00,
                'net_weight'   => 7900.00,
                'notes'        => 'Arrivage réfrigéré d\'Agadir. Contrôle sanitaire OK.',
                'items'        => [
                    ['fish' => 'Merlu',          'weight_kg' => 5000.00, 'quantity' => 500],
                    ['fish' => 'Crevette royale','weight_kg' => 2900.00, 'quantity' => 290],
                ],
                'processing'   => [
                    'prefix'         => 'TR',
                    'num'            => '2026002',
                    'start_date'     => now()->subDays(59),
                    'end_date'       => now()->subDays(57),
                    'billing_method' => 'kg',
                    'price'          => 1.20,
                    'status'         => 'completed',
                    'notes'          => 'Décorticage crevettes + étêtage merlu.',
                    'items'          => [
                        ['fish' => 'Merlu',          'input' => 5000.0, 'output' => 4250.0, 'waste' => 500.0,  'returned' => 250.0, 'ppu' => 1.10],
                        ['fish' => 'Crevette royale','input' => 2900.0, 'output' => 2030.0, 'waste' => 580.0,  'returned' => 290.0, 'ppu' => 1.50],
                    ],
                    'packaging'      => [
                        'prefix' => 'COND', 'num' => '2026002',
                        'status' => 'completed',
                        'items'  => [
                            ['fish' => 'Merlu',          'carton' => 'Carton 10 kg Standard', 'qty' => 420, 'wpc' => 10.0, 'cold_room' => 'Chambre Froide CF-01'],
                            ['fish' => 'Crevette royale','carton' => 'Carton 5 kg Standard',  'qty' => 400, 'wpc' => 5.0,  'cold_room' => 'Chambre Froide CF-02'],
                        ],
                    ],
                    'tunnel'   => 'Tunnel B — Surgélation Standard',
                    'carts_ok' => ['CHB-001', 'CHB-002'],
                ],
            ],

            // ── Scénario 3 : Atlantic Fish — Thon rouge — Export premium ──────
            [
                'client'       => 'Atlantic Fish Export SARL',
                'arrival_date' => now()->subDays(45),
                'prefix'       => 'ARR',
                'num'          => '2026003',
                'status'       => 'completed',
                'gross_weight' => 5500.00,
                'net_weight'   => 5300.00,
                'notes'        => 'Thon rouge — Destination marchés asiatiques.',
                'items'        => [
                    ['fish' => 'Thon rouge', 'weight_kg' => 5300.00, 'quantity' => 53],
                ],
                'processing'   => [
                    'prefix'         => 'TR',
                    'num'            => '2026003',
                    'start_date'     => now()->subDays(44),
                    'end_date'       => now()->subDays(42),
                    'billing_method' => 'carton',
                    'price'          => 22.50,
                    'status'         => 'completed',
                    'notes'          => 'Parage et surgélation norme export Japon.',
                    'items'          => [
                        ['fish' => 'Thon rouge', 'input' => 5300.0, 'output' => 4770.0, 'waste' => 318.0, 'returned' => 212.0, 'ppu' => 5.00],
                    ],
                    'packaging'      => [
                        'prefix' => 'COND', 'num' => '2026003',
                        'status' => 'completed',
                        'items'  => [
                            ['fish' => 'Thon rouge', 'carton' => 'Carton 20 kg Premium', 'qty' => 235, 'wpc' => 20.0, 'cold_room' => 'Chambre Froide CF-02'],
                        ],
                    ],
                    'tunnel'   => 'Tunnel C — Ultra-Congélation Export',
                    'carts_ok' => ['CHC-001', 'CHC-002'],
                ],
            ],

            // ── Scénario 4 : Marocpêche — Anchois — En cours ────────────────
            [
                'client'       => 'Marocpêche Industries',
                'arrival_date' => now()->subDays(10),
                'prefix'       => 'ARR',
                'num'          => '2026004',
                'status'       => 'processing',
                'gross_weight' => 9000.00,
                'net_weight'   => 8600.00,
                'notes'        => 'Arrivage urgent. Traitement prioritaire.',
                'items'        => [
                    ['fish' => 'Anchois', 'weight_kg' => 5000.00, 'quantity' => 500],
                    ['fish' => 'Sardine', 'weight_kg' => 3600.00, 'quantity' => 360],
                ],
                'processing'   => [
                    'prefix'         => 'TR',
                    'num'            => '2026004',
                    'start_date'     => now()->subDays(9),
                    'end_date'       => null,
                    'billing_method' => 'kg',
                    'price'          => 0.75,
                    'status'         => 'processing',
                    'notes'          => 'Traitement en cours — tunnels A et B.',
                    'items'          => [
                        ['fish' => 'Anchois', 'input' => 5000.0, 'output' => 4000.0, 'waste' => 600.0, 'returned' => 400.0, 'ppu' => 0.75],
                        ['fish' => 'Sardine', 'input' => 3600.0, 'output' => 3060.0, 'waste' => 360.0, 'returned' => 180.0, 'ppu' => 0.70],
                    ],
                    'packaging'      => null,
                    'tunnel'   => 'Tunnel A — Surgélation Rapide',
                    'carts_ok' => ['CHA-004', 'CHA-005'],
                ],
            ],

            // ── Scénario 5 : Azur — Sole + Loup de mer — Nouveau arrivage ────
            [
                'client'       => 'Azur Congélation & Stockage',
                'arrival_date' => now()->subDays(3),
                'prefix'       => 'ARR',
                'num'          => '2026005',
                'status'       => 'pending',
                'gross_weight' => 3200.00,
                'net_weight'   => 3050.00,
                'notes'        => 'Poissons nobles — attente pesée définitive.',
                'items'        => [
                    ['fish' => 'Sole',       'weight_kg' => 1500.00, 'quantity' => 300],
                    ['fish' => 'Loup de mer','weight_kg' => 1550.00, 'quantity' => 310],
                ],
                'processing'   => null,
            ],

            // ── Scénario 6 : El Bahr — Maquereau — Traitement planifié ───────
            [
                'client'       => 'El Bahr Distribution',
                'arrival_date' => now()->subDays(5),
                'prefix'       => 'ARR',
                'num'          => '2026006',
                'status'       => 'pending',
                'gross_weight' => 4800.00,
                'net_weight'   => 4600.00,
                'notes'        => 'Maquereau saison — très bonne qualité.',
                'items'        => [
                    ['fish' => 'Maquereau', 'weight_kg' => 4600.00, 'quantity' => 460],
                ],
                'processing'   => null,
            ],
        ];

        foreach ($scenarios as $s) {
            $client = $this->clients[$s['client']];

            // Arrival
            $arrival = Arrival::create([
                'client_id'    => $client->id,
                'reference'    => $s['prefix'] . '-' . $s['num'],
                'arrival_date' => $s['arrival_date'],
                'gross_weight' => $s['gross_weight'],
                'net_weight'   => $s['net_weight'],
                'status'       => $s['status'],
                'notes'        => $s['notes'],
            ]);

            foreach ($s['items'] as $item) {
                ArrivalItem::create([
                    'arrival_id'  => $arrival->id,
                    'fish_type_id'=> $this->fish[$item['fish']]->id,
                    'weight_kg'   => $item['weight_kg'],
                    'quantity'    => $item['quantity'],
                ]);
            }

            if (! $s['processing']) continue;

            $p = $s['processing'];

            // Processing Order
            $proc = ProcessingOrder::create([
                'client_id'      => $client->id,
                'arrival_id'     => $arrival->id,
                'reference'      => $p['prefix'] . '-' . $p['num'],
                'start_date'     => $p['start_date'],
                'end_date'       => $p['end_date'],
                'billing_method' => $p['billing_method'],
                'price'          => $p['price'],
                'status'         => $p['status'],
                'notes'          => $p['notes'],
            ]);

            // Processing Items
            foreach ($p['items'] as $pi) {
                $total = round($pi['input'] * $pi['ppu'], 2);
                ProcessingItem::create([
                    'processing_order_id' => $proc->id,
                    'fish_type_id'        => $this->fish[$pi['fish']]->id,
                    'input_weight'        => $pi['input'],
                    'output_weight'       => $pi['output'],
                    'waste_weight'        => $pi['waste'],
                    'returned_weight'     => $pi['returned'],
                    'price_per_kg'        => $pi['ppu'],
                    'total'               => $total,
                ]);
            }

            // Freezing Cycle
            $tunnel = $this->tunnels[$p['tunnel']];
            $cycle  = FreezingCycle::create([
                'tunnel_id'           => $tunnel->id,
                'processing_order_id' => $proc->id,
                'started_at'          => $p['start_date'],
                'ended_at'            => $p['end_date'],
                'start_temperature'   => -5.0,
                'end_temperature'     => $p['status'] === 'completed' ? $tunnel->temperature : null,
                'status'              => $p['status'] === 'completed' ? 'completed' : 'running',
            ]);

            // Cart Assignments
            foreach ($p['carts_ok'] as $cartCode) {
                CartAssignment::create([
                    'cart_id'             => $this->carts[$cartCode]->id,
                    'processing_order_id' => $proc->id,
                    'tunnel_id'           => $tunnel->id,
                    'assigned_at'         => $p['start_date'],
                    'released_at'         => $p['end_date'],
                    'status'              => $p['status'] === 'completed' ? 'completed' : 'active',
                ]);

                // Update cart status
                $this->carts[$cartCode]->update([
                    'status' => $p['status'] === 'completed' ? 'empty' : 'processing',
                ]);
            }

            if (! $p['packaging']) continue;

            $pk = $p['packaging'];

            // Packaging Order
            $pkgOrder = PackagingOrder::create([
                'processing_order_id' => $proc->id,
                'reference'           => $pk['prefix'] . '-' . $pk['num'],
                'date'                => $p['end_date'] ?? now(),
                'status'              => $pk['status'],
            ]);

            // Packaging Items → also create Stock Batches
            foreach ($pk['items'] as $pki) {
                $coldRoom    = $this->coldRooms[$pki['cold_room']];
                $fish        = $this->fish[$pki['fish']];
                $carton      = $this->cartons[$pki['carton']];
                $totalWeight = round($pki['qty'] * $pki['wpc'], 2);
                $lotNumber   = 'LOT-' . strtoupper(substr($fish->name, 0, 3)) . '-' . now()->format('Y') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);

                PackagingItem::create([
                    'packaging_order_id' => $pkgOrder->id,
                    'fish_type_id'       => $fish->id,
                    'carton_type_id'     => $carton->id,
                    'cartons_count'      => $pki['qty'],
                    'weight_per_carton'  => $pki['wpc'],
                    'lot_number'         => $lotNumber,
                    'cold_room_id'       => $coldRoom->id,
                    'produced_at'        => $p['end_date'] ?? now(),
                ]);

                // Client Stock Batch
                $batch = ClientStockBatch::create([
                    'client_id'         => $client->id,
                    'fish_type_id'      => $fish->id,
                    'carton_type_id'    => $carton->id,
                    'cold_room_id'      => $coldRoom->id,
                    'lot_number'        => $lotNumber,
                    'cartons_count'     => $pki['qty'],
                    'weight_per_carton' => $pki['wpc'],
                    'total_weight'      => $totalWeight,
                    'available_cartons' => $pki['qty'],
                    'available_weight'  => $totalWeight,
                    'reserved_cartons'  => 0,
                    'reserved_weight'   => 0,
                    'frozen_at'         => $p['end_date'] ?? now(),
                    'entry_date'        => $p['end_date'] ?? now(),
                    'status'            => 'active',
                    'notes'             => 'Issu du traitement ' . $proc->reference,
                ]);

                // Storage Entry (entrée)
                StorageEntry::create([
                    'client_stock_batch_id' => $batch->id,
                    'cold_room_id'          => $coldRoom->id,
                    'type'                  => 'in',
                    'cartons_count'         => $pki['qty'],
                    'weight_kg'             => $totalWeight,
                    'reference'             => $pkgOrder->reference,
                    'entry_date'            => $p['end_date'] ?? now(),
                    'notes'                 => 'Entrée stock suite cartonnage.',
                ]);
            }
        }

        $this->command->info('  → 6 arrivages | 4 traitements | 3 cartonnages | stock alimenté.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STOCK DIRECT (lots existants pour tests de livraison)
    // ─────────────────────────────────────────────────────────────────────────
    private function seedDirectStock(): void
    {
        $directBatches = [
            [
                'client'      => 'Chario SARL',
                'fish'        => 'Sardine',
                'carton'      => 'Carton 15 kg Export',
                'cold_room'   => 'Chambre Froide CF-02',
                'lot'         => 'LOT-SAR-2025-8801',
                'qty'         => 300,
                'wpc'         => 15.0,
                'frozen_at'   => now()->subDays(120),
                'entry_date'  => now()->subDays(120),
                'status'      => 'active',
                'notes'       => 'Lot hiver 2025 — conservation longue durée.',
            ],
            [
                'client'      => 'Kilian Pêche & Distribution',
                'fish'        => 'Merlu',
                'carton'      => 'Carton 10 kg Standard',
                'cold_room'   => 'Chambre Froide CF-01',
                'lot'         => 'LOT-MER-2025-7712',
                'qty'         => 150,
                'wpc'         => 10.0,
                'frozen_at'   => now()->subDays(90),
                'entry_date'  => now()->subDays(90),
                'status'      => 'active',
                'notes'       => 'Lot automne 2025.',
            ],
            [
                'client'      => 'El Bahr Distribution',
                'fish'        => 'Maquereau',
                'carton'      => 'Carton 20 kg Premium',
                'cold_room'   => 'Chambre Froide CF-01',
                'lot'         => 'LOT-MAQ-2025-5534',
                'qty'         => 180,
                'wpc'         => 20.0,
                'frozen_at'   => now()->subDays(50),
                'entry_date'  => now()->subDays(50),
                'status'      => 'active',
                'notes'       => 'Maquereau qualité premium.',
            ],
        ];

        foreach ($directBatches as $b) {
            $client    = $this->clients[$b['client']];
            $fish      = $this->fish[$b['fish']];
            $carton    = $this->cartons[$b['carton']];
            $coldRoom  = $this->coldRooms[$b['cold_room']];
            $total     = round($b['qty'] * $b['wpc'], 2);

            $batch = ClientStockBatch::create([
                'client_id'         => $client->id,
                'fish_type_id'      => $fish->id,
                'carton_type_id'    => $carton->id,
                'cold_room_id'      => $coldRoom->id,
                'lot_number'        => $b['lot'],
                'cartons_count'     => $b['qty'],
                'weight_per_carton' => $b['wpc'],
                'total_weight'      => $total,
                'available_cartons' => $b['qty'],
                'available_weight'  => $total,
                'reserved_cartons'  => 0,
                'reserved_weight'   => 0,
                'frozen_at'         => $b['frozen_at'],
                'entry_date'        => $b['entry_date'],
                'status'            => $b['status'],
                'notes'             => $b['notes'],
            ]);

            StorageEntry::create([
                'client_stock_batch_id' => $batch->id,
                'cold_room_id'          => $coldRoom->id,
                'type'                  => 'in',
                'cartons_count'         => $b['qty'],
                'weight_kg'             => $total,
                'reference'             => 'IMPORT-DIRECT',
                'entry_date'            => $b['entry_date'],
                'notes'                 => 'Lot antérieur importé en stock.',
            ]);
        }

        $this->command->info('  → 3 lots directs supplémentaires créés.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LIVRAISONS
    // ─────────────────────────────────────────────────────────────────────────
    private function seedDeliveries(): void
    {
        $deliveries = [
            // ── BL-001 : Chario — Sardines Export — Livrée ──────────────────
            [
                'client'        => 'Chario SARL',
                'reference'     => 'BL-2026001',
                'delivery_date' => now()->subDays(55),
                'status'        => 'completed',
                'notes'         => 'Livraison complète, réception signée.',
                'items'         => [
                    ['lot' => 'LOT-SAR-2025-8801', 'qty' => 100, 'weight_kg' => 1500.0],
                ],
            ],
            // ── BL-002 : Kilian — Merlu — Livrée ────────────────────────────
            [
                'client'        => 'Kilian Pêche & Distribution',
                'reference'     => 'BL-2026002',
                'delivery_date' => now()->subDays(40),
                'status'        => 'completed',
                'notes'         => 'Livraison Agadir — camion réfrigéré.',
                'items'         => [
                    ['lot' => 'LOT-MER-2025-7712', 'qty' => 80,  'weight_kg' => 800.0],
                ],
            ],
            // ── BL-003 : El Bahr — Maquereau — En attente ───────────────────
            [
                'client'        => 'El Bahr Distribution',
                'reference'     => 'BL-2026003',
                'delivery_date' => now()->subDays(2),
                'status'        => 'pending',
                'notes'         => 'Chargement prévu demain matin. Camion réfrigéré Rabat.',
                'items'         => [
                    ['lot' => 'LOT-MAQ-2025-5534', 'qty' => 60, 'weight_kg' => 1200.0],
                ],
            ],
        ];

        foreach ($deliveries as $d) {
            $client   = $this->clients[$d['client']];
            $delivery = Delivery::create([
                'client_id'     => $client->id,
                'reference'     => $d['reference'],
                'delivery_date' => $d['delivery_date'],
                'status'        => $d['status'],
                'notes'         => $d['notes'],
            ]);

            foreach ($d['items'] as $di) {
                $batch = ClientStockBatch::where('lot_number', $di['lot'])->first();
                if (! $batch) continue;

                DeliveryItem::create([
                    'delivery_id'           => $delivery->id,
                    'client_stock_batch_id' => $batch->id,
                    'fish_type_id'          => $batch->fish_type_id,
                    'carton_type_id'        => $batch->carton_type_id,
                    'cartons_count'         => $di['qty'],
                    'weight_kg'             => $di['weight_kg'],
                ]);

                // Décrémenter le stock
                $batch->decrement('available_cartons', $di['qty']);
                $batch->decrement('available_weight', $di['weight_kg']);

                // Storage Entry sortie
                StorageEntry::create([
                    'client_stock_batch_id' => $batch->id,
                    'cold_room_id'          => $batch->cold_room_id,
                    'type'                  => 'out',
                    'cartons_count'         => $di['qty'],
                    'weight_kg'             => $di['weight_kg'],
                    'reference'             => $delivery->reference,
                    'entry_date'            => $d['delivery_date'],
                    'notes'                 => 'Sortie pour livraison ' . $delivery->reference,
                ]);
            }
        }

        $this->command->info('  → 3 livraisons créées (2 livrées, 1 en transit).');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FACTURES
    // ─────────────────────────────────────────────────────────────────────────
    private function seedInvoices(): void
    {
        $invoices = [
            // ── FAC-2026001 : Chario — Traitement sardines+maquereaux — Payée
            [
                'client'       => 'Chario SARL',
                'number'       => 'FAC-2026001',
                'date'         => now()->subDays(68),
                'status'       => 'paid',
                'items'        => [
                    ['desc' => 'Traitement et surgélation Sardine — 7 200 kg',       'qty' => 7200,  'pu' => 0.80],
                    ['desc' => 'Traitement et surgélation Maquereau — 4 600 kg',     'qty' => 4600,  'pu' => 0.90],
                    ['desc' => 'Frais de conditionnement et cartonnage',             'qty' => 1,     'pu' => 1200.00],
                    ['desc' => 'Location espace chambre froide CF-01 (30 jours)',    'qty' => 30,    'pu' => 120.00],
                ],
                'tax_rate'     => 20,
            ],
            // ── FAC-2026002 : Kilian — Traitement merlu+crevettes — Payée ───
            [
                'client'       => 'Kilian Pêche & Distribution',
                'number'       => 'FAC-2026002',
                'date'         => now()->subDays(52),
                'status'       => 'paid',
                'items'        => [
                    ['desc' => 'Traitement et surgélation Merlu — 5 000 kg',         'qty' => 5000,  'pu' => 1.10],
                    ['desc' => 'Décorticage et surgélation Crevette — 2 900 kg',     'qty' => 2900,  'pu' => 1.50],
                    ['desc' => 'Conditionnement cartons 10 kg — 420 cartons',        'qty' => 420,   'pu' => 3.50],
                    ['desc' => 'Conditionnement cartons 5 kg — 400 cartons',         'qty' => 400,   'pu' => 2.80],
                ],
                'tax_rate'     => 20,
            ],
            // ── FAC-2026003 : Atlantic Fish — Thon rouge export — Brouillon ─
            [
                'client'       => 'Atlantic Fish Export SARL',
                'number'       => 'FAC-2026003',
                'date'         => now()->subDays(38),
                'status'       => 'draft',
                'items'        => [
                    ['desc' => 'Traitement Thon rouge — surgélation norme export',   'qty' => 1,     'pu' => 4500.00],
                    ['desc' => 'Conditionnement cartons 20 kg Premium — 235 cartons','qty' => 235,   'pu' => 5.00],
                    ['desc' => 'Location chambre froide CF-02 (20 jours)',            'qty' => 20,    'pu' => 150.00],
                    ['desc' => 'Certificat sanitaire export',                         'qty' => 1,     'pu' => 800.00],
                ],
                'tax_rate'     => 20,
            ],
            // ── FAC-2026004 : Chario — Livraison BL-2026001 — Partielle ─────
            [
                'client'       => 'Chario SARL',
                'number'       => 'FAC-2026004',
                'date'         => now()->subDays(50),
                'status'       => 'partial',
                'items'        => [
                    ['desc' => 'Livraison BL-2026001 — 100 cartons Sardine 15 kg',   'qty' => 100,   'pu' => 85.00],
                    ['desc' => 'Frais de transport réfrigéré',                        'qty' => 1,     'pu' => 1800.00],
                ],
                'tax_rate'     => 20,
            ],
            // ── FAC-2026005 : El Bahr — Maquereau — Brouillon ───────────────
            [
                'client'       => 'El Bahr Distribution',
                'number'       => 'FAC-2026005',
                'date'         => now()->subDays(1),
                'status'       => 'draft',
                'items'        => [
                    ['desc' => 'Livraison BL-2026003 — 60 cartons Maquereau 20 kg', 'qty' => 60,    'pu' => 95.00],
                    ['desc' => 'Frais de transport réfrigéré — Rabat',               'qty' => 1,     'pu' => 1500.00],
                ],
                'tax_rate'     => 20,
            ],
        ];

        foreach ($invoices as $inv) {
            $client   = $this->clients[$inv['client']];
            $subtotal = 0;

            $lineItems = [];
            foreach ($inv['items'] as $li) {
                $lineTotal  = round($li['qty'] * $li['pu'], 2);
                $subtotal  += $lineTotal;
                $lineItems[] = ['desc' => $li['desc'], 'qty' => $li['qty'], 'pu' => $li['pu'], 'total' => $lineTotal];
            }

            $tax   = round($subtotal * $inv['tax_rate'] / 100, 2);
            $total = round($subtotal + $tax, 2);

            $invoice = Invoice::create([
                'client_id'      => $client->id,
                'invoice_number' => $inv['number'],
                'invoice_date'   => $inv['date'],
                'subtotal'       => $subtotal,
                'tax'            => $tax,
                'total'          => $total,
                'status'         => $inv['status'],
            ]);

            foreach ($lineItems as $li) {
                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'service_type' => 'divers',
                    'description'  => $li['desc'],
                    'quantity'     => $li['qty'],
                    'unit_price'   => $li['pu'],
                    'total'        => $li['total'],
                ]);
            }
        }

        $this->command->info('  → 5 factures créées (2 payées, 1 partielle, 2 brouillons).');
    }
}

