# Go Live Feature Documentation

## Overview

The website now includes a "Go Live" feature that allows you to control when the main site becomes accessible to visitors. By default, all visitors are redirected to the coming-soon page until an administrator clicks the "Go Live" button with the correct password.

## How It Works

### Default State (Coming Soon Mode)
- All visitors to the root URL (`/`) are automatically redirected to `/coming-soon/`
- The main site content is not accessible
- The coming-soon page displays information about the upcoming launch

### After Going Live
- The "Go Live" button sets a flag in the browser's localStorage
- All subsequent visitors can access the main site normally
- No more redirects to the coming-soon page

## Using the Go Live Button

### Step 1: Access the Coming Soon Page
Visit your website at `/coming-soon/` (or just `/` if not yet live)

### Step 2: Click "Go Live" Button
Look for the green "Go Live" button with a rocket icon on the coming-soon page.

### Step 3: Enter Admin Password
A modal will appear asking for the admin password.

**Default Password:** `jktdc2025`

### Step 4: Launch Site
- Click "Launch Site" or press Enter
- If the password is correct, you'll see a success message
- The site will automatically redirect to the homepage
- The site is now live for all visitors!

### Step 5: Verify
- Open a new browser (or incognito window)
- Visit your site's root URL
- You should see the main site, not the coming-soon page

## Password Configuration

To change the admin password:

1. Open `/coming-soon/js/go-live.js`
2. Find this line near the top:
   ```javascript
   const ADMIN_PASSWORD = 'jktdc2025'; // Change this to your desired password
   ```
3. Replace `'jktdc2025'` with your desired password
4. Save the file
5. Deploy the changes

**Important:** Make sure to use a strong password for production!

## Reverting to Coming Soon Mode

If you need to take the site back to "coming soon" mode:

### Option 1: Browser Console (Individual User)
1. Open browser developer tools (F12)
2. Go to the Console tab
3. Run: `localStorage.removeItem('siteIsLive')`
4. Refresh the page

### Option 2: Clear All Users (Recommended)
To revert the site for ALL users, you need to modify the code:

1. **Temporarily change the localStorage key name:**
   - Edit `/coming-soon/js/go-live.js`
   - Change `const LIVE_FLAG = 'siteIsLive';` to `const LIVE_FLAG = 'siteIsLiveV2';`
   - This invalidates all existing "live" flags

2. **Or remove the redirect script from index.html:**
   - Edit `/index.html`
   - Remove or comment out the redirect script at the top of the `<body>` tag

## Technical Implementation

### Files Modified

1. **`/coming-soon/index.html`**
   - Added "Go Live" button
   - Added script reference to `go-live.js`

2. **`/coming-soon/css/coming-soon.css`**
   - Added button styling
   - Added modal styling with animations

3. **`/coming-soon/js/go-live.js`** (NEW)
   - Password verification logic
   - localStorage management
   - Modal functionality

4. **`/index.html`**
   - Added redirect check script
   - Redirects to coming-soon if not live

### How the Redirect Works

The redirect script in `/index.html` runs immediately when the page loads:

```javascript
<script>
  (function() {
    const isLive = localStorage.getItem('siteIsLive');
    if (isLive !== 'true') {
      window.location.href = '/coming-soon/';
    }
  })();
</script>
```

### localStorage Flag

- **Key:** `siteIsLive`
- **Value:** `'true'` (when live) or `null` (when not live)
- **Scope:** Per-browser, per-user (not global)

**Important:** localStorage is browser-specific. Each user must click "Go Live" in their browser, OR you can set it once and it persists for that browser.

## Security Considerations

### Current Implementation
- Password is stored in plain text in the JavaScript file
- Password verification happens client-side
- localStorage can be manually edited by tech-savvy users

### Recommended for Production
For better security in production:

1. **Use Server-Side Authentication**
   - Move password verification to server
   - Use session-based authentication
   - Store live/not-live state in a database

2. **Stronger Password Protection**
   - Use environment variables for passwords
   - Hash passwords server-side
   - Implement rate limiting on login attempts

3. **Global State Management**
   - Instead of localStorage (per-user), use a server-side flag
   - Check a database or config file to determine live state
   - This affects all users simultaneously

### Quick Security Improvement
Until you implement server-side logic, you can make it slightly harder to bypass:

1. **Obfuscate the Password:**
   Use a simple hash in the JavaScript (still not truly secure, but better):
   ```javascript
   // In go-live.js, replace password check with:
   const hashedPassword = '5f4dcc3b5aa765d61d8327deb882cf99'; // MD5 of 'password'
   function md5(string) { /* MD5 implementation */ }
   if (md5(enteredPassword) === hashedPassword) { ... }
   ```

2. **Rename the localStorage Key:**
   Use a less obvious key name like `_st_cfg_v1` instead of `siteIsLive`

## Testing

### Test Scenario 1: First Visit (Not Live)
1. Clear localStorage: `localStorage.clear()`
2. Visit `/`
3. Should redirect to `/coming-soon/`
4. ✅ Pass if redirected

### Test Scenario 2: Go Live Process
1. Click "Go Live" button
2. Enter incorrect password
3. Should show error message
4. Enter correct password (`jktdc2025`)
5. Should show success message and redirect
6. ✅ Pass if redirected to `/`

### Test Scenario 3: Persistent Live State
1. After going live, close and reopen browser
2. Visit `/`
3. Should stay on main site (no redirect)
4. ✅ Pass if no redirect occurs

### Test Scenario 4: Multiple Browsers
1. Go live in Chrome
2. Open Firefox (different browser)
3. Visit `/`
4. Should redirect to `/coming-soon/` (localStorage is per-browser)
5. ✅ Pass if redirected in new browser

## Deployment

### GitHub Actions
The automated deployment will include all new files:
- `coming-soon/js/go-live.js`
- Updated `coming-soon/index.html`
- Updated `coming-soon/css/coming-soon.css`
- Updated `index.html`

Just commit and push to `main` branch:
```bash
git add .
git commit -m "feat: add Go Live button with password protection"
git push origin main
```

### Manual FTP Deployment
If deploying manually, make sure to upload:
1. `/coming-soon/index.html`
2. `/coming-soon/css/coming-soon.css`
3. `/coming-soon/js/go-live.js`
4. `/index.html`

## Troubleshooting

### Issue: Button doesn't appear
- **Solution:** Check browser console for JavaScript errors
- **Solution:** Verify `go-live.js` is properly loaded
- **Solution:** Clear browser cache and refresh

### Issue: Password always wrong
- **Solution:** Check that password in `go-live.js` matches what you're typing
- **Solution:** Ensure no extra spaces in password
- **Solution:** Password is case-sensitive

### Issue: Redirect loop
- **Solution:** Open browser console and run `localStorage.clear()`
- **Solution:** Check that `index.html` redirect script is correct
- **Solution:** Verify file paths are correct

### Issue: Some users still see coming-soon page
- **Solution:** localStorage is per-browser; each user needs to click "Go Live"
- **Alternative:** For global launch, remove the redirect script from `index.html`

### Issue: Site not staying live after browser restart
- **Solution:** Check that localStorage is not being cleared by browser settings
- **Solution:** Verify user is not in private/incognito mode (localStorage doesn't persist)

## Alternative Approaches

### Global Launch (No Per-User Flag)
If you want a global launch instead of per-user:

1. **Remove redirect script from index.html:**
   Delete the redirect script at the top of `<body>` in `/index.html`

2. **Use server configuration:**
   Add a redirect rule in `.htaccess` (Apache) or Nginx config:
   ```apache
   # .htaccess - Redirect all to coming-soon
   RewriteEngine On
   RewriteCond %{REQUEST_URI} !^/coming-soon
   RewriteRule ^(.*)$ /coming-soon/ [L,R=302]
   ```

3. **Manual launch:**
   Simply delete or rename the `.htaccess` rule when ready to go live

### Environment Variable Approach
For more professional deployment:

1. Use a server-side environment variable: `SITE_IS_LIVE=false`
2. Check this variable in server-side code
3. Serve different pages based on the flag
4. Change variable to `true` when ready to launch

## FAQ

**Q: Can I preview the main site before going live?**
A: Yes! Just add `?preview=true` to the URL or click "Go Live" in your browser (won't affect other users).

**Q: How do I change the password?**
A: Edit `/coming-soon/js/go-live.js` and change the `ADMIN_PASSWORD` constant.

**Q: Can I disable the go-live button after launching?**
A: Yes, remove the "Go Live" button from `/coming-soon/index.html` and the redirect script from `/index.html`.

**Q: What happens if I clear localStorage?**
A: You'll be redirected back to coming-soon page. Click "Go Live" again to access the main site.

**Q: Is this secure?**
A: For basic protection, yes. For production, consider server-side authentication (see Security Considerations section).

## Support

For issues or questions about the Go Live feature:
1. Check the browser console for errors
2. Review this documentation
3. Check that all files are properly deployed
4. Contact the development team

---

**Last Updated:** December 19, 2025
**Feature Version:** 1.0.0
