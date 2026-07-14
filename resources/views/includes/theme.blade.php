<script>
    (() => {
        const root = document.documentElement;
        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const themes = ['light', 'dark', 'system'];
        const requestedTheme = new URLSearchParams(window.location.search).get('theme');
        let theme = themes.includes(requestedTheme)
            ? requestedTheme
            : (themes.includes(localStorage.getItem('theme')) ? localStorage.getItem('theme') : 'system');

        const applyTheme = () => {
            const isDark = theme === 'dark' || (theme === 'system' && media.matches);
            root.classList.toggle('dark', isDark);
            root.style.colorScheme = isDark ? 'dark' : 'light';
        };

        const syncTheme = (value, persist = true) => {
            theme = themes.includes(value) ? value : 'system';

            if (persist) {
                localStorage.setItem('theme', theme);
            }

            applyTheme();
            window.dispatchEvent(new CustomEvent('theme-synced', { detail: theme }));
        };

        applyTheme();

        media.addEventListener('change', applyTheme);
        window.addEventListener('theme-change', (event) => syncTheme(event.detail));
        window.addEventListener('storage', (event) => {
            if (event.key !== 'theme') {
                return;
            }

            syncTheme(event.newValue || 'system', false);
        });
    })();
</script>
