<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('🚀 FreezePro — Initialisation de la base de données...');
        $this->command->newLine();

        $this->call([
            RolesAndPermissionsSeeder::class,  // Rôles & permissions (Spatie)
            CompanySettingsSeeder::class,       // Paramètres société
            UsersSeeder::class,                 // Utilisateurs (5)
            FishDataSeeder::class,              // Types de poissons + pivot (appelle CartonTypesSeeder)
            InfrastructureSeeder::class,        // Chambres froides + tunnels + chariots
            ClientsSeeder::class,               // Clients (Chario, Kilian…)
            SampleDataSeeder::class,            // Arrivages, traitements, stock, livraisons, factures
        ]);

        $this->command->newLine();
        $this->command->info('✅ Base de données prête. Connexion : admin@freezepro.ma / password');
    }
}
