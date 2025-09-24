<?php
/**
 * Form Guard Update Configuration
 * 
 * This file contains configuration for automatic updates.
 * Choose your preferred update method by uncommenting the appropriate lines.
 */

if (!defined('ABSPATH')) exit;

// ===========================================
// UPDATE CONFIGURATION
// ===========================================

// Option 1: GitHub Repository Updates
// Uncomment these lines to use GitHub for updates:
define('FG_UPDATE_METHOD', 'github');
define('FG_GITHUB_REPO', 'roy-dela-torre/disable-form');

// Option 2: Custom Update Server
// Uncomment these lines to use a custom server:
/*
define('FG_UPDATE_METHOD', 'server');
define('FG_UPDATE_SERVER', 'https://your-update-server.com');
*/

// Option 3: WordPress.org Repository (future)
// Uncomment this line if you submit to WordPress.org:
/*
define('FG_UPDATE_METHOD', 'wordpress');
*/

// ===========================================
// DEFAULT CONFIGURATION (no auto-updates)
// ===========================================

if (!defined('FG_UPDATE_METHOD')) {
    define('FG_UPDATE_METHOD', 'none');
}

// ===========================================
// INITIALIZE UPDATER
// ===========================================

if (is_admin() && FG_UPDATE_METHOD !== 'none') {
    require_once plugin_dir_path(__FILE__) . 'includes/class-fg-updater.php';
    
    switch (FG_UPDATE_METHOD) {
        case 'github':
            if (defined('FG_GITHUB_REPO')) {
                new FG_Updater(FG_PLUGIN_FILE, FG_PLUGIN_SLUG, FG_PLUGIN_VERSION, null, FG_GITHUB_REPO);
            }
            break;
            
        case 'server':
            if (defined('FG_UPDATE_SERVER')) {
                new FG_Updater(FG_PLUGIN_FILE, FG_PLUGIN_SLUG, FG_PLUGIN_VERSION, FG_UPDATE_SERVER);
            }
            break;
            
        case 'wordpress':
            // WordPress.org handles updates automatically
            break;
    }
}