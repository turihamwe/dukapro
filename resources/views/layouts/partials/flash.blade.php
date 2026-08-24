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
