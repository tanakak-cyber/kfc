<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class CatchImageProcessor
{
    public function processAndStore(UploadedFile $file, string $disk = 'public'): string
    {
        $binary = @file_get_contents($file->getRealPath() ?: $file->path());
        if ($binary === false || $binary === '') {
            throw new RuntimeException('画像の読み込みに失敗しました。');
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            throw new RuntimeException('対応していない画像形式です。');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $targetWidth = min(1200, $width);
        $targetHeight = (int) max(1, round($height * ($targetWidth / $width)));

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );
        imagedestroy($image);

        $relative = 'catches/'.uniqid('c_', true).'.jpg';
        $fullPath = storage_path('app/'.$disk.'/'.$relative);

        $dir = dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (! imagejpeg($resized, $fullPath, 80)) {
            imagedestroy($resized);
            throw new RuntimeException('画像の保存に失敗しました。');
        }

        imagedestroy($resized);

        return $relative;
    }
}
