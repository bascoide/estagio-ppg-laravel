@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">
    @include('messageError')
    <h1 class="bold text-2xl">Documentos do Utilizador</h1>
    <h2 class="text-gray-600 mt-2">{{ $userName }}</h2>
    <br>

    <div class="flex gap-1 mb-4">
        <select class="border p-2 rounded w-1/3 h-10" id="presidential_email">
            <option>Selecione um email presidencial</option>
            @foreach($presidencialEmails as $email)
                <option value="{{ $email['email'] }}">{{ $email['email'] }}</option>
            @endforeach
        </select>
    </div>

    <form method="GET">
        <input type="hidden" name="user_id" value="{{ $userId }}">
        <div class="flex space-x-4">
            <input type="text" name="search" placeholder="Pesquisar documentos..." class="border p-2 rounded w-1/3"
                value="{{ request('search') }}">
            <input type="date" name="date_filter" class="border p-2 rounded"
                value="{{ request('date_filter') }}">
            <select name="order_by" class="border p-2 rounded">
                <option value="">Ordenar por...</option>
                <option value="date_newest" {{ request('order_by') === 'date_newest' ? 'selected' : '' }}>Mais recentes</option>
                <option value="date_oldest" {{ request('order_by') === 'date_oldest' ? 'selected' : '' }}>Mais antigos</option>
            </select>
            <select name="status" class="border p-2 rounded">
                <option value="">Status...</option>
                @foreach(['Pendente', 'Aceite', 'Recusado', 'Por validar', 'Validado', 'Invalidado', 'Inativo'] as $statusOption)
                    <option value="{{ $statusOption }}" {{ request('status') === $statusOption ? 'selected' : '' }}>{{ $statusOption }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Filtrar</button>
        </div>
    </form>

    <ul class="mt-4">
        @if(count($documents) > 0)
            @foreach($documents as $document)
                @if($document['status'] === 'Inativo')
                    @if(!request()->has('status'))
                        @continue
                    @elseif(request('status') !== 'Inativo')
                        @continue
                    @endif
                @endif
                @if($document['document_type'] === 'Plano')
                    @continue
                @endif

                <li class="flex p-2 border-b">
                    @php
                        $validationStatuses = ['Validado', 'Por validar', 'Invalidado'];
                        $isValidationStatus = in_array($document['status'], $validationStatuses);
                    @endphp

                    <form method="GET" action="{{ route('view-final-document') }}" class="flex-grow"
                        id="documentForm{{ $document['final_document_id'] }}">
                        <input type="hidden" name="final_document_id" value="{{ $document['final_document_id'] }}">
                        <input type="hidden" name="document_id" value="{{ $document['document_id'] }}">

                        <div class="flex-grow w-full items-start {{ !$isValidationStatus ? 'cursor-pointer' : '' }}"
                            @unless($isValidationStatus)
                                onclick="document.getElementById('documentForm{{ $document['final_document_id'] }}').submit()"
                            @endunless
                            >
                            {{ $document['document_name'] }} - {{ date('d/m/Y H:i', strtotime($document['created_at'])) }}
                        </div>

                        @php $needsValidation = false; @endphp
                        @switch($document['status'] ?? '')
                            @case('Pendente')
                                <span class="text-yellow-600">Pendente</span>
                                @break
                            @case('Aceite')
                                <span class="text-green-500">Aceite</span>
                                @break
                            @case('Recusado')
                                <span class="text-red-500">Recusado</span>
                                @break
                            @case('Por validar')
                                <span class="text-yellow-800 mr-4">Por validar</span>
                                @php $needsValidation = true; @endphp
                                @break
                            @case('Validado')
                                <span class="text-cyan-500">Validado</span>
                                @break
                            @case('Inativo')
                                <span class="text-gray-600">Inativo</span>
                                @break
                            @case('Invalidado')
                                <span class="text-purple-800">Invalidado</span>
                                @break
                            @default
                                <span class="text-gray-400">Desconhecido</span>
                        @endswitch
                    </form>

                    <div>
                        @if($needsValidation)
                            <div class="flex items-center gap-2 mr-2">
                                <form method="POST" action="{{ route('validate-document') }}"
                                    id="documentValidationForm{{ $document['final_document_id'] }}">
                                    @csrf
                                    <input type="hidden" name="final_document_id" value="{{ $document['final_document_id'] }}">
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
                                    <input type="hidden" name="email" value="{{ $userEmail }}">
                                    <input type="hidden" name="rejection_reason" id="rejectionReason{{ $document['final_document_id'] }}" value="">
                                    <button class="bg-red-500 text-white rounded p-2 w-10 cursor-pointer h-10 hover:bg-red-600"
                                        onclick="rejectDocument({{ $document['final_document_id'] }}, event)">
                                        ❌
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                    @if($document['status'] === 'Validado')
                        <form method="GET" action="{{ route('addition-document') }}">
                            <div class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
                                <input type="hidden" name="final_document_id" value="{{ $document['final_document_id'] }}">
                                <input type="submit" value="Aditamento">
                            </div>
                        </form>
                        <form method="POST" action="{{ route('cancel-document') }}">
                            @csrf
                            <div class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-2">
                                <input type="hidden" name="final_document_id" value="{{ $document['final_document_id'] }}">
                                <input type="hidden" name="status" value="Inativo">
                                <input type="submit" value="Anular" onclick="return confirm('Tem certeza que deseja anular este documento?')">
                            </div>
                        </form>
                    @endif
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
            <li class="p-2 text-gray-500">Nenhum documento encontrado para este utilizador.</li>
        @endif
    </ul>
</div>

<script src="{{ asset('js/statusNeedValidationDocuments.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const presidentialEmail = document.getElementById('presidential_email');
    if (presidentialEmail) {
        presidentialEmail.addEventListener('input', function () {
            const selectedValue = this.value;
            const hiddenInputs = document.querySelectorAll('.hidden_presidencial_email');
            hiddenInputs.forEach(function (input) {
                input.value = selectedValue;
            });
        });
    }
});
</script>
@endsection
