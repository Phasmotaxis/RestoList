/**
 * EIMS Dynamic Multi-Tenant Theme Engine
 * Toggles data-theme="<theme-slug>" on <html>, persists preference in localStorage and cookie.
 * Supports: lyra (Platform Neon), hiraya (Filipiniana), katipunan (Rustic Smokehouse), default (Neutral Clean-Room).
 * Fully accessible with zero third-party dependencies.
 */
(function () {
    'use strict';

    var VALID_THEMES = ['lyra', 'hiraya', 'katipunan', 'default'];

    function getSavedTheme() {
        try {
            var stored = localStorage.getItem('theme') || localStorage.getItem('eims_theme');
            if (stored && VALID_THEMES.indexOf(stored) !== -1) {
                return stored;
            }
        } catch (e) {}

        var match = document.cookie.match(/(?:^|; )(?:theme|eims_theme)=([^;]*)/);
        if (match) {
            var decoded = decodeURIComponent(match[1]);
            if (VALID_THEMES.indexOf(decoded) !== -1) {
                return decoded;
            }
        }

        var serverTheme = document.documentElement.getAttribute('data-theme');
        if (serverTheme && VALID_THEMES.indexOf(serverTheme) !== -1) {
            return serverTheme;
        }

        return 'lyra';
    }

    function applyTheme(theme) {
        if (!theme || VALID_THEMES.indexOf(theme) === -1) {
            theme = 'lyra';
        }

        document.documentElement.setAttribute('data-theme', theme);

        try {
            localStorage.setItem('theme', theme);
            localStorage.setItem('eims_theme', theme);
        } catch (e) {}
        document.cookie = 'theme=' + encodeURIComponent(theme) + '; path=/; max-age=31536000; SameSite=Lax';
        document.cookie = 'eims_theme=' + encodeURIComponent(theme) + '; path=/; max-age=31536000; SameSite=Lax';

        var selector = document.getElementById('theme-select');
        if (selector && selector.value !== theme) {
            selector.value = theme;
        }
    }

    // Apply immediately to prevent flash of unstyled theme (FOUT)
    var initialTheme = getSavedTheme();
    if (initialTheme) {
        document.documentElement.setAttribute('data-theme', initialTheme);
    }

    window.addEventListener('DOMContentLoaded', function () {
        var currentTheme = getSavedTheme();
        var selector = document.getElementById('theme-select');
        if (selector) {
            selector.value = currentTheme;
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
