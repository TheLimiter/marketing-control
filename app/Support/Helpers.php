<?php
if (!function_exists('rupiah')) {
    function rupiah($angka){ return 'Rp '.number_format((float)$angka, 0, ',', '.'); }
}
