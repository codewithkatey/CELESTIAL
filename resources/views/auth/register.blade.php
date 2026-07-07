@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<h2 class="auth-title">Create your account</h2>
<p class="auth-subtitle">Create an account to manage your inventory</p>

@if ($errors->any())
    <div class="auth-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('register') }}" class="auth-form">
    @csrf

    <div class="form-group">
        <label for="name">Full Name</label>
        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name') }}"
            required
            autofocus
            autocomplete="name"
        >
    </div>

    <div class="form-group">
        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            value="{{ old('username') }}"
            required
            autocomplete="username"
        >
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="new-password"
        >
    </div>

    <div class="form-group">
        <label for="password_confirmation">Confirm Password</label>
        <input
            type="password"
            id="password_confirmation"
            name="password_confirmation"
            required
            autocomplete="new-password"
        >
    </div>

    <button type="submit" class="btn btn-primary btn-block">Create Account</button>
</form>

<p class="auth-switch">
    Already have an account? <a href="{{ route('login') }}">Sign in</a>
</p>
@endsection
