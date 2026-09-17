<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ platform_brand('tagline') }} — Modern POS and business management for African retailers.">
    <title>@yield('title', platform_brand('name') . ' — Run Your Shop Digitally')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            900: '#064e3b',
                        },
                        ink: {
                            950: '#0b1220',
                            900: '#0f172a',
                            800: '#1e293b',
                            700: '#334155',
                        },
                    },
                    boxShadow: {
                        'soft': '0 4px 24px -4px rgba(15, 23, 42, 0.08)',
                        'lift': '0 20px 50px -12px rgba(15, 23, 42, 0.18)',
                        'glow': '0 0 0 1px rgba(16, 185, 129, 0.15), 0 8px 32px -8px rgba(16, 185, 129, 0.35)',
                    },
                },
            },
        };
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: Inter, system-ui, sans-serif; }
        .hero-grid {
            background-image:
                linear-gradient(to right, rgba(148, 163, 184, 0.07) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(148, 163, 184, 0.07) 1px, transparent 1px);
            background-size: 48px 48px;
        }
        .mockup-glass {
            background: linear-gradient(145deg, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0.04) 100%);
            backdrop-filter: blur(12px);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
    </style>

    @stack('styles')
</head>
<body class="bg-white text-ink-900 antialiased" x-data="{ mobileNav: false }" @keydown.escape.window="mobileNav = false">
    @yield('content')

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('layouts.partials.tawk-chat')
    @stack('scripts')
</body>
</html>
