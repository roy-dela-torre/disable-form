# Form Guard - Auto Update Setup Guide

This guide explains how to set up automatic updates for the Form Guard plugin from your repository.

## Update Methods Available

### 1. GitHub Repository Updates (Recommended)

This method allows your plugin to automatically check for updates from a GitHub repository.

#### Setup Steps:

1. **Create a GitHub Repository** for your plugin
2. **Edit `update-config.php`** and uncomment these lines:
   ```php
   define('FG_UPDATE_METHOD', 'github');
   define('FG_GITHUB_REPO', 'your-username/form-guard'); // Replace with your repo
   ```

3. **Create releases** in your GitHub repository:
   - Go to your repo → Releases → Create a new release
   - Use version tags like `v1.2.0`, `v1.3.0`, etc.
   - The plugin will automatically detect new releases

#### GitHub Repository Structure:
```
your-repo/
├── form-guard.php          # Main plugin file
├── readme.txt              # WordPress plugin readme
├── update-config.php       # Update configuration
├── includes/
│   └── class-fg-updater.php # Auto-updater class
└── README.md               # GitHub readme
```

### 2. Custom Update Server

Host your own update server to control plugin updates.

#### Setup Steps:

1. **Create Update Server** with these PHP files:

   **check-version.php** - Returns latest version:
   ```php
   <?php
   header('Content-Type: application/json');
   
   // Your logic to determine latest version
   $latest_version = '1.2.0';
   
   echo json_encode(['version' => $latest_version]);
   ?>
   ```

   **plugin-info.php** - Returns plugin information:
   ```php
   <?php
   header('Content-Type: application/json');
   
   $info = [
       'name' => 'Form Guard (Disable on Non-Production)',
       'version' => '1.2.0',
       'author' => 'Roy De La Torre',
       'homepage' => 'https://your-site.com',
       'requires' => '5.0',
       'tested' => '6.6',
       'description' => 'Plugin description...',
       'changelog' => 'Recent changes...'
   ];
   
   echo json_encode($info);
   ?>
   ```

   **download.php** - Serves plugin zip file:
   ```php
   <?php
   if (isset($_GET['plugin']) && $_GET['plugin'] === 'form-guard') {
       $file = 'form-guard-latest.zip';
       if (file_exists($file)) {
           header('Content-Type: application/zip');
           header('Content-Disposition: attachment; filename="form-guard.zip"');
           readfile($file);
           exit;
       }
   }
   http_response_code(404);
   ?>
   ```

2. **Edit `update-config.php`** and uncomment:
   ```php
   define('FG_UPDATE_METHOD', 'server');
   define('FG_UPDATE_SERVER', 'https://your-update-server.com');
   ```

### 3. WordPress.org Repository

If you submit your plugin to WordPress.org, updates are handled automatically.

1. **Edit `update-config.php`** and uncomment:
   ```php
   define('FG_UPDATE_METHOD', 'wordpress');
   ```

2. **Remove the Update URI** from the plugin header in `form-guard.php`

## How It Works

1. **Version Check**: Plugin checks for updates every 12 hours
2. **Update Notification**: WordPress shows update notification if newer version is available
3. **Automatic Download**: Users can update directly from WordPress admin
4. **Fallback**: If update source is unavailable, no errors are shown

## Deployment Workflow

### For GitHub Method:

1. **Make changes** to your plugin code
2. **Commit and push** to your repository
3. **Create a new release**:
   ```bash
   git tag v1.3.0
   git push origin v1.3.0
   ```
4. **Create release** on GitHub with the new tag
5. **Plugin will detect** the update within 12 hours

### For Custom Server:

1. **Update version** in your server's check-version.php
2. **Upload new plugin zip** to your server
3. **Plugin will detect** the update within 12 hours

## Security Considerations

- **HTTPS Only**: All update URLs should use HTTPS
- **File Validation**: Consider adding checksum validation
- **Access Control**: Limit who can create releases/updates
- **Backup**: Always backup before updates

## Troubleshooting

### Updates Not Showing?

1. **Check transients**: Delete `fg_remote_version` transient in database
2. **Verify URLs**: Ensure your update server/repo is accessible
3. **Check logs**: Look for WordPress or server errors
4. **Test manually**: Visit your update URLs directly

### Plugin Won't Update?

1. **Check permissions**: Ensure WordPress can write to plugins directory
2. **Verify download**: Test download URL manually
3. **Clear cache**: Clear any caching plugins
4. **Check format**: Ensure zip file has correct structure

## Example Implementation

Here's a complete example using GitHub:

1. **Repository**: `https://github.com/username/form-guard`
2. **Configuration** in `update-config.php`:
   ```php
   define('FG_UPDATE_METHOD', 'github');
   define('FG_GITHUB_REPO', 'username/form-guard');
   ```
3. **Release process**:
   - Update version in `form-guard.php`
   - Commit changes
   - Create GitHub release with tag `v1.3.0`
   - Plugin updates automatically appear in WordPress

This system provides professional-grade auto-updates for your plugin while maintaining full control over the release process.