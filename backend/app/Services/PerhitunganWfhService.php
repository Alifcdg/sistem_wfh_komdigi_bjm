<?php

namespace App\Services;

class PerhitunganWfhService
{
    // Faktor emisi BBM (kg CO2 per liter)
    private const FAKTOR_EMISI = [
        'Pertalite' => 2.31,
        'Pertamax' => 2.31,
        'Pertamax Turbo' => 2.31,
        'Solar' => 2.68,
        'Dexlite' => 2.68,
        'Pertamina Dex' => 2.68,
    ];

    // Menghitung estimasi penghematan BBM
    public function hitungPenghematanBbm(
        float $jarakKantorKm,
        float $efisiensiKmPerLiter,
        int $jumlahWfh
    ): float {
        if ($efisiensiKmPerLiter <= 0) {
            return 0;
        }

        $jarakPulangPergi = $jarakKantorKm * 2;

        $bbmHemat = ($jarakPulangPergi * $jumlahWfh)
            / $efisiensiKmPerLiter;

        return $bbmHemat;
    }

    // Menghitung estimasi pengurangan emisi CO2
    public function hitungPenguranganEmisi(
        float $bbmHemat,
        string $jenisBbm
    ): float {
        $faktorEmisi = self::FAKTOR_EMISI[$jenisBbm] ?? null;

        if ($faktorEmisi === null) {
            return 0;
        }

        return $bbmHemat * $faktorEmisi;
    }
}
