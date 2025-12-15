@extends('layouts.app')

@section('title', 'Home - IsekaiPHP')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-body text-center">
                <h1 class="card-title">Welcome to IsekaiPHP</h1>
                <p class="card-text">A lightweight, Laravel-inspired micro framework for PHP.</p>
                @if(auth())
                    <p class="text-muted">You are logged in as <strong>{{ auth()->username }}</strong></p>
                @else
                    <a href="/login" class="btn btn-primary">Login</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
