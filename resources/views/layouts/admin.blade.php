<!DOCTYPE html>
<html lang="pt-pt" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link href="{{ asset('output.css') }}" rel="stylesheet">
    <title>PPG - Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="min-h-screen bg-gray-100 text-gray-800">
    <header class="sticky top-0 z-50 flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 bg-white px-6 py-4 shadow-sm">
        <a href="{{ route('view-pending-documents') }}" class="flex min-w-0 items-center gap-3 text-gray-900">
            <img src="{{ asset('images/logo.webp') }}" alt="logo" class="h-10 flex-shrink-0">
            <span class="text-2xl font-bold text-gray-900">PPG</span>
        </a>

        <div class="flex flex-wrap items-center justify-end gap-3">
            @if(session('user_id'))
                <a href="#" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 font-semibold text-gray-900 shadow-sm transition hover:bg-gray-100 hover:text-blue-700"
                   onclick="event.preventDefault(); window.notifyLogoutTabs && window.notifyLogoutTabs(); document.getElementById('logout-form').submit();">
                    <img src="{{ asset('images/logout.webp') }}" alt="Terminar sess&atilde;o" class="h-3.5 w-3.5">
                    <span>Terminar sess&atilde;o</span>
                </a>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            @endif
        </div>
    </header>

    <main class="flex min-h-screen bg-gray-100">
        @include('adminDashboard.sideBar')
        <section class="min-w-0 flex-1 bg-gray-100 p-4">
            @yield('content')
        </section>
    </main>

    <footer class="flex items-center justify-center border-t border-gray-200 bg-white py-4 text-gray-600">
        <div class="text-center">
            <p>PPG &copy; 2025 - ISCAP</p>
            <p>Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="{{ asset('js/sessionSync.js') }}"></script>
</body>
</html>
