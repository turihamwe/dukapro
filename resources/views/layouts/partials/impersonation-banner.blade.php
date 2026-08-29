@if(\App\Support\Impersonation::isActive())
    <div class="border-b border-amber-300 bg-amber-50 px-4 py-2 text-center text-xs text-amber-900">
        <span>Impersonating <strong>{{ auth()->user()->business->name ?? 'business' }}</strong> as owner.</span>
        <form method="POST" action="{{ route('impersonation.leave') }}" class="ml-2 inline">
            @csrf
            <button type="submit" class="font-medium text-amber-800 underline decoration-amber-400/60 underline-offset-2 hover:text-amber-950">
                Exit impersonation
            </button>
        </form>
    </div>
@endif
