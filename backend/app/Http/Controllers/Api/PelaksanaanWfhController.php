<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PelaksanaanWfh;
use App\Services\PerhitunganWfhService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PelaksanaanWfhController extends Controller
{
    protected PerhitunganWfhService $perhitunganService;

    public function __construct(PerhitunganWfhService $perhitunganService)
    {
        $this->perhitunganService = $perhitunganService;
    }

    // Menampilkan seluruh data pelaksanaan WFH untuk Admin
    public function indexAdmin()
    {
        $pelaksanaan = PelaksanaanWfh::with('pegawai')
            ->latest('tanggal_wfh')
            ->get();

        return response()->json([
            'message' => 'Seluruh data pelaksanaan WFH berhasil diambil.',
            'data' => $pelaksanaan,
        ], 200);
    }

    // Menampilkan data pelaksanaan WFH milik Pegawai yang sedang login
    public function indexPegawai()
    {
        $pegawai = Auth::user()->pegawai;

        if (!$pegawai) {
            return response()->json([
                'message' => 'Data pegawai tidak ditemukan.',
            ], 404);
        }

        $pelaksanaan = PelaksanaanWfh::where(
            'pegawai_id',
            $pegawai->id_pegawai
        )
            ->latest('tanggal_wfh')
            ->get();

        return response()->json([
            'message' => 'Data pelaksanaan WFH berhasil diambil.',
            'data' => $pelaksanaan,
        ], 200);
    }

    // Konfirmasi realisasi WFH oleh Pegawai
    public function konfirmasi(Request $request, int $id)
    {
        $validated = $request->validate([
            'status_pelaksanaan' => [
                'required',
                'in:Terlaksana,Tidak Terlaksana',
            ],
        ]);

        $user = $request->user();
        $pegawai = $user->pegawai;

        if (!$pegawai) {
            return response()->json([
                'message' => 'Data pegawai tidak ditemukan.',
            ], 404);
        }

        $hasil = DB::transaction(function () use (
            $id,
            $pegawai,
            $user,
            $validated
        ) {
            $data = PelaksanaanWfh::where(
                'id_pelaksanaan',
                $id
            )
                ->where('pegawai_id', $pegawai->id_pegawai)
                ->lockForUpdate()
                ->first();

            if (!$data) {
                return [
                    'error' => 'Data pelaksanaan WFH tidak ditemukan.',
                    'status' => 404,
                ];
            }

            if ($data->status_pelaksanaan !== 'Terjadwal') {
                return [
                    'error' => 'Jadwal WFH ini sudah dikonfirmasi sebelumnya.',
                    'status' => 422,
                ];
            }

            // Konfirmasi hanya dapat dilakukan setelah tanggal WFH berakhir.
            $mulaiKonfirmasi = $data->tanggal_wfh
                ->copy()
                ->addDay()
                ->startOfDay();

            if (now()->lessThan($mulaiKonfirmasi)) {
                return [
                    'error' => 'Konfirmasi hanya dapat dilakukan setelah tanggal WFH berakhir.',
                    'status' => 422,
                ];
            }

            // Batas akhir konfirmasi adalah pukul 23.59 pada hari berikutnya.
            $batasKonfirmasi = $data->tanggal_wfh
                ->copy()
                ->addDay()
                ->endOfDay();

            if (now()->greaterThan($batasKonfirmasi)) {
                return [
                    'error' => 'Batas waktu konfirmasi WFH telah berakhir.',
                    'status' => 422,
                ];
            }

            $snapshot = [];

            // Snapshot hanya dihitung jika WFH benar-benar terlaksana.
            if ($validated['status_pelaksanaan'] === 'Terlaksana') {
                $pegawaiData = $pegawai->fresh('kendaraan');
                $kendaraan = $pegawaiData?->kendaraan;

                $jarakKantor = (float) ($pegawaiData?->jarak_kantor_km ?? 0);
                $efisiensi = (float) ($kendaraan?->efisiensi_km_per_liter ?? 0);
                $jenisBbm = $kendaraan?->jenis_bbm;

                $faktorEmisi = $jenisBbm
                    ? $this->perhitunganService->ambilFaktorEmisi($jenisBbm)
                    : null;

                // Simpan snapshot hanya jika seluruh data perhitungan lengkap.
                if (
                    $jarakKantor > 0 &&
                    $efisiensi > 0 &&
                    $faktorEmisi !== null
                ) {
                    $jarakPulangPergi = $jarakKantor * 2;

                    $bbmHemat = $this->perhitunganService
                        ->hitungPenghematanBbm(
                            $jarakKantor,
                            $efisiensi,
                            1
                        );

                    $snapshot = [
                        'jarak_pp_km' => $jarakPulangPergi,
                        'efisiensi_km_per_liter' => $efisiensi,
                        'estimasi_bbm_liter' => $bbmHemat,
                        'faktor_emisi_kg_per_liter' => $faktorEmisi,
                    ];
                }
            }

            $data->update(array_merge([
                'status_pelaksanaan' => $validated['status_pelaksanaan'],
                'tanggal_konfirmasi' => now(),
                'dikonfirmasi_oleh' => $user->id_user,
            ], $snapshot));

            return [
                'data' => $data->fresh(),
                'status' => 200,
            ];
        });

        if (isset($hasil['error'])) {
            return response()->json([
                'message' => $hasil['error'],
            ], $hasil['status']);
        }

        return response()->json([
            'message' => 'Konfirmasi pelaksanaan WFH berhasil disimpan.',
            'data' => $hasil['data'],
        ], 200);
    }
}
