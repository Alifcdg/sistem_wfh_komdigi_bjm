<?php

namespace App\Services;

use App\Models\PelaksanaanWfh;
use Illuminate\Database\Eloquent\Collection;

class MonitoringWfhService
{
    /**
     * Mengambil data WFH yang sudah terlaksana
     * berdasarkan rentang tanggal dan pegawai.
     */
    public function ambilDataWfh(
        string $tanggalMulai,
        string $tanggalSelesai,
        ?int $pegawaiId = null
    ): Collection {
        $query = PelaksanaanWfh::query()
            ->whereBetween('tanggal_wfh', [
                $tanggalMulai,
                $tanggalSelesai,
            ])
            ->where('status_pelaksanaan', 'Terlaksana');

        if ($pegawaiId !== null) {
            $query->where('pegawai_id', $pegawaiId);
        }

        return $query->get();
    }

    /**
     * Menghitung ringkasan efisiensi WFH
     * berdasarkan nilai snapshot saat konfirmasi.
     */
    public function hitungRingkasan(Collection $dataWfh): array
    {
        $totalHariWfh = $dataWfh->count();
        $totalBbmHemat = 0;
        $totalEmisiBerkurang = 0;
        $dataTidakLengkap = 0;

        foreach ($dataWfh as $pelaksanaan) {
            $jarakPulangPergi = $pelaksanaan->jarak_pp_km;
            $efisiensi = $pelaksanaan->efisiensi_km_per_liter;
            $bbmHemat = $pelaksanaan->estimasi_bbm_liter;
            $faktorEmisi = $pelaksanaan->faktor_emisi_kg_per_liter;

            // Periksa kelengkapan nilai snapshot.
            if (
                $jarakPulangPergi === null ||
                $efisiensi === null ||
                $bbmHemat === null ||
                $faktorEmisi === null ||
                (float) $jarakPulangPergi <= 0 ||
                (float) $efisiensi <= 0 ||
                (float) $bbmHemat <= 0 ||
                (float) $faktorEmisi <= 0
            ) {
                $dataTidakLengkap++;
                continue;
            }

            // Hitung pengurangan emisi berdasarkan snapshot.
            $emisiBerkurang = (float) $bbmHemat
                * (float) $faktorEmisi;

            $totalBbmHemat += (float) $bbmHemat;
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
