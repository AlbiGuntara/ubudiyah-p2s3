<?php

namespace App\Console\Commands;

use App\Mail\FeedbackMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestFeedbackMail extends Command
{
    protected $signature = 'feedback:test {email? : Alamat tujuan percobaan}';

    protected $description = 'Kirim email percobaan untuk memastikan konfigurasi email feedback berfungsi';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $recipient = (string) ($this->argument('email') ?: config('mail.feedback_to'));

        $this->line('Mailer   : '.$mailer);
        $this->line('Tujuan   : '.$recipient);
        $this->line('Pengirim : '.config('mail.from.address'));

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->error('Mailer "'.$mailer.'" tidak mengirim email sungguhan. Set MAIL_MAILER=smtp atau resend lebih dulu.');

            return self::FAILURE;
        }

        if ($mailer === 'smtp') {
            $this->line('SMTP     : '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port').' sebagai '.config('mail.mailers.smtp.username'));
        }

        if ($mailer === 'resend' && blank(config('mail.mailers.resend.key'))) {
            $this->error('RESEND_API_KEY belum diisi di .env.');

            return self::FAILURE;
        }

        try {
            Mail::to($recipient)->send(new FeedbackMail([
                'kategori' => 'saran',
                'pesan' => 'Ini email percobaan dari perintah feedback:test. Jika Anda menerimanya, konfigurasi email feedback sudah benar.',
                'halaman' => 'CLI (feedback:test)',
                'nama' => 'Sistem',
                'username' => 'system',
                'email' => null,
                'role' => 'system',
                'waktu' => now()->format('d/m/Y H:i'),
                'user_agent' => 'artisan',
            ]));
        } catch (Throwable $e) {
            $this->error('GAGAL mengirim: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Email percobaan terkirim ke '.$recipient.'. Periksa inbox dan folder spam.');

        return self::SUCCESS;
    }
}
