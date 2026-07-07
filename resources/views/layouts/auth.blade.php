<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign In') — Celestial</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-body">
    <div class="auth-split">
        <aside class="auth-hero">
            <div class="auth-hero-grid">
                <img src="{{ asset('assets/images/dom-hill-nimElTcTNyY-unsplash.jpg') }}" alt="Denim jacket" class="auth-hero-img img-1">
                <img src="{{ asset('assets/images/milada-vigerova-p8Drpg_duLw-unsplash.jpg') }}" alt="Fashion apparel" class="auth-hero-img img-2">
                <img src="{{ asset('assets/images/raquel-gambin-kS3YkVtf85U-unsplash.jpg') }}" alt="Clothing collection" class="auth-hero-img img-3">
                <img src="{{ asset('assets/images/tobias-tullius-Fg15LdqpWrs-unsplash.jpg') }}" alt="Accessories" class="auth-hero-img img-4">
            </div>
            <div class="auth-hero-overlay"></div>
            <div class="auth-hero-content">
                <div class="auth-hero-brand">
                    <img src="{{ asset('favicon.svg') }}" alt="Celestial" class="brand-logo">
                    <div>
                        <h1>Celestial</h1>
                        <p>Clothing Inventory</p>
                    </div>
                </div>
                <h2 class="auth-hero-title">Manage your apparel collection in one place</h2>
                <p class="auth-hero-text">Track products, stock levels, categories, and pricing for your clothing business.</p>
                <div class="auth-features">
                    <span class="auth-feature">👕 Apparel</span>
                    <span class="auth-feature">📦 Stock</span>
                    <span class="auth-feature">🏷️ Pricing</span>
                    <span class="auth-feature">🗂️ Categories</span>
                </div>
            </div>
        </aside>

        <main class="auth-panel">
            <div class="auth-card">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
