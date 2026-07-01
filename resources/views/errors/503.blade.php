@extends('errors.layout')

@section('code', '503')
@section('title', 'Estamos em Manutenção')
@section('message', $exception->getMessage() ?: 'Estamos a fazer algumas melhorias planeadas. Voltamos muito em breve!')