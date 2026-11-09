<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kendaraan extends Model
{
    use HasFactory;

    protected $table = 'kendaraans';

    protected $primaryKey = 'id_kendaraan';

    protected $fillable = [
        'nama_kendaraan',
        'jenis_kendaraan',
        'jenis_bbm',
        'efisiensi_km_per_liter',
        'sumber_efisiensi',
    ];

    public function pegawais()
    {
        return $this->hasMany(Pegawai::class, 'kendaraan_id', 'id_kendaraan');
    }
}
