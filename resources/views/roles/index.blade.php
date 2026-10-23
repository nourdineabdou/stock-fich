@extends('layouts.app')
@section('title', 'Rôles et permissions')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Rôles et permissions</h1>
        <p class="text-sm text-slate-500">{{ $totalPermissions }} actions dans le système, chacune avec sa propre permission.</p>
    </div>
    @can('roles.create')
    <a href="{{ route('roles.create') }}" class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-xl text-sm font-semibold">
        <i class="fas fa-plus"></i> Nouveau rôle
    </a>
    @endcan
</div>

@if($errors->any())
<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($roles as $role)
    @php
        $isSuper = $role->name === 'Super Admin';
        $pct = $totalPermissions > 0 ? min(100, round($role->permissions_count / $totalPermissions * 100)) : 0;
    @endphp
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 p-5">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="font-semibold text-slate-800 dark:text-white"><i class="fas {{ $isSuper ? 'fa-crown text-amber-500' : 'fa-user-tag text-primary-500' }} mr-1.5"></i>{{ $role->name }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $role->users_count }} utilisateur(s)</p>
            </div>
            <div class="flex gap-1">
                @if(! $isSuper)
                @can('roles.edit')
                <a href="{{ route('roles.edit', $role) }}" class="p-2 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-xl" title="Modifier les permissions"><i class="fas fa-pen text-xs"></i></a>
                @endcan
                @can('roles.destroy')
                <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Supprimer le rôle {{ addslashes($role->name) }} ?')">
                    @csrf @method('DELETE')
                    <button class="p-2 text-red-600 border border-red-200 hover:bg-red-50 rounded-xl" title="Supprimer"><i class="fas fa-trash text-xs"></i></button>
                </form>
                @endcan
                @endif
            </div>
        </div>
        <div class="mt-4">
            <div class="flex justify-between text-xs text-slate-500 mb-1">
                <span>{{ $isSuper ? 'Toutes les permissions' : $role->permissions_count . ' / ' . $totalPermissions . ' actions' }}</span>
                <span>{{ $isSuper ? 100 : $pct }} %</span>
            </div>
            <div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full {{ $isSuper ? 'bg-amber-500' : 'bg-primary-500' }}" style="width: {{ $isSuper ? 100 : $pct }}%"></div></div>
        </div>
    </div>
    @endforeach
</div>
@endsection
