@extends('layouts.admin')

@section('content')
<div class="flex-grow h-screen p-4 bg-white rounded shadow-md overflow-y-scroll">
    @include('messageError')
    <h3 class="bold text-2xl">Sign up Admin</h3>

    <div id="errorDiv"></div>

    <form method="POST" action="{{ route('create-admin') }}" onsubmit="comparePassword(event)">
        @csrf
        <input type="email" name="email" class="mt-4 border-s border-grey-100 p-2 bg-gray-100 w-full"
            placeholder="Email" required>
        <br>
        <input type="password" name="password" class="mt-4 border-s border-grey-100 p-2 bg-gray-100 w-full"
            placeholder="Password" id="passwordInput" required>
        <br>
        <input type="password" name="conf-password" class="mt-4 border-s border-grey-100 p-2 bg-gray-100 w-full"
            placeholder="Confirm password" id="comfirmPasswordInput" required>
        <br><br>
        <input type="submit" class="mt-4 p-2 bg-blue-500 w-full cursor-pointer hover:bg-blue-600 text-white"
            value="Registar" name="register">
    </form>
</div>

<script src="{{ asset('js/accountUser.js') }}"></script>
@endsection
