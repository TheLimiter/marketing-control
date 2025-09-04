<?php

return [
    // kalau true: MOU & TTD wajib sebelum jadi klien
    // kalau false: boleh langsung jadikan klien
    'require_mou_ttd' => env('REQUIRE_MOU_TTD', false),
];
