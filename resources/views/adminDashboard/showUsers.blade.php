@extends('layouts.admin')

@section('content')
@php
    $visibleUsers = collect($users)->reject(fn ($user) => !empty($user['admin']))->values();
    $searchTerm = request('search', '');
@endphp

<div class="flex-grow min-h-screen p-4 bg-white rounded shadow-md overflow-y-auto">
    @include('messageError')

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-bold text-2xl">Utilizadores</h1>
            <p class="mt-2 text-gray-600">Consulte os utilizadores registados e aceda aos documentos associados.</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-center">
            <span class="block text-3xl font-bold text-blue-900">{{ $visibleUsers->count() }}</span>
            <small class="text-xs font-bold uppercase tracking-wider text-gray-600">{{ $visibleUsers->count() === 1 ? 'utilizador' : 'utilizadores' }}</small>
        </div>
    </div>

    <form method="GET" class="mb-6 flex flex-wrap items-center gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="min-w-0 flex-1">
            <label for="search" class="mb-1 block text-sm font-medium text-gray-800">Pesquisar</label>
            <input type="text" id="search" name="search" placeholder="Nome do utilizador"
                value="{{ $searchTerm }}"
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Pesquisar
            </button>
            @if($searchTerm !== '')
                <a href="{{ route('show-users') }}" class="border border-gray-300 px-4 py-2 rounded text-gray-700 hover:bg-gray-100">
                    Limpar
                </a>
            @endif
        </div>
    </form>

    <div class="grid gap-3">
        @forelse($visibleUsers as $user)
            <a href="{{ route('user-documents', ['user_id' => $user['id']]) }}" class="flex items-center gap-4 rounded-lg border border-gray-200 bg-white p-4 text-gray-800 shadow-sm transition hover:bg-gray-50">
                <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-700 font-bold text-white" aria-hidden="true">
                    {{ strtoupper(substr(trim($user['name'] ?? 'U'), 0, 1)) }}
                </span>
                <span class="min-w-0 flex-1">
                    <strong class="block truncate text-gray-900">{{ $user['name'] }}</strong>
                    <span class="block truncate text-sm text-gray-600">{{ $user['email'] ?? 'Sem e-mail associado' }}</span>
                </span>
                <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-gray-600">Utilizador</span>
            </a>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center">
                <h2 class="mb-2 text-xl font-bold text-gray-800">Nenhum utilizador encontrado</h2>
                <p class="text-gray-600">Experimente ajustar o termo de pesquisa.</p>
                @if($searchTerm !== '')
                    <a href="{{ route('show-users') }}" class="mt-4 inline-flex text-blue-700 hover:text-blue-700 hover:underline">Limpar pesquisa</a>
                @endif
            </div>
        @endforelse
    </div>
</div>
@endsection
