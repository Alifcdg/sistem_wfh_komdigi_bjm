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
        'tanggal_wfh',
        'sumber_jadwal',
        'status_pelaksanaan',
        'jarak_pp_km',
        'efisiensi_km_per_liter',
        'estimasi_bbm_liter',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_wfh' => 'date',
            'jarak_pp_km' => 'decimal:2',
            'efisiensi_km_per_liter' => 'decimal:2',
            'estimasi_bbm_liter' => 'decimal:2',
        ];
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id', 'id_pegawai');
    }
}
