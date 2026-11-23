# Enterprise Theming Guide: Multi-Tenant Design Tokens & Custom Themes

- **Root Navigation**: [Project Overview](../../README.md)
- **Architecture Baseline**: [Architecture Decision Records](specifications/architecture.md)
- **Active Task Ledger**: [Subsystem Task Registry](ai/tasks.md)
- **Changelog Tracker**: [Project Changelog](../CHANGELOG.md)

---

## 1. Multi-Tenant Theming Architecture Overview
EIMS presentation implements a zero-dependency, modular CSS Custom Property (CSS Variables) token architecture with strict WCAG AA/AAA contrast compliance and zero inline styles:

- `src/public/assets/css/base.css`: CSS reset, semantic grid/flex utilities, typography, accessible focus states, buttons, badges, tables, pagination, and component primitives. All elements consume CSS custom properties (`var(--bg-primary)`, `var(--text-primary)`, `var(--accent-primary)`, `var(--border-subtle)`, `var(--font-family)`). Zero inline styles permitted in PHP views.
- `src/public/assets/css/app.css`: Restaurant ERP specific floor plan grid (12x8 layout), coordinate placement classes (`.gc-1` to `.gc-12`, `.gr-1` to `.gr-8`), table statuses, and role-based header navigation.
- `src/public/assets/css/themes/lyra.css`: Personal Platform Theme (`lyra`) featuring a cutesy, elegant blurple and neon pink aesthetic with sans-serif system font stack.
- `src/public/assets/css/themes/hiraya.css`: Filipiniana Tenant Theme (`hiraya` - Hiraya Bistro) featuring authentic Philippine heritage styling (warm ivory capiz, parian parchment, burnished Manila gold, Vigan terracotta, and roasted barako serif typography).
- `src/public/assets/css/themes/katipunan.css`: Grill Tenant Theme (`katipunan` - Katipunan Grill) featuring rustic charcoal, cast iron skillet slate, bamboo green, and blazing ember orange.
- `src/public/assets/css/themes/default.css`: Default high-contrast neutral theme with automatic `prefers-color-scheme` dark mode support.
- `src/public/assets/css/theme-engine.js`: Vanilla JS theme engine toggling `data-theme="<theme-slug>"` on `<html>`, persisting selection across `localStorage` and HTTP cookies (`theme`, `eims_theme`) for instant live stage switching.

---

## 2. Platform Theme Tokens & Contrast Metrics

### 2.1 Personal Platform Theme (`lyra`)
Designed for platform-wide administration (SuperAdmin, Admin, Customer Portal default). Dark twilight violet background with high-contrast text and vibrant interactive accents:

| Token | Hex Value | Purpose | WCAG Contrast vs Background | Compliance |
|---|---|---|---|---|
| `--bg-primary` | `#191a28` | Deep twilight violet page background | N/A | N/A |
| `--bg-secondary` | `#161824` | Card slate / container background | N/A | N/A |
| `--card-bg` | `#161824` | Card component background | N/A | N/A |
| `--bg-surface` | `#2a2e45` | Form inputs and elevated table headers | N/A | N/A |
| `--text-primary` | `#ffffff` | Pure white primary typography | 18.2:1 | **WCAG AAA** |
| `--text-secondary` | `#fcd7ff` | Soft pastel lavender secondary text | 14.8:1 | **WCAG AAA** |
| `--text-muted` | `#dec1cf` | Muted captions and metadata | 11.2:1 | **WCAG AAA** |
| `--accent-primary` | `#ff59c6` | Bubblegum neon pink (links, primary buttons) | 6.5:1 | **WCAG AA** |
| `--accent-secondary` | `#ae89ff` | Royal blurple brand accent | 7.2:1 | **WCAG AAA** |
| `--accent-tertiary` | `#75beff` | Cyan-blue informational accents | 10.6:1 | **WCAG AAA** |
| `--border-subtle` | `#2d314f` | Container borders and table row dividers | 1.8:1 | UI Divider |
| `--border-strong` | `#ff59c6` | Focused active states and highlights | 6.5:1 | **WCAG AA** |
| `--font-family` | `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif` | Clean modern sans-serif stack | N/A | N/A |

### 2.2 Filipiniana Tenant Theme (`hiraya` - Hiraya Bistro)
Designed for Hiraya Bistro & Farm-to-Table (`tenant-hiraya-001`). Warm, organic Philippine heritage palette evoking ancestral architectural materials and heirloom culinary craft:

| Token | Hex Value | Heritage Material Reference | WCAG Contrast vs Surface | Compliance |
|---|---|---|---|---|
| `--bg-primary` | `#fbf9f4` | Warm ivory capiz shell / native woven linen | N/A | N/A |
| `--bg-secondary` | `#f4eee2` | Parian parchment archival tone | N/A | N/A |
| `--card-bg` | `#ffffff` | Crisp white dining table linen | N/A | N/A |
| `--bg-surface` | `#f4eee2` | Input fields and surface shading | N/A | N/A |
| `--text-primary` | `#24160e` | Roasted Batangas barako coffee / dark kamagong wood | 15.6:1 (on `#fbf9f4`), 16.5:1 (on `#ffffff`) | **WCAG AAA** |
| `--text-secondary` | `#5c4436` | Warm Philippine mahogany | 8.0:1 | **WCAG AAA** |
| `--text-muted` | `#5c4436` | Accessible muted body text | 8.0:1 | **WCAG AAA** |
| `--accent-primary` | `#8c3b2d` | Vigan terracotta clay brick (actions, buttons) | 7.2:1 (on `#fbf9f4`); 7.2:1 (white text on `#8c3b2d`) | **WCAG AAA** |
| `--accent-secondary` | `#b8934a` | Antique burnished Manila gold | 2.8:1 | Accent / Border |
| `--accent-tertiary` | `#3a5335` | Intramuros patio moss / garden patio accent | 7.5:1 | **WCAG AAA** |
| `--border-subtle` | `#e2d7c5` | Woven bamboo reed divider | 1.4:1 | Structural Reed |
| `--border-strong` | `#b8934a` | Burnished antique gold active boundary | 2.8:1 | Accent Boundary |
| `--font-family` | `'Playfair Display', 'Cormorant Garamond', 'Baskerville', 'Georgia', 'Times New Roman', serif` | Elegant Philippine heritage serif stack | N/A | N/A |

### 2.3 Grill Tenant Theme (`katipunan` - Katipunan Grill)
Designed for Katipunan Grill & Smokehouse (`tenant-katipunan-002`). Robust, industrial pitmaster aesthetic with charcoal surfaces, cast iron textures, bamboo greens, and ember glows:

| Token | Hex Value | Pitmaster Element Reference | WCAG Contrast vs Surface | Compliance |
|---|---|---|---|---|
| `--bg-primary` | `#18191a` | Deep rustic charcoal black | N/A | N/A |
| `--bg-secondary` | `#222426` | Cast iron skillet slate | N/A | N/A |
| `--card-bg` | `#222426` | Cast iron skillet card background | N/A | N/A |
| `--bg-surface` | `#2c2f33` | Charred timber input & container background | N/A | N/A |
| `--text-primary` | `#f5f6f7` | Ash white primary typography | 16.5:1 | **WCAG AAA** |
| `--text-secondary` | `#d1d5db` | Light slate ash secondary text | 11.2:1 | **WCAG AAA** |
| `--text-muted` | `#9ca3af` | Smoke gray captions | 7.1:1 | **WCAG AAA** |
| `--accent-primary` | `#ff6d00` | Blazing charcoal ember orange | 6.5:1 | **WCAG AA** |
| `--accent-secondary` | `#4ade80` | Native bamboo cane green | 10.3:1 | **WCAG AAA** |
| `--accent-tertiary` | `#fbbf24` | Cast iron flame gold | 11.5:1 | **WCAG AAA** |
| `--border-subtle` | `#3a3d42` | Iron grill grate divider | 1.7:1 | Structural Grate |
| `--border-strong` | `#ff6d00` | Blazing ember flame boundary | 6.5:1 | **WCAG AA** |
| `--font-family` | `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Trebuchet MS", sans-serif` | Industrial sans-serif typography | N/A | N/A |

---

## 3. Dynamic Tenant Resolution Hierarchy
When any request arrives at the application gateway (`src/app/Views/layouts/header.php`), the active theme is determined via deterministic priority resolution:

1. **User / Evaluation Override**: If `$_COOKIE['theme']` or `$_COOKIE['eims_theme']` is present and matches the allowlist (`lyra`, `hiraya`, `katipunan`, `default`), the cookie selection takes precedence. This allows an evaluator or instructor to test different brandings live on any screen.
2. **Tenant Session Context**: If the authenticated user belongs to `tenant-hiraya-001`, the system defaults to `hiraya`. If the user belongs to `tenant-katipunan-002`, the system defaults to `katipunan`.
3. **Route & Resource Context**: If the request URI matches `hiraya` (e.g. `/discover/hiraya-bistro`, `/menu/tenant-hiraya-001`, `/bookings/new?restaurant_id=tenant-hiraya-001`) or the controller provides `$restaurantData` matching Hiraya Bistro, the default is `hiraya`. If the URI matches `katipunan`, the default is `katipunan`.
4. **Platform Baseline**: All administrative dashboards (`/superadmin/*`, `/admin/*`), user dashboards (`/user/*`), authentication (`/login`), and public landing pages default to `lyra`.

---

## 4. Live Theme Switcher Architecture
The theme switcher is embedded in the top site navigation header (`src/app/Views/layouts/header.php`):

```html
<div class="theme-switcher-container">
    <label for="theme-select" class="theme-switcher-label">Theme:</label>
    <select id="theme-select" class="theme-select" aria-label="Select Color Theme">
        <option value="lyra">Lyra (Platform Neon)</option>
        <option value="hiraya">Hiraya Bistro (Filipiniana)</option>
        <option value="katipunan">Katipunan Grill (Rustic Smokehouse)</option>
        <option value="default">Default (Neutral)</option>
    </select>
</div>
```

The accompanying client-side script (`src/public/assets/css/theme-engine.js`):
- Runs immediately in `<head>` to prevent Flash of Unstyled Theme (FOUT).
- Listens for change events on `#theme-select`.
- Updates `document.documentElement.setAttribute('data-theme', theme)`.
- Persists user selection in both `localStorage.setItem('theme', theme)` and `document.cookie = 'theme=...; path=/; max-age=31536000; SameSite=Lax'`.
- Exposes `window.EIMS_THEME.get()` and `window.EIMS_THEME.set(theme)` programmatic API.

---

## 5. Registering a New Tenant Theme in 4 Steps

### Step 1: Create the Tenant Theme CSS File
Create `src/public/assets/css/themes/<slug>.css` scoping all tokens under `[data-theme="<slug>"]`:

```css
[data-theme="manila-diner"] {
    --bg-primary: #121820;
    --bg-secondary: #1a222d;
    --card-bg: #1a222d;
    --bg-surface: #242f3e;
    --bg-elevated: #2e3b4e;
    --border-subtle: #2d3a4d;
    --border-strong: #00d2ff;
    --border-color: #2d3a4d;
    --text-primary: #ffffff;
    --text-secondary: #cde4ff;
    --text-muted: #8da4be;
    --accent-primary: #00d2ff;
    --accent-primary-hover: #33dcff;
    --accent-secondary: #ff9900;
    --accent-tertiary: #00ffaa;
    --status-success: #00ffaa;
    --status-warning: #ffcc00;
    --status-error: #ff3366;
    --focus-ring: 0 0 0 3px rgba(0, 210, 255, 0.45);
    --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    --radius-sm: 4px;
    --radius-md: 8px;
    --spacing-unit: 1rem;
    --max-width: 1200px;
}
```

### Step 2: Include Stylesheet in Header Partial
In `src/app/Views/partials/header.php`, add the stylesheet reference:
```html
<link rel="stylesheet" href="<?= htmlspecialchars(\App\Core\Request::url('/assets/css/themes/manila-diner.css'), ENT_QUOTES, 'UTF-8') ?>" />
```

### Step 3: Register in Theme Switcher and Theme Engine
In `src/public/assets/css/theme-engine.js`, append the slug to `VALID_THEMES`:
```javascript
var VALID_THEMES = ['lyra', 'hiraya', 'katipunan', 'default', 'manila-diner'];
```

In `src/app/Views/layouts/header.php`, append an option to `<select id="theme-select">`:
```html
<option value="manila-diner"<?= $activeTheme === 'manila-diner' ? ' selected' : '' ?>>Manila Diner</option>
```

### Step 4: Register in Application Metadata
In `dev/values/constants/main.yaml`:
```yaml
themes:
  manila-diner:
    name: "Manila Diner"
    slug: "manila-diner"
    wcag_compliance: "AAA"
```
