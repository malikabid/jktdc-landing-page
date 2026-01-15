# DOTK Landing Page - Copilot Instructions

## Project Overview

Static website for Directorate of Tourism Kashmir (DOTK), featuring a modular component architecture with URL-based feature flags and dynamic theming. Deployed on Render as a static site with cache-busting versioning.

## Architecture & Key Patterns

### Component-Based Structure

- **Reusable HTML Components**: Header ([pub/pages/header.html](../pub/pages/header.html)) and footer ([pub/pages/footer.html](../pub/pages/footer.html)) are loaded dynamically into placeholder divs using the `loadComponent()` function in [pub/js/header.js](../pub/js/header.js)
- **Service Cards Module**: Optional content loaded from [pub/html/service-cards.html](../pub/html/service-cards.html) when `?showServiceCards=true` URL parameter is set
- **Pattern**: Always use `fetch()` + `.innerHTML` for loading HTML fragments, not server-side includes or build tools

### URL Parameter System (Critical)

All feature flags are controlled via URL parameters - **NEVER hardcode visibility**:

- `?theme=blue|eggplant|purple` - Switches color schemes (default: `purple`)
- `?showServiceCards=true` - Displays/hides service cards overlay

See [URL-PARAMETERS.md](../URL-PARAMETERS.md) for comprehensive documentation. When adding new features, extend this URL parameter pattern.

### CSS Theming System

- **Base Theme Variables**: Defined in [pub/css/theme.css](../pub/css/theme.css) using CSS custom properties (`--primary-color`, `--secondary-color`, etc.)
- **Theme Switching**: [pub/js/theme-switcher.js](../pub/js/theme-switcher.js) applies `data-theme` attribute to `<html>` element
- **Pattern**: ALL colors must reference CSS variables (`var(--primary-color)`), never hardcode hex values in [pub/css/style.css](../pub/css/style.css)
- **Theme Definitions**: Each theme overrides root CSS variables under `html[data-theme="themeName"]` selectors

### Cache-Busting Versioning (Automated)

**Automated system** documented in [AUTOMATED-VERSIONING.md](../AUTOMATED-VERSIONING.md):

1. **GitHub Actions**: Automatically updates versions when CSS/JS files change (runs on push to `main`)
2. **Local Script**: Run `bash update-versions.sh` to manually update versions using Git commit hash
3. **Version Format**: Git commit hash (e.g., `style.css?v=a1b2c3d`) instead of semantic versioning
4. **Zero Manual Work**: HTML files auto-update with new versions on every CSS/JS change

**Key Files:**

- Version updater: [update-versions.sh](../update-versions.sh)
- GitHub workflow: [.github/workflows/update-versions.yml](workflows/update-versions.yml)
- Versioned files: All CSS in `pub/css/` and JS in `pub/js/`

## File Organization

```
/                      # HTML pages (index.html in root only)
pub/
  pages/               # HTML pages (coming-soon, organizational-chart, events, header, footer)
  css/                 # style.css (main styles), theme.css (color variables)
  js/                  # Feature modules (header, theme-switcher, service-cards-loader, slider)
  html/                # HTML fragments (service-cards.html)
  images/              # Static assets organized by type (officials/, slider/)
```

## Development Workflows

### Adding New Features

1. Check if feature should be URL-parameter controlled (if optional/toggleable)
2. Create feature-specific JS module in `pub/js/` following naming pattern `feature-name.js`
3. Use CSS variables for all colors to support theming
4. Update [URL-PARAMETERS.md](../URL-PARAMETERS.md) if adding new parameters

### Modifying Styles

- Primary colors: Edit [pub/css/theme.css](../pub/css/theme.css) variables
- Layout/structure: Edit [pub/css/style.css](../pub/css/style.css) using existing CSS variables
- **Test all three themes** before committing: `?theme=blue`, `?theme=eggplant`, `?theme=purple`
- **Versions auto-update**: GitHub Actions updates cache-busting versions automatically on push

### Responsive Design Pattern

- **Mobile-First**: Base styles for mobile, use `@media (min-width: 768px)` for desktop
- **Flexible Navigation**: [pub/js/header.js](../pub/js/header.js) automatically collapses nav items into "»" menu based on available space (desktop only)
- Breakpoints: 768px (tablet), 1024px (desktop), 1200px (max-width)

## Deployment

**Automated deployment to GoDaddy** via FTP documented in [DEPLOYMENT.md](../DEPLOYMENT.md):

- GitHub Actions automatically deploys on push to `main` branch
- FTP credentials stored as GitHub Secrets (never in code)
- Only changed files uploaded for faster deployments
- Workflow: [.github/workflows/deploy-to-godaddy.yml](workflows/deploy-to-godaddy.yml)

**Legacy Render hosting**: Configuration exists in [render.yaml](../render.yaml) if needed for alternative deployment

## Code Conventions

### JavaScript

- Vanilla JS only - no frameworks/build tools
- DOMContentLoaded listeners for initialization
- Async/await for fetch operations
- Store UI state in sessionStorage when needed (theme preference)

### HTML

- Semantic HTML5 elements (`<section>`, `<nav>`, `<aside>`)
- FontAwesome 6.7.2 for icons
- Google Fonts (Open Sans) for typography
- Always include version query params on local CSS/JS links

### CSS

- BEM-inspired naming for components (`.service-card`, `.official-card`)
- Section comments: `/* ==================== SECTION NAME ==================== */`
- Mobile-first responsive design
- Use CSS variables for all themeable properties
