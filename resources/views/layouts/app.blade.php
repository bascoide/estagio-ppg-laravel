<!DOCTYPE html>
<html lang="pt-pt" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Ricardo da Costa Ferreira">
    <meta name="author" content="Simão Pedro Carvalho Ferreira">
    <link href="{{ asset('output.css') }}" rel="stylesheet">
    <title>PPG</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
</head>
<body class="">
    <div class="flex p-5 bg-gray-200">
        <img src="{{ asset('images/logo.webp') }}" alt="logo" class="h-12 transition delay-150 duration-300 ease-in-out">
        <div class="ml-auto">
            @if(session('user_id'))
                <a href="{{ route('logout') }}" class="flex items-center gap-1 group"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <img src="{{ asset('images/logout.webp') }}" alt="Logout"
                        class="w-8 h-8 transition duration-300 group-hover:brightness-150" />
                    <span class="text-gray-700 group-hover:text-blue-500 transition text-lg">Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            @endif
        </div>
    </div>

    <div class="w-full bg-gray-100 flex justify-center">
        @yield('content')
    </div>

    <div class="p-5 bg-gray-200 flex justify-center items-center">
        <div class="text-center">
            <p>PPG © 2025 - ISCAP</p>
            <p>Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
