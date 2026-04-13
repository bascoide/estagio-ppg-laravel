@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">
    <div class="mb-6">
        <h1 class="text-2xl font-bold mb-4">Registo de Atividades</h1>

        <form method="GET" class="mb-4 flex gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Utilizador</label>
                <select name="logged_name" class="border p-2 rounded">
                    <option value="">Todos</option>
                    @foreach($loggedNames as $logName)
                        <option value="{{ $logName }}">{{ $logName }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Data</label>
                <input type="date" name="date" class="border p-2 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Ação</label>
                <select name="action_type" class="border p-2 rounded">
                    <option value="">Todas</option>
                    <option value="create-account">Criar conta</option>
                    <option value="accept-document">Aceitar documento</option>
                    <option value="reject-document">Rejeitar documento</option>
                    <option value="invalidate-document">Invalidar documento</option>
                    <option value="validate-document">Validar documento</option>
                    <option value="edit-document">Editar documento</option>
                    <option value="annul-document">Anular documento</option>
                    <option value="addition-document">Fazer aditamento de um documento</option>
                    <option value="upload-document">Fazer upload de um documento</option>
                    <option value="deactivation-document">Desativar documento</option>
                    <option value="restore-document">Restaurar documento</option>
                    <option value="create-course">Criar curso</option>
                    <option value="delete-course">Eliminar curso</option>
                    <option value="edit-course">Editar curso</option>
                    <option value="deactivation-course">Desativar curso</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">&nbsp;</label>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Filtrar</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilizador</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($logs as $log)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ date('d/m/Y H:i:s', strtotime($log['created_at'])) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $log['name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @php
                                    $actionLabels = [
                                        'create-account'       => 'Criar conta',
                                        'accept-document'      => 'Aceitar documento',
                                        'reject-document'      => 'Rejeitar documento',
                                        'invalidate-document'  => 'Invalidar documento',
                                        'validate-document'    => 'Validar documento',
                                        'edit-document'        => 'Editar documento',
                                        'annul-document'       => 'Anular documento',
                                        'addition-document'    => 'Fazer aditamento de um documento',
                                        'upload-document'      => 'Fazer upload de um documento',
                                        'deactivation-document'=> 'Desativar documento',
                                        'restore-document'     => 'Restaurar documento',
                                        'create-course'        => 'Criar curso',
                                        'delete-course'        => 'Eliminar curso',
                                        'edit-course'          => 'Editar curso',
                                        'deactivation-course'  => 'Desativar curso',
                                    ];
                                @endphp
                                {{ $actionLabels[$log['action']] ?? 'Não encontrado' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if(isset($log['final_document_id']))
                                    <form method="POST" action="{{ route('print-pdf') }}" target="_blank">
                                        @csrf
                                        <input type="hidden" name="final_document_id" value="{{ $log['final_document_id'] }}">
                                        <button type="submit" class="p-2 bg-gray-300 hover:bg-gray-200 cursor-pointer rounded-lg">Ver Documento</button>
                                    </form>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

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
</div>
@endsection
