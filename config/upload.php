<?php

return [
    'max_size' => env('UPLOAD_MAX_SIZE', 104857600), // 100MB in bytes
    'path' => env('UPLOAD_PATH', 'storage/uploads'),
    'allowed_extensions' => [
        'torrent' => ['torrent'],
    ],
    'allowed_mime_types' => [
        'application/x-bittorrent',
    ],
];
