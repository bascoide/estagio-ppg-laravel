@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">
    @include('messageError')

    <h1 class="bold text-2xl mb-4">Protocolos Por Validar</h1>
    <div class="flex gap-1">
        <select class="border p-2 rounded w-1/3 h-10" id="presidential_email">
            <option>Selecione um email presidencial</option>
            @foreach($presidencialEmails as $email)
                <option value="{{ $email['email'] }}">{{ $email['email'] }}</option>
            @endforeach
        </select>
        <form action="{{ route('president-list') }}">
            <button type="submit"
                class="h-10 px-2 bg-blue-600 hover:bg-blue-700 cursor-pointer flex items-center justify-center">
                <img src="{{ asset('images/hamburger-menu.png') }}" class="h-6">
            </button>
        </form>
    </div>

    <ul class="mt-4">
        @if(count($documents) > 0)
            @foreach($documents as $document)
                <li class="flex p-2 border-b">
                    <div class="flex-grow">
                        <div onclick="document.getElementById('documentForm{{ $document['final_document_id'] }}').submit()"
                            class="flex-grow w-full items-start">
                            {{ ($document['name'] ?? '') . ' - ' . date('d/m/Y H:i', strtotime($document['created_at'] ?? 'now')) }}
                        </div>
                        <span class="text-yellow-800">Por validar</span>
                    </div>

                    <div class="flex items-center ml-4 gap-2">
                        <form method="POST" action="{{ route('validate-document') }}"
                            id="documentForm{{ $document['final_document_id'] }}">
                            @csrf
                            <input type="hidden" name="final_document_id" value="{{ $document['final_document_id'] }}">
                            <input type="hidden" name="user_id" value="{{ $document['user_id'] }}">
                            <input type="hidden" name="email" value="{{ $document['email'] }}">
                            <input type="hidden" name="presidencial_email" class="hidden_presidencial_email">
                            <button class="bg-green-500 text-white rounded p-2 w-10 h-10 cursor-pointer hover:bg-green-600"
                                onclick="validateDocument({{ $document['final_document_id'] }}, event)">
                                ✔
                            </button>
                        </form>

                        <form method="POST" action="{{ route('invalidate-document') }}"
                            id="documentRejectForm{{ $document['final_document_id'] }}">
                            @csrf
                            <input type="hidden" name="final_document_id" value="{{ $document['final_document_id'] }}">
                            <input type="hidden" name="email" value="{{ $document['email'] }}">
                            <input type="hidden" name="rejection_reason" id="rejectionReason{{ $document['final_document_id'] }}" value="">
                            <button class="bg-red-500 text-white rounded p-2 w-10 cursor-pointer h-10 hover:bg-red-600"
                                onclick="rejectDocument({{ $document['final_document_id'] }}, event)">
                                ❌
                            </button>
                        </form>
                        <form method="POST" action="{{ route('print-pdf') }}" target="_blank">
                            @csrf
                            <input type="hidden" name="final_document_id" value="{{ $document['final_document_id'] }}">
                            <button type="submit">
                                <img class="h-10 cursor-pointer" src="{{ asset('images/print_icon.webp') }}">
                            </button>
                        </form>
                    </div>
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

<script src="{{ asset('js/statusNeedValidationDocuments.js') }}"></script>
@endsection
