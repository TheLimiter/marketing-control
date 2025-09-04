<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class TagihanExport implements FromView
{
    protected $tagihan;

    public function __construct($tagihan)
    {
        $this->tagihan = $tagihan;
    }

    public function view(): View
    {
        return view('tagihan.export_excel', ['tagihan' => $this->tagihan]);
    }
}
