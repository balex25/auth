<script>
    (() => {
        const root = document.documentElement;
        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const requestedTheme = new URLSearchParams(window.location.search).get('theme');
        let theme = requestedTheme || localStorage.getItem('theme') || 'system';

        const applyTheme = () => {
            const isDark = theme === 'dark' || (theme === 'system' && media.matches);
            root.classList.toggle('dark', isDark);
            root.style.colorScheme = isDark ? 'dark' : 'light';
        };

        applyTheme();

        media.addEventListener('change', applyTheme);
        window.addEventListener('storage', (event) => {
            if (event.key !== 'theme') {
                return;
            }

            theme = event.newValue || 'system';
            applyTheme();
        });
    })();
</script>
