@extends('layouts.app') {{-- O layout principal do teu projeto --}}

@section('title')
    Erro @yield('code') - @yield('title')
@endsection

@section('content')
<div class="error-wrapper" style="display: flex; justify-content: center; align-items: center; min-height: 65vh; text-align: center; padding: 30px;">
    <div class="error-box">
        <!-- Código do Erro (Ex: 404, 500) -->
        <h1 style="font-size: clamp(4rem, 12vw, 8rem); font-weight: 800; line-height: 1; margin-bottom: 15px; color: inherit;">
            @yield('code')
        </h1>
        
        <!-- Título do Erro -->
        <h2 style="font-size: clamp(1.5rem, 4vw, 2.2rem); margin-bottom: 20px; font-weight: 600;">
            @yield('title')
        </h2>
        
        <!-- Mensagem Descritiva -->
        <p style="margin-bottom: 35px; max-width: 550px; margin-left: auto; margin-right: auto; opacity: 0.8; font-size: 1.1rem;">
            @yield('message')
        </p>
        
        <!-- Botão de Retorno (Usa as classes do teu projeto, ex: btn btn-primary) -->
        <a href="{{ url('/') }}" class="btn btn-primary" style="padding: 12px 28px; text-decoration: none; font-weight: 600; border-radius: 4px; display: inline-block;">
            Voltar ao Painel Principal
        </a>
    </div>
</div>
@endsection