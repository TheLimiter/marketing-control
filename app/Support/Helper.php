<?php

if (! function_exists('rupiah')) {
    function rupiah($angka, bool $withRp = true): string {
        $n = (float) ($angka ?? 0);
        $s = number_format($n, 0, ',', '.');
        return $withRp ? 'Rp ' . $s : $s;
    }

    if (! function_exists('date_id')) {
    function date_id($date, $format = 'd/m/Y'): string {
        if (! $date) return '—';
        return \Illuminate\Support\Carbon::parse($date)->translatedFormat($format);
    }

    }}
