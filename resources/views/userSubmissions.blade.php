@extends('layouts.app')

@section('content')
@php
    $statusClasses = [
        'Pendente' => 'bg-gray-50 text-yellow-800 border-gray-200',
        'Aceite' => 'bg-green-100 text-green-800 border-green-400',
        'Recusado' => 'bg-red-100 text-red-800 border-red-400',
        'Por validar' => 'bg-gray-50 text-yellow-800 border-gray-200',
        'Validado' => 'bg-blue-100 text-cyan-500 border-cyan-600',
        'Invalidado' => 'bg-gray-50 text-purple-800 border-gray-200',
        'Inativo' => 'bg-gray-100 text-gray-700 border-gray-200',
        'Cancelado' => 'bg-gray-100 text-gray-700 border-gray-200',
    ];

    $statusDescriptions = [
        'Pendente' => 'A submissão aguarda análise.',
        'Aceite' => 'O documento foi aceite. Pode avançar para a próxima etapa indicada.',
        'Recusado' => 'O documento tem de ser corrigido e submetido novamente.',
        'Por validar' => 'O documento assinado foi enviado e aguarda validação.',
        'Validado' => 'O processo foi validado e está concluído.',
        'Invalidado' => 'O documento assinado não foi validado.',
        'Inativo' => 'Registo mantido apenas para histórico.',
        'Cancelado' => 'Submissão cancelada.',
    ];
@endphp

<div class="max-w-7xl w-full p-4 sm:p-10 bg-white mt-10 shadow-lg sm:mx-10 mx-4 mb-10">
    @include('messageError')

    <div class="mb-8 flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
        <div class="max-w-3xl">
            <h1 class="text-3xl font-bold text-blue-900">As minhas submissões</h1>
            <p class="text-gray-600 mt-2">
                Consulte os planos, protocolos e o estado atual de cada submissão.
            </p>
        </div>
        <a href="{{ route('form') }}"
            class="inline-flex h-11 w-full shrink-0 items-center justify-center rounded-md bg-blue-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 md:w-auto">
            Novo plano
        </a>
    </div>

    <div class="md:flex gap-4 mb-8">
        <div class="flex-1 border border-gray-200 rounded p-4 bg-gray-50">
            <p class="text-sm text-gray-600">Planos submetidos</p>
            <p class="text-3xl font-bold text-blue-900 mt-1">{{ $plansCount }}</p>
        </div>
        <div class="flex-1 border border-gray-200 rounded p-4 bg-gray-50 mt-4 md:mt-0">
            <p class="text-sm text-gray-600">Protocolos submetidos</p>
            <p class="text-3xl font-bold text-blue-900 mt-1">{{ $protocolsCount }}</p>
        </div>
        <div class="flex-1 border border-gray-200 rounded p-4 bg-gray-50 mt-4 md:mt-0">
            <p class="text-sm text-gray-600">Em acompanhamento</p>
            <p class="text-3xl font-bold text-blue-900 mt-1">{{ $activeCount }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('user-submissions') }}" class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="min-w-0 flex-1">
                <label for="type" class="block text-sm text-gray-700 mb-1">Tipo</label>
                <select id="type" name="type" class="w-full h-10 p-2 border rounded-lg">
                    <option value="">Todos</option>
                    <option value="Plano" {{ $type === 'Plano' ? 'selected' : '' }}>Planos</option>
                    <option value="Protocolo" {{ $type === 'Protocolo' ? 'selected' : '' }}>Protocolos</option>
                </select>
            </div>
            <div class="min-w-0 flex-1">
                <label for="status" class="block text-sm text-gray-700 mb-1">Estado</label>
                <select id="status" name="status" class="w-full h-10 p-2 border rounded-lg">
                    <option value="">Todos</option>
                    @foreach($statuses as $statusOption)
                        <option value="{{ $statusOption }}" {{ $status === $statusOption ? 'selected' : '' }}>{{ $statusOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="h-10 px-5 text-white bg-blue-900 rounded hover:bg-blue-800 cursor-pointer">
                    Filtrar
                </button>
                <a href="{{ route('user-submissions') }}" class="h-10 px-5 flex items-center border border-gray-300 rounded text-gray-700 hover:bg-gray-100">
                    Limpar
                </a>
            </div>
        </div>
    </form>

    <div id="submissions-documents" class="border border-gray-200 rounded overflow-hidden">
        @forelse($submissions as $submission)
            @php
                $document = $submission->document;
                $documentType = $document?->type ?? 'Documento';
                $badgeClass = $statusClasses[$submission->status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                $description = $statusDescriptions[$submission->status] ?? 'Estado não identificado.';
            @endphp

            <div class="p-4 border-b">
                <div class="md:flex sm:items-center sm:justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs uppercase tracking-wider text-gray-500">{{ $documentType }}</span>
                            <span class="text-xs border rounded px-2 py-1 {{ $badgeClass }}">{{ $submission->status }}</span>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 mt-2">{{ $document?->name ?? 'Documento removido' }}</h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Submetido em {{ optional($submission->created_at)->format('d/m/Y H:i') }}
                        </p>
                        <p class="text-sm text-gray-700 mt-2">{{ $description }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-4 md:mt-0">
                        @if($submission->pdf_path)
                            <form method="POST" action="{{ route('print-pdf') }}" target="_blank">
                                @csrf
                                <input type="hidden" name="final_document_id" value="{{ $submission->id }}">
                                <button type="submit" class="px-4 py-2 border border-blue-400 text-blue-900 rounded hover:bg-gray-100 cursor-pointer">
                                    Ver documento
                                </button>
                            </form>
                        @endif

                        @if($submission->plan)
                            <form method="POST" action="{{ route('view-user-plan') }}" target="_blank">
                                @csrf
                                <input type="hidden" name="final_document_id" value="{{ $submission->id }}">
                                <input type="hidden" name="plan_id" value="{{ $submission->plan_id }}">
                                <button type="submit" class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-100 cursor-pointer">
                                    Ver plano assinado
                                </button>
                            </form>
                        @endif

                        @if($documentType === 'Plano' && $submission->status === 'Aceite')
                            <a href="{{ route('user-upload-final-document-form', ['final_document_id' => $submission->id]) }}"
                                class="px-4 py-2 text-white bg-blue-900 rounded hover:bg-blue-800">
                                Submeter assinado
                            </a>
                        @endif

                        @if($documentType === 'Plano' && in_array($submission->status, ['Aceite', 'Validado'], true))
                            <a href="{{ route('form', ['filled_plan_id' => $submission->id]) }}"
                                class="px-4 py-2 text-white bg-green-600 rounded hover:bg-green-700">
                                Criar protocolo
                            </a>
                        @endif

                        <form method="POST" action="{{ route('user-submissions.destroy', $submission) }}"
                            onsubmit="return confirm('Tem a certeza que pretende eliminar este documento? Esta ação não pode ser anulada.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 border border-red-300 text-red-700 rounded hover:bg-red-50 cursor-pointer">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-10 text-center">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Ainda não existem submissões</h2>
                <p class="text-gray-600 mb-8">Quando submeter um plano ou protocolo, ele aparecerá nesta área.</p>
                <a href="{{ route('form') }}"
                    class="inline-block px-5 py-2 text-white bg-blue-900 rounded-md hover:bg-blue-800">
                    Começar preenchimento
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-8 p-4 border-l-4 border-blue-400 bg-blue-100 rounded">
        <h2 class="text-lg font-bold text-blue-900 mb-2">Guia rápido dos estados</h2>
        <ul class="list-disc ml-6 text-gray-700 space-y-1">
            <li><strong>Pendente:</strong> o documento foi submetido e aguarda análise.</li>
            <li><strong>Aceite:</strong> o documento foi aprovado para a próxima etapa.</li>
            <li><strong>Recusado:</strong> é necessário corrigir e submeter novamente.</li>
            <li><strong>Por validar:</strong> o documento assinado foi enviado e aguarda validação.</li>
            <li><strong>Validado:</strong> o processo ficou concluído.</li>
        </ul>
    </div>
</div>

<script src="{{ asset('js/submissionsRefresh.js') }}" data-target="#submissions-documents" data-interval="5000"></script>
@endsection
