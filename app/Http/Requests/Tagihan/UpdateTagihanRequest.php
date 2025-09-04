<?php

namespace App\Http\Requests\Tagihan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTagihanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nomor'             => ['nullable','string','max:100'],
            'tanggal_tagihan'   => ['required','date'],
            'jatuh_tempo'       => ['required','date','after_or_equal:tanggal_tagihan'],
            'total'             => ['required','integer','min:0'],
            'terbayar'          => ['required','integer','min:0','lte:total'],
            'status'            => ['nullable','in:draft,open,paid,overdue'],
            'catatan'           => ['nullable','string','max:500'],
        ];
    }
}
