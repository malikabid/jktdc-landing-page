# Page Creation Guide

Complete step-by-step instructions for adding new destination, monument, or district pages to the JKTDC website.

## Overview

This guide covers the process of creating new content pages following the established patterns in the project. Use this as a reference when adding new tourist destinations, monuments, heritage sites, or district pages.

## Prerequisites

- Basic understanding of HTML structure
- Knowledge of the project folder structure
- Access to edit files in the repository

## Page Creation Steps

### Step 1: Create the HTML Page

**Location:** Choose the appropriate subfolder based on page type:
- Districts: `/pub/pages/districts/`
- Pilgrimage sites: `/pub/pages/pilgrimage/`
- Monuments/Heritage: `/pub/pages/monuments/`
- Tourist Places: `/pub/pages/places/`
- Other pages: `/pub/pages/`

**File naming convention:**
- Use lowercase with hyphens: `location-name.html`
- Be descriptive: `sps-museum-srinagar.html` not `museum.html`
- Avoid spaces and special characters

**Template Structure:**
```html
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Page Title - Directorate of Tourism Kashmir</title>

    <!-- Font Awesome -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
      integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
    
    <!-- Google Fonts -->
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400&display=swap"
    />
    
    <!-- CSS Files (with versioning) -->
    <link rel="stylesheet" href="/pub/css/theme.css?v=764e0ba" />
    <link rel="stylesheet" href="/pub/css/style.css?v=764e0ba" />
    <link rel="stylesheet" href="/pub/css/districts.css?v=764e0ba" />
  </head>

  <body>
    <!-- Include header -->
    <div id="header-placeholder"></div>

    <main class="main-content">
      <div class="district-page-container">
        <a href="/" class="back-link">
          <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="district-header">
          <h1>Page Title</h1>
          <p class="subtitle">Brief descriptive subtitle</p>
        </div>

        <!-- Add your content sections here -->
        <section class="district-info-section">
          <h2><i class="fas fa-info-circle"></i> Section Title</h2>
          <p>Content goes here...</p>
        </section>

        <!-- More sections as needed -->

        <section class="cta-section">
          <h2>Call to Action</h2>
          <p>Closing message</p>
          <a href="/" class="cta-button">Explore More Destinations</a>
        </section>
      </div>
    </main>

    <!-- Include footer -->
    <div id="footer-placeholder"></div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/pub/js/theme-switcher.js?v=764e0ba"></script>
    <script src="/pub/js/header.js?v=764e0ba"></script>
  </body>
</html>
```

**Common Section Types:**

1. **About Section**
   ```html
   <section class="district-info-section">
     <h2><i class="fas fa-landmark"></i> About Location Name</h2>
     <p>Description paragraphs...</p>
   </section>
   ```

2. **Information Table**
   ```html
   <section class="district-info-section">
     <h2><i class="fas fa-info-circle"></i> Key Information</h2>
     <table class="info-table">
       <tr>
         <td>Label</td>
         <td>Value</td>
       </tr>
     </table>
   </section>
   ```

3. **Info Grid (Cards)**
   ```html
   <section class="district-info-section">
     <h2><i class="fas fa-star"></i> Highlights</h2>
     <div class="info-grid">
       <div class="info-card">
         <h3><i class="fas fa-icon"></i> Card Title</h3>
         <p>Description...</p>
       </div>
     </div>
   </section>
   ```

4. **Features List**
   ```html
   <section class="district-info-section">
     <h2><i class="fas fa-check"></i> Features</h2>
     <ul class="features-list">
       <li><i class="fas fa-check-circle"></i> Feature item</li>
     </ul>
   </section>
   ```

### Step 2: Update Header Navigation

**File:** `/pub/pages/header.html`

Add your new page link to the appropriate navigation menu:

```html
<li>
  <a href="#">Menu Category</a>
  <ul>
    <!-- Existing items -->
    <li><a href="/your-new-page.html">Your New Page Title</a></li>
  </ul>
</li>
```

**Important Notes:**
- Links in header use root-relative paths: `/page-name.html`
- Maintain alphabetical order within menu sections when logical
- Use clear, concise menu labels
- Keep submenu items organized by theme/location

### Step 3: Add URL Redirects (render.yaml)

**File:** `/render.yaml`

Add redirect rules for clean URL routing (for Render deployment):

```yaml
- type: redirect
  source: /your-page-name.html
  destination: /pub/pages/category/your-page-name.html
```

**Location in file:** Add after existing similar redirects in the `routes:` section

**Examples:**
```yaml
# For monuments
- type: redirect
  source: /sps-museum-srinagar.html
  destination: /pub/pages/monuments/sps-museum-srinagar.html

# For districts
- type: redirect
  source: /srinagar.html
  destination: /pub/pages/districts/srinagar.html

# For places
- type: redirect
  source: /dal-lake.html
  destination: /pub/pages/places/dal-lake.html
```

### Step 4: Update .htaccess (Apache Rewrite Rules)

**File:** `/.htaccess`

Add rewrite rules for local/GoDaddy hosting:

```apache
# For individual special cases
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^your-special-page\.html$ /pub/pages/category/actual-filename.html [L]

# For groups of similar pages
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(page1|page2|page3)\.html$ /pub/pages/category/$1.html [L]
```

**Pattern Examples:**

1. **Single page redirect:**
   ```apache
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^martand-temple\.html$ /pub/pages/monuments/martand-sun-temple.html [L]
   ```

2. **Multiple pages pattern:**
   ```apache
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(sps-museum-srinagar|saqafat-ghar-sopore)\.html$ /pub/pages/monuments/$1.html [L]
   ```

**Location:** Add in the appropriate category section (monuments, districts, places, etc.)

### Step 5: Test Your Page

**Local Testing:**
1. Navigate to `http://localhost/your-page-name.html`
2. Check header and footer load correctly
3. Verify all images load (if any)
4. Test responsive design (mobile view)
5. Validate all internal links work
6. Check theme switching works

**Navigation Testing:**
1. Verify page appears in correct menu
2. Test dropdown navigation
3. Confirm active state highlights correctly
4. Check mobile hamburger menu

**Production Testing (after deployment):**
1. Test clean URL routing
2. Verify all external resources load
3. Check page on multiple devices
4. Validate SEO meta tags

## Content Guidelines

### Writing Style
- Use clear, descriptive language
- Write in present tense
- Keep paragraphs concise (3-5 sentences)
- Use active voice
- Maintain consistent tone throughout

### Information to Include

**For Tourist Destinations:**
- Location and accessibility
- Best time to visit
- Historical/cultural significance
- Activities available
- Nearby attractions
- Practical visitor information

**For Monuments/Heritage:**
- Historical background
- Architectural features
- Cultural significance
- Visiting hours and fees
- Photography rules
- Conservation status

**For Districts:**
- Geographic overview
- Major attractions
- Cultural highlights
- Connectivity
- Local specialties
- Tourist facilities

### Icon Usage

Use Font Awesome icons consistently:
- `fa-landmark` - Heritage/monuments
- `fa-info-circle` - General information
- `fa-map-marker-alt` - Location
- `fa-clock` - Timing
- `fa-camera` - Photography
- `fa-star` - Highlights/features
- `fa-route` - How to reach
- `fa-lightbulb` - Tips
- `fa-check-circle` - Features list items

## File Organization

```
pub/
  pages/
    districts/          # District pages
    monuments/          # Heritage & monuments
    pilgrimage/        # Religious sites
    places/            # Tourist destinations
    header.html        # Site header (navigation)
    footer.html        # Site footer
    coming-soon.html   # Placeholder page
```

## Version Control

When adding new pages:
1. Work on a feature branch
2. Commit with descriptive messages
3. Update relevant documentation
4. Test thoroughly before merging
5. Update CHANGELOG if applicable

## Automation

### Cache-Busting Versions
- CSS/JS versions update automatically via GitHub Actions
- Format: `?v=<git-hash>`
- Don't manually edit version numbers
- System defined in `AUTOMATED-VERSIONING.md`

### Deployment
- Changes auto-deploy to GoDaddy on push to `main`
- Staging changes deploy to Render on push to `staging`
- See `DEPLOYMENT.md` for details

## Common Issues & Solutions

### Issue: Page not loading
**Solution:** Check rewrite rules in both `.htaccess` and `render.yaml`

### Issue: Header/footer not appearing
**Solution:** Verify JavaScript files load correctly and paths are correct

### Issue: Images not displaying
**Solution:** Use absolute paths from root: `/pub/images/...`

### Issue: Styles not applying
**Solution:** Clear cache, check CSS file paths, verify theme.css loads

### Issue: Navigation not highlighting active page
**Solution:** Check filename matches exactly in header.js active state logic

## Best Practices

1. **Always use absolute paths** for resources (`/pub/css/...` not `../css/...`)
2. **Include alt text** for all images
3. **Use semantic HTML** (proper heading hierarchy)
4. **Optimize images** before adding (max 500KB for web)
5. **Test on multiple devices** before deploying
6. **Keep content accurate** and up-to-date
7. **Follow existing naming conventions**
8. **Document any special requirements**

## Quick Reference Checklist

- [ ] Create HTML page in correct folder
- [ ] Use proper file naming (lowercase-with-hyphens.html)
- [ ] Add complete head section with meta tags
- [ ] Include header and footer placeholders
- [ ] Add content sections with appropriate styling
- [ ] Update `/pub/pages/header.html` navigation
- [ ] Add redirect in `/render.yaml`
- [ ] Add rewrite rule in `/.htaccess`
- [ ] Test page locally
- [ ] Check responsive design
- [ ] Verify all links work
- [ ] Test after deployment
- [ ] Update this guide if new patterns emerge

## Related Documentation

- `URL-PARAMETERS.md` - Feature flags and theming
- `AUTOMATED-VERSIONING.md` - Cache-busting system
- `DEPLOYMENT.md` - Deployment process
- `.github/copilot-instructions.md` - Project overview

## Example: Creating "New Destination" Page

```bash
# 1. Create the file
/pub/pages/places/new-destination.html

# 2. Update header.html
Add: <li><a href="/new-destination.html">New Destination</a></li>

# 3. Update render.yaml
Add:
- type: redirect
  source: /new-destination.html
  destination: /pub/pages/places/new-destination.html

# 4. Update .htaccess
Add to places section:
RewriteRule ^(existing-place|new-destination)\.html$ /pub/pages/places/$1.html [L]

# 5. Test
http://localhost/new-destination.html
```

## Support

For questions or issues:
- Check existing pages for reference patterns
- Review related documentation
- Test changes in staging before production
- Follow project coding standards

---

**Last Updated:** January 3, 2026  
**Maintained By:** Directorate of Tourism Kashmir - Development Team
