@extends('layouts.app')

@section('content')
@php
    $successMessage = $message ?? session('message');
    $errorMessage = $error ?? session('error');
    $isSuccess = !empty($successMessage);
@endphp

<div class="max-w-3xl w-full sm:p-10 p-6 bg-white mt-10 shadow-lg sm:mx-10 mx-4 mb-10">
    <div class="text-center">
        <h3 class="bold text-2xl text-gray-900">Verificação de Conta</h3>
        <p class="mt-2 text-gray-600">
            @if($isSuccess)
                A sua conta foi confirmada com sucesso.
            @elseif(!empty($errorMessage))
                Não foi possível concluir a verificação da conta.
            @else
                O estado da verificação foi processado.
            @endif
        </p>
    </div>

    <div class="mt-8 max-w-md mx-auto border border-gray-200 bg-gray-100 p-6 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full {{ $isSuccess ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} text-lg font-bold">
            {{ $isSuccess ? '✓' : '!' }}
        </div>

        @if($isSuccess)
            <p class="text-lg font-semibold text-gray-800">Conta confirmada</p>
            <p class="mt-2 text-sm text-gray-700">{{ $successMessage }}</p>
            <p class="mt-1 text-sm text-gray-700">Já pode iniciar sessão na plataforma.</p>
        @elseif(!empty($errorMessage))
            <p class="text-lg font-semibold text-gray-800">Verificação não concluída</p>
            <p class="mt-2 text-sm text-gray-700">{{ $errorMessage }}</p>
            <p class="mt-1 text-sm text-gray-700">Verifique se está a usar o link correto.</p>
        @else
            <p class="text-lg font-semibold text-gray-800">Estado processado</p>
            <p class="mt-2 text-sm text-gray-700">Volte ao login para continuar.</p>
        @endif

        <a href="{{ route('login') }}"
           class="mt-6 inline-block w-full p-2 bg-blue-900 cursor-pointer hover:bg-blue-600 text-white">
            Ir para login
        </a>
    </div>
</div>
@endsection
