<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CatchImageRotationService
{
    /**
     * 保存済み JPEG（public ディスク相対パス）を、90°単位で時計回りに回転して上書き保存する。
     *
     * @param  int  $clockwiseQuarterTurns  1=90°時計回り, -1=90°反時計回り, 2=180°, …
     */
    public function rotateQuarterTurns(string $relativePath, int $clockwiseQuarterTurns, string $disk = 'public'): void
    {
        $turns = (($clockwiseQuarterTurns % 4) + 4) % 4;
        if ($turns === 0) {
            return;
        }

        $fullPath = Storage::disk($disk)->path($relativePath);
        if (! is_file($fullPath)) {
            throw new RuntimeException('画像ファイルが見つかりません。');
        }

        // imagerotate: 正の角度は反時計回り → 時計回り90° は -90
        $angleCcw = -90 * $turns;

        $binary = @file_get_contents($fullPath);
        if ($binary === false || $binary === '') {
            throw new RuntimeException('画像の読み込みに失敗しました。');
        }

        $src = @imagecreatefromstring($binary);
        if ($src === false) {
            throw new RuntimeException('対応していない画像形式です。');
        }

        $white = imagecolorallocate($src, 255, 255, 255);
        $rotated = imagerotate($src, $angleCcw, $white);
        imagedestroy($src);

        if ($rotated === false) {
            throw new RuntimeException('画像の回転に失敗しました。');
        }

        if (! imagejpeg($rotated, $fullPath, 80)) {
            imagedestroy($rotated);
            throw new RuntimeException('画像の保存に失敗しました。');
        }

        imagedestroy($rotated);
    }
}
