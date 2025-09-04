<?php

namespace App\Models\Concerns;

trait TracksUser
{
    protected static function bootTracksUser(): void
    {
        static::creating(function ($m) {
            if (auth()->check()) {
                if (empty($m->created_by)) $m->created_by = auth()->id();
                $m->updated_by = auth()->id();
            }
        });

        static::saving(function ($m) {
            if (auth()->check()) {
                $m->updated_by = auth()->id();
            }
        });
    }
}
