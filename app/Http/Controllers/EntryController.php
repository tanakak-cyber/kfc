<?php

namespace App\Http\Controllers;

use App\Enums\CatchApprovalStatus;
use App\Enums\CatchScoringBasis;
use App\Models\FishCatch;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\Team;
use App\Services\CatchImageProcessor;
use App\Services\EntryCatchPhotoExifValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EntryController extends Controller
{
    /** 1回の送信で投稿できる釣果の最大件数（複数匹まとめて投稿） */
    private const MAX_ENTRIES_PER_SUBMIT = 20;

    public function __construct(
        private CatchImageProcessor $images,
        private EntryCatchPhotoExifValidator $entryPhotoExif
    ) {}

    public function show(string $token): View
    {
        $team = Team::query()
            ->where('entry_token', $token)
            ->with(['gameMatch.season', 'players'])
            ->first();

        if ($team !== null) {
            $gameMatch = $team->gameMatch;
            $topCatches = $this->topScoringCatchesForTeam($team, false);

            return view('entry.show', [
                'entryMode' => 'team',
                'team' => $team,
                'participant' => null,
                'gameMatch' => $gameMatch,
                'topCatches' => $topCatches,
                'entryTopLimit' => $this->entryCatchScoringLimit($gameMatch),
            ]);
        }

        $participant = MatchParticipant::query()
            ->where('entry_token', $token)
            ->with(['gameMatch.season', 'player'])
            ->firstOrFail();

        $gameMatch = $participant->gameMatch;
        $topCatches = $this->topScoringCatchesForPlayer($gameMatch, $participant->player_id, false);

        return view('entry.show', [
            'entryMode' => 'individual',
            'team' => null,
            'participant' => $participant,
            'gameMatch' => $gameMatch,
            'topCatches' => $topCatches,
            'entryTopLimit' => $this->entryCatchScoringLimit($gameMatch),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        if (Team::query()->where('entry_token', $token)->exists()) {
            return $this->storeTeamEntry($request, $token);
        }

        return $this->storeIndividualEntry($request, $token);
    }

    private function storeTeamEntry(Request $request, string $token): RedirectResponse
    {
        $team = Team::query()
            ->where('entry_token', $token)
            ->with(['gameMatch', 'players'])
            ->firstOrFail();

        if ($team->gameMatch->is_finalized) {
            return back()->withErrors(['match' => 'この試合は結果確定済みのため投稿できません。']);
        }

        if ($team->gameMatch->isBeforeStartDatetime()) {
            return back()->withErrors(['match' => '試合開始前のため投稿できません。']);
        }

        if ($team->gameMatch->isAtOrAfterEndDatetime()) {
            return back()->withErrors(['match' => '試合終了後のため投稿できません。']);
        }

        $playerIds = $team->players->pluck('id')->all();

        $validated = $request->validate([
            'entries' => ['required', 'array', 'min:1', 'max:'.self::MAX_ENTRIES_PER_SUBMIT],
            'entries.*.player_id' => ['required', 'integer', 'in:'.implode(',', $playerIds)],
            'entries.*.length_cm' => ['required', 'numeric', 'min:0', 'max:999'],
            'entries.*.weight_g' => ['required', 'integer', 'min:0', 'max:9999'],
        ], [], [
            'entries' => '釣果',
            'entries.*.player_id' => '釣った人',
            'entries.*.length_cm' => '長さ（cm）',
            'entries.*.weight_g' => '重さ（g）',
        ]);

        $photoFilesByIndex = [];
        foreach (array_keys($validated['entries']) as $idx) {
            $key = 'entries.'.$idx.'.photos';
            $photoFilesByIndex[$idx] = $this->validatedPhotoUploads($request, $key, $key);
            $this->entryPhotoExif->assertAllPhotosWithinMatchWindow(
                $team->gameMatch,
                $photoFilesByIndex[$idx],
                $key
            );
        }

        return $this->withEntrySubmitLock($token, function () use ($team, $validated, $photoFilesByIndex): RedirectResponse {
            try {
                DB::transaction(function () use ($team, $validated, $photoFilesByIndex): void {
                    foreach ($validated['entries'] as $idx => $row) {
                        $photoFiles = $photoFilesByIndex[$idx] ?? [];
                        $catch = FishCatch::query()->create([
                            'match_id' => $team->match_id,
                            'team_id' => $team->id,
                            'player_id' => (int) $row['player_id'],
                            'length_cm' => $row['length_cm'],
                            'weight_g' => (int) $row['weight_g'],
                            'approval_status' => CatchApprovalStatus::Pending,
                        ]);

                        foreach ($photoFiles as $sort => $file) {
                            $path = $this->images->processAndStore($file);
                            $catch->images()->create([
                                'path' => $path,
                                'sort_order' => (int) $sort,
                            ]);
                        }

                        if ($catch->images()->count() === 0) {
                            throw ValidationException::withMessages([
                                "entries.$idx.photos" => ['画像を1枚以上正しくアップロードしてください。'],
                            ]);
                        }
                    }
                });
            } catch (ValidationException $e) {
                return back()->withErrors($e->errors())->withInput();
            }

            $n = count($validated['entries']);
            $msg = $n === 1
                ? '釣果を送信しました。承認後に公開されます。'
                : "{$n}件の釣果を送信しました。承認後に公開されます。";

            return back()->with('status', $msg);
        });
    }

    private function storeIndividualEntry(Request $request, string $token): RedirectResponse
    {
        $participant = MatchParticipant::query()
            ->where('entry_token', $token)
            ->with('gameMatch')
            ->firstOrFail();

        if (! $participant->is_present) {
            return back()->withErrors(['match' => '欠席のため投稿できません。']);
        }

        if ($participant->gameMatch->is_finalized) {
            return back()->withErrors(['match' => 'この試合は結果確定済みのため投稿できません。']);
        }

        if ($participant->gameMatch->isBeforeStartDatetime()) {
            return back()->withErrors(['match' => '試合開始前のため投稿できません。']);
        }

        if ($participant->gameMatch->isAtOrAfterEndDatetime()) {
            return back()->withErrors(['match' => '試合終了後のため投稿できません。']);
        }

        $validated = $request->validate([
            'entries' => ['required', 'array', 'min:1', 'max:'.self::MAX_ENTRIES_PER_SUBMIT],
            'entries.*.length_cm' => ['required', 'numeric', 'min:0', 'max:999'],
            'entries.*.weight_g' => ['required', 'integer', 'min:0', 'max:9999'],
        ], [], [
            'entries' => '釣果',
            'entries.*.length_cm' => '長さ（cm）',
            'entries.*.weight_g' => '重さ（g）',
        ]);

        $photoFilesByIndex = [];
        foreach (array_keys($validated['entries']) as $idx) {
            $key = 'entries.'.$idx.'.photos';
            $photoFilesByIndex[$idx] = $this->validatedPhotoUploads($request, $key, $key);
            $this->entryPhotoExif->assertAllPhotosWithinMatchWindow(
                $participant->gameMatch,
                $photoFilesByIndex[$idx],
                $key
            );
        }

        return $this->withEntrySubmitLock($token, function () use ($participant, $validated, $photoFilesByIndex): RedirectResponse {
            try {
                DB::transaction(function () use ($participant, $validated, $photoFilesByIndex): void {
                    foreach ($validated['entries'] as $idx => $row) {
                        $photoFiles = $photoFilesByIndex[$idx] ?? [];
                        $catch = FishCatch::query()->create([
                            'match_id' => $participant->match_id,
                            'team_id' => null,
                            'player_id' => $participant->player_id,
                            'length_cm' => $row['length_cm'],
                            'weight_g' => (int) $row['weight_g'],
                            'approval_status' => CatchApprovalStatus::Pending,
                        ]);

                        foreach ($photoFiles as $sort => $file) {
                            $path = $this->images->processAndStore($file);
                            $catch->images()->create([
                                'path' => $path,
                                'sort_order' => (int) $sort,
                            ]);
                        }

                        if ($catch->images()->count() === 0) {
                            throw ValidationException::withMessages([
                                "entries.$idx.photos" => ['画像を1枚以上正しくアップロードしてください。'],
                            ]);
                        }
                    }
                });
            } catch (ValidationException $e) {
                return back()->withErrors($e->errors())->withInput();
            }

            $n = count($validated['entries']);
            $msg = $n === 1
                ? '釣果を送信しました。承認後に公開されます。'
                : "{$n}件の釣果を送信しました。承認後に公開されます。";

            return back()->with('status', $msg);
        });
    }

    /**
     * 同じ投稿URLからの連打・二重送信で釣果が複製されないよう、短時間の排他ロックをかける。
     */
    private function withEntrySubmitLock(string $token, callable $callback): RedirectResponse
    {
        $lock = Cache::lock('entry_submit:'.hash('sha256', $token), 45);

        if (! $lock->get()) {
            return back()->withErrors([
                'match' => '前の送信を処理しています。画面を更新せず、そのままお待ちください。',
            ])->withInput();
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /**
     * GameMatch にヘルパーが無い古い本番でも動くよう、ここで解決（あればモデルに委譲）。
     */
    private function resolveEntryCatchScoringBasis(GameMatch $match): CatchScoringBasis
    {
        if (method_exists($match, 'resolvedCatchScoringBasis')) {
            return $match->resolvedCatchScoringBasis();
        }

        $b = $match->getAttribute('catch_scoring_basis');

        if ($b instanceof CatchScoringBasis) {
            return $b;
        }

        if (is_string($b) && $b !== '') {
            return CatchScoringBasis::tryFrom($b) ?? CatchScoringBasis::Weight;
        }

        return CatchScoringBasis::Weight;
    }

    private function entryCatchScoringLimit(GameMatch $match): int
    {
        if (method_exists($match, 'effectiveCatchScoringLimit')) {
            return $match->effectiveCatchScoringLimit();
        }

        $n = (int) $match->getAttribute('catch_scoring_limit');

        if ($n < 1 || $n > 30) {
            return 3;
        }

        return $n;
    }

    /**
     * 試合の順位設定（基準・本数）に合わせた上位釣果（未承認含む／承認のみは引数で切替）。
     *
     * @return list<array{weight_g: string, length_cm: string}>
     */
    private function topScoringCatchesForTeam(Team $team, bool $approvedOnly): array
    {
        $match = $team->gameMatch;
        $limit = $this->entryCatchScoringLimit($match);

        $query = $team->catches();
        if ($this->resolveEntryCatchScoringBasis($match) === CatchScoringBasis::Length) {
            $query->orderByRaw('COALESCE(length_cm, 0) DESC');
        } else {
            $query->orderByDesc('weight_g');
        }

        if ($approvedOnly) {
            $query->where('approval_status', CatchApprovalStatus::Approved);
        } else {
            $query->whereIn('approval_status', [
                CatchApprovalStatus::Pending,
                CatchApprovalStatus::Approved,
            ]);
        }

        return $query->limit($limit)
            ->get(['weight_g', 'length_cm'])
            ->map(fn (FishCatch $c) => [
                'weight_g' => (string) $c->weight_g,
                'length_cm' => (string) $c->length_cm,
            ])
            ->all();
    }

    /**
     * @return list<array{weight_g: string, length_cm: string}>
     */
    private function topScoringCatchesForPlayer(GameMatch $match, int $playerId, bool $approvedOnly): array
    {
        $limit = $this->entryCatchScoringLimit($match);

        $query = FishCatch::query()
            ->where('match_id', $match->id)
            ->where('player_id', $playerId);
        if ($this->resolveEntryCatchScoringBasis($match) === CatchScoringBasis::Length) {
            $query->orderByRaw('COALESCE(length_cm, 0) DESC');
        } else {
            $query->orderByDesc('weight_g');
        }

        if ($approvedOnly) {
            $query->where('approval_status', CatchApprovalStatus::Approved);
        } else {
            $query->whereIn('approval_status', [
                CatchApprovalStatus::Pending,
                CatchApprovalStatus::Approved,
            ]);
        }

        return $query->limit($limit)
            ->get(['weight_g', 'length_cm'])
            ->map(fn (FishCatch $c) => [
                'weight_g' => (string) $c->weight_g,
                'length_cm' => (string) $c->length_cm,
            ])
            ->all();
    }

    /**
     * photos / photos[] が 1 枚のとき単一 UploadedFile になることがあるため、常に UploadedFile のリストに揃える。
     *
     * @param  string  $inputKey  例: photos, entries.0.photos
     * @return list<UploadedFile>
     */
    private function normalizeUploadedPhotos(Request $request, string $inputKey = 'photos'): array
    {
        $raw = $request->file($inputKey);
        if ($raw === null) {
            return [];
        }
        if ($raw instanceof UploadedFile) {
            return [$raw];
        }
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if ($item instanceof UploadedFile) {
                $out[] = $item;
            }
        }

        return array_values($out);
    }

    /**
     * @param  string  $errorKey  バリデーションエラー時のキー（例: entries.0.photos）
     * @return list<UploadedFile>
     */
    private function validatedPhotoUploads(Request $request, string $inputKey = 'photos', ?string $errorKey = null): array
    {
        $errorKey ??= $inputKey;
        $files = $this->normalizeUploadedPhotos($request, $inputKey);

        if (count($files) < 1) {
            throw ValidationException::withMessages([
                $errorKey => ['画像を1枚以上選択してください。'],
            ]);
        }

        if (count($files) > 10) {
            throw ValidationException::withMessages([
                $errorKey => ['画像は10枚までです。'],
            ]);
        }

        foreach ($files as $file) {
            if (! $file->isValid()) {
                throw ValidationException::withMessages([
                    $errorKey => ['画像のアップロードに失敗しました。ファイルサイズ（合計・1枚あたり）や枚数の上限をご確認ください。'],
                ]);
            }

            $v = Validator::make(
                ['_photo' => $file],
                ['_photo' => ['required', 'file', 'image', 'max:10240']],
            );

            if ($v->fails()) {
                throw ValidationException::withMessages([
                    $errorKey => $v->errors()->get('_photo'),
                ]);
            }
        }

        return $files;
    }
}
