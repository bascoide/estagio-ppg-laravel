@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">
    @include('messageError')
    <h1 class="bold text-2xl">Documentos</h1>
    <br>

    <form method="GET">
        <input type="text" name="search" placeholder="Pesquisar documentos..."
            class="border-gray-300 border p-2 rounded w-1/2"
            value="{{ request('search') }}">

        <select name="show" class="border p-2 rounded">
            @php $show = request('show', 'Active'); @endphp
            <option value="Active" {{ $show === 'Active' ? 'selected' : '' }}>Ativos</option>
            <option value="Inactive" {{ $show === 'Inactive' ? 'selected' : '' }}>Inativos</option>
        </select>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Filtrar</button>
    </form>

    <ul class="mt-4">
        @php
            $search = request('search', '');
            $documentsFound = false;
        @endphp
        @if(count($documents) > 0)
            @foreach($documents as $document)
                @if($show === 'Active' && $document['is_active'] == 0)
                    @continue
                @elseif($show === 'Inactive' && $document['is_active'] == 1)
                    @continue
                @endif
                @if($search === '' || stripos($document['name'], $search) !== false)
                    @php $documentsFound = true; @endphp
                    <li class="p-2 border-b border-gray-300 list-none flex">
                        <div class="flex-grow">
                            <span class="text-blue-500">{{ $document['name'] }}</span>
                            <span class="ml-2 text-gray-500">{{ $document['type'] === 'Plano' ? '(Plano)' : '(Protocolo)' }}</span>
                        </div>
                        <form method="POST" action="{{ route('print-document') }}" target="_blank">
                            @csrf
                            <input type="hidden" name="document_id" value="{{ $document['id'] }}">
                            <button type="submit">
                                <img class="h-10 cursor-pointer" src="{{ asset('images/print_icon.webp') }}" title="PDF do documento limpo">
                            </button>
                        </form>
                        <form method="POST" action="{{ route('download-docx') }}" target="_blank">
                            @csrf
                            <input type="hidden" name="id" value="{{ $document['id'] }}">
                            <button type="submit">
                                <img class="h-10 cursor-pointer" src="{{ asset('images/download_icon.webp') }}" title="Download do docx">
                            </button>
                        </form>
                        @if($document['is_active'] == 1)
                            <form method="POST" action="{{ route('deactivate-document') }}"
                                id="deactivate-document-form-{{ $document['id'] }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ $document['id'] }}">
                                <input type="hidden" name="name" value="{{ $document['name'] }}">
                                <button onclick="deleteDocumentPrompt(event, {{ $document['id'] }})">
                                    <img class="h-10 cursor-pointer" src="{{ asset('images/eliminar_documento.webp') }}" title="Desativar documento">
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('activate-document') }}"
                                id="activate-document-form-{{ $document['id'] }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ $document['id'] }}">
                                <input type="hidden" name="name" value="{{ $document['name'] }}">
                                <button onclick="restoreDocumentPrompt(event, {{ $document['id'] }})">
                                    <img class="h-10 cursor-pointer" src="{{ asset('images/restaurar.webp') }}" title="Restaurar documento">
                                </button>
                            </form>
                        @endif
                    </li>
                @endif
            @endforeach
        @endif

        @if(!$documentsFound)
            <li class="p-2 text-gray-500 list-none">
                @if($show === 'Active')
                    Nenhum documento ativo encontrado.
                @else
                    Nenhum documento inativo encontrado.
                @endif
            </li>
        @endif
    </ul>
</div>

<script src="{{ asset('js/showDocuments.js') }}"></script>
@endsection
