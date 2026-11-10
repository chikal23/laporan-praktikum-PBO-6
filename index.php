<?php
require_once __DIR__ . '/DataMahasiswa.php'; // Definisi class sebelum session_start
session_start();

function aman(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$repo  = new DataMahasiswa($_SESSION['mahasiswa'] ?? []);
$error = '';
$pesan = (string)($_SESSION['pesan'] ?? '');
unset($_SESSION['pesan']);
$old   = [];
$mode  = 'tambah';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string)($_POST['aksi'] ?? 'tambah');
    try {
        if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new InvalidArgumentException('Token keamanan tidak valid');
        }

        // ---- HAPUS ----
        if ($aksi === 'hapus') {
            $nimHapus = trim((string)($_POST['nim'] ?? ''));
            if (!$repo->hapus($nimHapus)) {
                throw new InvalidArgumentException('NIM tidak ditemukan');
            }
            $_SESSION['mahasiswa'] = $repo->semua();
            $_SESSION['pesan'] = 'Data berhasil dihapus';
            header('Location: index.php'); exit;
        }

        // ---- TAMBAH / UBAH ----
        $nim      = trim((string)($_POST['nim'] ?? ''));
        $nama     = trim((string)($_POST['nama'] ?? ''));
        $prodi    = trim((string)($_POST['prodi'] ?? ''));
        $semester = filter_var($_POST['semester'] ?? null, FILTER_VALIDATE_INT);
        $ukt      = filter_var($_POST['ukt'] ?? null, FILTER_VALIDATE_INT);

        if ($semester === false || $ukt === false) {
            throw new InvalidArgumentException('Semester dan UKT harus bilangan bulat');
        }

        $jenis = (string)($_POST['jenis'] ?? '');
        if ($jenis === 'reguler') {
            $m = new MahasiswaReguler($nim, $nama, $prodi, $semester, $ukt);
        } elseif ($jenis === 'beasiswa') {
            $potongan = filter_var($_POST['potongan'] ?? null, FILTER_VALIDATE_INT);
            if ($potongan === false) throw new InvalidArgumentException('Potongan tidak valid');
            $m = new MahasiswaBeasiswa($nim, $nama, $prodi, $semester, $ukt, $potongan);
        } else {
            throw new InvalidArgumentException('Jenis mahasiswa tidak dikenal');
        }

        $ok = $aksi === 'ubah'
            ? $repo->ubah((string)($_POST['nim_lama'] ?? ''), $m)
            : $repo->tambah($m);
        if (!$ok) throw new InvalidArgumentException('NIM duplikat atau data edit tidak cocok');

        $_SESSION['mahasiswa'] = $repo->semua();
        $_SESSION['pesan'] = $aksi === 'ubah' ? 'Data berhasil diubah' : 'Data berhasil ditambahkan';
        header('Location: index.php'); exit;
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
        $old   = $_POST;
        $mode  = $aksi === 'ubah' ? 'ubah' : 'tambah';
    }
} elseif (isset($_GET['edit'])) {
    $m = $repo->cari((string)$_GET['edit']);
    if ($m !== null) {
        $mode = 'ubah';
        $old = [
            'nim' => $m->getNim(), 'nama' => $m->getNama(),
            'prodi' => $m->getProdi(), 'semester' => $m->getSemester(),
            'nim_lama' => $m->getNim(),
        ];
        if ($m instanceof MahasiswaBeasiswa) {
            $old['jenis'] = 'beasiswa';
            $old['ukt'] = $m->getUkt();
            $old['potongan'] = $m->getPotonganPersen();
        } elseif ($m instanceof MahasiswaReguler) {
            $old['jenis'] = 'reguler';
            $old['ukt'] = $m->getUkt();
        }
    }
}

// ---- CARI ----
$q = trim((string)($_GET['q'] ?? ''));
$daftar = array_filter($repo->semua(), function (Mahasiswa $m) use ($q): bool {
    return $q === ''
        || stripos($m->getNim(), $q) !== false
        || stripos($m->getNama(), $q) !== false;
});

$jenisAktif = (string)($old['jenis'] ?? 'reguler');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CRUD Mahasiswa — Inheritance</title>
<style>
  body{font-family:Arial,sans-serif;max-width:960px;margin:24px auto;padding:0 12px;color:#222}
  h1{font-size:1.4rem} table{border-collapse:collapse;width:100%;margin-top:12px}
  th,td{border:1px solid #bbb;padding:6px 8px;text-align:left} th{background:#f0f0f0}
  td.num{text-align:right} .error{background:#fde8e8;color:#a00;padding:8px;border-radius:4px;margin:8px 0}
  .ok{background:#e6f6e6;color:#060;padding:8px;border-radius:4px;margin:8px 0}
  form.f label{display:block;margin:8px 0 2px} form.f input,form.f select{padding:6px;width:100%;max-width:340px;box-sizing:border-box}
  button,.btn{padding:6px 12px;cursor:pointer} .inline{display:inline}
</style>
</head>
<body>
<h1>CRUD Data Mahasiswa (Inheritance)</h1>

<?php if ($error !== ''): ?><div class="error"><?= aman($error) ?></div><?php endif; ?>
<?php if ($pesan !== ''): ?><div class="ok"><?= aman($pesan) ?></div><?php endif; ?>

<h2><?= $mode === 'ubah' ? 'Edit Mahasiswa' : 'Tambah Mahasiswa' ?></h2>
<form class="f" method="post" action="index.php">
  <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
  <input type="hidden" name="aksi" value="<?= aman($mode) ?>">
  <?php if ($mode === 'ubah'): ?>
    <input type="hidden" name="nim_lama" value="<?= aman($old['nim_lama'] ?? $old['nim'] ?? '') ?>">
  <?php endif; ?>

  <label for="nim">NIM</label>
  <input id="nim" name="nim" value="<?= aman($old['nim'] ?? '') ?>" <?= $mode === 'ubah' ? 'readonly' : '' ?> required>

  <label for="nama">Nama</label>
  <input id="nama" name="nama" value="<?= aman($old['nama'] ?? '') ?>" required>

  <label for="prodi">Prodi</label>
  <input id="prodi" name="prodi" value="<?= aman($old['prodi'] ?? '') ?>" required>

  <label for="semester">Semester (1–14)</label>
  <input id="semester" name="semester" type="number" min="1" max="14" value="<?= aman($old['semester'] ?? '') ?>" required>

  <label for="jenis">Jenis Mahasiswa</label>
  <select id="jenis" name="jenis">
    <option value="reguler"  <?= $jenisAktif === 'reguler'  ? 'selected' : '' ?>>Reguler</option>
    <option value="beasiswa" <?= $jenisAktif === 'beasiswa' ? 'selected' : '' ?>>Beasiswa</option>
  </select>

  <label for="ukt">UKT (Rp)</label>
  <input id="ukt" name="ukt" type="number" min="0" value="<?= aman($old['ukt'] ?? '') ?>" required>

  <div id="boxPotongan" style="display:none">
    <label for="potongan">Potongan Beasiswa (%) 0–100</label>
    <input id="potongan" name="potongan" type="number" min="0" max="100" value="<?= aman($old['potongan'] ?? '') ?>">
  </div>

  <p>
    <button type="submit"><?= $mode === 'ubah' ? 'Simpan Perubahan' : 'Tambah' ?></button>
    <?php if ($mode === 'ubah'): ?><a class="btn" href="index.php">Batal</a><?php endif; ?>
  </p>
</form>

<h2>Daftar Mahasiswa</h2>
<form method="get" action="index.php">
  <input name="q" placeholder="Cari NIM / nama" value="<?= aman($q) ?>">
  <button type="submit">Cari</button>
  <?php if ($q !== ''): ?><a href="index.php">Reset</a><?php endif; ?>
</form>

<table>
  <thead>
    <tr><th>NIM</th><th>Nama</th><th>Prodi</th><th>Smt</th><th>Jenis</th><th>Biaya Akhir (Rp)</th><th>Aksi</th></tr>
  </thead>
  <tbody>
  <?php foreach ($daftar as $m): ?>
    <tr>
      <td><?= aman($m->getNim()) ?></td>
      <td><?= aman($m->getNama()) ?></td>
      <td><?= aman($m->getProdi()) ?></td>
      <td><?= aman($m->getSemester()) ?></td>
      <td><?= aman($m->getJenis()) ?></td>
      <td class="num"><?= aman(number_format($m->hitungBiaya(), 0, ',', '.')) ?></td>
      <td>
        <a href="index.php?edit=<?= urlencode($m->getNim()) ?>">Edit</a>
        <form class="inline" method="post" action="index.php" onsubmit="return confirm('Hapus data ini?');">
          <input type="hidden" name="csrf" value="<?= aman($_SESSION['csrf']) ?>">
          <input type="hidden" name="aksi" value="hapus">
          <input type="hidden" name="nim" value="<?= aman($m->getNim()) ?>">
          <button type="submit">Hapus</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (count($daftar) === 0): ?>
    <tr><td colspan="7">Belum ada data.</td></tr>
  <?php endif; ?>
  </tbody>
</table>

<script>
  const jenis = document.getElementById('jenis');
  const box = document.getElementById('boxPotongan');
  const pot = document.getElementById('potongan');
  function tampil() {
    const b = jenis.value === 'beasiswa';
    box.style.display = b ? 'block' : 'none';
    pot.required = b;
  }
  jenis.addEventListener('change', tampil);
  tampil();
</script>
</body>
</html>
