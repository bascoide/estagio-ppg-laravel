@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-auto">
    @include('messageError')
    <h1 class="bold text-2xl mb-4">Professor</h1>
    <h2 class="text-lg text-gray-700 mb-6">{{ $professor['name'] ?? '' }}</h2>

    <form action="{{ route('create-report') }}" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="professor_id" value="{{ $professor['id'] ?? '' }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
                <select name="course_id" id="course_id" required
                    class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Selecione um curso</option>
                    @foreach($courses as $course)
                        <option value="{{ $course['id'] }}">{{ $course['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="school_year" class="block text-sm font-medium text-gray-700 mb-1">Ano Letivo</label>
                <select name="school_year" id="school_year" required
                    class="w-full p-2 border rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Selecione um Ano Letivo</option>
                    @foreach($schoolYears as $schoolYear)
                        <option value="{{ $schoolYear }}">{{ $schoolYear }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                Gerar Relatório
            </button>
        </div>
    </form>
    <hr class="border-gray-300 my-2">
    <form action="{{ route('create-status-excel') }}" method="POST">
        @csrf
        <input type="hidden" value="{{ $professor['name'] ?? '' }}" name="teacher_name">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
            Gerar excel do estado dos alunos
        </button>
    </form>
</div>
@endsection
