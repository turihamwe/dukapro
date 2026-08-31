<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance — {{ platform_brand('name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="max-w-md text-center">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-3xl">🔧</div>
        <h1 class="text-2xl font-bold text-gray-900">Under Maintenance</h1>
        <p class="mt-3 text-gray-600">{{ platform_brand('name') }} is temporarily unavailable while we perform system updates. Please check back shortly.</p>
        <a href="{{ route('portal') }}" class="mt-8 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500">Return to portal</a>
    </div>
</body>
</html>
