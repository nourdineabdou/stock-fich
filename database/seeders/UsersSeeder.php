<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Super Admin',
                'email'    => 'admin@freezepro.ma',
                'password' => Hash::make('password'),
                'role'     => 'Super Admin',
            ],
            [
                'name'     => 'Hamza Moutawakkil',
                'email'    => 'hamza@freezepro.ma',
                'password' => Hash::make('password'),
                'role'     => 'Direction',
            ],
            [
                'name'     => 'Fatima Zahra Idrissi',
                'email'    => 'fatima@freezepro.ma',
                'password' => Hash::make('password'),
                'role'     => 'Gestionnaire Stock',
            ],
            [
                'name'     => 'Youssef Benmoussa',
                'email'    => 'youssef@freezepro.ma',
                'password' => Hash::make('password'),
                'role'     => 'Gestionnaire Traitement',
            ],
            [
                'name'     => 'Kilian Vasseur',
                'email'    => 'kilian@freezepro.ma',
                'password' => Hash::make('password'),
                'role'     => 'Facturation',
            ],
            [
                'name'     => 'Omar Hajjaji',
                'email'    => 'omar@freezepro.ma',
                'password' => Hash::make('password'),
                'role'     => 'Opérateur Tunnel',
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::updateOrCreate(['email' => $data['email']], $data);
            $user->syncRoles([$role]);
        }

        $this->command->info('✅ Utilisateurs créés (6).');
    }
}
