<?php
/**
 * Plugin Information Endpoint
 * Place this file on your update server
 * URL: https://your-update-server.com/plugin-info.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$plugin = $_POST['plugin'] ?? $_GET['plugin'] ?? '';

if ($plugin !== 'form-guard') {
    http_response_code(404);
    echo json_encode(['error' => 'Plugin not found']);
    exit;
}

$info = [
    'name' => 'Form Guard (Disable on Non-Production)',
    'slug' => 'form-guard',
    'version' => '1.2.0',
    'author' => 'Roy De La Torre',
    'author_profile' => 'https://github.com/your-username',
    'homepage' => 'https://github.com/your-username/form-guard',
    'requires' => '5.0',
    'tested' => '6.6',
    'requires_php' => '7.4',
    'downloaded' => 150,
    'active_installs' => 50,
    'last_updated' => '2025-09-24',
    'description' => 'Disables forms sitewide on non-production domains with IP geolocation support and selective Contact Form 7 blocking.',
    'short_description' => 'Protect development/staging sites from accidental form submissions.',
    'changelog' => '
        <h3>Version 1.2.0</h3>
        <ul>
            <li><strong>NEW:</strong> Selective Contact Form 7 form disabling with visual interface</li>
            <li><strong>NEW:</strong> Advanced geolocation with multi-tier detection</li>
            <li><strong>NEW:</strong> Philippines-specific messaging with automatic localization</li>
            <li><strong>NEW:</strong> Dynamic form detection using MutationObserver</li>
            <li><strong>IMPROVED:</strong> 24-hour caching system for geolocation</li>
            <li><strong>IMPROVED:</strong> Enhanced admin interface</li>
            <li><strong>FIXED:</strong> Search forms properly excluded from disabling</li>
        </ul>
        
        <h3>Version 1.1.0</h3>
        <ul>
            <li>Added Contact Form 7 server-side blocking</li>
            <li>Improved admin interface</li>
            <li>Enhanced form detection</li>
        </ul>
    ',
    'installation' => '
        <ol>
            <li>Download the plugin zip file</li>
            <li>Upload to WordPress via Plugins → Add New → Upload</li>
            <li>Activate the plugin</li>
            <li>Go to Settings → Form Guard to configure</li>
        </ol>
    ',
    'faq' => '
        <h4>Will this affect my production site?</h4>
        <p>No, the plugin only activates when your current domain doesn\'t match the configured production domain.</p>
        
        <h4>Can I disable only specific Contact Form 7 forms?</h4>
        <p>Yes! Version 1.2.0 introduces selective CF7 form disabling with a visual interface.</p>
    ',
    'screenshots' => [
        [
            'src' => 'https://your-server.com/screenshots/settings.png',
            'caption' => 'Plugin settings page with selective form disabling'
        ],
        [
            'src' => 'https://your-server.com/screenshots/banner.png', 
            'caption' => 'Dismissible banner shown on non-production sites'
        ]
    ],
    'tags' => ['form', 'security', 'production', 'development', 'staging', 'contact-form-7'],
    'donate_link' => 'https://your-donation-link.com'
];

echo json_encode($info);
?>