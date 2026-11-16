<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PelaksanaanWfh extends Model
{
    use HasFactory;

    protected $table = 'pelaksanaan_wfhs';

    protected $primaryKey = 'id_pelaksanaan';

    protected $fillable = [
        'pegawai_id',
        'dikonfirmasi_oleh',
        'tanggal_wfh',
        'sumber_jadwal',
        'status_pelaksanaan',
        'tanggal_konfirmasi',
        'jarak_pp_km',
        'efisiensi_km_per_liter',
        'estimasi_bbm_liter',
        'faktor_emisi_kg_per_liter',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_wfh' => 'date',
            'tanggal_konfirmasi' => 'datetime',
            'jarak_pp_km' => 'decimal:2',
            'efisiensi_km_per_liter' => 'decimal:2',
            'estimasi_bbm_liter' => 'decimal:3',
            'faktor_emisi_kg_per_liter' => 'decimal:3',
        ];
    }

    public function pegawai()
    {
        return $this->belongsTo(
            Pegawai::class,
            'pegawai_id',
            'id_pegawai'
        );
    }

    public function dikonfirmasiOleh()
    {
        return $this->belongsTo(
            User::class,
            'dikonfirmasi_oleh',
            'id_user'
        );
    }
}
