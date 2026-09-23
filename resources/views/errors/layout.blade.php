<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', platform_brand('name'))</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100dvh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #374151;
            background: #f3f4f6;
            -webkit-font-smoothing: antialiased;
        }
        .page {
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem 1rem 2rem;
        }
        .card {
            width: 100%;
            max-width: 28rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            padding: 1.75rem 1.5rem 1.5rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        }
        .logo { margin-bottom: 1.25rem; }
        .logo img { max-height: 2.5rem; width: auto; max-width: min(100%, 200px); }
        .logo-fallback {
            display: inline-flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center;
            border-radius: 0.625rem; background: #10b981; font-weight: 700; font-size: 0.875rem; color: #fff;
        }
        .badge {
            display: inline-block;
            margin: 0;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            background: #ecfdf5;
            color: #047857;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .badge-warn { background: #fffbeb; color: #b45309; }
        .badge-muted { background: #f3f4f6; color: #6b7280; }
        h1 {
            margin: 1rem 0 0;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.35;
            color: #111827;
        }
        .lead {
            margin: 0.75rem 0 0;
            font-size: 0.9375rem;
            line-height: 1.6;
            color: #4b5563;
        }
        .hint {
            margin: 0.625rem 0 0;
            font-size: 0.8125rem;
            line-height: 1.5;
            color: #9ca3af;
        }
        .actions {
            margin-top: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }
        .btn {
            display: inline-flex;
            min-height: 2.75rem;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1rem;
            border-radius: 0.625rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            width: 100%;
            font-family: inherit;
        }
        .btn-primary {
            background: #059669;
            color: #fff;
        }
        .btn-primary:hover { background: #047857; }
        .btn-secondary {
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-secondary:hover { background: #f9fafb; }
        .support-link {
            display: inline-block;
            margin-top: 1rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #6b7280;
            text-decoration: none;
        }
        .support-link:hover { color: #059669; text-decoration: underline; }
        .footer {
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid #f3f4f6;
            font-size: 0.6875rem;
            color: #9ca3af;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="logo">
                @php $logoUrl = dukapro_logo_url(); @endphp
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ platform_brand('name') }}" width="200" height="50" decoding="async">
                @else
                    <span class="logo-fallback" aria-hidden="true">D</span>
                @endif
            </div>

            <p class="@yield('badge_class', 'badge')">@yield('badge')</p>
            <h1>@yield('heading')</h1>
            <p class="lead">@yield('message')</p>
            @hasSection('hint')
                <p class="hint">@yield('hint')</p>
            @endif

            <div class="actions">
                @if(error_page_can_go_back())
                    <a href="{{ error_page_previous_url() }}" class="btn btn-primary">
                        Go back
                    </a>
                    <a href="{{ error_page_home_url() }}" class="btn btn-secondary">{{ error_page_home_label() }}</a>
                @else
                    <a href="{{ error_page_home_url() }}" class="btn btn-primary">{{ error_page_home_label() }}</a>
                @endif
            </div>

            <a href="{{ error_page_support_url() }}" class="support-link" target="_blank" rel="noopener noreferrer">Help on WhatsApp</a>

            <p class="footer">{{ platform_brand('name') }}</p>
        </div>
    </div>
</body>
</html>
