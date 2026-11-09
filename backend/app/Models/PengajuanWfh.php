<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengajuanWfh extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_wfhs';

    protected $primaryKey = 'id_pengajuan';

    protected $fillable = [
        'pegawai_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id', 'id_pegawai');
    }

    public function verifikasiWfhs()
    {
        return $this->hasMany(VerifikasiWfh::class, 'pengajuan_wfh_id', 'id_pengajuan');
    }
}
