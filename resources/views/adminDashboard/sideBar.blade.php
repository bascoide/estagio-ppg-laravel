<aside class="max-w-md flex-shrink-0 bg-gray-100 p-4">
    <div class="sticky top-0 h-screen overflow-y-auto rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <nav class="grid gap-1">
            <div>
                <button onclick="toggleDropdown('protocol', this)" class="flex w-full items-center justify-between rounded-lg px-4 py-2 text-left font-semibold text-gray-800 transition hover:bg-gray-100 hover:text-blue-700">
                    Protocolos <span data-sidebar-arrow>&#9650;</span>
                </button>
                <div id="protocol" class="border-s border-gray-200 pl-4">
                    <a href="{{ route('view-pending-documents') }}" class="block rounded-lg px-4 py-2 text-gray-600 transition hover:bg-gray-100 hover:text-blue-700">Pendentes</a>
                    <a href="{{ route('need-validation-documents') }}" class="block rounded-lg px-4 py-2 text-gray-600 transition hover:bg-gray-100 hover:text-blue-700">Por validar</a>
                    <a href="{{ route('view-validation-documents') }}" class="block rounded-lg px-4 py-2 text-gray-600 transition hover:bg-gray-100 hover:text-blue-700">Validados</a>
                </div>

                <hr class="my-2 border-t border-gray-200">

                <button onclick="toggleDropdown('document', this)" class="flex w-full items-center justify-between rounded-lg px-4 py-2 text-left font-semibold text-gray-800 transition hover:bg-gray-100 hover:text-blue-700">
                    Documentos <span data-sidebar-arrow>&#9650;</span>
                </button>
                <div id="document" class="border-s border-gray-200 pl-4">
                    <a href="{{ route('upload-document-form') }}" class="block rounded-lg px-4 py-2 text-gray-600 transition hover:bg-gray-100 hover:text-blue-700">Carregar</a>
                    <a href="{{ route('show-documents') }}" class="block rounded-lg px-4 py-2 text-gray-600 transition hover:bg-gray-100 hover:text-blue-700">Consultar</a>
                </div>

                <hr class="my-2 border-t border-gray-200">

                <button onclick="toggleDropdown('register', this)" class="flex w-full items-center justify-between rounded-lg px-4 py-2 text-left font-semibold text-gray-800 transition hover:bg-gray-100 hover:text-blue-700">
                    Registos <span data-sidebar-arrow>&#9650;</span>
                </button>
                <div id="register" class="border-s border-gray-200 pl-4">
                    <a href="{{ route('create-admin') }}" class="block rounded-lg px-4 py-2 text-gray-600 transition hover:bg-gray-100 hover:text-blue-700">Criar</a>
                    <a href="{{ route('show-users') }}" class="block rounded-lg px-4 py-2 text-gray-600 transition hover:bg-gray-100 hover:text-blue-700">Consultar</a>
                    <a href="{{ route('courses') }}" class="block rounded-lg px-4 py-2 text-gray-600 transition hover:bg-gray-100 hover:text-blue-700">Cursos</a>
                </div>
            </div>

            <hr class="my-2 border-t border-gray-200">
            <a href="{{ route('professor-search') }}" class="block rounded-lg px-4 py-2 font-semibold text-gray-800 transition hover:bg-gray-100 hover:text-blue-700">Professores</a>

            <hr class="my-2 border-t border-gray-200">
            <a href="{{ route('admin-documentation') }}" class="block rounded-lg px-4 py-2 font-semibold text-gray-800 transition hover:bg-gray-100 hover:text-blue-700">Documenta&ccedil;&atilde;o</a>

            <hr class="my-2 border-t border-gray-200">
            <a href="{{ route('admin-logs') }}" class="block rounded-lg px-4 py-2 font-semibold text-gray-800 transition hover:bg-gray-100 hover:text-blue-700">Registos de atividade</a>
        </nav>
    </div>
</aside>

<script src="{{ asset('js/sidebar.js') }}"></script>
