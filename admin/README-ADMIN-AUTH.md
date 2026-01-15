# Admin Folder Password Protection

## Setup Instructions

### Method 1: Using Online Tool (Easiest)

1. Visit: https://www.web2generators.com/apache-tools/htpasswd-generator
2. Enter your desired username (e.g., `admin`)
3. Enter your desired password
4. Click "Generate"
5. Copy the generated line (looks like: `admin:$apr1$xyz123$encryptedpasswordhash`)
6. Replace the content in `.htpasswd` with the generated line

### Method 2: Using Command Line (Mac/Linux)

Generate password locally:

```bash
# Generate password for user "admin"
htpasswd -nb admin your-password-here

# Or create/update .htpasswd file directly
htpasswd -c admin/.htpasswd admin
```

### Method 3: Using PHP Script

Create a temporary PHP file to generate the password:

```php
<?php
$username = 'admin';
$password = 'your-password-here';
echo $username . ':' . password_hash($password, PASSWORD_BCRYPT);
?>
```

## Configuration Steps

### Step 1: Update .htaccess File Path

Open `admin/.htaccess` and update this line:

```
AuthUserFile /home/yourusername/.htpasswd
```

**Find the correct path:**

**Option A: Use Absolute Path (Recommended)**
1. Create a temporary PHP file in admin folder: `getpath.php`
```php
<?php echo __DIR__; ?>
```
2. Visit: `https://yourdomain.com/admin/getpath.php`
3. Copy the path shown (e.g., `/home/username/public_html/admin`)
4. Update .htaccess to: `AuthUserFile /home/username/public_html/admin/.htpasswd`
5. Delete getpath.php

**Option B: Use Relative Path**
```
AuthUserFile .htpasswd
```

### Step 2: Generate Password

Use one of the methods above to generate an encrypted password and add it to `.htpasswd`

Example `.htpasswd` content:
```
admin:$apr1$8bze0123$KqW7L.xyz123abcdefgh
```

### Step 3: Set Correct Permissions (Important!)

On your GoDaddy server via FTP or cPanel File Manager:

- `.htaccess` - Set to `644`
- `.htpasswd` - Set to `644` or `640` (more secure)

### Step 4: Test

1. Visit `https://yourdomain.com/admin/`
2. You should see a login prompt
3. Enter your username and password

## Multiple Users

To add multiple users, add one line per user in `.htpasswd`:

```
admin:$apr1$xyz123$encryptedpasswordhash1
editor:$apr1$abc456$encryptedpasswordhash2
moderator:$apr1$def789$encryptedpasswordhash3
```

## Security Best Practices

✅ **Do:**
- Use strong, unique passwords
- Keep `.htpasswd` outside web root if possible
- Use HTTPS (SSL) to prevent password sniffing
- Regularly update passwords
- Limit login attempts if possible

❌ **Don't:**
- Use plain text passwords
- Commit `.htpasswd` with real passwords to Git
- Share admin credentials
- Use common usernames like "admin" (use something unique)

## Troubleshooting

**Issue**: 500 Internal Server Error
- **Solution**: Check `.htaccess` syntax
- **Solution**: Verify `AuthUserFile` path is correct
- **Solution**: Check file permissions (644 for both files)

**Issue**: Still asks for password but rejects valid credentials
- **Solution**: Verify `.htpasswd` format is correct
- **Solution**: Regenerate password hash
- **Solution**: Check for extra spaces or line breaks

**Issue**: Files not working after upload to GoDaddy
- **Solution**: Ensure files start with `.` (dot) - they should be hidden files
- **Solution**: Check that FTP client shows hidden files
- **Solution**: Verify files are in the `/admin/` directory

**Issue**: Password protection not working
- **Solution**: Check if Apache's mod_auth module is enabled (ask GoDaddy support)
- **Solution**: Try absolute path in AuthUserFile instead of relative path
- **Solution**: Clear browser cache and try in incognito mode

## Alternative: PHP Session-Based Authentication

If `.htaccess` doesn't work on your server, you can use PHP authentication. Create a login system in `index.php`.

## Git Configuration

Add to `.gitignore` to prevent committing real passwords:

```
admin/.htpasswd
```

Keep the template file for reference but use a different password on production.

## Quick Setup Example

1. Generate password:
```bash
htpasswd -nb admin MySecurePass123
# Output: admin:$apr1$rOioh3tn$fvMPxq7XL.9E0u7N7vI8P.
```

2. Update `.htpasswd`:
```
admin:$apr1$rOioh3tn$fvMPxq7XL.9E0u7N7vI8P.
```

3. Update `.htaccess` path:
```
AuthUserFile /home/username/public_html/admin/.htpasswd
```

4. Upload both files to `/admin/` folder on GoDaddy

5. Test at: `https://yourdomain.com/admin/`

## Contact

For issues, check GoDaddy's documentation or contact their support about enabling `.htaccess` authentication.
