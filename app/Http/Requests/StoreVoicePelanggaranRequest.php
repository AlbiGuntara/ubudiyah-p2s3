<?php

namespace App\Http\Requests;

use App\Rules\AudioRecording;
use Illuminate\Foundation\Http\FormRequest;

class StoreVoicePelanggaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create_pelanggaran') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'audio' => [
                'required',
                'file',
                new AudioRecording,
                'max:'.config('voice.recording.max_size_kb'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'audio.required' => 'Rekaman suara tidak terkirim. Coba ulangi merekam.',
            'audio.max' => 'Rekaman terlalu besar. Batasnya '.round(config('voice.recording.max_size_kb') / 1024).' MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['audio' => 'rekaman suara'];
    }
}
