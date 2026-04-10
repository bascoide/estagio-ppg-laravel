@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">
    @include('messageError')
    <form action="{{ route('addition-document') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <h1 class="bold text-2xl">Aditamentos</h1>

        <input type="text" placeholder="Nome do documento" class="mt-4 border-s border-grey-100 p-2 bg-gray-100 w-full"
            id="documentName" name="name" required>
        <br><br>

        <div class="flex items-center">
            <label for="documentFile">Upload documento:</label>
            <input type="file"
                class="ml-2 border p-1 cursor-pointer hover:bg-gray-300 rounded-lg border-gray-300 bg-gray-200"
                id="documentFile" name="documentFile" accept=".pdf" required>
        </div>

        <button type="submit"
                class="mt-4 p-2 bg-blue-500 w-full cursor-pointer hover:bg-blue-600 text-white">Upload</button>
    </form>
    <ul class="mt-4">
        @if(count($additions) > 0)
            @foreach($additions as $addition)
                <li class="flex p-2 border-b">
                    <div class="flex-grow">
                        <form id="documentForm{{ $addition['final_document_id'] }}"
                              action="{{ route('print-addition') }}" method="POST" target="_blank">
                            @csrf
                            <input type="hidden" name="document_id" value="{{ $addition['final_document_id'] }}">
                            <input type="hidden" name="addition_path" value="{{ $addition['addition_path'] ?? '' }}">
                            <div onclick="document.getElementById('documentForm{{ $addition['final_document_id'] }}').submit()"
                                class="flex-grow w-full items-start cursor-pointer">
                                {{ ($addition['name'] ?? '') . ' - ' . date('d/m/Y H:i', strtotime($addition['created_at'] ?? 'now')) }}
                            </div>
                        </form>
                    </div>
                </li>
            @endforeach
        @else
            <li class="p-2 text-gray-500">Nenhum aditamento encontrado.</li>
        @endif
    </ul>
</div>
@endsection
