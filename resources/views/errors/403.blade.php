@extends('errors.layout')

@section('code', '403')
@section('title', 'Acesso Restrito')
@section('message', $exception->getMessage() ?: 'Não tens as permissões necessárias para aceder a esta página.')