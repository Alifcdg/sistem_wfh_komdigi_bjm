<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pegawai;
use App\Models\PelaksanaanWfh;
use Carbon\Carbon;

class GenerateFridayWfh extends Command
{
    protected $signature = 'wfh:generate-friday {--date=}';

    protected $description = 'Membuat jadwal WFH otomatis setiap hari Jumat untuk seluruh pegawai aktif';

    public function handle()
    {
        // Mengambil tanggal dari opsi atau menggunakan tanggal hari ini
        try {
            $tanggal = $this->option('date')
                ? Carbon::parse($this->option('date'))
                : Carbon::today();
        } catch (\Exception $e) {
            $this->error('Format tanggal tidak valid. Gunakan format YYYY-MM-DD.');

            return self::FAILURE;
        }

        // Memastikan tanggal yang diproses adalah hari Jumat
        if (!$tanggal->isFriday()) {
            $this->error('Tanggal yang dipilih bukan hari Jumat.');

            return self::FAILURE;
        }

        $jumlahDibuat = 0;

        // Mengambil seluruh pegawai aktif
        Pegawai::where('status_pegawai', 'Aktif')
            ->each(function ($pegawai) use ($tanggal, &$jumlahDibuat) {

                $pelaksanaan = PelaksanaanWfh::firstOrCreate(
                    [
                        'pegawai_id' => $pegawai->id_pegawai,
                        'tanggal_wfh' => $tanggal->toDateString(),
                    ],
                    [
                        'sumber_jadwal' => 'Otomatis',
                        'status_pelaksanaan' => 'Terlaksana',
                        'catatan' => 'Jadwal WFH otomatis setiap hari Jumat.',
                    ]
                );

                if ($pelaksanaan->wasRecentlyCreated) {
                    $jumlahDibuat++;
                }
            });

        $this->info(
            "Berhasil membuat {$jumlahDibuat} jadwal WFH otomatis untuk tanggal {$tanggal->toDateString()}."
        );

        return self::SUCCESS;
    }
}
