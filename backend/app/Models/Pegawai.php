<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pegawai extends Model
{
    use HasFactory;

    protected $table = 'pegawais';

    protected $primaryKey = 'id_pegawai';

    protected $fillable = [
        'kendaraan_id',
        'nip',
        'nama',
        'jabatan',
        'unit_kerja',
        'alamat_rumah',
        'jarak_kantor_km',
        'status_pegawai',
    ];

    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class, 'kendaraan_id', 'id_kendaraan');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'pegawai_id', 'id_pegawai');
    }

    public function pengajuanWfhs()
    {
        return $this->hasMany(PengajuanWfh::class, 'pegawai_id', 'id_pegawai');
    }

    public function pelaksanaanWfhs()
    {
        return $this->hasMany(PelaksanaanWfh::class, 'pegawai_id', 'id_pegawai');
    }
}
