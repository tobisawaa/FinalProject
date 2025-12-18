<?php
/**
 * Image helper functions for profile picture processing
 */

function ensure_avatars_dir() {
    $dir = __DIR__ . '/../../public/assets/img/avatars';
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function save_profile_image_from_upload($tmpPath, $userId, $maxSizeBytes = 5 * 1024 * 1024) {
    if (!file_exists($tmpPath)) return false;
    if (filesize($tmpPath) > $maxSizeBytes) return false;

    $imgData = file_get_contents($tmpPath);
    return save_profile_image_from_string($imgData, $userId);
}

function save_profile_image_from_base64($base64Data, $userId) {
    // data like "data:image/png;base64,...."
    if (strpos($base64Data, 'base64,') !== false) {
        $parts = explode('base64,', $base64Data);
        $base64Data = end($parts);
    }
    $imgData = base64_decode($base64Data);
    if ($imgData === false) return false;
    return save_profile_image_from_string($imgData, $userId);
}

function save_profile_image_from_string($imgData, $userId) {
    if (empty($imgData)) return false;

    $src = @imagecreatefromstring($imgData);
    if (!$src) return false;

    $w = imagesx($src);
    $h = imagesy($src);
    $size = min($w, $h);
    // center crop
    $src_x = intval(($w - $size) / 2);
    $src_y = intval(($h - $size) / 2);

    $outSize = 300; // square profile image
    $dst = imagecreatetruecolor($outSize, $outSize);
    // preserve transparency
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $outSize, $outSize, $transparent);

    imagecopyresampled($dst, $src, 0, 0, $src_x, $src_y, $outSize, $outSize, $size, $size);

    $dir = ensure_avatars_dir();
    $filename = 'user_' . intval($userId) . '.jpg';
    $path = $dir . '/' . $filename;

    // save as jpeg
    imagejpeg($dst, $path, 90);

    imagedestroy($src);
    imagedestroy($dst);

    // return path relative to public
    return 'assets/img/avatars/' . $filename;
}

?>
