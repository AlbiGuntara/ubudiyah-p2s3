<?php

namespace App\Http\Controllers;

use App\Mail\FeedbackMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class FeedbackController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kategori' => 'required|string|in:bug,saran,lainnya',
            'pesan' => 'required|string|min:10|max:3000',
            'halaman' => 'nullable|string|max:255',
        ]);

        $recipient = (string) config('mail.feedback_to');
        $mailer = (string) config('mail.default');

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Log::error('Feedback tidak terkirim: alamat MAIL_FEEDBACK_TO tidak valid.', [
                'recipient' => $recipient,
            ]);

            return redirect()->back()->with('error', 'Tujuan email feedback belum dikonfigurasi dengan benar.');
        }

        // Mailer "log"/"array" selalu sukses tanpa mengirim apa pun, jadi jangan
        // pernah melaporkan feedback terkirim saat mailer masih salah konfigurasi.
        if (in_array($mailer, ['log', 'array'], true)) {
            Log::warning('Feedback tidak terkirim karena MAIL_MAILER masih "'.$mailer.'". Isi MAIL_MAILER=smtp atau resend beserta kredensialnya.');

            return redirect()->back()->with(
                'error',
                'Email belum bisa dikirim: MAIL_MAILER masih "'.$mailer.'". Admin perlu mengatur MAIL_MAILER=smtp atau resend.'
            );
        }

        $user = $request->user();

        try {
            Mail::to($recipient)->send(new FeedbackMail([
                'kategori' => $validated['kategori'],
                'pesan' => $validated['pesan'],
                'halaman' => $validated['halaman'] ?? null,
                'nama' => $user?->name,
                'username' => $user?->username,
                'email' => $user?->email,
                'role' => $user?->role,
                'waktu' => now()->format('d/m/Y H:i'),
                'user_agent' => $request->userAgent(),
            ]));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim feedback: '.$e->getMessage(), ['exception' => $e]);

            $message = 'Feedback gagal dikirim. Silakan coba lagi atau hubungi admin.';

            if (config('app.debug')) {
                $message .= ' ('.$e->getMessage().')';
            }

            return redirect()->back()->with('error', $message);
        }

        return redirect()->back()->with('success', 'Terima kasih! Feedback Anda sudah dikirim.');
    }
}
