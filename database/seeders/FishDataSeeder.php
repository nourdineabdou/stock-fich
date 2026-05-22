<?php

namespace Database\Seeders;

use App\Models\FishType;
use App\Models\CartonType;
use Illuminate\Database\Seeder;

class FishDataSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Types de poissons ───────────────────────────────────────────────
        $fishTypes = [
            ['name' => 'Sardine',        'scientific_name' => 'Sardina pilchardus',         'description' => 'Poisson pélagique côtier, très abondant en Atlantique marocain.'],
            ['name' => 'Maquereau',      'scientific_name' => 'Scomber scombrus',            'description' => 'Poisson pélagique à chair grasse, riche en oméga-3.'],
            ['name' => 'Anchois',        'scientific_name' => 'Engraulis encrasicolus',      'description' => 'Petit poisson pélagique utilisé frais ou transformé.'],
            ['name' => 'Thon rouge',     'scientific_name' => 'Thunnus thynnus',             'description' => 'Grand poisson migrateur à haute valeur commerciale.'],
            ['name' => 'Merlu',          'scientific_name' => 'Merluccius merluccius',       'description' => 'Poisson démersal très apprécié sur le marché européen.'],
            ['name' => 'Crevette royale','scientific_name' => 'Penaeus kerathurus',          'description' => 'Crustacé de haute valeur, pêché sur le littoral atlantique.'],
            ['name' => 'Sole',           'scientific_name' => 'Solea solea',                 'description' => 'Poisson plat démersal, très prisé en restauration gastronomique.'],
            ['name' => 'Loup de mer',    'scientific_name' => 'Dicentrarchus labrax',        'description' => 'Poisson côtier noble, élevé en aquaculture et pêché en mer.'],
        ];

        $fishIds = [];
        foreach ($fishTypes as $data) {
            $fish = FishType::updateOrCreate(['name' => $data['name']], array_merge($data, ['active' => true]));
            $fishIds[$data['name']] = $fish->id;
        }

        // ─── Types de cartons ────────────────────────────────────────────────
        $cartonTypes = [
            ['name' => 'Carton 5 kg Standard',   'weight_kg' => 5.00,  'description' => 'Petit carton standard pour poissons nobles (sole, loup).'],
            ['name' => 'Carton 10 kg Standard',  'weight_kg' => 10.00, 'description' => 'Carton polyvalent, usage général.'],
            ['name' => 'Carton 15 kg Export',    'weight_kg' => 15.00, 'description' => 'Carton export certifié, norme UE.'],
            ['name' => 'Carton 20 kg Premium',   'weight_kg' => 20.00, 'description' => 'Carton premium renforcé pour longue conservation.'],
            ['name' => 'Carton 25 kg Vrac',      'weight_kg' => 25.00, 'description' => 'Grand carton vrac pour sardines et anchois.'],
        ];

        $cartonIds = [];
        foreach ($cartonTypes as $data) {
            $carton = CartonType::updateOrCreate(['name' => $data['name']], array_merge($data, ['active' => true]));
            $cartonIds[$data['name']] = $carton->id;
        }

        // ─── Associations Poisson ↔ Carton (pivot) ───────────────────────────
        $associations = [
            // Sardine → Carton 25kg Vrac (défaut) + Carton 15kg Export
            'Sardine' => [
                ['carton' => 'Carton 25 kg Vrac',     'default_weight' => 25.0, 'price' => 1.50, 'is_default' => true,  'min_cartons' => 50],
                ['carton' => 'Carton 15 kg Export',   'default_weight' => 15.0, 'price' => 2.20, 'is_default' => false, 'min_cartons' => 20],
            ],
            // Maquereau → Carton 20kg Premium + Carton 15kg Export
            'Maquereau' => [
                ['carton' => 'Carton 20 kg Premium',  'default_weight' => 20.0, 'price' => 2.00, 'is_default' => true,  'min_cartons' => 30],
                ['carton' => 'Carton 15 kg Export',   'default_weight' => 15.0, 'price' => 2.20, 'is_default' => false, 'min_cartons' => 20],
            ],
            // Anchois → Carton 25kg Vrac
            'Anchois' => [
                ['carton' => 'Carton 25 kg Vrac',     'default_weight' => 25.0, 'price' => 1.80, 'is_default' => true,  'min_cartons' => 40],
                ['carton' => 'Carton 10 kg Standard', 'default_weight' => 10.0, 'price' => 2.50, 'is_default' => false, 'min_cartons' => 10],
            ],
            // Thon rouge → Carton 20kg Premium
            'Thon rouge' => [
                ['carton' => 'Carton 20 kg Premium',  'default_weight' => 20.0, 'price' => 5.00, 'is_default' => true,  'min_cartons' => 10],
                ['carton' => 'Carton 15 kg Export',   'default_weight' => 15.0, 'price' => 5.50, 'is_default' => false, 'min_cartons' => 5],
            ],
            // Merlu → Carton 10kg Standard + Carton 15kg Export
            'Merlu' => [
                ['carton' => 'Carton 10 kg Standard', 'default_weight' => 10.0, 'price' => 3.00, 'is_default' => true,  'min_cartons' => 20],
                ['carton' => 'Carton 15 kg Export',   'default_weight' => 15.0, 'price' => 3.20, 'is_default' => false, 'min_cartons' => 10],
            ],
            // Crevette → Carton 5kg Standard + Carton 10kg Standard
            'Crevette royale' => [
                ['carton' => 'Carton 5 kg Standard',  'default_weight' => 5.0,  'price' => 8.00, 'is_default' => true,  'min_cartons' => 20],
                ['carton' => 'Carton 10 kg Standard', 'default_weight' => 10.0, 'price' => 7.50, 'is_default' => false, 'min_cartons' => 10],
            ],
            // Sole → Carton 5kg Standard + Carton 10kg Standard
            'Sole' => [
                ['carton' => 'Carton 5 kg Standard',  'default_weight' => 5.0,  'price' => 10.00, 'is_default' => true,  'min_cartons' => 10],
                ['carton' => 'Carton 10 kg Standard', 'default_weight' => 10.0, 'price' => 9.00,  'is_default' => false, 'min_cartons' => 5],
            ],
            // Loup de mer → Carton 10kg Standard
            'Loup de mer' => [
                ['carton' => 'Carton 10 kg Standard', 'default_weight' => 10.0, 'price' => 9.00, 'is_default' => true,  'min_cartons' => 10],
                ['carton' => 'Carton 5 kg Standard',  'default_weight' => 5.0,  'price' => 9.50, 'is_default' => false, 'min_cartons' => 5],
            ],
        ];

        foreach ($associations as $fishName => $cartons) {
            $fish = FishType::where('name', $fishName)->first();
            if (! $fish) continue;

            $syncData = [];
            foreach ($cartons as $c) {
                $carton = CartonType::where('name', $c['carton'])->first();
                if (! $carton) continue;
                $syncData[$carton->id] = [
                    'default_weight' => $c['default_weight'],
                    'price'          => $c['price'],
                    'is_default'     => $c['is_default'] ? 1 : 0,
                    'min_cartons'    => $c['min_cartons'],
                    'max_cartons'    => null,
                    'active'         => 1,
                ];
            }
            $fish->cartonTypes()->sync($syncData);
        }

        $this->command->info('✅ Types de poissons (8) et cartons (5) créés + associations pivot.');
    }
}
