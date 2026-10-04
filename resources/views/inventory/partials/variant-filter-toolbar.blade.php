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
    <div id="variant-filter-toolbar" class="mb-3 flex flex-wrap items-center gap-x-2 gap-y-1.5">
        <span class="shrink-0 text-xs font-medium text-gray-500">Filter</span>
        @foreach($attributeGroups as $attributeName => $valueCounts)
            @php $slug = \Illuminate\Support\Str::slug($attributeName); @endphp
            <div class="variant-filter-group shrink-0" data-filter-attribute="{{ $slug }}" data-filter-label="{{ $attributeName }}">
                <label for="variant-filter-{{ $slug }}" class="sr-only">{{ $attributeName }}</label>
                <select id="variant-filter-{{ $slug }}"
                        class="variant-filter-select max-w-[9rem] rounded-lg border-gray-300 bg-white py-1 pl-2 pr-7 text-xs font-medium text-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-[10rem]">
                    <option value="">All {{ $attributeName }}</option>
                    @foreach($valueCounts as $optionValue => $count)
                        <option value="{{ $optionValue }}">{{ $optionValue }} ({{ $count }})</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <button type="button" id="variant-filter-reset"
                class="hidden shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-800">
            Reset
        </button>
        <span id="variant-filter-count" class="ml-auto shrink-0 text-xs tabular-nums text-gray-500">
            {{ $totalVariants }}/{{ $totalVariants }}
        </span>
    </div>

    <p id="variant-filter-empty" class="mb-3 hidden text-xs text-amber-800">
        No variants match — change a filter or reset.
    </p>

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

            function itemMatches(el) {
                var attrs = parseFilters(el);
                for (var key in selections) {
                    if (!selections.hasOwnProperty(key)) continue;
                    var wanted = selections[key];
                    if (!wanted) continue;
                    var group = toolbar.querySelector('[data-filter-attribute="' + key + '"]');
                    var human = group ? group.getAttribute('data-filter-label') : key;
                    if (String(attrs[human]) !== String(wanted)) {
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
                    countEl.textContent = visible + '/' + list.length;
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

            toolbar.querySelectorAll('.variant-filter-select').forEach(function (select) {
                var group = select.closest('.variant-filter-group');
                var attr = group.getAttribute('data-filter-attribute');
                selections[attr] = '';

                select.addEventListener('change', function () {
                    selections[attr] = select.value || '';
                    applyFilters();
                });
            });

            var resetBtn = document.getElementById('variant-filter-reset');
            if (resetBtn) {
                resetBtn.addEventListener('click', function () {
                    toolbar.querySelectorAll('.variant-filter-select').forEach(function (select) {
                        select.value = '';
                        var group = select.closest('.variant-filter-group');
                        selections[group.getAttribute('data-filter-attribute')] = '';
                    });
                    applyFilters();
                });
            }

            applyFilters();
        })();
        </script>
        @endpush
    @endonce
@endif
