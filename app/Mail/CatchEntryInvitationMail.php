<?php

namespace App\Mail;

use App\Models\GameMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CatchEntryInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GameMatch $gameMatch,
        public string $entryUrl,
    ) {}

    public function envelope(): Envelope
    {
        $this->gameMatch->loadMissing('season');
        $m = $this->gameMatch;
        $datePrefix = $m->start_datetime !== null
            ? $m->start_datetime->format('n').'月'.$m->start_datetime->format('j').'日の'
            : '';
        $subject = $datePrefix.$m->season->name.' | '.$m->title.'の釣果について';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.catch-entry-invitation',
        );
    }
}
