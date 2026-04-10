@extends('layouts.app')

@section('content')
<div class="max-w-3xl w-full p-10 bg-white mt-10 shadow-lg mx-10 mb-10">
    <h3 class="bold text-2xl">Upload</h3>

    @if(!isset($finalDocumentId))
        @if(!session('message'))
            @php session()->flash('error', 'Nenhum documento foi encontrado'); @endphp
        @endif
        @include('messageError')
    @else
        @include('messageError')

        <form method="POST" action="{{ route('user-upload-final-document') }}?final_document_id={{ $finalDocumentId }}"
            enctype="multipart/form-data">
            @csrf
            <input type="email" name="email" class="mt-4 border-s border-grey-100 p-2 bg-gray-200 w-full"
                placeholder="Email" required>
            <br>
            <input type="password" name="password" class="mt-4 border-s border-grey-100 p-2 bg-gray-200 w-full"
                placeholder="Password" id="passwordInput" required>
            <br>
            <br>
            <input type="checkbox" name="Password" onclick="showPassword()" id="showPass">
            <label for="showPass">Mostrar Palavra-passe</label>
            <br>
            <br>
            <label for="document">Upload documento final (.pdf):</label>
            <input type="file" class="border p-1 cursor-pointer hover:bg-gray-300 rounded-lg border-gray-300 bg-gray-200"
                id="document" name="document" accept=".pdf" required>
            <br>
            <input type="submit" class="mt-4 p-2 bg-blue-900 w-full cursor-pointer hover:bg-blue-600 text-white"
                value="Submeter" name="submit">
        </form>
    @endif
    <br>
</div>

<script src="{{ asset('js/accountUser.js') }}"></script>
@endsection
