<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    $ext = pathinfo($uri, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
    ];
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        readfile(__DIR__ . $uri);
        return true;
    }
    return false;
}

if (is_dir(__DIR__ . $uri) && file_exists(__DIR__ . $uri . '/index.php')) {
    $uri .= 'index.php';
}

if ($uri === '/') $uri = '/index.php';

$filePath = __DIR__ . $uri;
if (file_exists($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
    require $filePath;
    return true;
}

http_response_code(404);
echo '<h1>404 - Page Not Found</h1>';
return true;
