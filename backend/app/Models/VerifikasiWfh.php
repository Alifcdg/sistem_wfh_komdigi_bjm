<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VerifikasiWfh extends Model
{
    use HasFactory;

    protected $table = 'verifikasi_wfhs';

    protected $primaryKey = 'id_verifikasi';

    protected $fillable = [
        'pengajuan_wfh_id',
        'user_id',
        'status_verifikasi',
        'catatan',
        'tanggal_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_verifikasi' => 'datetime',
        ];
    }

    public function pengajuanWfh()
    {
        return $this->belongsTo(PengajuanWfh::class, 'pengajuan_wfh_id', 'id_pengajuan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id_user');
    }
}
