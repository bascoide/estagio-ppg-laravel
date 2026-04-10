@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">
    @include('messageError')
    <h1 class="bold text-2xl mb-4">Professores</h1>

    <form method="GET" class="mb-6">
        <div class="flex flex-wrap items-center gap-4">
            <input type="text" name="search" placeholder="Pesquisar professores..."
                value="{{ request('search') }}"
                class="border-gray-300 border p-2 rounded w-full sm:w-1/3">

            <select name="course_id" class="border-gray-300 border p-2 rounded w-full sm:w-1/4">
                <option value="">Todos os cursos</option>
                @foreach($courses as $course)
                    <option value="{{ $course['id'] }}" {{ request('course_id') == $course['id'] ? 'selected' : '' }}>
                        {{ $course['name'] }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full sm:w-auto">
                Pesquisar
            </button>
        </div>
    </form>

    <ul class="mt-4">
        @if(empty($professors))
            <li class="text-gray-500">Nenhum professor encontrado.</li>
        @else
            @foreach($professors as $professor)
                <li class="p-2 border-b border-gray-300 list-none hover:bg-gray-50 rounded transition">
                    <form method="GET" action="{{ route('professor-documents') }}">
                        <input type="hidden" name="professor_id" value="{{ $professor['id'] }}">
                        <button type="submit" class="text-left w-full cursor-pointer">
                            <span class="text-blue-500 cursor-pointer">{{ $professor['name'] }}</span>
                            <span class="ml-2 text-gray-500">(Professor)</span>
                        </button>
                    </form>
                </li>
            @endforeach
        @endif
    </ul>
</div>
@endsection
