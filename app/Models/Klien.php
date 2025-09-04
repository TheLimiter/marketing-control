<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Klien extends Model {
    use HasFactory; protected $table='klien';
    protected $fillable=['nama','alamat','no_hp','narahubung','jenjang','tanggal_mou','mou_file','catatan','status_ttd'];
    public function tagihan(){ return $this->hasMany(TagihanKlien::class,'klien_id'); }
    public function modul(){ return $this->belongsToMany(Modul::class,'penggunaan_modul')->withTimestamps()->withPivot(['tanggal_mulai','tanggal_akhir','tanggal_pelatihan_terakhir','catatan']); }
}
