<?php

namespace App\Services;

use App\Models\GameMatch;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * 釣果投稿用: アップロード直後のファイルに対し EXIF DateTimeOriginal で試合時間内撮影かを検証する。
 * （リサイズ・JPEG 化の前に呼ぶこと。処理後は EXIF が失われる。）
 */
final class EntryCatchPhotoExifValidator
{
    public const MSG_NO_EXIF = '撮影日時が確認できない画像は投稿できません';

    public const MSG_OUTSIDE_MATCH = 'この画像は試合時間内に撮影されたものではありません';

    private const EXIF_DATETIME_FORMAT = 'Y:m:d H:i:s';

    private const TZ = 'Asia/Tokyo';

    /**
     * @param  list<UploadedFile>  $files
     * @param  string  $errorKey  Validation エラー時のキー（例: photos, entries.0.photos）
     */
    public function assertAllPhotosWithinMatchWindow(GameMatch $match, array $files, string $errorKey = 'photos'): void
    {
        if ($match->start_datetime === null) {
            throw ValidationException::withMessages([
                $errorKey => [self::MSG_NO_EXIF],
            ]);
        }

        foreach ($files as $file) {
            $this->assertUploadedFileWithinMatchWindow($match, $file, $errorKey);
        }
    }

    private function assertUploadedFileWithinMatchWindow(GameMatch $match, UploadedFile $file, string $errorKey = 'photos'): void
    {
        if (! function_exists('exif_read_data')) {
            throw ValidationException::withMessages([
                $errorKey => [self::MSG_NO_EXIF],
            ]);
        }

        $path = $file->getRealPath() ?: $file->path();
        if ($path === false || $path === '' || ! is_readable($path)) {
            throw ValidationException::withMessages([
                $errorKey => [self::MSG_NO_EXIF],
            ]);
        }

        $exif = @exif_read_data($path);
        if ($exif === false || empty($exif['DateTimeOriginal'])) {
            throw ValidationException::withMessages([
                $errorKey => [self::MSG_NO_EXIF],
            ]);
        }

        $raw = trim((string) $exif['DateTimeOriginal']);
        try {
            $photoTime = Carbon::createFromFormat(self::EXIF_DATETIME_FORMAT, $raw, self::TZ);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $errorKey => [self::MSG_NO_EXIF],
            ]);
        }

        $start = $match->start_datetime->copy()->timezone(self::TZ);

        if ($photoTime->lt($start)) {
            throw ValidationException::withMessages([
                $errorKey => [self::MSG_OUTSIDE_MATCH],
            ]);
        }

        if ($match->end_datetime !== null) {
            $end = $match->end_datetime->copy()->timezone(self::TZ);
            if ($photoTime->gt($end)) {
                throw ValidationException::withMessages([
                    $errorKey => [self::MSG_OUTSIDE_MATCH],
                ]);
            }
        }
    }
}
