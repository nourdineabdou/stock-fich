<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'company_name'        => 'FreezePro Maroc',
            'company_address'     => 'Zone Industrielle Ahl Loghlam, Sidi Bernoussi, Casablanca 20600',
            'company_phone'       => '+212 522 35 67 89',
            'company_email'       => 'contact@freezepro.ma',
            'company_logo'        => '/images/company-default-logo.svg',
            'company_ice'         => '001823456000089',
            'company_nif'         => '45612389',
            'company_rc'          => 'RC 156789 Casa',
            'company_rib'         => 'CIH 230 810 0012345678901234 67',
            'invoice_prefix'      => 'FAC',
            'delivery_prefix'     => 'BL',
            'arrival_prefix'      => 'ARR',
            'processing_prefix'   => 'TR',
            'packaging_prefix'    => 'COND',
            'tax_rate'            => '20',
            'currency'            => 'MRU',
            'temp_min'            => '-22',
            'temp_max'            => '-18',
        ];

        foreach ($settings as $key => $value) {
            CompanySetting::set($key, $value);
        }

        $this->command->info('✅ Paramètres société créés.');
    }
}

