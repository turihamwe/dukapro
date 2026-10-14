{{-- Readable dark mode: remaps common light-theme Tailwind utilities when .dark is on <html>. --}}
<style>
    .dark {
        color-scheme: dark;
    }

    /* Primary copy (was dark-on-light → light-on-dark) */
    .dark .text-gray-900,
    .dark .text-gray-800 { color: rgb(241 245 249); }
    .dark .text-gray-700 { color: rgb(226 232 240); }
    .dark .text-gray-600 { color: rgb(203 213 225); }
    .dark .text-gray-500 { color: rgb(148 163 184); }
    .dark .text-gray-400 { color: rgb(148 163 184); }
    .dark .text-black { color: rgb(248 250 252); }

    .dark .hover\:text-gray-900:hover,
    .dark .hover\:text-gray-800:hover,
    .dark .hover\:text-gray-700:hover { color: rgb(248 250 252); }
    .dark .hover\:text-gray-600:hover,
    .dark .hover\:text-gray-500:hover { color: rgb(226 232 240); }

    /* Surfaces */
    .dark .bg-white { background-color: rgb(30 41 59); }
    .dark .bg-gray-50 { background-color: rgb(15 23 42); }
    .dark .bg-gray-100 { background-color: rgb(51 65 85); }
    .dark .bg-gray-200 { background-color: rgb(71 85 105); }

    .dark .hover\:bg-white:hover { background-color: rgb(51 65 85); }
    .dark .hover\:bg-gray-50:hover { background-color: rgb(51 65 85); }
    .dark .hover\:bg-gray-100:hover { background-color: rgb(71 85 105); }

    /* Borders & dividers */
    .dark .border-gray-100,
    .dark .border-gray-200,
    .dark .border-gray-300 { border-color: rgb(71 85 105); }
    .dark .divide-gray-100 > :not([hidden]) ~ :not([hidden]),
    .dark .divide-gray-200 > :not([hidden]) ~ :not([hidden]) {
        border-color: rgb(71 85 105);
    }
    .dark .ring-gray-200,
    .dark .ring-gray-300 { --tw-ring-color: rgb(71 85 105); }

    /* Nav / accents still readable */
    .dark .bg-indigo-50 { background-color: rgb(30 27 75); }
    .dark .text-indigo-700,
    .dark .text-indigo-600 { color: rgb(165 180 252); }
    .dark .hover\:text-indigo-600:hover { color: rgb(199 210 254); }
    .dark .border-indigo-100,
    .dark .border-indigo-200 { border-color: rgb(67 56 202); }

    .dark .text-emerald-600,
    .dark .text-emerald-700 { color: rgb(110 231 183); }
    .dark .text-red-600,
    .dark .text-red-700,
    .dark .text-red-800 { color: rgb(252 165 165); }
    .dark .text-amber-700,
    .dark .text-amber-800 { color: rgb(252 211 77); }
    .dark .text-sky-700 { color: rgb(125 211 252); }

    /* Flash / alert banners */
    .dark .bg-emerald-50 { background-color: rgb(6 78 59 / 0.45); }
    .dark .text-emerald-800 { color: rgb(167 243 208); }
    .dark .border-emerald-200 { border-color: rgb(16 185 129 / 0.45); }

    .dark .bg-amber-50 { background-color: rgb(120 53 15 / 0.45); }
    .dark .text-amber-800 { color: rgb(253 230 138); }
    .dark .border-amber-200 { border-color: rgb(245 158 11 / 0.45); }

    .dark .bg-red-50 { background-color: rgb(127 29 29 / 0.45); }
    .dark .text-red-800 { color: rgb(254 202 202); }
    .dark .border-red-200 { border-color: rgb(248 113 113 / 0.45); }

    .dark .bg-indigo-50.text-indigo-800,
    .dark .alert-banner.bg-indigo-50 { background-color: rgb(30 27 75 / 0.55); }
    .dark .text-indigo-800 { color: rgb(199 210 254); }

    /* Form fields */
    .dark input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="color"]):not([type="file"]),
    .dark select,
    .dark textarea {
        background-color: rgb(30 41 59);
        border-color: rgb(100 116 139);
        color: rgb(241 245 249);
    }
    .dark input::placeholder,
    .dark textarea::placeholder {
        color: rgb(148 163 184);
    }

    /* App modals (base layout) */
    .dark .app-modal-panel,
    .dark .app-modal-header,
    .dark .app-modal-footer {
        background: rgb(30 41 59);
        border-color: rgb(71 85 105);
    }
    .dark .app-modal-header {
        border-bottom-color: rgb(71 85 105);
    }
    .dark .app-modal-footer {
        border-top-color: rgb(71 85 105);
        box-shadow: 0 -4px 16px rgb(0 0 0 / 0.35);
    }

    /* Stat / metric cards with forced bg-white */
    .dark .bg-gradient-to-br.bg-white {
        background-image: linear-gradient(to bottom right, rgb(30 41 59 / 0.9), rgb(15 23 42 / 0.95));
    }
</style>
