# Enterprise Theming Guide: Design Tokens & Custom Themes

- **Root Navigation**: [Project Overview](../../README.md)
- **Architecture Baseline**: [Architecture Decision Records](specifications/architecture.md)
- **Active Task Ledger**: [Subsystem Task Registry](ai/tasks.md)
- **Changelog Tracker**: [Project Changelog](../CHANGELOG.md)

---

## 1. Architecture Overview
EIMS presentation implements a zero-dependency, modular CSS Custom Property (CSS Variables) token architecture with strict WCAG AA contrast compliance:

- `src/public/assets/css/base.css`: CSS reset, semantic grid/flex utilities, typography, accessible focus states, and component primitives. Zero inline styles permitted in PHP views.
- `src/public/assets/css/themes/default.css`: Default high-contrast neutral theme with automatic `prefers-color-scheme` dark mode support.
- `src/public/assets/css/themes/lyra.css`: Custom dark theme extracted and adapted from `whinee/piiiiink.json` with accessible text and border tokens.
- `src/public/assets/css/theme-engine.js`: Vanilla JS theme switcher toggling `data-theme="<theme-slug>"` on `<html>`, persisting selection in `localStorage` and a secure cookie (`eims_theme`).

---

## 2. The `lyra` Theme Tokens (WCAG AA Compliant)
Derived from `piiiiink.json` while maintaining >= 4.5:1 body text contrast:

```css
[data-theme="lyra"] {
    /* Surfaces & Backgrounds */
    --bg-primary: #191a28;        /* Editor background */
    --bg-secondary: #161824;      /* Sidebar / Card background */
    --bg-surface: #2a2e45;        /* Input / Container background */
    --bg-elevated: #282b3f;       /* Dropdown / Modal background */

    /* Borders & Dividers */
    --border-subtle: #2d314f;     /* Subtle container border */
    --border-strong: #ff59c6;     /* High-visibility active border */

    /* Text & Foreground */
    --text-primary: #ffffff;      /* Pure white for primary body (>= 4.5:1) */
    --text-secondary: #fcd7ff;    /* Soft lavender-pink secondary text */
    --text-muted: #dec1cf;        /* Muted accessible text */

    /* Brand & Interactive Accents */
    --accent-primary: #ff59c6;    /* Hot pink (links, primary actions) */
    --accent-primary-hover: #ff89cc;
    --accent-secondary: #ae89ff;  /* Lavender accent */
    --accent-tertiary: #75beff;   /* Cyan-blue info accent */

    /* Status Tokens */
    --status-success: #23d18b;    /* High-contrast terminal green */
    --status-warning: #ffd100;    /* Amber warning */
    --status-error: #f48771;      /* Coral red error */

    /* Focus & Accessibility */
    --focus-ring: 0 0 0 3px rgba(255, 89, 198, 0.45);
}
```

---

## 3. Registering a New Theme in Under 5 Minutes

### Step 1: Create the Theme CSS File
Create a new file at `src/public/assets/css/themes/<your-theme>.css`:

```css
[data-theme="emerald"] {
    --bg-primary: #064e3b;
    --bg-secondary: #065f46;
    --bg-surface: #047857;
    --bg-elevated: #0f766e;
    --border-subtle: #047857;
    --border-strong: #10b981;
    --text-primary: #ffffff;
    --text-secondary: #d1fae5;
    --text-muted: #a7f3d0;
    --accent-primary: #34d399;
    --accent-primary-hover: #6ee7b7;
    --accent-secondary: #2dd4bf;
    --accent-tertiary: #38bdf8;
    --status-success: #10b981;
    --status-warning: #f59e0b;
    --status-error: #ef4444;
    --focus-ring: 0 0 0 3px rgba(52, 211, 153, 0.5);
    --radius-sm: 4px;
    --radius-md: 8px;
    --spacing-unit: 1rem;
    --max-width: 1200px;
}
```

### Step 2: Link the Stylesheet in the Header Partial
In `src/app/Views/partials/header.php`, include your new stylesheet:
```html
<link rel="stylesheet" href="/assets/css/themes/emerald.css" />
```

### Step 3: Register Option in Header Dropdown
In `src/app/Views/layouts/header.php`, add an option to `<select id="theme-select">`:
```html
<option value="emerald">Emerald Theme</option>
```

### Step 4: Register in Application Metadata
In `dev/values/constants/main.yaml`:
```yaml
themes:
  emerald:
    name: "Emerald Green"
    slug: "emerald"
    wcag_compliance: "AA"
```

The theme switcher will immediately persist the selection in browser storage and cookie.
