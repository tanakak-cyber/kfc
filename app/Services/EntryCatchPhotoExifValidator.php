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
        $requireCaptureDatetime = $match->requiresCaptureDatetime();

        // 撮影日時必須なのに試合開始日時が無い場合は判定不能 → 従来どおり弾く。
        // 任意（チェックOFF）なら、開始日時が無くても受け付ける。
        if ($requireCaptureDatetime && $match->start_datetime === null) {
            throw ValidationException::withMessages([
                $errorKey => [self::MSG_NO_EXIF],
            ]);
        }

        foreach ($files as $file) {
            $this->assertUploadedFileWithinMatchWindow($match, $file, $errorKey, $requireCaptureDatetime);
        }
    }

    private function assertUploadedFileWithinMatchWindow(GameMatch $match, UploadedFile $file, string $errorKey, bool $requireCaptureDatetime): void
    {
        // 撮影日時が読み取れない画像の扱い。
        // 必須（チェックON）: 例外で弾く。任意（チェックOFF）: そのまま受け付ける（true を返す）。
        $handleMissingCaptureDatetime = function () use ($requireCaptureDatetime, $errorKey): bool {
            if ($requireCaptureDatetime) {
                throw ValidationException::withMessages([
                    $errorKey => [self::MSG_NO_EXIF],
                ]);
            }

            return true;
        };

        if (! function_exists('exif_read_data')) {
            if ($handleMissingCaptureDatetime()) {
                return;
            }
        }

        $path = $file->getRealPath() ?: $file->path();
        if ($path === false || $path === '' || ! is_readable($path)) {
            if ($handleMissingCaptureDatetime()) {
                return;
            }
        }

        $exif = @exif_read_data($path);
        if ($exif === false || empty($exif['DateTimeOriginal'])) {
            if ($handleMissingCaptureDatetime()) {
                return;
            }
        }

        $raw = trim((string) $exif['DateTimeOriginal']);
        try {
            $photoTime = Carbon::createFromFormat(self::EXIF_DATETIME_FORMAT, $raw, self::TZ);
        } catch (\Throwable) {
            if ($handleMissingCaptureDatetime()) {
                return;
            }
        }

        // ここまで来たら撮影日時を取得できている。試合時間内かを確認する
        // （必須/任意に関わらず、撮影日時がある画像は試合時間内チェックの対象）。
        // 任意モードで開始日時が無い場合はチェック不能なため受け付ける。
        if ($match->start_datetime === null) {
            return;
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
