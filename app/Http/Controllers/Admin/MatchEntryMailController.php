<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CatchEntryInvitationMail;
use App\Models\GameMatch;
use App\Models\MatchParticipant;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class MatchEntryMailController extends Controller
{
    public function sendAll(GameMatch $gameMatch): RedirectResponse
    {
        $gameMatch->loadMissing(['season', 'teams.players', 'matchParticipants.player']);

        $sent = 0;
        $skippedNoEmail = 0;

        if ($gameMatch->isTeamMatch()) {
            foreach ($gameMatch->teams as $team) {
                $url = url('/entry/'.$team->entry_token);
                foreach ($team->players as $player) {
                    if (! filled($player->email)) {
                        $skippedNoEmail++;

                        continue;
                    }
                    Mail::to($player->email)->send(new CatchEntryInvitationMail($gameMatch, $url));
                    $sent++;
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
                Mail::to($player->email)->send(new CatchEntryInvitationMail($gameMatch, $url));
                $sent++;
            }
        }

        if ($sent === 0) {
            return back()->withErrors([
                'entry_mail' => '送信先がありません。選手にメールアドレスを登録するか、個人戦では出席の参加者のみ送信対象です。',
            ]);
        }

        $msg = "釣果投稿URLのメールを {$sent} 件送信しました。";
        if ($skippedNoEmail > 0) {
            $msg .= "（メール未登録などでスキップ: {$skippedNoEmail} 件）";
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

        foreach ($team->players as $player) {
            if (! filled($player->email)) {
                continue;
            }
            Mail::to($player->email)->send(new CatchEntryInvitationMail($gameMatch, $url));
            $sent++;
        }

        if ($sent === 0) {
            return back()->withErrors([
                'entry_mail' => 'このチームの選手にメールアドレスが登録されていません。',
            ]);
        }

        return back()->with('status', $this->appendMailTransportHint("チーム「{$team->name}」のメンバーに {$sent} 件送信しました。"));
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
        Mail::to($player->email)->send(new CatchEntryInvitationMail($gameMatch, $url));

        return back()->with('status', $this->appendMailTransportHint('釣果投稿URLのメールを送信しました。'));
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
