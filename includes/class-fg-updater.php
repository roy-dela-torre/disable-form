<?php

/**
 * Form Guard Auto-Updater Class
 * Handles automatic updates from GitHub or custom server
 */

if (!defined('ABSPATH')) exit;

class FG_Updater {
    
    private $plugin_file;
    private $plugin_slug;
    private $version;
    private $update_server;
    private $github_repo; // Optional: for GitHub integration
    
    public function __construct($plugin_file, $plugin_slug, $version, $update_server = null, $github_repo = null) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
        $this->update_server = $update_server;
        $this->github_repo = $github_repo; // e.g., 'username/repository-name'
        
        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_filter('upgrader_pre_download', array($this, 'upgrade_download'), 10, 3);
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $plugin_slug = plugin_basename($this->plugin_file);
        
        // Get remote version
        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version, '<')) {
            $transient->response[$plugin_slug] = (object) array(
                'slug' => $this->plugin_slug,
                'plugin' => $plugin_slug,
                'new_version' => $remote_version,
                'url' => $this->get_plugin_url(),
                'package' => $this->get_download_url()
            );
        }
        
        return $transient;
    }
    
    /**
     * Get plugin information for the update screen
     */
    public function plugin_info($false, $action, $response) {
        if ($action !== 'plugin_information') return $false;
        if ($response->slug !== $this->plugin_slug) return $false;
        
        $info = $this->get_plugin_info();
        
        return (object) array(
            'slug' => $this->plugin_slug,
            'plugin_name' => $info['name'],
            'version' => $info['version'],
            'author' => $info['author'],
            'homepage' => $info['homepage'],
            'requires' => $info['requires'],
            'tested' => $info['tested'],
            'downloaded' => $info['downloaded'],
            'last_updated' => $info['last_updated'],
            'sections' => array(
                'description' => $info['description'],
                'changelog' => $info['changelog']
            ),
            'download_link' => $this->get_download_url()
        );
    }
    
    /**
     * Handle plugin download
     */
    public function upgrade_download($false, $package, $upgrader) {
        if (strpos($package, $this->get_download_url()) !== false) {
            return $this->download_package($package);
        }
        return $false;
    }
    
    /**
     * Get remote version from update server or GitHub
     */
    private function get_remote_version() {
        $transient_key = 'fg_remote_version';
        $version = get_transient($transient_key);
        
        if ($version === false) {
            if ($this->github_repo) {
                $version = $this->get_github_version();
            } elseif ($this->update_server) {
                $version = $this->get_server_version();
            }
            
            // Cache for 12 hours
            set_transient($transient_key, $version, 12 * HOUR_IN_SECONDS);
        }
        
        return $version ?: $this->version;
    }
    
    /**
     * Get version from GitHub releases
     */
    private function get_github_version() {
        $url = "https://api.github.com/repos/{$this->github_repo}/releases/latest";
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Form Guard WordPress Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['tag_name'])) {
            return ltrim($data['tag_name'], 'v'); // Remove 'v' prefix if present
        }
        
        return false;
    }
    
    /**
     * Get version from custom update server
     */
    private function get_server_version() {
        $url = trailingslashit($this->update_server) . 'check-version.php';
        
        $response = wp_remote_post($url, array(
            'timeout' => 10,
            'body' => array(
                'plugin' => $this->plugin_slug,
                'version' => $this->version
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return isset($data['version']) ? $data['version'] : false;
    }
    
    /**
     * Get plugin information
     */
    private function get_plugin_info() {
        if ($this->github_repo) {
            return $this->get_github_info();
        } else {
            return $this->get_server_info();
        }
    }
    
    /**
     * Get plugin info from GitHub
     */
    private function get_github_info() {
        $url = "https://api.github.com/repos/{$this->github_repo}";
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Form Guard WordPress Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            return $this->get_default_info();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return array(
            'name' => 'Form Guard (Disable on Non-Production)',
            'version' => $this->get_remote_version(),
            'author' => 'Roy De La Torre',
            'homepage' => $data['html_url'] ?? '',
            'requires' => '5.0',
            'tested' => '6.6',
            'downloaded' => 0,
            'last_updated' => $data['updated_at'] ?? date('Y-m-d'),
            'description' => $data['description'] ?? 'Disables forms sitewide on non-production domains with IP geolocation support.',
            'changelog' => $this->get_changelog()
        );
    }
    
    /**
     * Get plugin info from custom server
     */
    private function get_server_info() {
        $url = trailingslashit($this->update_server) . 'plugin-info.php';
        
        $response = wp_remote_post($url, array(
            'timeout' => 10,
            'body' => array(
                'plugin' => $this->plugin_slug
            )
        ));
        
        if (is_wp_error($response)) {
            return $this->get_default_info();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data ?: $this->get_default_info();
    }
    
    /**
     * Get default plugin info
     */
    private function get_default_info() {
        return array(
            'name' => 'Form Guard (Disable on Non-Production)',
            'version' => $this->version,
            'author' => 'Roy De La Torre',
            'homepage' => '',
            'requires' => '5.0',
            'tested' => '6.6',
            'downloaded' => 0,
            'last_updated' => date('Y-m-d'),
            'description' => 'Disables forms sitewide on non-production domains with IP geolocation support.',
            'changelog' => $this->get_changelog()
        );
    }
    
    /**
     * Get download URL
     */
    private function get_download_url() {
        if ($this->github_repo) {
            return "https://github.com/{$this->github_repo}/archive/main.zip";
        } elseif ($this->update_server) {
            return trailingslashit($this->update_server) . 'download.php?plugin=' . $this->plugin_slug;
        }
        return '';
    }
    
    /**
     * Get plugin URL
     */
    private function get_plugin_url() {
        if ($this->github_repo) {
            return "https://github.com/{$this->github_repo}";
        }
        return $this->update_server ?: '';
    }
    
    /**
     * Download package
     */
    private function download_package($package) {
        $temp_file = download_url($package);
        
        if (is_wp_error($temp_file)) {
            return $temp_file;
        }
        
        return $temp_file;
    }
    
    /**
     * Get changelog
     */
    private function get_changelog() {
        return '<h3>Version 1.2.0</h3>
<ul>
<li><strong>NEW:</strong> Selective Contact Form 7 form disabling with visual interface</li>
<li><strong>NEW:</strong> Advanced geolocation with multi-tier detection</li>
<li><strong>NEW:</strong> Philippines-specific messaging with automatic localization</li>
<li><strong>NEW:</strong> Dynamic form detection using MutationObserver</li>
<li><strong>IMPROVED:</strong> 24-hour caching system for geolocation</li>
<li><strong>IMPROVED:</strong> Enhanced admin interface</li>
<li><strong>FIXED:</strong> Search forms properly excluded from disabling</li>
</ul>';
    }
}