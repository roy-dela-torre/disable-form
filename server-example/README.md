# Update Server Setup

This directory contains example server files for hosting your own plugin update server.

## Files Included

- `check-version.php` - Returns the latest version information
- `plugin-info.php` - Returns detailed plugin information
- `download.php` - Serves the plugin zip file for download

## Server Setup

### 1. Upload Files

Upload these files to your web server:
```
your-update-server.com/
├── check-version.php
├── plugin-info.php  
├── download.php
└── releases/
    ├── form-guard-1.2.0.zip
    └── form-guard-latest.zip
```

### 2. Create Plugin Zip Files

Create zip files containing your plugin:
```bash
# Create a zip of your plugin directory
zip -r form-guard-1.2.0.zip form-guard/
cp form-guard-1.2.0.zip form-guard-latest.zip
```

### 3. Update Configuration

In your plugin's `update-config.php`, set:
```php
define('FG_UPDATE_METHOD', 'server');
define('FG_UPDATE_SERVER', 'https://your-update-server.com');
```

### 4. Test the Endpoints

Test each endpoint:

**Version Check:**
```bash
curl -X POST https://your-update-server.com/check-version.php \
     -d "plugin=form-guard&version=1.1.0"
```

**Plugin Info:**
```bash
curl -X POST https://your-update-server.com/plugin-info.php \
     -d "plugin=form-guard"
```

**Download:**
```bash
curl "https://your-update-server.com/download.php?plugin=form-guard"
```

## Security Features

### Access Control
- Only specific plugins are allowed
- File type validation for downloads
- MIME type checking

### Logging
- Optional download and version check logging
- IP and user agent tracking
- Timestamp recording

### Error Handling
- Proper HTTP status codes
- Graceful error messages
- File existence validation

## Customization

### Adding New Plugins
Edit the `$allowed_plugins` array in `check-version.php`:
```php
$allowed_plugins = ['form-guard', 'your-other-plugin'];
```

### Version Management
Update the `$versions` array when releasing new versions:
```php
$versions = [
    'form-guard' => '1.3.0'  // Update this
];
```

### Enhanced Security
Add authentication, rate limiting, or IP whitelisting as needed.

## Deployment Workflow

1. **Update version** in `check-version.php`
2. **Create new zip** file with updated plugin
3. **Upload zip** to `releases/` directory
4. **Update `form-guard-latest.zip`** to point to new version
5. **Test endpoints** to ensure they work
6. **Plugin will detect** update within 12 hours

## Monitoring

Consider adding:
- Analytics to track downloads
- Error logging for debugging
- Performance monitoring
- Automated deployment scripts

This setup provides a professional update server that you have full control over.