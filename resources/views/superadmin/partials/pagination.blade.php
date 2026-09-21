@isset($paginator)
    @if($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->total() > 0)
        <div class="border-t border-gray-200 bg-gray-50/60 px-3 py-3 sm:px-4 sm:py-4">
            {{ $paginator->links() }}
        </div>
    @endif
@endisset
