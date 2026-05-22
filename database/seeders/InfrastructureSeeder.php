<?php

namespace Database\Seeders;

use App\Models\ColdRoom;
use App\Models\Tunnel;
use App\Models\Cart;
use App\Models\CartLevel;
use Illuminate\Database\Seeder;

class InfrastructureSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Chambres froides ─────────────────────────────────────────────────
        $coldRooms = [
            [
                'name'        => 'Chambre Froide CF-01',
                'type'        => 'storage',
                'temperature' => -18.0,
                'capacity_kg' => 500000.0,
                'description' => 'Chambre principale de conservation longue durée. Température cible : -18°C.',
                'active'      => true,
            ],
            [
                'name'        => 'Chambre Froide CF-02',
                'type'        => 'storage',
                'temperature' => -20.0,
                'capacity_kg' => 300000.0,
                'description' => 'Chambre haute capacité pour produits export. Température cible : -20°C.',
                'active'      => true,
            ],
            [
                'name'        => 'Salle de Conditionnement SC-01',
                'type'        => 'processing',
                'temperature' => 4.0,
                'capacity_kg' => 50000.0,
                'description' => 'Zone réfrigérée de conditionnement et cartonnage. Température cible : +4°C.',
                'active'      => true,
            ],
        ];

        $createdRooms = [];
        foreach ($coldRooms as $data) {
            $room = ColdRoom::updateOrCreate(['name' => $data['name']], $data);
            $createdRooms[$data['name']] = $room;
        }

        // ─── Tunnels de surgélation ────────────────────────────────────────────
        $tunnels = [
            [
                'cold_room'          => 'Chambre Froide CF-01',
                'name'               => 'Tunnel A — Surgélation Rapide',
                'capacity_chariots'  => 10,
                'temperature'        => -35.0,
                'active'             => true,
            ],
            [
                'cold_room'          => 'Chambre Froide CF-01',
                'name'               => 'Tunnel B — Surgélation Standard',
                'capacity_chariots'  => 8,
                'temperature'        => -35.0,
                'active'             => true,
            ],
            [
                'cold_room'          => 'Chambre Froide CF-02',
                'name'               => 'Tunnel C — Ultra-Congélation Export',
                'capacity_chariots'  => 6,
                'temperature'        => -40.0,
                'active'             => true,
            ],
        ];

        $createdTunnels = [];
        foreach ($tunnels as $data) {
            $coldRoom = $createdRooms[$data['cold_room']];
            unset($data['cold_room']);
            $data['cold_room_id'] = $coldRoom->id;
            $tunnel = Tunnel::updateOrCreate(['name' => $data['name']], $data);
            $createdTunnels[$tunnel->name] = $tunnel;
        }

        // ─── Chariots (Carts) ─────────────────────────────────────────────────
        // Tunnel A : 10 chariots (A-001 → A-010)
        $tunnelA = $createdTunnels['Tunnel A — Surgélation Rapide'];
        for ($i = 1; $i <= 10; $i++) {
            $code = 'CHA-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $cart = Cart::updateOrCreate(['code' => $code], [
                'tunnel_id'   => $tunnelA->id,
                'code'        => $code,
                'capacity_kg' => 500.0,
                'status'      => 'empty',
            ]);
            $this->createLevels($cart);
        }

        // Tunnel B : 8 chariots (B-001 → B-008)
        $tunnelB = $createdTunnels['Tunnel B — Surgélation Standard'];
        for ($i = 1; $i <= 8; $i++) {
            $code = 'CHB-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $cart = Cart::updateOrCreate(['code' => $code], [
                'tunnel_id'   => $tunnelB->id,
                'code'        => $code,
                'capacity_kg' => 600.0,
                'status'      => 'empty',
            ]);
            $this->createLevels($cart);
        }

        // Tunnel C : 6 chariots (C-001 → C-006)
        $tunnelC = $createdTunnels['Tunnel C — Ultra-Congélation Export'];
        for ($i = 1; $i <= 6; $i++) {
            $code = 'CHC-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $cart = Cart::updateOrCreate(['code' => $code], [
                'tunnel_id'   => $tunnelC->id,
                'code'        => $code,
                'capacity_kg' => 700.0,
                'status'      => 'empty',
            ]);
            $this->createLevels($cart);
        }

        $this->command->info('✅ Infrastructure : 3 chambres froides, 3 tunnels, 24 chariots créés.');
    }

    private function createLevels(Cart $cart): void
    {
        // Chaque chariot a 4 niveaux de 125 kg chacun (= 500 kg max)
        for ($l = 1; $l <= 4; $l++) {
            CartLevel::updateOrCreate(
                ['cart_id' => $cart->id, 'level_number' => $l],
                ['capacity_kg' => $cart->capacity_kg / 4]
            );
        }
    }
}
