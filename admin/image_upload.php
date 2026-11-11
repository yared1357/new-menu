<?php

function store_menu_image(array $file): string
{
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'The image exceeds the server upload limit.',
        UPLOAD_ERR_FORM_SIZE => 'The image exceeds the form upload limit.',
        UPLOAD_ERR_PARTIAL => 'The image upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE => 'Please choose an image to upload.',
        UPLOAD_ERR_NO_TMP_DIR => 'The server is missing its temporary upload folder.',
        UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded image.',
        UPLOAD_ERR_EXTENSION => 'A server extension stopped the image upload.',
    ];
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException($upload_errors[$error] ?? 'The image upload failed. Please try again.');
    }

    $temporary_path = $file['tmp_name'] ?? '';
    if ($temporary_path === '' || !is_uploaded_file($temporary_path)) {
        throw new RuntimeException('The uploaded image could not be verified. Please try again.');
    }

    $image_info = @getimagesize($temporary_path);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $mime_type = $image_info['mime'] ?? '';
    if (!isset($extensions[$mime_type])) {
        throw new RuntimeException('Please upload a valid JPG, PNG, GIF, or WebP image.');
    }

    $upload_dir = dirname(__DIR__) . '/uploads';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        throw new RuntimeException('The server could not create the uploads folder.');
    }
    if (!is_writable($upload_dir)) {
        throw new RuntimeException('The uploads folder is not writable by the web server. Grant Apache write access to the uploads folder.');
    }

    $file_name = 'item_' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime_type];
    if (!move_uploaded_file($temporary_path, $upload_dir . '/' . $file_name)) {
        throw new RuntimeException('The server could not save the image. Check write access to the uploads folder.');
    }

    return 'uploads/' . $file_name;
}

function remove_menu_image(string $image_path): void
{
    if (strncmp($image_path, 'uploads/', 8) !== 0) {
        return;
    }

    $file_name = substr($image_path, 8);
    if ($file_name === '' || basename($file_name) !== $file_name) {
        return;
    }

    $path = dirname(__DIR__) . '/uploads/' . $file_name;
    if (is_file($path) && !unlink($path)) {
        throw new RuntimeException('The menu was updated, but the previous image could not be removed.');
    }
}
