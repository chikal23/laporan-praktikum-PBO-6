<?php
// Jalankan: php pengujian.php   (atau buka di browser)
require_once __DIR__ . '/DataMahasiswa.php';

function uji(string $no, string $skenario, callable $fn, string $harapan): void {
    try {
        $hasil = $fn();
    } catch (Throwable $e) {
        $hasil = 'Ditolak: ' . $e->getMessage();
    }
    $status = (strpos($hasil, $harapan) !== false) ? 'SESUAI' : 'TIDAK SESUAI';
    echo "$no | $skenario | harapan: $harapan | hasil: $hasil | $status" . PHP_EOL;
}

if (PHP_SAPI !== 'cli') echo '<pre>';

uji('01', 'Reguler UKT 5.000.000', fn() =>
    'Biaya ' . (new MahasiswaReguler('1','A','IF',1,5000000))->hitungBiaya(), 'Biaya 5000000');
uji('02', 'Beasiswa 5.000.000 potongan 60%', fn() =>
    'Biaya ' . (new MahasiswaBeasiswa('2','B','IF',1,5000000,60))->hitungBiaya(), 'Biaya 2000000');
uji('03', 'NIM kosong', fn() => new MahasiswaReguler('','A','IF',1,1), 'Identitas wajib diisi');
uji('04a', 'Semester 0', fn() => new MahasiswaReguler('1','A','IF',0,1), 'Semester harus 1–14');
uji('04b', 'Semester 15', fn() => new MahasiswaReguler('1','A','IF',15,1), 'Semester harus 1–14');
uji('05', 'UKT -100', fn() => new MahasiswaReguler('1','A','IF',1,-100), 'UKT tidak boleh negatif');
uji('06', 'Potongan 120%', fn() => new MahasiswaBeasiswa('1','A','IF',1,1000,120), 'UKT/potongan tidak valid');

$repo = new DataMahasiswa();
$repo->tambah(new MahasiswaReguler('100','Andi','IF',1,5000000));
uji('07', 'NIM duplikat', fn() =>
    $repo->tambah(new MahasiswaReguler('100','Lain','IF',1,1)) ? 'Diterima' : 'Ditolak: repository',
    'Ditolak');
uji('08', 'Edit Reguler -> Beasiswa NIM sama', function () use ($repo) {
    $ok = $repo->ubah('100', new MahasiswaBeasiswa('100','Andi','IF',1,5000000,60));
    return ($ok && count($repo->semua()) === 1 && $repo->cari('100')->getJenis() === 'Beasiswa')
        ? 'Jenis berubah, 1 record' : 'Gagal';
}, 'Jenis berubah, 1 record');
uji('09', 'Edit dengan NIM berbeda', fn() =>
    $repo->ubah('100', new MahasiswaReguler('999','X','IF',1,1)) ? 'Diterima' : 'Ditolak: repository',
    'Ditolak');
uji('10', 'Hapus satu record', function () use ($repo) {
    $repo->tambah(new MahasiswaReguler('200','Budi','IF',1,1));
    $repo->hapus('100');
    return (count($repo->semua()) === 1 && $repo->cari('200') !== null)
        ? 'Record lain tersedia' : 'Gagal';
}, 'Record lain tersedia');
echo '11 | Refresh browser selama Session aktif | uji manual lewat browser (index.php)' . PHP_EOL;
uji('12', 'Akses $m->nim langsung', function () {
    $m = new MahasiswaReguler('1','A','IF',1,1);
    return $m->nim;
}, 'Ditolak');