@extends('layouts.app')
@section('title', $role->exists ? 'Rôle : '.$role->name : 'Nouveau rôle')
@section('content')
@php
    $groupsJs = $groups->map(fn ($perms, $label) => [
        'label' => $label,
        'perms' => $perms->map(fn ($p) => ['name' => $p['name'], 'label' => $p['label'], 'read_only' => $p['read_only']])->values(),
    ])->values();
@endphp
<div class="max-w-6xl mx-auto"
     x-data="rolePermissions(@js($groupsJs), @js(array_values($selected)))">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('roles.index') }}" class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $role->exists ? 'Rôle : '.$role->name : 'Nouveau rôle' }}</h1>
    </div>

    @if($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}">
        @csrf
        @if($role->exists) @method('PUT') @endif
        <template x-for="name in selected" :key="name"><input type="hidden" name="permissions[]" :value="name"></template>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5 mb-4 flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[14rem]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Nom du rôle <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $role->name) }}" required placeholder="ex. Comptable, Chef d'équipe..."
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white">
            </div>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" x-model="search" placeholder="Chercher une action..." class="pl-8 pr-3 py-2.5 border border-slate-200 rounded-xl text-sm w-60 dark:bg-slate-700 dark:border-slate-600 dark:text-white">
            </div>
            <div class="flex gap-2">
                <button type="button" @click="selectAll()" class="px-3 py-2.5 text-xs rounded-xl bg-primary-50 text-primary-700 font-semibold hover:bg-primary-100">Tout cocher</button>
                <button type="button" @click="selectReadOnly()" class="px-3 py-2.5 text-xs rounded-xl bg-sky-50 text-sky-700 font-semibold hover:bg-sky-100" title="Uniquement voir et imprimer">Lecture seule</button>
                <button type="button" @click="selected = []" class="px-3 py-2.5 text-xs rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50">Tout décocher</button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-20">
            <template x-for="group in visibleGroups" :key="group.label">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-700"
                         :class="countIn(group) === group.perms.length ? 'bg-emerald-50 dark:bg-emerald-900/20' : (countIn(group) ? 'bg-amber-50/60 dark:bg-amber-900/10' : '')">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="rounded" :checked="countIn(group) === group.perms.length"
                                   x-effect="$el.indeterminate = countIn(group) > 0 && countIn(group) < group.perms.length"
                                   @change="toggleGroup(group, $event.target.checked)">
                            <span class="text-sm font-semibold text-slate-700 dark:text-white" x-text="group.label"></span>
                        </label>
                        <span class="text-xs text-slate-500" x-text="`${countIn(group)} / ${group.perms.length}`"></span>
                    </div>
                    <div class="p-2">
                        <template x-for="p in filtered(group)" :key="p.name">
                            <label class="flex items-start gap-2.5 px-2 py-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/40 cursor-pointer">
                                <input type="checkbox" class="mt-0.5 rounded" :checked="selected.includes(p.name)" @change="toggle(p.name)">
                                <span class="flex-1">
                                    <span class="text-sm text-slate-700 dark:text-slate-200" x-text="p.label"></span>
                                    <span class="block text-[10px] font-mono text-slate-400" x-text="p.name"></span>
                                </span>
                                <i class="fas text-[10px] mt-1" :class="p.read_only ? 'fa-eye text-sky-400' : 'fa-pen-to-square text-amber-400'" :title="p.read_only ? 'Lecture / impression' : 'Modifie des données'"></i>
                            </label>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <div class="fixed bottom-4 right-4 left-4 lg:left-auto z-20 bg-slate-900 text-white rounded-2xl shadow-xl px-5 py-3 flex items-center gap-5">
            <span class="text-sm"><b x-text="selected.length"></b> / <span x-text="total"></span> actions autorisées</span>
            <a href="{{ route('roles.index') }}" class="text-sm text-slate-300 hover:text-white">Annuler</a>
            <button type="submit" class="px-5 py-2 bg-primary-500 hover:bg-primary-600 rounded-xl text-sm font-semibold"><i class="fas fa-check mr-1"></i> Enregistrer</button>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script>
function rolePermissions(groups, selected) {
    return {
        groups,
        selected,
        search: '',
        get total() { return this.groups.reduce((s, g) => s + g.perms.length, 0); },
        get visibleGroups() { return this.groups.filter(g => this.filtered(g).length); },
        filtered(group) {
            const q = this.search.trim().toLowerCase();
            if (!q) return group.perms;
            return group.label.toLowerCase().includes(q)
                ? group.perms
                : group.perms.filter(p => p.label.toLowerCase().includes(q) || p.name.includes(q));
        },
        countIn(group) { return group.perms.filter(p => this.selected.includes(p.name)).length; },
        toggle(name) {
            this.selected = this.selected.includes(name) ? this.selected.filter(n => n !== name) : [...this.selected, name];
        },
        toggleGroup(group, on) {
            const names = group.perms.map(p => p.name);
            this.selected = on ? [...new Set([...this.selected, ...names])] : this.selected.filter(n => !names.includes(n));
        },
        selectAll() { this.selected = this.groups.flatMap(g => g.perms.map(p => p.name)); },
        selectReadOnly() { this.selected = this.groups.flatMap(g => g.perms.filter(p => p.read_only).map(p => p.name)); },
    };
}
</script>
@endpush
