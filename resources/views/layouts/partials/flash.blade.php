@if(session('import_errors') && count(session('import_errors')))
    <x-alert type="warning" class="mb-6">
        <p class="font-medium">Some rows were skipped:</p>
        <ul class="mt-2 list-inside list-disc text-xs">
            @foreach(array_slice(session('import_errors'), 0, 8) as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
@if(session('welcome_message'))
    <x-alert type="success" class="mb-6">{{ session('welcome_message') }}</x-alert>
@endif
@if(session('success'))
    <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
@endif
@if(session('warning'))
    <x-alert type="warning" class="mb-6">{{ session('warning') }}</x-alert>
@endif
@if($errors->any())
    <x-alert type="error" class="mb-6">
        <ul class="list-inside list-disc space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
