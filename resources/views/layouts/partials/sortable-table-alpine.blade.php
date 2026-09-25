<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('sortableTable', function () {
        return {
            sortKey: null,
            sortDir: 'asc',
            sort: function (key) {
                if (this.sortKey === key) {
                    this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortKey = key;
                    this.sortDir = 'asc';
                }
                var tbody = this.$refs.tbody;
                if (!tbody) {
                    return;
                }
                var groups = this.collectGroups(tbody);
                var emptyGroups = groups.filter(function (g) {
                    return g[0].getAttribute('data-sort-empty') === '1';
                });
                var dataGroups = groups.filter(function (g) {
                    return g[0].getAttribute('data-sort-empty') !== '1';
                });
                var self = this;
                dataGroups.sort(function (a, b) {
                    return self.compareRows(a[0], b[0], key);
                });
                dataGroups.forEach(function (group) {
                    group.forEach(function (row) {
                        tbody.appendChild(row);
                    });
                });
                emptyGroups.forEach(function (group) {
                    group.forEach(function (row) {
                        tbody.appendChild(row);
                    });
                });
            },
            collectGroups: function (container) {
                var rows = Array.from(container.children);
                var groups = [];
                var i = 0;
                while (i < rows.length) {
                    var row = rows[i];
                    if (row.getAttribute('data-sort-follows') === '1') {
                        i++;
                        continue;
                    }
                    var group = [row];
                    i++;
                    while (i < rows.length && rows[i].getAttribute('data-sort-follows') === '1') {
                        group.push(rows[i]);
                        i++;
                    }
                    groups.push(group);
                }
                return groups;
            },
            getSortValue: function (row, key) {
                var val = row.getAttribute('data-sort-' + key);
                return val === null ? '' : val;
            },
            compareRows: function (rowA, rowB, key) {
                var av = this.getSortValue(rowA, key);
                var bv = this.getSortValue(rowB, key);
                var an = parseFloat(av);
                var bn = parseFloat(bv);
                var numeric = av !== '' && bv !== '' && !isNaN(an) && !isNaN(bn);
                var cmp;
                if (numeric) {
                    cmp = an - bn;
                } else {
                    cmp = av.localeCompare(bv, undefined, { sensitivity: 'base', numeric: true });
                }
                return this.sortDir === 'asc' ? cmp : -cmp;
            },
            indicator: function (key) {
                if (this.sortKey !== key) {
                    return '↕';
                }
                return this.sortDir === 'asc' ? '↑' : '↓';
            },
        };
    });
});
</script>
