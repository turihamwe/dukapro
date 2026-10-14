<script>
(function () {
    try {
        var root = document.documentElement;
        var stored = localStorage.getItem('theme');
        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (stored === 'dark' || (stored !== 'light' && prefersDark)) {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
    } catch (e) {}
})();
</script>
