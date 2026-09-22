<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', platform_brand('name'))</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @stack('styles')
</head>
<body class="min-h-[100dvh] bg-gray-50 text-gray-900 antialiased">

    <main class="mx-auto flex w-full max-w-lg flex-col items-stretch px-4 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-[max(1rem,env(safe-area-inset-top))] sm:px-6 sm:py-8 md:min-h-[100dvh] md:justify-center md:py-10">
        <div class="@yield('container_class', 'w-full')">
            @yield('content')
        </div>
    </main>

    @include('layouts.partials.tawk-chat')
    @stack('scripts')
    @include('layouts.partials.form-validation')
    <script>
    (function () {
        document.querySelectorAll('.alert-dismiss').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var banner = btn.closest('.alert-banner');
                if (banner) banner.remove();
            });
        });

        document.querySelectorAll('.password-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = btn.parentElement.querySelector('input');
                if (!input) return;
                var open = btn.querySelector('.eye-open');
                var closed = btn.querySelector('.eye-closed');
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                open.classList.toggle('hidden', show);
                closed.classList.toggle('hidden', !show);
            });
        });
    })();
    </script>
</body>
</html>
