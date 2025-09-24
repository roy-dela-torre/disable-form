<?php
/**
 * Version Check Endpoint
 * Place this file on your update server
 * URL: https://your-update-server.com/check-version.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Security: Only allow specific plugins
$allowed_plugins = ['form-guard'];

$plugin = $_POST['plugin'] ?? $_GET['plugin'] ?? '';
$current_version = $_POST['version'] ?? $_GET['version'] ?? '';

if (!in_array($plugin, $allowed_plugins)) {
    http_response_code(404);
    echo json_encode(['error' => 'Plugin not found']);
    exit;
}

// Define latest versions for each plugin
$versions = [
    'form-guard' => '1.2.0'
];

$latest_version = $versions[$plugin] ?? '1.0.0';

// Log the check (optional)
$log = [
    'plugin' => $plugin,
    'current_version' => $current_version,
    'latest_version' => $latest_version,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'timestamp' => date('Y-m-d H:i:s')
];

// You can log to file or database here
// file_put_contents('update-checks.log', json_encode($log) . "\n", FILE_APPEND);

echo json_encode([
    'version' => $latest_version,
    'requires' => '5.0',
    'tested' => '6.6',
    'requires_php' => '7.4'
]);
?>