<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientsSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'name'       => 'Chario SARL',
                'phone'      => '+212 661 23 45 67',
                'email'      => 'achats@chario.ma',
                'address'    => 'Lot 47, Zone Industrielle Aïn Sebaâ, Casablanca',
                'ice_number' => '002145789000045',
                'active'     => true,
            ],
            [
                'name'       => 'Kilian Pêche & Distribution',
                'phone'      => '+212 522 87 65 43',
                'email'      => 'contact@kilian-peche.ma',
                'address'    => 'Port de Pêche, Avenue Mohammed VI, Agadir 80000',
                'ice_number' => '003256890000012',
                'active'     => true,
            ],
            [
                'name'       => 'Marocpêche Industries',
                'phone'      => '+212 537 45 12 36',
                'email'      => 'direction@marocpeche.ma',
                'address'    => 'Zone Franche, Tanger Med, Tanger 90000',
                'ice_number' => '001987654000098',
                'active'     => true,
            ],
            [
                'name'       => 'Atlantic Fish Export SARL',
                'phone'      => '+212 528 23 11 55',
                'email'      => 'export@atlantic-fish.ma',
                'address'    => 'Quai des Pêcheurs, Port d\'Agadir, Agadir 80100',
                'ice_number' => '004321098000067',
                'active'     => true,
            ],
            [
                'name'       => 'Azur Congélation & Stockage',
                'phone'      => '+212 522 50 34 78',
                'email'      => 'ops@azur-congelation.ma',
                'address'    => 'Rue des Industries, Sidi Bernoussi, Casablanca 20600',
                'ice_number' => '005678901000034',
                'active'     => true,
            ],
            [
                'name'       => 'El Bahr Distribution',
                'phone'      => '+212 539 33 22 11',
                'email'      => 'commandes@elbahr.ma',
                'address'    => 'Marché de Gros, Rue Allal El Fassi, Rabat 10000',
                'ice_number' => '006543210000021',
                'active'     => true,
            ],
        ];

        foreach ($clients as $data) {
            Client::updateOrCreate(['name' => $data['name']], $data);
        }

        $this->command->info('✅ 6 clients créés (dont Chario SARL et Kilian Pêche).');
    }
}
