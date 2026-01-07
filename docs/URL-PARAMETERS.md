# JKTDC Website - URL Parameters Documentation

This document explains how to use URL parameters to control various features of the JKTDC (Jammu & Kashmir Tourism Development Corporation) website.

## Available URL Parameters

### 1. Theme Switcher (`theme`)

Control the color scheme of the entire website.

**Parameter:** `theme`

**Values:**
- `blue` (default) - Original blue color scheme
- `eggplant` - Purple/eggplant color scheme

**Examples:**
```
https://yoursite.com/                    # Default blue theme
https://yoursite.com/?theme=blue         # Explicitly set blue theme
https://yoursite.com/?theme=eggplant     # Eggplant/purple theme
```

**Theme Color Differences:**

| Component | Blue Theme | Eggplant Theme |
|-----------|------------|----------------|
| Primary Color | #3e8aba (Blue) | #614051 (Eggplant) |
| Secondary Color | #f26522 (Orange) | #8b5a6f (Mauve) |
| Accent Color | #003366 (Dark Blue) | #3d2734 (Dark Purple) |
| Navbar Background | #505050 (Gray) | #4a3344 (Dark Mauve) |

**Affected Elements:**
- Header background
- Navigation bar and hover states
- Ticker banner
- Column headers (Notifications & Events)
- Footer background
- Service card icons
- Buttons (Subscribe, Get More Information, etc.)
- Links and hover effects
- Border colors and accents

---

### 2. Service Cards Display (`showServiceCards`)

Control whether the service cards overlay section is displayed on the homepage.

**Parameter:** `showServiceCards`

**Values:**
- `true` - Display service cards with -110px top margin on main content
- `false` or omitted (default) - Hide service cards section

**Examples:**
```
https://yoursite.com/                           # Service cards hidden (default)
https://yoursite.com/?showServiceCards=false    # Service cards hidden
https://yoursite.com/?showServiceCards=true     # Service cards visible
```

---

### 3. Skip Coming Soon (`skipComingSoon`)

Bypass the server redirect to the `coming-soon` page so you can access the main site directly.

**Parameter:** `skipComingSoon`

**Values:**
- `true` — When present in the query string (e.g. `?skipComingSoon=true`), the server will not redirect and the requested page will load normally.

**Cookie support:** The admin "Go Live" flow sets a cookie named `skipComingSoon=1` so subsequent requests are not redirected. The cookie-based bypass is recognized by the server as well.

**Examples:**
```
https://yoursite.com/?skipComingSoon=true                # Load main site once without cookie
https://yoursite.com/?theme=eggplant&skipComingSoon=true # Combine with other params
```

**Notes:**
- The query-string parameter is best for single-use bypassing. The cookie is used by site administrators (Go Live) to persist the bypass across requests.
- Use the "Continue to site" button on the coming-soon page to set a temporary cookie (30 days).
**Service Cards Content:**
When enabled, displays 4 cards:
1. **About Kashmir** - History, culture, and natural beauty
2. **Tourist Destinations** - Dal Lake, Mughal gardens, Gulmarg, Pahalgam
3. **Adventure Activities** - Skiing, trekking, river rafting, paragliding
4. **Travel Information** - Travel guides, accommodation, essential info

**Visual Impact:**
- Service cards appear as an overlay above the main content
- Main content area margin adjusts to -110px when cards are shown
- Cards are hidden completely when parameter is false or omitted

---

## Combined Parameters

You can combine multiple parameters using `&` separator:

### Examples:

**Eggplant theme with service cards:**
```
https://yoursite.com/?theme=eggplant&showServiceCards=true
```

**Blue theme with service cards:**
```
https://yoursite.com/?theme=blue&showServiceCards=true
```

**Eggplant theme without service cards (default behavior):**
```
https://yoursite.com/?theme=eggplant
```

---

## Implementation Details

### Theme Switching
- Handled by: `/pub/js/theme-switcher.js`
- CSS Variables: Defined in `/pub/css/style.css` (`:root` and `[data-theme="eggplant"]`)
- Applies theme by setting `data-theme` attribute on document root
- Theme selection is stored in `sessionStorage`

### Service Cards
- Handled by: `/pub/js/service-cards-loader.js`
- Content file: `/pub/html/service-cards.html`
- Dynamically loads cards via Fetch API when parameter is true
- Adds `.service-cards` class to `.main-content` for margin adjustment
- Hides `.service-cards-overlay` section when parameter is false

---

## Default Behavior

When no URL parameters are provided:
- **Theme:** Blue (default color scheme)
- **Service Cards:** Hidden

Example: `https://yoursite.com/`

---

## Browser Compatibility

Both features work with modern browsers that support:
- URLSearchParams API
- CSS Custom Properties (Variables)
- Fetch API
- ES6+ JavaScript

---

## Testing URLs

### Theme Testing:
1. Default: `/?`
2. Blue: `/?theme=blue`
3. Eggplant: `/?theme=eggplant`
4. Invalid (falls back to blue): `/?theme=invalid`

### Service Cards Testing:
1. Hidden: `/?showServiceCards=false`
2. Visible: `/?showServiceCards=true`
3. Default (hidden): `/?`

### Combined Testing:
1. `/?theme=eggplant&showServiceCards=true`
2. `/?theme=blue&showServiceCards=true`
3. `/?theme=eggplant&showServiceCards=false`

---

## Programmatic Theme Switching

You can also switch themes programmatically using JavaScript:

```javascript
// Switch to eggplant theme
window.switchTheme('eggplant');

// Switch to blue theme
window.switchTheme('blue');
```

---

## File Structure

```
/pub
  /css
    style.css          # Main styles with CSS variables
  /js
    theme-switcher.js  # Theme switching logic
    service-cards-loader.js  # Service cards loader
  /html
    service-cards.html # Service cards content
```

---

## Notes

- URL parameters are case-sensitive for values
- Invalid theme values default to blue theme
- Service cards only appear when explicitly set to 'true'
- Theme preference is stored in session storage (not persistent across browser sessions)
- Changes take effect immediately on page load

---

## Maintenance

To add new themes:
1. Add theme colors in CSS variables section of `style.css`
2. Update `theme-switcher.js` to include new theme name in `validThemes` array
3. Define theme-specific overrides in `[data-theme="new-theme"]` selector
