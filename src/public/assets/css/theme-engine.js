/**
 * EIMS Dynamic Theme Engine
 * Toggles data-theme="lyra" on <html>, persists preference in localStorage and cookie.
 * Fully accessible with zero third-party dependencies.
 */
(function () {
    'use strict';

    function getSavedTheme() {
        try {
            var stored = localStorage.getItem('eims_theme');
            if (stored) return stored;
        } catch (e) {}

        var match = document.cookie.match(/(?:^|; )eims_theme=([^;]*)/);
        if (match) return decodeURIComponent(match[1]);

        return 'default';
    }

    function applyTheme(theme) {
        if (theme === 'lyra') {
            document.documentElement.setAttribute('data-theme', 'lyra');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }

        try {
            localStorage.setItem('eims_theme', theme);
        } catch (e) {}
        document.cookie = 'eims_theme=' + encodeURIComponent(theme) + '; path=/; max-age=31536000; SameSite=Lax';

        var selector = document.getElementById('theme-select');
        if (selector && selector.value !== theme) {
            selector.value = theme;
        }
    }

    // Apply immediately to prevent flash of unstyled theme (FOUT)
    var initialTheme = getSavedTheme();
    if (initialTheme === 'lyra') {
        document.documentElement.setAttribute('data-theme', 'lyra');
    }

    window.addEventListener('DOMContentLoaded', function () {
        var selector = document.getElementById('theme-select');
        if (selector) {
            selector.value = getSavedTheme();
            selector.addEventListener('change', function (e) {
                applyTheme(e.target.value);
            });
        }
    });

    window.EIMS_THEME = {
        get: getSavedTheme,
        set: applyTheme,
    };
})();
