# Form Guard Plugin - Installation Guide

## Overview

Form Guard is a WordPress plugin designed to automatically disable forms on non-production environments, preventing accidental form submissions during development, staging, or testing phases.

## System Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **JavaScript**: Enabled in browser for client-side protection
- **Permissions**: WordPress admin access for configuration

## Installation Methods

### Method 1: Manual Installation (Recommended)

1. **Download or Copy Plugin Files**
   - Ensure you have both `form-guard.php` and `readme.txt` files
   - The plugin folder should be named `disable-form`

2. **Upload to WordPress**
   ```
   /wp-content/plugins/disable-form/
   ├── form-guard.php
   ├── readme.txt
   └── INSTALL.md (this file)
   ```

3. **Set Proper Permissions**
   - Files: 644 (`-rw-r--r--`)
   - Directory: 755 (`drwxr-xr-x`)

4. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Form Guard (Disable on Non-Production)"
   - Click "Activate"

### Method 2: WordPress Admin Upload

1. **Prepare Plugin Archive**
   - Create a ZIP file containing the `disable-form` folder with all plugin files
   - Name it `form-guard.zip` or similar

2. **Upload via WordPress Admin**
   - Go to Plugins → Add New → Upload Plugin
   - Choose your ZIP file
   - Click "Install Now"
   - Click "Activate Plugin"

### Method 3: FTP/SFTP Upload

1. **Connect to Your Server**
   - Use your preferred FTP client (FileZilla, WinSCP, etc.)
   - Connect to your WordPress hosting server

2. **Navigate to Plugins Directory**
   ```
   /public_html/wp-content/plugins/
   ```

3. **Upload Plugin Folder**
   - Upload the entire `disable-form` folder
   - Ensure all files are transferred correctly

4. **Activate via WordPress Admin**
   - Log in to WordPress admin
   - Go to Plugins and activate Form Guard

## Configuration

### Initial Setup

1. **Access Plugin Settings**
   - WordPress Admin → Settings → Form Guard
   - Or go directly to: `yourdomain.com/wp-admin/options-general.php?page=form-guard`

2. **Basic Configuration**
   - ✅ Check "Enable guard" to activate protection
   - Enter your production domain (e.g., `www.yoursite.com`)
   - Customize the warning message if desired
   - Click "Save Changes"

### Configuration Options

| Setting | Description | Example |
|---------|-------------|---------|
| **Enable guard** | Activates form protection | ✅ Checked |
| **Allowed domain** | Production domain where forms work | `www.bankofmakati.com.ph` |
| **Default message** | Message shown to users | `🚧 Forms are disabled on this domain.` |

### Domain Configuration Examples

```
Production Domain: www.example.com
✅ Matches: www.example.com, example.com
❌ Blocks: dev.example.com, staging.example.com, localhost

Production Domain: example.com  
✅ Matches: www.example.com, example.com
❌ Blocks: dev.example.com, staging.example.com

Production Domain: subdomain.example.com
✅ Matches: subdomain.example.com only
❌ Blocks: www.example.com, example.com, other.example.com
```

## Verification

### Test on Development/Staging Site

1. **Enable the plugin** on your dev/staging site
2. **Configure production domain** (your live site)
3. **Visit any page** with forms
4. **Expected behavior**:
   - 🟡 Orange banner appears at top: "Forms are disabled on this non-production domain"
   - 🚫 All form fields are disabled and grayed out
   - ⚠️ Clicking submit shows alert message
   - 🔍 Search forms still work normally

### Test on Production Site

1. **Enable the plugin** on your live site
2. **Configure the same domain** as production
3. **Expected behavior**:
   - ✅ No banner appears
   - ✅ All forms work normally
   - ✅ No JavaScript blocking
   - ✅ Contact Form 7 emails are sent

## Troubleshooting

### Common Issues

#### Plugin Not Activating
```bash
# Check file permissions
chmod 644 form-guard.php
chmod 755 disable-form/

# Check PHP syntax
php -l form-guard.php
```

#### Forms Still Working on Staging
- ✅ Verify "Enable guard" is checked
- ✅ Check current domain vs configured domain
- ✅ Clear any caching plugins
- ✅ Check browser console for JavaScript errors

#### Banner Not Showing
- ✅ Confirm you're on a non-production domain
- ✅ Check if theme has custom header that might conflict
- ✅ Inspect page source for CSS styles

#### Search Forms Disabled
- Search forms should be excluded automatically
- Check if search form has proper attributes:
  ```html
  <form role="search">
  <input type="search" name="s">
  ```

### Debug Information

Add this to `wp-config.php` for debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check debug logs at: `/wp-content/debug.log`

## Advanced Configuration

### Geolocation Features (Optional)

The plugin includes advanced geolocation features for different messages based on visitor location:

1. **Uncomment PH Message Section** in admin settings
2. **Enable IP Lookup API** for enhanced detection
3. **Configure Philippines-specific message**

### Custom CSS Styling

Add custom styles to your theme:
```css
.fg-banner {
    background: #custom-color !important;
    font-family: 'Your-Font' !important;
}

form.fg-disabled {
    border: 2px dashed #ccc !important;
}
```

### Server-Level Configuration

For enhanced performance on staging servers:
```apache
# .htaccess - Block form submissions
RewriteCond %{REQUEST_METHOD} POST
RewriteCond %{HTTP_HOST} ^staging\.
RewriteRule ^.*$ - [R=405,L]
```

## Security Notes

- ✅ Plugin only runs on WordPress sites
- ✅ No external dependencies required
- ✅ Geolocation data cached locally
- ✅ No sensitive data transmitted
- ⚠️ API calls to ipapi.co are optional and rate-limited

## Performance Impact

- **Minimal impact** on production sites (guard disabled)
- **Small JavaScript payload** (~2KB) on non-production
- **Cached geolocation** results for 24 hours
- **No database queries** on production domains

## Support

### Before Contacting Support

1. ✅ Check this installation guide
2. ✅ Review the main readme.txt file
3. ✅ Test with default WordPress theme
4. ✅ Disable other plugins temporarily
5. ✅ Check browser console for errors

### Getting Help

- **Plugin Issues**: Contact the plugin author
- **WordPress Issues**: Check WordPress.org support forums
- **Server Issues**: Contact your hosting provider

### Reporting Bugs

When reporting issues, include:
- WordPress version
- PHP version  
- Active theme name
- List of active plugins
- Steps to reproduce
- Expected vs actual behavior

## Uninstallation

### Remove Plugin

1. **Deactivate** in WordPress Admin → Plugins
2. **Delete** plugin files from `/wp-content/plugins/disable-form/`
3. **Optional**: Remove settings from database
   ```sql
   DELETE FROM wp_options WHERE option_name LIKE 'fg_%';
   ```

### Clean Removal

The plugin stores these WordPress options:
- `fg_guard_enabled`
- `fg_allowed_domain` 
- `fg_guard_message`
- `fg_guard_message_ph`
- `fg_use_geo_api`

These are automatically removed when the plugin is deleted through WordPress admin.

---

## Quick Start Checklist

- [ ] Upload plugin files to `/wp-content/plugins/disable-form/`
- [ ] Activate plugin in WordPress admin
- [ ] Go to Settings → Form Guard
- [ ] Check "Enable guard"
- [ ] Enter production domain
- [ ] Save settings
- [ ] Test on staging site (forms should be disabled)
- [ ] Test on production site (forms should work normally)

**Need help?** Refer to the troubleshooting section or contact support.