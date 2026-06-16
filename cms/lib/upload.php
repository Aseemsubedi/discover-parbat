<?php
declare(strict_types=1);

function cms_uploads_dir(): string
{
    return dirname(__DIR__, 2) . '/uploads';
}

function cms_public_image_url(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    return '/' . ltrim($path, '/');
}

function cms_handle_image_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Please try again.');
    }

    $maxBytes = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('Image must be 5 MB or smaller.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string)$file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!is_string($mime) || !isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WebP, or GIF images are allowed.');
    }

    $uploadsDir = cms_uploads_dir();
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
        throw new RuntimeException('Could not create uploads folder.');
    }

    $filename = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $destination = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file((string)$file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save uploaded image.');
    }

    return [
        'ok' => true,
        'path' => 'uploads/' . $filename,
        'url' => cms_public_image_url('uploads/' . $filename),
    ];
}
