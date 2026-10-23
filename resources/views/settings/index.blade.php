@extends('layouts.app')
@section('title', 'Paramètres')
@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-6">Paramètres Société</h1>

    @if(session('success'))
    <div class="mb-4 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6 mb-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-building mr-2 text-primary-500"></i>Informations Société</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nom de la société</label>
                    <input type="text" name="company_name" value="{{ $settings['company_name'] ?? '' }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Adresse</label>
                    <textarea name="company_address" rows="2" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">{{ $settings['company_address'] ?? '' }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Téléphone</label>
                    <input type="text" name="company_phone" value="{{ $settings['company_phone'] ?? '' }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                    <input type="email" name="company_email" value="{{ $settings['company_email'] ?? '' }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">ICE</label>
                    <input type="text" name="company_ice" value="{{ $settings['company_ice'] ?? '' }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">IF</label>
                    <input type="text" name="company_nif" value="{{ $settings['company_nif'] ?? '' }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Logo</label>
                    @php
                        $logoValue = $settings['company_logo'] ?? '';
                        $logoSrc = $logoValue
                            ? (str_starts_with($logoValue, 'http') ? $logoValue : asset(ltrim($logoValue, '/')))
                            : null;
                    @endphp
                    @if($logoSrc)
                    <div class="mb-2"><img src="{{ $logoSrc }}" alt="Logo" class="h-16 object-contain rounded border border-slate-200"></div>
                    @endif
                    <input type="file" name="company_logo" accept="image/*" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none">
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6 mb-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-hashtag mr-2 text-cyan-500"></i>Numérotation & TVA</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Préfixe Facture</label>
                    <input type="text" name="invoice_prefix" value="{{ $settings['invoice_prefix'] ?? 'FAC' }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Préfixe Livraison</label>
                    <input type="text" name="delivery_prefix" value="{{ $settings['delivery_prefix'] ?? 'BL' }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Préfixe Arrivage</label>
                    <input type="text" name="arrival_prefix" value="{{ $settings['arrival_prefix'] ?? 'ARR' }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Préfixe Traitement</label>
                    <input type="text" name="processing_prefix" value="{{ $settings['processing_prefix'] ?? 'PROC' }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">TVA par défaut (%)</label>
                    <input type="number" step="0.1" name="tax_rate" value="{{ $settings['tax_rate'] ?? 20 }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" name="tax_enabled" value="1" @checked(($settings['tax_enabled'] ?? '0') === '1') class="rounded">
                        Activer la TVA sur les factures
                    </label>
                    <p class="text-xs text-slate-400 mt-1">Désactivée : les factures (traitement, froid) sont émises sans TVA. Le taux est conservé.</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6 mb-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-4"><i class="fas fa-thermometer-half mr-2 text-blue-500"></i>Températures</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Température min (°C)</label>
                    <input type="number" step="0.1" name="temp_min" value="{{ $settings['temp_min'] ?? -25 }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Température max (°C)</label>
                    <input type="number" step="0.1" name="temp_max" value="{{ $settings['temp_max'] ?? -18 }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-6 mb-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-white mb-1"><i class="fas fa-stopwatch mr-2 text-indigo-500"></i>Temps des chariots dans le tunnel</h3>
            <p class="text-xs text-slate-500 mb-4">Avant le minimum : en congélation (bleu). Entre les deux : prêt à sortir (vert). Après le maximum : à sortir d'urgence (rouge).</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Temps minimum (heures)</label>
                    <input type="number" min="1" max="720" name="tunnel_min_hours" value="{{ $settings['tunnel_min_hours'] ?? 24 }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p class="text-xs text-slate-400 mt-1">24 h = 1 jour</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Temps maximum (heures)</label>
                    <input type="number" min="1" max="720" name="tunnel_max_hours" value="{{ $settings['tunnel_max_hours'] ?? 72 }}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p class="text-xs text-slate-400 mt-1">72 h = 3 jours</p>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold text-sm">
                <i class="fas fa-save mr-1.5"></i> Enregistrer
            </button>
        </div>
    </form>
</div>
@endsection
