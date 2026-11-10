<?php
require_once __DIR__ . '/MahasiswaReguler.php';

$m = new MahasiswaReguler('23001', 'Andi', 'Informatika', 4, 5000000);

echo $m->getNim();   // BENAR: lewat method public
echo $m->nim;        // SALAH: langsung ke property private