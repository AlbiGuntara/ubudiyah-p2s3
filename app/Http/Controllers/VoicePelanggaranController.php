<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoicePelanggaranRequest;
use App\Http\Services\Voice\SpeechToText;
use App\Http\Services\Voice\VoiceIntentResolver;
use App\Http\Services\Voice\VoiceTranscriptionException;
use Illuminate\Http\JsonResponse;

class VoicePelanggaranController extends Controller
{
    public function __construct(
        private readonly SpeechToText $speechToText,
        private readonly VoiceIntentResolver $resolver,
    ) {}

    /**
     * Terjemahkan satu rekaman menjadi draft isian, tanpa menyimpannya.
     *
     * Penyimpanan tetap memakai endpoint pelanggaran yang sudah ada supaya
     * validasi, riwayat, dan pembinaan tidak terduplikasi.
     */
    public function store(StoreVoicePelanggaranRequest $request): JsonResponse
    {
        if (! config('voice.enabled')) {
            return response()->json([
                'message' => 'Pencatatan dengan voice sedang dimatikan.',
            ], 403);
        }

        try {
            $result = $this->speechToText->transcribe($request->file('audio'));
        } catch (VoiceTranscriptionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'draft' => $this->resolver->resolve($result['text']),
            'tanggal_default' => now()->toDateString(),
        ]);
    }
}
