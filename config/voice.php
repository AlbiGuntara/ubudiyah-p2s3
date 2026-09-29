<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master Switch
    |--------------------------------------------------------------------------
    | false -> tombol "Catat dengan Voice" disembunyikan, form manual utuh.
    |
    */

    'enabled' => env('VOICE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Speech-to-Text (Groq Whisper)
    |--------------------------------------------------------------------------
    | Endpoint OpenAI-compatible, dipanggil lewat Guzzle agar provider cukup
    | ditukar lewat .env tanpa menambah SDK.
    |
    | Groq menerima webm, mp4, m4a, ogg, wav, mp3 sekaligus, jadi tidak perlu
    | ffmpeg di server untuk hasil rekam Chrome (webm) maupun Safari/iOS (mp4).
    |
    */

    'stt' => [
        'base_url' => env('VOICE_GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'key' => env('GROQ_API_KEY'),
        'model' => env('VOICE_GROQ_MODEL', 'whisper-large-v3-turbo'),
        'language' => env('VOICE_STT_LANGUAGE', 'id'),
        'timeout' => (int) env('VOICE_STT_TIMEOUT', 60),

        // Gangguan jaringan di sisi server sering hanya sesaat, misalnya
        // resolver DNS yang lambat. Tanpa pengulangan, satu kedipan tersebut
        // langsung menggagalkan pencatatan yang sudah dicatat petugas.
        'retry' => [
            'attempts' => (int) env('VOICE_STT_RETRY_ATTEMPTS', 2),
            'backoff_ms' => (int) env('VOICE_STT_RETRY_BACKOFF_MS', 400),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kunci Istilah (biasing prompt Whisper)
    |--------------------------------------------------------------------------
    | Dikirim sebagai parameter `prompt` (maks 224 token) supaya Whisper tidak
    | salah eja istilah kepesantrenan.
    |
    */

    'biasing_prompt' => env('VOICE_GROQ_BIASING_PROMPT', implode(' ', [
        'Transkripsi kalimat Bahasa Indonesia tentang pencatatan pelanggaran Santri di pesantren.',
        'Istilah: Jamaah, Jubah, Gamis, Sholat, Shof, Wirid, Subuh, Dhuhur, Dzuhur, Ashar, Maghrib, Isya, Jum\'at.',
        'Tahajjud, Tarhim, Dhuha, Qiyamul Lail, Mahallul Qiyam, Khotmil Qur\'an, Munjiyat, Syawariq, Dzikir, Kaji, Kajian.',
        'Asrama, Daerah, Santri, Ketua Kamar, Pelanggaran, Salaman, Idarah, Shalat Berjamaah, Murid, Santriyin.',
    ])),

    /*
    |--------------------------------------------------------------------------
    | LLM Fallback (Gemini)
    |--------------------------------------------------------------------------
    | Hanya dipanggil bila rules parser gagal atau ambigu. Dipakai dengan
    | structured output (responseSchema) dan daftar tertutup daerah/asrama/
    | jenis pelanggaran supaya model tidak mengarang nilai di luar master data.
    |
    */

    'llm' => [
        'base_url' => env('VOICE_GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'key' => env('GEMINI_API_KEY'),
        'model' => env('VOICE_GEMINI_MODEL', 'gemini-3.8-flash'),
        'timeout' => (int) env('VOICE_LLM_TIMEOUT', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Batas Rekaman
    |--------------------------------------------------------------------------
    | Dihard-cap juga di sisi klien (voice-recorder.tsx). Nilai di sini adalah
    | penjaga terakhir bila request dibuat langsung.
    |
    */

    'recording' => [
        'max_duration' => (int) env('VOICE_MAX_DURATION', 30),
        'max_size_kb' => (int) env('VOICE_MAX_SIZE_KB', 12288),
        'mimes' => 'webm,mp4,m4a,ogg,wav,mp3,mpeg,mpga,flac,aac',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ambang Cocok Santri
    |--------------------------------------------------------------------------
    | auto_threshold      -> skor >= nilai ini: dianggap pasti, isi otomatis
    | candidate_threshold -> skor >= nilai ini: tampilkan daftar kandidat
    | Di bawahnya kandidat terbaik tetap ditampilkan, tetapi diberi label ragu.
    |
    | Di-tuned untuk data produksi: kolom `nama_panggilan` nyaris kosong
    | (1 dari 5.476 baris), sehingga strategi substring-token, soundex, dan
    | Levenshtein adalah jalur utama untuk mendukung nama panggilan.
    |
    */

    'matching' => [
        'auto_threshold' => (float) env('VOICE_AUTO_THRESHOLD', 0.75),
        'candidate_threshold' => (float) env('VOICE_CANDIDATE_THRESHOLD', 0.40),
        'max_candidates' => (int) env('VOICE_MAX_CANDIDATES', 8),
        'min_margin' => (float) env('VOICE_MIN_MARGIN', 0.05),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Indeks Nama
    |--------------------------------------------------------------------------
    | Daftar (id, nama, nama_panggilan, asrama_id) di-cache agar fuzzy scoring
    | tidak menembak database tiap rekaman, dan tidak pernah dikirim ke browser
    | (halaman `pelanggaran` saat ini mengirim 5.476 baris sebagai JSON).
    |
    */

    'cache' => [
        'key' => 'voice:santri-index',
        'ttl' => (int) env('VOICE_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit
    |--------------------------------------------------------------------------
    */

    'throttle' => [
        'transcribe' => env('VOICE_THROTTLE_TRANSCRIBE', '20,1'),
        'search' => env('VOICE_THROTTLE_SEARCH', '60,1'),
    ],

];
