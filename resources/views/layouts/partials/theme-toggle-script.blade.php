<script>
(function () {
    function syncThemeToggleUi() {
        var dark = document.documentElement.classList.contains('dark');
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
            var moon = btn.querySelector('[data-theme-icon="moon"]');
            var sun = btn.querySelector('[data-theme-icon="sun"]');
            if (moon) {
                moon.classList.toggle('hidden', dark);
            }
            if (sun) {
                sun.classList.toggle('hidden', !dark);
            }
        });
    }

    function setTheme(theme) {
        var root = document.documentElement;
        if (theme === 'dark') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
        try {
            localStorage.setItem('theme', theme);
        } catch (e) {}
        syncThemeToggleUi();
    }

    function toggleTheme() {
        var dark = document.documentElement.classList.contains('dark');
        setTheme(dark ? 'light' : 'dark');
    }

    window.dukaproSetTheme = setTheme;
    window.dukaproToggleTheme = toggleTheme;

    function bindThemeToggles() {
        syncThemeToggleUi();
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            if (btn.dataset.themeBound) {
                return;
            }
            btn.dataset.themeBound = '1';
            btn.addEventListener('click', toggleTheme);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindThemeToggles);
    } else {
        bindThemeToggles();
    }
})();
</script>
