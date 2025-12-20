<?php

/**
 * Router script for PHP built-in server
 *
 * This script handles static file serving and routes non-existent files
 * through the application.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Helper function to find hashed font files
$findHashedFont = function ($fontName, $buildAssetsPath) {
    if (preg_match('/^(fa-[^.]+)\.(woff2|ttf|woff|eot)$/', $fontName, $matches)) {
        $fontPrefix = $matches[1];
        $fontExt = $matches[2];
        $pattern = $buildAssetsPath . '/' . $fontPrefix . '-*.' . $fontExt;
        $files = glob($pattern);
        if (! empty($files) && file_exists($files[0])) {
            return $files[0];
        }
    }

    return null;
};

// Handle /build/webfonts/ paths first (before general /build/ check)
if (strpos($uri, '/build/webfonts/') === 0 || strpos($uri, '/webfonts/') === 0) {
    // Handle webfonts - try to find in build/assets with hashed names
    $fontName = basename($uri);
    $buildAssetsPath = __DIR__ . '/build/assets';
    $hashedFont = $findHashedFont($fontName, $buildAssetsPath);
    $requestedFile = $hashedFont ?: __DIR__ . $uri;
} elseif (strpos($uri, '/build/assets/') === 0) {
    // Handle /build/assets/ paths - check if it's a font without hash
    $requestedFile = __DIR__ . $uri;
    if (! file_exists($requestedFile) || ! is_file($requestedFile)) {
        // Try to find hashed version
        $fontName = basename($uri);
        $buildAssetsPath = __DIR__ . '/build/assets';
        $hashedFont = $findHashedFont($fontName, $buildAssetsPath);
        if ($hashedFont) {
            $requestedFile = $hashedFont;
        }
    }
} elseif (strpos($uri, '/build/') === 0) {
    // Handle other /build/ paths for Vite assets
    $requestedFile = __DIR__ . $uri;
} elseif (strpos($uri, '/assets/') === 0) {
    // Try /build/assets/ first, then fall back to /assets/
    $buildPath = __DIR__ . '/build' . $uri;
    if (file_exists($buildPath) && is_file($buildPath)) {
        $requestedFile = $buildPath;
    } else {
        // If file not found, try to find hashed version (for fonts)
        $fileName = basename($uri);
        $buildAssetsPath = __DIR__ . '/build/assets';

        // Check if it's a font file without hash (e.g., fa-solid-900.ttf)
        $hashedFont = $findHashedFont($fileName, $buildAssetsPath);
        if ($hashedFont) {
            $requestedFile = $hashedFont;
        } else {
            $requestedFile = __DIR__ . $uri;
        }
    }
} else {
    // Get the requested file path
    $requestedFile = __DIR__ . $uri;
}

// If the requested file exists and is a static file, serve it directly
if ($uri !== '/' && file_exists($requestedFile) && is_file($requestedFile)) {
    // Check if it's a static file (not PHP)
    $extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
    $staticExtensions = ['js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'json', 'xml', 'txt', 'pdf', 'zip'];

    if (in_array($extension, $staticExtensions)) {
        // Set proper MIME types for fonts
        $mimeTypes = [
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'otf' => 'font/otf',
        ];

        if (isset($mimeTypes[$extension])) {
            header('Content-Type: ' . $mimeTypes[$extension]);
            header('Access-Control-Allow-Origin: *');
            readfile($requestedFile);
            exit;
        }

        // Let PHP built-in server handle other static files
        return false;
    }
}

// Route through the application
require_once __DIR__ . '/index.php';
