<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $feedback
     */
    public function __construct(public array $feedback) {}

    public function envelope(): Envelope
    {
        $kategori = ucfirst((string) ($this->feedback['kategori'] ?? 'feedback'));
        $pengirim = (string) ($this->feedback['nama'] ?? 'Pengguna');

        return new Envelope(
            subject: "[Feedback Ubudiyah] {$kategori} dari {$pengirim}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.feedback',
        );
    }
}
