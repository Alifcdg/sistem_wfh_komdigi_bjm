<?php

namespace App\Services;

use App\Models\PelaksanaanWfh;
use Illuminate\Database\Eloquent\Collection;

class MonitoringWfhService
{
    protected PerhitunganWfhService $perhitunganService;

    public function __construct(
        PerhitunganWfhService $perhitunganService
    ) {
        $this->perhitunganService = $perhitunganService;
    }

    public function ambilDataWfh(
        string $tanggalMulai,
        string $tanggalSelesai,
        ?int $pegawaiId = null
    ) {
        $query = PelaksanaanWfh::with([
            'pegawai.kendaraan'
        ])
        ->whereBetween('tanggal_wfh', [
            $tanggalMulai,
            $tanggalSelesai
        ])
        ->where('status_pelaksanaan', 'Terlaksana');

        if ($pegawaiId !== null) {
            $query->where('pegawai_id', $pegawaiId);
        }

        return $query->get();
    }

    public function hitungRingkasan(Collection $dataWfh): array
    {
        $totalHariWfh = $dataWfh->count();
        $totalBbmHemat = 0;
        $totalEmisiBerkurang = 0;
        $dataTidakLengkap = 0;

        foreach ($dataWfh as $pelaksanaan) {
            $pegawai = $pelaksanaan->pegawai;
            $kendaraan = $pegawai?->kendaraan;

            // Periksa kelengkapan data perhitungan
            if (
                !$pegawai ||
                !$kendaraan ||
                !$pegawai->jarak_kantor_km ||
                !$kendaraan->efisiensi_km_per_liter ||
                !$kendaraan->jenis_bbm ||
                $pegawai->jarak_kantor_km <= 0 ||
                $kendaraan->efisiensi_km_per_liter <= 0
            ) {
                $dataTidakLengkap++;
                continue;
            }

            // Hitung penghematan BBM
            $bbmHemat = $this->perhitunganService
                ->hitungPenghematanBbm(
                    (float) $pegawai->jarak_kantor_km,
                    (float) $kendaraan->efisiensi_km_per_liter,
                    1
                );

            // Hitung pengurangan emisi
            $emisiBerkurang = $this->perhitunganService
                ->hitungPenguranganEmisi(
                    $bbmHemat,
                    $kendaraan->jenis_bbm
                );

            $totalBbmHemat += $bbmHemat;
            $totalEmisiBerkurang += $emisiBerkurang;
        }

        return [
            'total_hari_wfh' => $totalHariWfh,
            'total_penghematan_bbm_liter' => round($totalBbmHemat, 3),
            'total_pengurangan_emisi_kg' => round($totalEmisiBerkurang, 3),
            'jumlah_data_tidak_lengkap' => $dataTidakLengkap,
        ];
    }
}
