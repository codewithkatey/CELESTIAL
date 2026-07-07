<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Celestial') — Inventory</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <img src="{{ asset('favicon.svg') }}" alt="Celestial" class="brand-logo" width="44" height="44">
                <div>
                    <h1>Celestial</h1>
                    <p>Clothing Inventory</p>
                </div>
            </div>
            <nav class="sidebar-nav">
                <button class="nav-item active" data-panel="products">
                    <span class="nav-icon">▦</span> Products
                </button>
                <button class="nav-item" data-panel="categories">
                    <span class="nav-icon">▤</span> Categories
                </button>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-greeting">{{ session('auth_user.name', 'Guest') }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="logout-form">
                        @csrf
                        <button type="submit" class="btn-logout">Sign Out</button>
                    </form>
                </div>
                <small>Inventory management system</small>
            </div>
        </aside>

        <main class="main-content">
            @yield('content')
        </main>
    </div>

    <div id="toast-container"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @stack('scripts')
</body>
</html>
