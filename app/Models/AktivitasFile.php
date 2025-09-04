<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AktivitasFile extends Model
{
    protected $fillable = ['aktivitas_id','path','original_name','size','mime'];

    public function aktivitas()
    {
        return $this->belongsTo(AktivitasProspek::class, 'aktivitas_id');
    }

    // URL publik
    public function url(): string
    {
        return asset('storage/'.$this->path);
    }
}
