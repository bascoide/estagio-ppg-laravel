@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">
    @include('messageError')
    
    @if($finalDocumentId)
        <form action="{{ route('addition-document') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <h1 class="bold text-2xl">Aditamentos</h1>

            <input type="text" placeholder="Nome do aditamento" class="mt-4 border-s border-grey-100 p-2 bg-gray-100 w-full"
                id="documentName" name="addition_name" required>
            <br><br>

            <div class="flex items-center">
                <label for="documentFile">Upload documento:</label>
                <input type="file"
                    class="ml-2 border p-1 cursor-pointer hover:bg-gray-300 rounded-lg border-gray-300 bg-gray-200"
                    id="documentFile" name="documentFile" accept=".pdf" required>
            </div>

            <input type="hidden" name="final_document_id" value="{{ $finalDocumentId }}">

            <button type="submit"
                    class="mt-4 p-2 bg-blue-500 w-full cursor-pointer hover:bg-blue-600 text-white">Upload</button>
        </form>
        <ul class="mt-4">
            @if(count($additions) > 0)
                @foreach($additions as $addition)
                    <li class="flex p-2 border-b">
                        <div class="flex-grow">
                            <form id="documentForm{{ $addition['id'] }}"
                                  action="{{ route('print-addition') }}" method="POST" target="_blank">
                                @csrf
                                <input type="hidden" name="addition_id" value="{{ $addition['id'] }}">
                                <div onclick="document.getElementById('documentForm{{ $addition['id'] }}').submit()"
                                    class="flex-grow w-full items-start cursor-pointer">
                                    {{ ($addition['name'] ?? '') . ' - ' . date('d/m/Y H:i', strtotime($addition['created_at'] ?? 'now')) }}
                                </div>
                            </form>
                        </div>
                    </li>
                @endforeach
            @else
                <li class="p-2 text-gray-500">Nenhum aditamento encontrado para este documento.</li>
            @endif
        </ul>
    @else
        <div class="p-4 bg-yellow-100 border border-yellow-400 rounded">
            <p class="text-yellow-800">Nenhum documento foi selecionado. Por favor, selecione um documento validado para fazer aditamentos.</p>
        </div>
    @endif
</div>
@endsection
