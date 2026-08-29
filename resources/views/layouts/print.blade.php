<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Print') — DukaPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 1.2cm; }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-white text-gray-900 antialiased">
    <div class="no-print border-b border-gray-200 bg-gray-50 px-4 py-3">
        <div class="mx-auto flex max-w-4xl items-center justify-between gap-4">
            <p class="text-sm text-gray-600">Preview — use your browser's print dialog to save as PDF.</p>
            <div class="flex gap-2">
                <button type="button" onclick="window.print()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Print / Save PDF</button>
                <button type="button" onclick="window.close()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Close</button>
            </div>
        </div>
    </div>
    <main class="mx-auto max-w-4xl px-4 py-8">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
