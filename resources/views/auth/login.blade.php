@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<h2 class="auth-title">Welcome back</h2>
<p class="auth-subtitle">Sign in to manage your clothing inventory</p>

@if ($errors->any())
    <div class="auth-error">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}" class="auth-form">
    @csrf

    <div class="form-group">
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            value="{{ old('username', 'admin') }}"
            required
            autofocus
            autocomplete="username"
        >
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            value="password"
            required
            autocomplete="current-password"
        >
    </div>

    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
</form>

<p class="auth-switch">
    Don't have an account? <a href="{{ route('register') }}">Create one</a>
</p>

<div class="auth-hint">
    <small>Default: <strong>admin</strong> / <strong>password</strong> — you can change these before signing in</small>
</div>
@endsection
