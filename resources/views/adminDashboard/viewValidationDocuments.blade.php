@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">
    @include('messageError')

    <h1 class="bold text-2xl mb-4">Protocolos Validados</h1>

    <h3 class="text-lg text-blue-800 mb-2">Quantidade de Protocolos</h3>
    <form method="GET" class="mb-4" id="filterForm">
        <select name="course_id" id="course" class="border-s border-grey-100 p-2 bg-gray-100" required>
            <option value="">Selecione um curso</option>
            @foreach($courses as $course)
                <option value="{{ $course['id'] }}" {{ request('course_id') == $course['id'] ? 'selected' : '' }}>
                    {{ $course['name'] }}
                </option>
            @endforeach
        </select>
        <select id="year_type" name="year_type" class="border-s border-grey-100 p-2 bg-gray-100"
            onchange="toggleYearSelect()">
            <option value="">Selecione o tipo de ano</option>
            <option value="civil" {{ request('year_type') == 'civil' ? 'selected' : '' }}>Ano Civil</option>
            <option value="school" {{ request('year_type') == 'school' ? 'selected' : '' }}>Ano Letivo</option>
        </select>
        <select name="civil_year" id="civil_year"
            class="border-s border-grey-100 p-2 bg-gray-100 {{ (!request('year_type') || request('year_type') == 'school') ? 'hidden' : '' }}"
            {{ request('year_type') == 'civil' ? 'required' : '' }}>
            <option value="">Selecione o ano civil</option>
            @foreach($civilYears as $year)
                <option value="{{ $year }}" {{ request('civil_year') == $year ? 'selected' : '' }}>
                    {{ $year }}
                </option>
            @endforeach
        </select>
        <select name="school_year" id="school_year"
            class="border-s border-grey-100 p-2 bg-gray-100 {{ (!request('year_type') || request('year_type') == 'civil') ? 'hidden' : '' }}"
            {{ request('year_type') == 'school' ? 'required' : '' }}>
            <option value="">Selecione o ano letivo</option>
            @foreach($schoolYears as $year)
                <option value="{{ $year }}" {{ request('school_year') == $year ? 'selected' : '' }}>
                    {{ $year }}
                </option>
            @endforeach
        </select>
        <input type="hidden" id="select_school_year_input" name="select_school_year">
        <button type="submit" class="mt-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Ver quantidade e protocolos
        </button>
    </form>

    @isset($protocolCount)
        <p class="text-gray-600 mb-4">Quantidade de protocolos: <span id="protocolCount">{{ $protocolCount }}</span></p>
    @endisset

    <h3 class="text-lg text-blue-800 mb-2">Gestão de Protocolos</h3>
    <p class="text-sm text-gray-500 mb-4">
        @if(request('course_id') && request('year_type'))
            Mostrando protocolos filtrados. 
            <a href="?page=1" class="text-blue-600 hover:underline">Limpar filtros</a>
        @else
            Selecione os filtros acima para ver os protocolos.
        @endif
    </p>

    <ul class="mt-2">
        @if(count($documents) > 0)
            @foreach($documents as $document)
                <li class="flex p-2 border-b">
                    <form method="GET" action="{{ route('view-final-document') }}" id="documentForm{{ $document['final_document_id'] }}" class="hidden">
                        <input type="hidden" name="final_document_id" value="{{ $document['final_document_id'] }}">
                    </form>
                    <div class="flex-grow">
                        <div onclick="document.getElementById('documentForm{{ $document['final_document_id'] }}').submit()"
                            class="flex-grow w-full items-start cursor-pointer">
                            {{ ($document['name'] ?? '') . ' - ' . date('d/m/Y H:i', strtotime($document['created_at'] ?? 'now')) }}
                        </div>
                        <span class="text-cyan-600">Validado</span>
                    </div>
                    <form method="GET" action="{{ route('addition-document') }}">
                        <div class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                            <input type="hidden" name="final_document_id" value="{{ $document['final_document_id'] }}">
                            <input type="submit" value="Aditamento" id="aditamento" class="cursor-pointer">
                        </div>
                    </form>
                    <form method="POST" action="{{ route('cancel-document') }}">
                        @csrf
                        <div class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                            <input type="hidden" name="final_document_id" value="{{ $document['final_document_id'] }}">
                            <input type="hidden" name="status" value="Inativo">
                            <input type="submit" value="Anular" id="anular" class="cursor-pointer"
                                onclick="return confirm('Tem certeza que deseja anular este documento?')">
                        </div>
                    </form>
                    <form method="POST" action="{{ route('print-pdf') }}" target="_blank">
                        @csrf
                        <input type="hidden" name="final_document_id" value="{{ $document['final_document_id'] }}">
                        <button type="submit">
                            <img class="h-10 cursor-pointer" src="{{ asset('images/print_icon.webp') }}">
                        </button>
                    </form>
                </li>
            @endforeach
        @else
            <li class="p-2 text-gray-500 list-none">Nenhum utilizador encontrado.</li>
        @endif
    </ul>

    <div class="mt-4 flex justify-between items-center">
        <div class="text-sm text-gray-700">
            Mostrando <span class="font-medium">{{ $startRecord }}</span> a
            <span class="font-medium">{{ $endRecord }}</span> de
            <span class="font-medium">{{ $totalRecords }}</span> registos
        </div>
        <div class="flex gap-2">
            @if($currentPage > 1)
                <a href="?{{ http_build_query(array_merge(request()->all(), ['page' => $currentPage - 1])) }}"
                   class="px-4 py-2 border rounded text-sm">Anterior</a>
            @endif
            @if($currentPage < $totalPages)
                <a href="?{{ http_build_query(array_merge(request()->all(), ['page' => $currentPage + 1])) }}"
                   class="px-4 py-2 border rounded text-sm">Próximo</a>
            @endif
        </div>
    </div>
</div>

<script src="{{ asset('js/yearType.js') }}"></script>
@endsection
