{{-- Shared form partial for Client create/edit --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
            Nom du client <span class="text-red-500">*</span>
        </label>
        <input type="text" name="name" value="{{ old('name', $client->name ?? '') }}" required
            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 @error('name') border-red-400 @enderror">
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Téléphone</label>
        <input type="tel" name="phone" value="{{ old('phone', $client->phone ?? '') }}"
            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
        <input type="email" name="email" value="{{ old('email', $client->email ?? '') }}"
            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Numéro ICE</label>
        <input type="text" name="ice_number" value="{{ old('ice_number', $client->ice_number ?? '') }}"
            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm font-mono dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        @error('ice_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Statut</label>
        <select name="active" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
            <option value="1" {{ old('active', $client->active ?? 1) == 1 ? 'selected' : '' }}>Actif</option>
            <option value="0" {{ old('active', $client->active ?? 1) == 0 ? 'selected' : '' }}>Inactif</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Adresse</label>
        <textarea name="address" rows="3"
            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('address', $client->address ?? '') }}</textarea>
        @error('address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>
