@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">
    @include('messageError')
    <form action="{{ route('president-list') }}" method="POST">
        @csrf
        <h1 class="bold text-2xl">Emails presidenciais</h1>

        <input type="email" placeholder="Email presidencial" class="mt-4 border-s border-grey-100 p-2 bg-gray-100 w-full"
            name="new_president_email" required>
        <br>
        <button type="submit" class="mt-4 p-2 bg-blue-500 w-full cursor-pointer hover:bg-blue-600 text-white">Adicionar</button>
    </form>

    <ul class="mt-4">
        @if(!empty($presidentEmails))
            @foreach($presidentEmails as $email)
                @if(!is_array($email) || !isset($email['id'], $email['email']))
                    @continue
                @endif
                <li class="flex items-center p-2 border-b hover:bg-gray-50">
                    <div class="flex-grow">
                        <span class="flex-grow w-full items-start">{{ $email['email'] }}</span>
                    </div>
                    <form method="POST" action="{{ route('delete-president-email') }}" class="ml-4">
                        @csrf
                        <input type="hidden" name="email_id" value="{{ $email['id'] }}">
                        <button type="submit" class="text-red-500 hover:text-red-700 cursor-pointer p-1 rounded-full hover:bg-red-100">
                            ×
                        </button>
                    </form>
                </li>
            @endforeach
        @else
            <li class="p-2 text-gray-500">Nenhum email encontrado.</li>
        @endif
    </ul>
</div>
@endsection
