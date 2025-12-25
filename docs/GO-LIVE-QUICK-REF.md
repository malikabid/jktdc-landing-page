# 🚀 Go Live Feature - Quick Reference

## Default Password
```
jktdc2025
```

## How to Launch the Site

1. Visit: `/coming-soon/`
2. Click: **"Go Live"** button (green with rocket icon)
3. Enter password: `jktdc2025`
4. Click: **"Launch Site"**
5. ✅ Site is now live!

## How to Test

### Test if site is in "coming soon" mode:
```javascript
// In browser console:
localStorage.getItem('siteIsLive')
// Returns: null (not live) or 'true' (live)
```

### Manually set site to live (bypass password):
```javascript
// In browser console:
localStorage.setItem('siteIsLive', 'true');
location.reload();
```

### Revert back to coming soon mode:
```javascript
// In browser console:
localStorage.removeItem('siteIsLive');
location.reload();
```

## Change Password

Edit `/coming-soon/js/go-live.js`:
```javascript
const ADMIN_PASSWORD = 'your-new-password-here';
```

## Disable Go Live Feature

### Option 1: Remove redirect from main site
Remove this script from `/index.html` (lines 23-31):
```html
<script>
  (function() {
    const isLive = localStorage.getItem('siteIsLive');
    if (isLive !== 'true') {
      window.location.href = '/coming-soon/';
    }
  })();
</script>
```

### Option 2: Remove the button
Remove the "Go Live" section from `/coming-soon/index.html`

## Files Added/Modified

### New Files:
- ✅ `/coming-soon/js/go-live.js` - Main logic
- ✅ `/GO-LIVE-FEATURE.md` - Full documentation

### Modified Files:
- ✅ `/coming-soon/index.html` - Added button
- ✅ `/coming-soon/css/coming-soon.css` - Added styling
- ✅ `/index.html` - Added redirect check

## Important Notes

⚠️ **Per-Browser Setting:** The "live" state is stored in browser localStorage, so:
- Each browser needs to click "Go Live" separately
- Incognito/private mode won't persist the setting
- Clearing browser data resets the setting

💡 **For Production:** Consider implementing server-side state management for global launch control.

## Deployment

```bash
# Commit and push all changes
git add .
git commit -m "feat: add Go Live feature with password protection"
git push origin main

# GitHub Actions will automatically deploy to GoDaddy
```

## Quick Troubleshooting

| Issue | Solution |
|-------|----------|
| Button not showing | Check browser console, clear cache |
| Password rejected | Check spelling (case-sensitive) |
| Still redirecting after Go Live | Run `localStorage.clear()` in console |
| Some users see coming-soon | Expected - they need to click Go Live too |

---
📚 **Full Documentation:** See `GO-LIVE-FEATURE.md`
