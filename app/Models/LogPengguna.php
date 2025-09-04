<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogPengguna extends Model
{
    protected $table = 'log_pengguna';
    protected $fillable = ['user_id','aktivitas','keterangan','ip_address','user_agent','route','method'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
