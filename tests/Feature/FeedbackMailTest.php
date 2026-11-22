<?php

use App\Mail\FeedbackMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

it('mengirim feedback ke albiguntara394@gmail.com', function () {
    config(['mail.default' => 'smtp']);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/feedback', [
            'kategori' => 'saran',
            'pesan' => 'Mohon tambahkan rekap pembinaan per kelas.',
            'halaman' => '/pembinaan',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertSent(FeedbackMail::class, function (FeedbackMail $mail): bool {
        return $mail->hasTo('albiguntara394@gmail.com')
            && $mail->feedback['pesan'] === 'Mohon tambahkan rekap pembinaan per kelas.'
            && $mail->feedback['halaman'] === '/pembinaan';
    });
});

it('tidak mengaku berhasil saat mailer hanya menulis log', function () {
    config(['mail.default' => 'log']);

    $this->actingAs(User::factory()->create())
        ->post('/feedback', [
            'kategori' => 'bug',
            'pesan' => 'Tombol simpan tidak merespons sama sekali.',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    Mail::assertNothingSent();
});

it('menolak feedback yang pesannya terlalu pendek', function () {
    config(['mail.default' => 'smtp']);

    $this->actingAs(User::factory()->create())
        ->post('/feedback', ['kategori' => 'lainnya', 'pesan' => 'singkat'])
        ->assertSessionHasErrors('pesan');

    Mail::assertNothingSent();
});
