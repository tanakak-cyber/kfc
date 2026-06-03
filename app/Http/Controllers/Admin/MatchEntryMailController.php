<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CatchEntryInvitationMail;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MatchEntryMailController extends Controller
{
    /**
     * メール送信の最大試行回数（初回 + 再送）。
     */
    private const MAIL_MAX_ATTEMPTS = 3;

    /**
     * 再送時の待機時間（マイクロ秒）。試行回数に比例させる簡易バックオフ。
     */
    private const MAIL_RETRY_BACKOFF_US = 500000;

    public function sendAll(GameMatch $gameMatch): RedirectResponse
    {
        $gameMatch->loadMissing(['season', 'teams.players', 'matchParticipants.player']);

        $sent = 0;
        $failed = 0;
        $skippedNoEmail = 0;

        if ($gameMatch->isTeamMatch()) {
            foreach ($gameMatch->teams as $team) {
                $url = url('/entry/'.$team->entry_token);
                foreach ($team->players as $player) {
                    if (! filled($player->email)) {
                        $skippedNoEmail++;

                        continue;
                    }
                    if ($this->deliver($gameMatch, $player->id, $player->email, $url)) {
                        $sent++;
                    } else {
                        $failed++;
                    }
                }
            }
        } else {
            foreach ($gameMatch->matchParticipants as $participant) {
                if (! $participant->is_present) {
                    continue;
                }
                $player = $participant->player;
                if (! filled($player->email)) {
                    $skippedNoEmail++;

                    continue;
                }
                $url = url('/entry/'.$participant->entry_token);
                if ($this->deliver($gameMatch, $player->id, $player->email, $url)) {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        }

        if ($sent === 0 && $failed === 0) {
            return back()->withErrors([
                'entry_mail' => '送信先がありません。選手にメールアドレスを登録するか、個人戦では出席の参加者のみ送信対象です。',
            ]);
        }

        $msg = "釣果投稿URLのメールを {$sent} 件送信しました。";
        if ($skippedNoEmail > 0) {
            $msg .= "（メール未登録などでスキップ: {$skippedNoEmail} 件）";
        }
        if ($failed > 0) {
            $msg .= "（送信に失敗: {$failed} 件。時間をおいて再度お試しください）";
        }

        return back()->with('status', $this->appendMailTransportHint($msg));
    }

    public function sendTeam(GameMatch $gameMatch, Team $team): RedirectResponse
    {
        if ($team->match_id !== $gameMatch->id) {
            abort(404);
        }

        $team->load('players');
        $gameMatch->loadMissing('season');

        $url = url('/entry/'.$team->entry_token);
        $sent = 0;
        $failed = 0;
        $hasEmail = false;

        foreach ($team->players as $player) {
            if (! filled($player->email)) {
                continue;
            }
            $hasEmail = true;
            if ($this->deliver($gameMatch, $player->id, $player->email, $url)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        if (! $hasEmail) {
            return back()->withErrors([
                'entry_mail' => 'このチームの選手にメールアドレスが登録されていません。',
            ]);
        }

        $msg = "チーム「{$team->name}」のメンバーに {$sent} 件送信しました。";
        if ($failed > 0) {
            $msg .= "（送信に失敗: {$failed} 件。時間をおいて再度お試しください）";
        }

        return back()->with('status', $this->appendMailTransportHint($msg));
    }

    public function sendParticipant(GameMatch $gameMatch, MatchParticipant $participant): RedirectResponse
    {
        if ($participant->match_id !== $gameMatch->id) {
            abort(404);
        }

        if (! $participant->is_present) {
            return back()->withErrors([
                'entry_mail' => '欠席の参加者には送信できません。',
            ]);
        }

        $participant->load('player');
        $gameMatch->loadMissing('season');

        $player = $participant->player;
        if (! filled($player->email)) {
            return back()->withErrors([
                'entry_mail' => 'この選手にメールアドレスが登録されていません。',
            ]);
        }

        $url = url('/entry/'.$participant->entry_token);

        if (! $this->deliver($gameMatch, $player->id, $player->email, $url)) {
            return back()->withErrors([
                'entry_mail' => 'メールの送信に失敗しました。時間をおいて再度お試しください。',
            ]);
        }

        return back()->with('status', $this->appendMailTransportHint('釣果投稿URLのメールを送信しました。'));
    }

    /**
     * 1件分のメールを送信する。失敗時は MAIL_MAX_ATTEMPTS まで再送し、
     * 成功時は entry_mail.sent、失敗時は entry_mail.failed をログに残す。
     */
    private function deliver(GameMatch $gameMatch, int $playerId, string $email, string $url): bool
    {
        for ($attempt = 1; $attempt <= self::MAIL_MAX_ATTEMPTS; $attempt++) {
            try {
                Mail::to($email)->send(new CatchEntryInvitationMail($gameMatch, $url));

                Log::info('entry_mail.sent', [
                    'match_id' => $gameMatch->id,
                    'player_id' => $playerId,
                    'email' => $email,
                    'attempt' => $attempt,
                ]);

                return true;
            } catch (Throwable $e) {
                Log::warning('entry_mail.failed', [
                    'match_id' => $gameMatch->id,
                    'player_id' => $playerId,
                    'email' => $email,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < self::MAIL_MAX_ATTEMPTS) {
                    usleep(self::MAIL_RETRY_BACKOFF_US * $attempt);
                }
            }
        }

        return false;
    }

    private function appendMailTransportHint(string $message): string
    {
        $driver = (string) config('mail.default');

        if ($driver === 'log') {
            return $message.' 現在 MAIL_MAILER=log のため受信者のメールボックスには届きません（storage/logs/laravel.log に記録されます）。実送信するには .env で smtp 等に変更してください。';
        }

        if ($driver === 'array') {
            return $message.' 現在 MAIL_MAILER=array のため実送信されません（テスト用です）。';
        }

        return $message;
    }
}
