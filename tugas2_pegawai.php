<?php
declare(strict_types=1);

abstract class Pegawai {
    private int $id;
    private string $nama;

    public function __construct(int $id, string $nama) {
        if (trim($nama) === '') {
            throw new InvalidArgumentException('Nama wajib diisi');
        }
        $this->id = $id;
        $this->nama = trim($nama);
    }

    public function getId(): int { return $this->id; }
    public function getNama(): string { return $this->nama; }

    abstract public function hitungGaji(): int;
}

final class PegawaiTetap extends Pegawai {
    private int $gajiPokok;

    public function __construct(int $id, string $nama, int $gajiPokok) {
        parent::__construct($id, $nama);
        if ($gajiPokok < 0) throw new InvalidArgumentException('Gaji pokok tidak boleh negatif');
        $this->gajiPokok = $gajiPokok;
    }

    public function hitungGaji(): int { return $this->gajiPokok; }
}

final class PegawaiKontrak extends Pegawai {
    private int $upahPerJam;
    private int $jamKerja;

    public function __construct(int $id, string $nama, int $upahPerJam, int $jamKerja) {
        parent::__construct($id, $nama);
        if ($upahPerJam < 0 || $jamKerja < 0) {
            throw new InvalidArgumentException('Upah per jam dan jam kerja tidak boleh negatif');
        }
        $this->upahPerJam = $upahPerJam;
        $this->jamKerja = $jamKerja;
    }

    public function hitungGaji(): int { return $this->upahPerJam * $this->jamKerja; }
}

$daftar = [
    new PegawaiTetap(1, 'Budi', 6000000),
    new PegawaiKontrak(2, 'Citra', 50000, 120),
];
foreach ($daftar as $p) {
    echo $p->getId() . ' / ' . $p->getNama() . ' / Rp'
       . number_format($p->hitungGaji(), 0, ',', '.') . PHP_EOL;
}
// Output: 1 / Budi / Rp6.000.000  dan  2 / Citra / Rp6.000.000
