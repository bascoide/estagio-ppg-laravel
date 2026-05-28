@extends('layouts.app')

@section('content')
<div class="mx-4 mt-10 mb-10 w-full max-w-7xl rounded-lg border border-gray-200 border-t bg-white p-6 shadow-lg sm:mx-10 sm:p-10">
    <h3 class="text-3xl font-bold text-gray-900">Iniciar sess&atilde;o</h3>
    <p class="mb-6 mt-2 max-w-3xl text-gray-600">Entre na plataforma para gerir documentos, submiss&otilde;es e valida&ccedil;&otilde;es acad&eacute;micas.</p>

    @include('messageError')

    <form method="POST" action="{{ route('login') }}" class="grid gap-4">
        @csrf
        <input type="email" name="email" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="E-mail" required>
        <input type="password" name="password" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Palavra-passe" id="passwordInput" required>
        <div>
            <input type="checkbox" name="mostrar_palavra_passe" onclick="showPassword()" id="showPass">
            <label for="showPass" class="text-sm font-medium text-gray-800">Mostrar palavra-passe</label>
        </div>
        <a href="{{ route('register') }}" class="text-blue-700 hover:text-blue-700 hover:underline">N&atilde;o tem uma conta? Registe-se!</a>
        <input type="submit" class="w-full cursor-pointer rounded-lg bg-blue-900 px-4 py-3 font-bold text-white hover:bg-blue-600"
            value="Iniciar sess&atilde;o" name="login">
    </form>
</div>

<script src="{{ asset('js/accountUser.js') }}"></script>
@endsection
