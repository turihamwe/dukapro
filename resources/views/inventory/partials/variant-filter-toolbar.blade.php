@php
    /** @var \Illuminate\Support\Collection|\App\Models\Product[] $variants */
    $attributeGroups = [];
    foreach ($variants as $variant) {
        $values = $variant->attribute_values ?? [];
        if (! is_array($values)) {
            continue;
        }
        foreach ($values as $name => $value) {
            $name = (string) $name;
            $value = (string) $value;
            if ($name === '' || $value === '') {
                continue;
            }
            if (! isset($attributeGroups[$name])) {
                $attributeGroups[$name] = [];
            }
            $attributeGroups[$name][$value] = ($attributeGroups[$name][$value] ?? 0) + 1;
        }
    }
    foreach ($attributeGroups as $name => $counts) {
        ksort($attributeGroups[$name]);
    }
    ksort($attributeGroups);
    $totalVariants = $variants->count();
@endphp

@if($totalVariants > 0 && $attributeGroups !== [])
    <div id="variant-filter-toolbar" class="mb-5 rounded-xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-4 shadow-sm">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-sm font-semibold text-gray-900">Filter variants</p>
                <p class="text-xs text-gray-500">Narrow the list by size, color, or other options.</p>
            </div>
            <p id="variant-filter-count" class="text-xs font-medium text-indigo-700 tabular-nums">
                Showing {{ $totalVariants }} of {{ $totalVariants }}
            </p>
        </div>

        <div class="space-y-3">
            @foreach($attributeGroups as $attributeName => $valueCounts)
                @php
                    $slug = \Illuminate\Support\Str::slug($attributeName);
                    $isColor = stripos($attributeName, 'color') !== false || stripos($attributeName, 'colour') !== false;
                @endphp
                <div class="variant-filter-group" data-filter-attribute="{{ $slug }}" data-filter-label="{{ $attributeName }}">
                    <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ $attributeName }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                                class="variant-filter-pill is-active rounded-full border border-indigo-600 bg-indigo-600 px-3 py-1 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                                data-filter-value=""
                                aria-pressed="true">
                            All
                        </button>
                        @foreach($valueCounts as $optionValue => $count)
                            <button type="button"
                                    class="variant-filter-pill rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700 shadow-sm transition hover:border-indigo-300 hover:text-indigo-800"
                                    data-filter-value="{{ $optionValue }}"
                                    aria-pressed="false">
                                @if($isColor)
                                    <span class="mr-1.5 inline-block h-2 w-2 rounded-full border border-gray-300 bg-gray-200 align-middle variant-color-dot" data-color-label="{{ $optionValue }}" aria-hidden="true"></span>
                                @endif
                                <span>{{ $optionValue }}</span>
                                <span class="ml-1 tabular-nums text-[10px] font-normal text-gray-400">({{ $count }})</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <button type="button" id="variant-filter-reset"
                class="mt-3 text-xs font-medium text-indigo-600 hover:text-indigo-800 hidden">
            Clear all filters
        </button>

        <p id="variant-filter-empty" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
            No variants match these filters. Try another combination or clear filters.
        </p>
    </div>

    @once
        @push('scripts')
        <script>
        (function () {
            var toolbar = document.getElementById('variant-filter-toolbar');
            if (!toolbar) return;

            var items = function () {
                return Array.prototype.slice.call(document.querySelectorAll('.variant-catalog-item'));
            };

            var selections = {};

            function parseFilters(el) {
                try {
                    return JSON.parse(el.getAttribute('data-variant-filters') || '{}');
                } catch (e) {
                    return {};
                }
            }

            function slugify(text) {
                return String(text).toLowerCase().trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            function itemMatches(el) {
                var attrs = parseFilters(el);
                for (var key in selections) {
                    if (!selections.hasOwnProperty(key)) continue;
                    var wanted = selections[key];
                    if (!wanted) continue;
                    var label = toolbar.querySelector('[data-filter-attribute="' + key + '"]');
                    var human = label ? label.getAttribute('data-filter-label') : key;
                    var actual = attrs[human];
                    if (String(actual) !== String(wanted)) {
                        return false;
                    }
                }
                return true;
            }

            function applyFilters() {
                var list = items();
                var visible = 0;
                list.forEach(function (el) {
                    var show = itemMatches(el);
                    el.classList.toggle('hidden', !show);
                    if (show) visible++;
                });

                var countEl = document.getElementById('variant-filter-count');
                if (countEl) {
                    countEl.textContent = 'Showing ' + visible + ' of ' + list.length;
                }

                var emptyEl = document.getElementById('variant-filter-empty');
                if (emptyEl) {
                    emptyEl.classList.toggle('hidden', visible > 0 || list.length === 0);
                }

                var resetBtn = document.getElementById('variant-filter-reset');
                var anyActive = Object.keys(selections).some(function (k) { return selections[k]; });
                if (resetBtn) {
                    resetBtn.classList.toggle('hidden', !anyActive);
                }
            }

            function setPillState(group, value) {
                group.querySelectorAll('.variant-filter-pill').forEach(function (pill) {
                    var active = (pill.getAttribute('data-filter-value') || '') === (value || '');
                    pill.classList.toggle('is-active', active);
                    pill.classList.toggle('border-indigo-600', active);
                    pill.classList.toggle('bg-indigo-600', active);
                    pill.classList.toggle('text-white', active);
                    pill.classList.toggle('border-gray-200', !active);
                    pill.classList.toggle('bg-white', !active);
                    pill.classList.toggle('text-gray-700', !active);
                    pill.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            }

            toolbar.querySelectorAll('.variant-filter-group').forEach(function (group) {
                var attr = group.getAttribute('data-filter-attribute');
                selections[attr] = '';

                group.addEventListener('click', function (e) {
                    var pill = e.target.closest('.variant-filter-pill');
                    if (!pill || !group.contains(pill)) return;
                    var value = pill.getAttribute('data-filter-value') || '';
                    selections[attr] = value;
                    setPillState(group, value);
                    applyFilters();
                });
            });

            var resetBtn = document.getElementById('variant-filter-reset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    toolbar.querySelectorAll('.variant-filter-group').forEach(function (group) {
                        var attr = group.getAttribute('data-filter-attribute');
                        selections[attr] = '';
                        setPillState(group, '');
                    });
                    applyFilters();
                });
            }

            var colorMap = {
                red: '#ef4444', blue: '#3b82f6', green: '#22c55e', black: '#111827', white: '#f9fafb',
                yellow: '#eab308', orange: '#f97316', purple: '#a855f7', pink: '#ec4899', gray: '#9ca3af',
                grey: '#9ca3af', navy: '#1e3a8a', beige: '#d6d3d1', brown: '#92400e', gold: '#ca8a04'
            };
            toolbar.querySelectorAll('.variant-color-dot').forEach(function (dot) {
                var label = (dot.getAttribute('data-color-label') || '').toLowerCase();
                var hex = colorMap[label] || null;
                if (hex) {
                    dot.style.backgroundColor = hex;
                    if (label === 'white') {
                        dot.style.boxShadow = 'inset 0 0 0 1px #d1d5db';
                    }
                }
            });

            applyFilters();
        })();
        </script>
        @endpush
    @endonce
@endif
