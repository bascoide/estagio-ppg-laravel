@extends('layouts.app')

@section('content')
<div class="w-screen h-96 screen flex justify-center items-center bg-gray-100">
    <div class="bg-white p-4 w-3xl">
        <form method="POST" action="{{ route('set-name') }}">
            @csrf
            <span>Insira o seu nome: </span>
            <input type="text" name="admin_name"
                class="w-full p-2 border-black border-1 mb-3"
                value="{{ session('admin_name', '') }}"
                required>
            <button type="submit" class="w-full p-4 bg-blue-700 text-white cursor-pointer hover:bg-blue-600">Entrar</button>
        </form>
    </div>
</div>
@endsection
