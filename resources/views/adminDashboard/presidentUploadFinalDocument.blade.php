@extends('layouts.app')

@section('content')
<div class="max-w-3xl w-full p-10 bg-white mt-10 shadow-lg mx-10 mb-10">
    <h3 class="bold text-2xl">Upload</h3>

    @if(!isset($uuid))
        @if(!session('message') && !session('error'))
            @php session()->flash('error', 'Nenhum documento foi encontrado'); @endphp
        @endif
        @include('messageError')
    @else
        @include('messageError')

        <form method="POST" action="{{ route('president-final-document') }}" enctype="multipart/form-data">
            @csrf
            <label for="document">Upload documento assinado (.pdf):</label>
            <input type="file" class="border p-1 cursor-pointer hover:bg-gray-300 rounded-lg border-gray-300 bg-gray-200"
                id="document" name="document" accept=".pdf" required>
            <br>
            <input type="hidden" name="verified_uuid" value="{{ $uuid }}">
            <input type="submit" class="mt-4 p-2 bg-blue-900 w-full cursor-pointer hover:bg-blue-600 text-white"
                value="Submeter" name="submit">
        </form>
    @endif
    <br>
</div>
@endsection
