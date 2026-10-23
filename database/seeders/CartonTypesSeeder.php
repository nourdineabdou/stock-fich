<?php

namespace Database\Seeders;

use App\Models\CartonType;
use Illuminate\Database\Seeder;

class CartonTypesSeeder extends Seeder
{
    /** Relancable sans doublon : les cartons sont mis a jour par leur nom. */
    public function run(): void
    {
        $cartonTypes = [
            ['name' => 'Carton 5 kg Standard',   'weight_kg' => 5.00,  'description' => 'Petit carton standard pour poissons nobles (sole, loup).'],
            ['name' => 'Carton 10 kg Standard',  'weight_kg' => 10.00, 'description' => 'Carton polyvalent, usage général.'],
            ['name' => 'Carton 15 kg Export',    'weight_kg' => 15.00, 'description' => 'Carton export certifié, norme UE.'],
            ['name' => 'Carton 16 kg',           'weight_kg' => 16.00, 'description' => 'Carton standard céphalopodes (poulpe, seiche).'],
            ['name' => 'Carton 20 kg Premium',   'weight_kg' => 20.00, 'description' => 'Carton premium renforcé pour longue conservation.'],
            ['name' => 'Carton 25 kg Vrac',      'weight_kg' => 25.00, 'description' => 'Grand carton vrac pour sardines et anchois.'],
        ];

        foreach ($cartonTypes as $data) {
            CartonType::updateOrCreate(['name' => $data['name']], array_merge($data, ['active' => true]));
        }

        $this->command?->info('✅ Types de cartons (' . count($cartonTypes) . ') créés.');
    }
}
