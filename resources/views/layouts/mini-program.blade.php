<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Calamus Mini Program')</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}" />
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        :root {
            --accent: #6366f1;
            --accent-soft: #e0e7ff;
            --bg-page: #f9fafb;
            --bg-card: #ffffff;
            --text-title: #111827;
            --text-body: #4b5563;
            --text-muted: #9ca3af;
            --border: #f3f4f6;
            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 18px;
            --shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.02);
        }
        
        body { 
            background-color: var(--bg-page); 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            min-height: 100vh;
            color: var(--text-body);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        
        .mini-app-container { 
            max-width: 460px; 
            margin: 0 auto;
            padding: 0 12px 60px;
        }
        
        .navbar-mini {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 10px 14px;
            z-index: 1100;
            position: sticky;
            top: 0;
        }
        
        .navbar-brand {
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--accent) !important;
        }
        
        /* Interactive feedback */
        .tap-active { transition: transform 0.1s ease; }
        .tap-active:active { transform: scale(0.97); }
        
        /* Compact typography */
        h4, h5, h6 { margin-bottom: 0.4rem; color: var(--text-title); letter-spacing: -0.01em; }
        .small-meta { font-size: 0.75rem; color: var(--text-muted); font-weight: 500; }
        
        @yield('styles')
    </style>
</head>
<body>

@hasSection('nav-title')
<nav class="navbar navbar-mini sticky-top">
    <div class="container d-flex justify-content-between align-items-center">
        <span class="navbar-brand mb-0 h1 font-weight-bold text-primary">
            @yield('nav-title', 'Calamus')
        </span>
        @yield('nav-actions')
    </div>
</nav>
@endif

<div class="container mini-app-container">
    <br>
    @yield('content')
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')

</body>
</html>
