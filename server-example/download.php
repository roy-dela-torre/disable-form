<?php
/**
 * Plugin Download Endpoint
 * Place this file on your update server
 * URL: https://your-update-server.com/download.php
 */

$plugin = $_GET['plugin'] ?? '';

if ($plugin !== 'form-guard') {
    http_response_code(404);
    echo 'Plugin not found';
    exit;
}

// Define available versions and their file paths
$versions = [
    '1.2.0' => 'releases/form-guard-1.2.0.zip',
    'latest' => 'releases/form-guard-latest.zip'
];

$version = $_GET['version'] ?? 'latest';
$file_path = $versions[$version] ?? $versions['latest'];

// Security check
if (!file_exists($file_path) || !is_readable($file_path)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

// Verify it's actually a zip file
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

if ($mime_type !== 'application/zip') {
    http_response_code(500);
    echo 'Invalid file type';
    exit;
}

// Log the download (optional)
$log = [
    'plugin' => $plugin,
    'version' => $version,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'timestamp' => date('Y-m-d H:i:s')
];

// You can log to file or database here
// file_put_contents('downloads.log', json_encode($log) . "\n", FILE_APPEND);

// Set headers for download
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="form-guard.zip"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Output file
readfile($file_path);
exit;
?>