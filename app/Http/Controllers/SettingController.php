<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $defaults = [
            'company_name' => 'FreezePro Maroc',
            'company_address' => '',
            'company_phone' => '',
            'company_email' => '',
            'company_nif' => '',
            'company_ice' => '',
            'company_logo' => '/images/company-default-logo.svg',
            'invoice_prefix' => 'FAC',
            'delivery_prefix' => 'BL',
            'arrival_prefix' => 'ARR',
            'processing_prefix' => 'TR',
            'tax_rate' => '20',
            'tax_enabled' => '0',
            'currency' => 'MRU',
            'temp_min' => '-25',
            'temp_max' => '-18',
            'tunnel_min_hours' => '24',
            'tunnel_max_hours' => '72',
        ];

        $settings = array_replace($defaults, CompanySetting::pluck('value', 'key')->toArray());
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name'    => 'required|string|max:255',
            'company_address' => 'nullable|string',
            'company_phone'   => 'nullable|string|max:30',
            'company_email'   => 'nullable|email',
            'company_nif'     => 'nullable|string|max:50',
            'company_ice'     => 'nullable|string|max:50',
            'invoice_prefix'  => 'nullable|string|max:10',
            'delivery_prefix' => 'nullable|string|max:10',
            'arrival_prefix'  => 'nullable|string|max:10',
            'processing_prefix' => 'nullable|string|max:10',
            'tax_rate'        => 'nullable|numeric|min:0|max:100',
            'currency'        => 'nullable|string|max:10',
            'temp_min'        => 'nullable|numeric',
            'temp_max'        => 'nullable|numeric',
            'tunnel_min_hours' => 'nullable|integer|min:1|max:720',
            'tunnel_max_hours' => 'nullable|integer|min:1|max:720|gte:tunnel_min_hours',
            'logo'            => 'nullable|image|max:2048',
            'company_logo'    => 'nullable|image|max:2048',
        ]);

        $keys = [
            'company_name','company_address','company_phone','company_email',
            'company_nif','company_ice','invoice_prefix','delivery_prefix',
            'arrival_prefix','processing_prefix','tax_rate','currency','temp_min','temp_max','tunnel_min_hours','tunnel_max_hours',
        ];
        foreach ($keys as $key) {
            if ($request->has($key)) {
                CompanySetting::set($key, $request->$key ?? '');
            }
        }

        CompanySetting::set('tax_enabled', $request->boolean('tax_enabled') ? '1' : '0');

        $logoFile = $request->file('company_logo') ?? $request->file('logo');
        if ($logoFile) {
            $path = $logoFile->store('public/company');
            CompanySetting::set('company_logo', Storage::url($path));
        }

        return back()->with('success', 'Paramètres enregistrés.');
    }
}

