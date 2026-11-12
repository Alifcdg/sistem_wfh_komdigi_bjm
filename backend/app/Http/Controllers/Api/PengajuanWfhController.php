<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengajuanWfh;
use App\Models\VerifikasiWfh;
use App\Models\PelaksanaanWfh;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengajuanWfhController extends Controller
{
    // Mengajukan WFH
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'nullable|string',
        ]);

        $pegawaiId = $request->user()->pegawai_id;

        if (!$pegawaiId) {
            return response()->json([
                'message' => 'Akun ini belum terhubung dengan data pegawai.',
            ], 403);
        }

        $hasil = DB::transaction(function () use ($pegawaiId, $validated) {

            // Mengunci data pegawai selama proses pengajuan
            $pegawai = Pegawai::where('id_pegawai', $pegawaiId)
                ->lockForUpdate()
                ->first();

            if (!$pegawai) {
                return [
                    'error' => 'Data pegawai tidak ditemukan.',
                    'status_code' => 404,
                ];
            }

            if ($pegawai->status_pegawai !== 'Aktif') {
                return [
                    'error' => 'Pegawai tidak aktif dan tidak dapat mengajukan WFH.',
                    'status_code' => 403,
                ];
            }

            $tanggalMulai = Carbon::parse($validated['tanggal_mulai']);
            $tanggalSelesai = Carbon::parse($validated['tanggal_selesai']);

            // Mengambil tanggal WFH selain hari Jumat
            $tanggalWfh = [];

            for (
                $tanggal = $tanggalMulai->copy();
                $tanggal->lte($tanggalSelesai);
                $tanggal->addDay()
            ) {
                if (!$tanggal->isFriday()) {
                    $tanggalWfh[] = $tanggal->toDateString();
                }
            }

            // Menolak pengajuan yang seluruh tanggalnya hari Jumat
            if (empty($tanggalWfh)) {
                return [
                    'error' => 'Pengajuan WFH tidak diperlukan karena seluruh tanggal yang dipilih merupakan jadwal WFH otomatis hari Jumat.',
                    'status_code' => 422,
                ];
            }

            // Memeriksa bentrok dengan pengajuan sebelumnya
            $adaBentrok = $this->periksaBentrok(
                $pegawai->id_pegawai,
                $tanggalWfh
            );

            if ($adaBentrok) {
                return [
                    'error' => 'Pengajuan WFH bertumpukan dengan pengajuan sebelumnya yang masih menunggu atau sudah disetujui.',
                    'status_code' => 422,
                ];
            }

            $pengajuan = PengajuanWfh::create([
                'pegawai_id' => $pegawai->id_pegawai,
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'keterangan' => $validated['keterangan'] ?? null,
                'status' => 'Menunggu',
            ]);

            return [
                'data' => $pengajuan,
            ];
        });

        if (isset($hasil['error'])) {
            return response()->json([
                'message' => $hasil['error'],
            ], $hasil['status_code']);
        }

        return response()->json([
            'message' => 'Pengajuan WFH berhasil dikirim.',
            'data' => $hasil['data'],
        ], 201);
    }

    // Menampilkan seluruh pengajuan WFH untuk Admin
    public function indexAdmin()
    {
        $pengajuan = PengajuanWfh::with('pegawai')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Seluruh pengajuan WFH berhasil diambil.',
            'data' => $pengajuan,
        ], 200);
    }

    // Menampilkan riwayat pengajuan WFH milik pegawai
    public function index(Request $request)
    {
        $pegawai = $request->user()->pegawai;

        if (!$pegawai) {
            return response()->json([
                'message' => 'Akun ini belum terhubung dengan data pegawai.',
            ], 403);
        }

        $pengajuan = PengajuanWfh::where(
            'pegawai_id',
            $pegawai->id_pegawai
        )
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Riwayat pengajuan WFH berhasil diambil.',
            'data' => $pengajuan,
        ], 200);
    }

    // Memeriksa bentrok tanggal WFH
    private function periksaBentrok(
        int $pegawaiId,
        array $tanggalWfh,
        ?int $pengajuanDikecualikan = null
    ): bool {
        $query = PengajuanWfh::where('pegawai_id', $pegawaiId)
            ->whereIn('status', ['Menunggu', 'Disetujui'])
            ->whereDate('tanggal_mulai', '<=', max($tanggalWfh))
            ->whereDate('tanggal_selesai', '>=', min($tanggalWfh));

        if ($pengajuanDikecualikan !== null) {
            $query->where('id_pengajuan', '!=', $pengajuanDikecualikan);
        }

        return $query->get()->contains(function ($pengajuan) use ($tanggalWfh) {
            $tanggalLama = Carbon::parse($pengajuan->tanggal_mulai);
            $tanggalAkhir = Carbon::parse($pengajuan->tanggal_selesai);

            while ($tanggalLama->lte($tanggalAkhir)) {
                if (
                    !$tanggalLama->isFriday() &&
                    in_array($tanggalLama->toDateString(), $tanggalWfh)
                ) {
                    return true;
                }

                $tanggalLama->addDay();
            }

            return false;
        });
    }

    // Verifikasi pengajuan WFH oleh Admin
    public function verifikasi(Request $request, int $id)
    {
        $validated = $request->validate([
            'status_verifikasi' => 'required|in:Disetujui,Ditolak',
            'catatan' => 'nullable|string',
        ]);

        $hasil = DB::transaction(function () use (
            $request,
            $validated,
            $id
        ) {
            // Mengambil pengajuan untuk mengetahui pemiliknya
            $pengajuanAwal = PengajuanWfh::find($id);

            if (!$pengajuanAwal) {
                return [
                    'error' => 'Pengajuan WFH tidak ditemukan.',
                    'status_code' => 404,
                ];
            }

            // Mengunci data pegawai agar proses pengajuan
            // dan persetujuan untuk pegawai yang sama tidak bertabrakan
            Pegawai::where('id_pegawai', $pengajuanAwal->pegawai_id)
                ->lockForUpdate()
                ->first();

            // Mengunci data pengajuan selama proses verifikasi
            $pengajuan = PengajuanWfh::where('id_pengajuan', $id)
                ->lockForUpdate()
                ->first();

            if (!$pengajuan || $pengajuan->status !== 'Menunggu') {
                return [
                    'error' => 'Pengajuan WFH sudah pernah diproses atau tidak ditemukan.',
                    'status_code' => 422,
                ];
            }

            // Menyiapkan rentang tanggal pengajuan
            $tanggalMulai = Carbon::parse($pengajuan->tanggal_mulai);
            $tanggalSelesai = Carbon::parse($pengajuan->tanggal_selesai);

            // Memeriksa kembali bentrok sebelum menyetujui
            if ($validated['status_verifikasi'] === 'Disetujui') {

                $tanggalWfh = [];

                for (
                    $tanggal = $tanggalMulai->copy();
                    $tanggal->lte($tanggalSelesai);
                    $tanggal->addDay()
                ) {
                    if (!$tanggal->isFriday()) {
                        $tanggalWfh[] = $tanggal->toDateString();
                    }
                }

                if (
                    !empty($tanggalWfh) &&
                    $this->periksaBentrok(
                        $pengajuan->pegawai_id,
                        $tanggalWfh,
                        $pengajuan->id_pengajuan
                    )
                ) {
                    return [
                        'error' => 'Pengajuan tidak dapat disetujui karena tanggal WFH bertumpukan dengan pengajuan lain yang masih menunggu atau sudah disetujui.',
                        'status_code' => 422,
                    ];
                }
            }

            // Menyimpan riwayat verifikasi
            VerifikasiWfh::create([
                'pengajuan_wfh_id' => $pengajuan->id_pengajuan,
                'user_id' => $request->user()->id_user,
                'status_verifikasi' => $validated['status_verifikasi'],
                'catatan' => $validated['catatan'] ?? null,
                'tanggal_verifikasi' => now(),
            ]);

            // Memperbarui status pengajuan
            $pengajuan->update([
                'status' => $validated['status_verifikasi'],
            ]);

            // Membuat pelaksanaan jika pengajuan disetujui
            if ($validated['status_verifikasi'] === 'Disetujui') {
                for (
                    $tanggal = $tanggalMulai->copy();
                    $tanggal->lte($tanggalSelesai);
                    $tanggal->addDay()
                ) {
                    // Jumat sudah dijadwalkan secara otomatis
                    if ($tanggal->isFriday()) {
                        continue;
                    }

                    // Mencegah duplikasi pelaksanaan WFH
                    PelaksanaanWfh::firstOrCreate(
                        [
                            'pegawai_id' => $pengajuan->pegawai_id,
                            'tanggal_wfh' => $tanggal->toDateString(),
                        ],
                        [
                            'sumber_jadwal' => 'Pengajuan',
                            'status_pelaksanaan' => 'Terlaksana',
                        ]
                    );
                }
            }

            return [
                'data' => $pengajuan->fresh(),
            ];
        });

        if (isset($hasil['error'])) {
            return response()->json([
                'message' => $hasil['error'],
            ], $hasil['status_code']);
        }

        return response()->json([
            'message' => 'Pengajuan WFH berhasil diverifikasi.',
            'data' => $hasil['data'],
        ], 200);
    }
}
