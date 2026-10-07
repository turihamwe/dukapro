<div
    x-data
    x-cloak
    x-show="$store.deleteConfirm.open"
    x-transition.opacity
    x-on:keydown.escape.window="$store.deleteConfirm.cancel()"
    class="fixed inset-0 z-[200] flex items-end justify-center p-4 sm:items-center"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="delete-confirm-title"
>
    <div
        class="absolute inset-0 bg-gray-900/50"
        x-on:click="$store.deleteConfirm.cancel()"
        aria-hidden="true"
    ></div>

    <div
        class="relative w-full max-w-md rounded-xl border border-gray-200 bg-white p-5 shadow-xl sm:p-6"
        x-on:click.stop
    >
        <h2 id="delete-confirm-title" class="text-base font-semibold text-gray-900" x-text="$store.deleteConfirm.message"></h2>
        <p
            x-show="$store.deleteConfirm.detail"
            x-text="$store.deleteConfirm.detail"
            class="mt-2 text-sm text-gray-600"
        ></p>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-button
                type="button"
                variant="secondary"
                size="md"
                class="w-full sm:w-auto"
                x-on:click="$store.deleteConfirm.cancel()"
            >
                Cancel
            </x-button>
            <x-button
                type="button"
                variant="danger"
                size="md"
                class="w-full sm:w-auto !border-red-600 !bg-red-600 !text-white hover:!bg-red-700"
                x-on:click="$store.deleteConfirm.confirm()"
            >
                Confirm Delete
            </x-button>
        </div>
    </div>
</div>
