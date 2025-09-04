<?php

namespace App\Http\Requests\PenggunaanModul;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDatesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'mulai_tanggal' => ['nullable','date'],
            'akhir_tanggal' => ['nullable','date','after_or_equal:mulai_tanggal'],
        ];
    }
}
