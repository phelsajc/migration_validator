@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid">
        <h1 class="mb-4">Welcome, {{ Session::get('firstname') }}</h1>
        <p>You are logged in!</p>
        {{-- {{dd(session()->all())}} --}}
    </div>
@endsection
