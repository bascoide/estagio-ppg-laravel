@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">
    @include('messageError')
    <h1 class="bold text-2xl">Protocolos Pendentes</h1>

    <ul class="mt-4">
        @if(count($documents) > 0)
            @foreach($documents as $document)
                @if($document->document_type === 'Plano')
                    @continue
                @endif
                <li class="flex p-2 border-b" id="document-{{ $document->final_document_id }}">
                    <form method="GET" action="{{ route('view-final-document') }}" class="flex-grow"
                        id="documentForm{{ $document->final_document_id }}">
                        <input type="hidden" name="final_document_id" value="{{ $document->final_document_id ?? '' }}">
                        <input type="hidden" name="document_id" value="{{ $document->document_id ?? '' }}">
                        <div onclick="document.getElementById('documentForm{{ $document->final_document_id }}').submit()"
                            class="flex-grow w-full items-start cursor-pointer">
                            {{ ($document->name ?? '') . ' - ' . date('d/m/Y H:i', strtotime($document->created_at ?? 'now')) }}
                        </div>
                        <span class="text-yellow-600">Pendente</span>
                    </form>

                    <div class="flex items-center">
                        @if($document->plan_is_verified)
                            <form method="POST" action="{{ route('view-plan') }}" target="_blank" class="plan-form">
                                @csrf
                                <input type="hidden" name="final_document_id" value="{{ $document->final_document_id }}">
                                <input type="hidden" name="plan_path" value="{{ $document->plan_path }}">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 rounded-lg p-1 mr-2">
                                    <img class="h-10 cursor-pointer" src="{{ asset('images/plan_icon.webp') }}">
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('view-plan') }}" target="_blank" class="plan-form"
                                id="planForm{{ $document->final_document_id }}"
                                onsubmit="event.preventDefault(); verifyPlan({{ $document->final_document_id }}, {{ $document->plan_id }}, '{{ $document->plan_path }}', this);">
                                @csrf
                                <input type="hidden" name="final_document_id" value="{{ $document->final_document_id }}">
                                <input type="hidden" name="plan_id" value="{{ $document->plan_id }}">
                                <input type="hidden" name="plan_path" value="{{ $document->plan_path }}">
                                <button type="submit" class="bg-red-600 hover:bg-red-700 rounded-lg p-1 mr-2">
                                    <img class="h-10 cursor-pointer" src="{{ asset('images/plan_icon.webp') }}">
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('print-pdf') }}" target="_blank">
                            @csrf
                            <input type="hidden" name="final_document_id" value="{{ $document->final_document_id }}">
                            <button type="submit">
                                <img class="h-10 cursor-pointer" src="{{ asset('images/print_icon.webp') }}">
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        @else
            <li class="p-2 text-gray-500">Nenhum utilizador encontrado.</li>
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

<script src="{{ asset('js/pendingDocuments.js') }}"></script>
@endsection
