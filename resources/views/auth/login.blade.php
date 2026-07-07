@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="auth-form-header">
    <span class="auth-badge">Clothing Store Portal</span>
    <h2 class="auth-title">Sign in to your inventory</h2>
    <p class="auth-subtitle">Access your products, stock, and categories</p>
</div>

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
            placeholder="Enter your username"
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
            placeholder="Enter your password"
            required
            autocomplete="current-password"
        >
    </div>

    <button type="submit" class="btn btn-primary btn-block">Sign In to Dashboard</button>
</form>

<div class="auth-quick-info">
    <div class="auth-quick-item">
        <strong>Products</strong>
        <span>Add & edit clothing items</span>
    </div>
    <div class="auth-quick-item">
        <strong>Inventory</strong>
        <span>Monitor stock & pricing</span>
    </div>
</div>

<p class="auth-switch">
    Don't have an account? <a href="{{ route('register') }}">Create one</a>
</p>

<div class="auth-hint">
    <small>Demo login: <strong>admin</strong> / <strong>password</strong></small>
</div>
@endsection
