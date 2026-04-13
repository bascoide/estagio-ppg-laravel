@extends('layouts.app')

@section('content')
<div class="max-w-3xl w-full p-4 sm:p-10 bg-white mx-4 sm:mx-10 shadow-lg mb-10 grow-0">
    @include('messageError')

    <form action="{{ route('get-form') }}" method="GET" class="md:flex items-center gap-x-4">

        @if(isset($filledPlanId) && $filledPlanId)
        <input type="hidden" name="filled_plan_id" value="{{ $filledPlanId }}">
        @endif

        <label for="document" class="whitespace-nowrap">Documento:</label>
        <select name="document" id="document" class="mt-2 md:mt-0 w-full flex-grow p-2 border rounded-lg" required>
            <option value="">Selecione um documento</option>
            @foreach($documents as $document)
                @if($document['is_active'] == 0)
                    @continue
                @endif
                @if((int) request()->input('filled_plan_id', 0) == 0)
                    @if($document['type'] !== 'Plano')
                        @continue
                    @endif
                @else
                    @if($document['type'] !== 'Protocolo')
                        @continue
                    @endif
                @endif

                <option value="{{ $document['id'] }}">{{ $document['name'] }} {{ $document['type'] }}</option>

            @endforeach
        </select>
        <input type="submit" class="w-full mt-2 md:mt-0 md:w-auto bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 cursor-pointer" value="Selecionar">
    </form>

    @if(!empty($fields))
        <form method="POST" action="{{ route('print-document') }}" target="_blank">
            @csrf
            <input type="hidden" name="document_id" value="{{ $documentId ?? '' }}">
            <button type="submit" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 flex items-center gap-2">
                Visualizar Documento
                <img class="h-8" src="{{ asset('images/document_view.webp') }}" alt="Visualizar">
            </button>
        </form>

        <form action="{{ route('submit-form') }}" method="POST" id="main-form" enctype="multipart/form-data">
        @csrf

        @if(isset($filledPlanId) && $filledPlanId)
        <h3 class="text-3xl mt-4 text-blue-900">Preencha o Protocolo</h3>
        <label for="planFile">Faça upload do plano assinado: </label>
        <input type="file" name="planFile"
            class="ml-2 border p-1 cursor-pointer hover:bg-gray-300 rounded-lg border-gray-300 bg-gray-200"
            accept=".pdf" id="planFile" required>
        @else
        <h3 class="text-3xl mt-4 text-blue-900">Preencha o Plano</h3>
        @endif

        <input type="hidden" name="document_id" value="{{ $documentId ?? '' }}">

        @php $current_group = 0; @endphp

        @foreach($fields as $index => $field)
            @switch($field['data_type'])
                @case('title')
                    <h3 class="text-2xl mt-4 text-blue-900">{{ $field['name'] }}</h3>
                    @break

                @case('group')
                    @php $current_group++ @endphp
                    @break

                @case('radio')
                    <input type="hidden" name="field_ids[]" value="{{ $field['id'] }}">
                    <input type="hidden" name="field_names[]" value="{{ $field['name'] }}">
                    <label>
                        <input type="radio"
                            name="group_{{ $current_group }}"
                            value="{{ $index }}"
                            {{ $loop->first ? 'checked' : '' }}
                            class="mr-2"
                            onchange="updateRadioGroup({{ $current_group }})"
                            required>
                        {{ $field['name'] }}
                    </label>
                    <br>
                    <input type="hidden" name="field_values[]" id="field_value_{{ $index }}" value="{{ $loop->first ? 'true' : 'false' }}">
                    @break

                @case('checkbox')
                    <input type="hidden" name="field_ids[]" value="{{ $field['id'] }}">
                    <input type="hidden" name="field_names[]" value="{{ $field['name'] }}">
                    <label>
                        <input type="checkbox"
                            id="checkbox_{{ $index }}"
                            class="mr-2"
                            onchange="document.getElementById('field_value_{{ $index }}').value = this.checked ? 'true' : 'false'">
                        {{ $field['name'] }}
                    </label>
                    <br>
                    <input type="hidden" name="field_values[]" id="field_value_{{ $index }}" value="false">
                    @break

                @case('nif')
                    <input type="hidden" name="field_ids[]" value="{{ $field['id'] }}">
                    <input type="hidden" name="field_names[]" value="{{ $field['name'] }}">
                    <label for="field_{{ $field['id'] }}">{{ $field['name'] }}:</label>
                    <input type="text"
                        name="field_values[]"
                        id="field_{{ $field['id'] }}"
                        class="w-full p-2 border border-gray-300 bg-gray-50 rounded-lg nif-input"
                        pattern="\d{9}"
                        title="NIF deve conter 9 dígitos."
                        required>
                    <div id="nif-error-{{ $field['id'] }}" class="text-red-500 hidden mt-1">
                        NIF inválido. Por favor, insira um NIF válido.
                    </div>
                    @break

                @case('nipc')
                    <input type="hidden" name="field_ids[]" value="{{ $field['id'] }}">
                    <input type="hidden" name="field_names[]" value="{{ $field['name'] }}">
                    <label for="field_{{ $field['id'] }}">{{ $field['name'] }}:</label>
                    <input type="text"
                        name="field_values[]"
                        id="field_{{ $field['id'] }}"
                        class="w-full p-2 border border-gray-300 bg-gray-50 rounded-lg nipc-input"
                        pattern="[5-9]\d{8}"
                        title="NIPC deve começar com 5-9 e conter 9 dígitos."
                        required>
                    <div id="nipc-error-{{ $field['id'] }}" class="text-red-500 hidden mt-1">
                        NIPC inválido. Deve começar com 5-9 e ter 9 dígitos válidos.
                    </div>
                    @break

                @case('course')
                    <input type="hidden" name="field_ids[]" value="{{ $field['id'] }}">
                    <input type="hidden" name="field_names[]" value="{{ $field['name'] }}">
                    <input type="hidden" name="field_values[]" value="{{ $userCourseName }}">
                    @break

                @case('professor')
                    <input type="hidden" name="field_ids[]" value="{{ $field['id'] }}">
                    <input type="hidden" name="field_names[]" value="{{ $field['name'] }}">
                    <label>{{ $field['name'] }}:</label>
                    <input list="field_{{ $field['id'] }}" name="field_values[]" class="w-full p-2 border border-gray-300 bg-gray-50 rounded-lg">
                    <datalist id="field_{{ $field['id'] }}">
                        @foreach($availableProfessors as $professor)
                        <option value="{{ $professor['name'] }}"></option>
                        @endforeach
                    </datalist>
                    @break

                @case('NA start')
                    <div class="na-toggle-container mt-2">
                        <input type="checkbox"
                            onclick="toggleContent({{ $field['id'] }})"
                            id="na-toggle-{{ $field['id'] }}">
                        <label for="na-toggle-{{ $field['id'] }}">Não aplicável</label>
                        <div id="NA-{{ $field['id'] }}">
                    @break

                @case('NA end')
                        </div>
                    </div>
                    <hr class="my-6 border-gray-300">
                    @break

                @default
                    <input type="hidden" name="field_ids[]" value="{{ $field['id'] }}">
                    <input type="hidden" name="field_names[]" value="{{ $field['name'] }}">
                    <label for="field_{{ $field['id'] }}">{{ $field['name'] }}:</label>
                    <input type="{{ $field['data_type'] }}"
                        name="field_values[]"
                        id="field_{{ $field['id'] }}"
                        class="w-full p-2 border border-gray-300 bg-gray-50 rounded-lg"
                        {{ !in_array($field['data_type'], ['checkbox', 'radio']) ? 'required' : '' }}>
            @endswitch
        @endforeach

        <input class="mt-4 hover:bg-blue-600 bg-blue-900 p-2 w-full cursor-pointer text-white" type="submit" value="Submeter" />
        </form>
    @endif
</div>

<script src="{{ asset('js/radioForm.js') }}"></script>
<script src="{{ asset('js/toggleNaDiv.js') }}"></script>
<script src="{{ asset('js/nifValidation.js') }}"></script>
<script src="{{ asset('js/nipcValidation.js') }}"></script>
@endsection
