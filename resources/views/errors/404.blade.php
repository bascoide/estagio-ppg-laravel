@extends('errors.layout')

@section('code', '404')
@section('title', 'Página Não Encontrada')
@section('message', $exception->getMessage() ?: 'A página que procuras não existe, foi movida ou removida definitivamente.')