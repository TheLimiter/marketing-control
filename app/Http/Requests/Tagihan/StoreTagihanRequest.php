<?php

namespace App\Http\Requests\Tagihan;

use Illuminate\Foundation\Http\FormRequest;

class StoreTagihanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'master_sekolah_id' => ['required','integer','exists:master_sekolah,id'],
            'nomor'             => ['nullable','string','max:100'],
            'tanggal_tagihan'   => ['required','date'],
            'jatuh_tempo'       => ['required','date','after_or_equal:tanggal_tagihan'],
            'total'             => ['required','integer','min:0'],
            'terbayar'          => ['nullable','integer','min:0','lte:total'],
            'status'            => ['nullable','in:draft,open,paid,overdue'],
            'catatan'           => ['nullable','string','max:500'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $v = parent::validated($key, $default);
        $v['terbayar'] = (int)($v['terbayar'] ?? 0);
        return $v;
    }
}
