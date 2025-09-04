<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mou extends Model
{
    protected $table = 'mou';
    protected $fillable = ['master_sekolah_id','mou_path','ttd_status','ttd_signed_at','catatan'];

    public function master() { return $this->belongsTo(MasterSekolah::class,'master_sekolah_id'); }
}
