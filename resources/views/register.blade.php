@extends('layouts.app')

@section('content')
<div class="mx-4 mt-10 mb-10 w-full max-w-7xl rounded-lg border border-gray-200 border-t bg-white p-6 shadow-lg sm:mx-10 sm:p-10">
    <div class="mb-1 text-sm font-bold uppercase tracking-wider text-blue-700">Novo utilizador</div>
    <h3 class="text-3xl font-bold text-gray-900">Registo</h3>
    <p class="mb-6 mt-2 max-w-3xl text-gray-600">Crie a sua conta institucional para submeter e acompanhar documentos acad&eacute;micos.</p>

    @include('messageError')

    <div id="errorDiv"></div>

    <form method="POST" action="{{ route('register') }}" onsubmit="comparePassword(event)" class="grid gap-4">
        @csrf
        <input type="text" name="name" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Nome completo" required>
        <input type="email" name="email" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="E-mail" required>
        <input type="password" name="password" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Palavra-passe" id="passwordInput" required>
        <input type="password" name="conf-password" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Confirmar palavra-passe" id="comfirmPasswordInput" required>
        <div class="flex flex-wrap gap-4">
            <select name="CourseType" id="typecourse" class="w-full flex-1 rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="">Selecione um tipo de curso</option>
                @foreach($coursesTypes as $courseType)
                    <option value="{{ $courseType['id'] }}">{{ $courseType['name'] }}</option>
                @endforeach
            </select>
            <select name="Course" id="course" class="w-full flex-1 rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm focus:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="">Selecione um curso</option>
            </select>
        </div>
        <a href="{{ route('login') }}" class="text-blue-700 hover:text-blue-700 hover:underline">J&aacute; tem uma conta? Inicie sess&atilde;o!</a>

        <input type="submit" class="w-full cursor-pointer rounded-lg bg-blue-900 px-4 py-3 font-bold text-white hover:bg-blue-600"
            value="Registar" name="register">
    </form>
</div>

<script>
    const allCourses = {!! json_encode($courses) !!};
</script>

<script src="{{ asset('js/accountUser.js') }}"></script>
<script src="{{ asset('js/courseByType.js') }}"></script>
@endsection
