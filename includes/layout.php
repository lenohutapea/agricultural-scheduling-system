<?php
// Kerangka halaman dan komponen tampilan yang dipakai ulang.

const MENU = [
    'index.php' => 'Beranda',
    'jadwal.php' => 'Jadwal',
    'kalender.php' => 'Kalender',
    'musim.php' => 'Musim Tanam',
    'lahan.php' => 'Lahan',
    'tanaman.php' => 'Tanaman',
    'akun.php' => 'Akun',
];

function awal_halaman(string $judul, string $aktif = ''): void
{
    $u = pengguna();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/style.css">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231f5130'/%3E%3Cpath d='M16 25V12M16 17c-4 0-7-3-7-7 4 0 7 3 7 7zm0-2c0-4 3-7 7-7 0 4-3 7-7 7z' stroke='%23e9f3df' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E">
</head>
<body>
<header class="atas">
  <a class="merek" href="index.php"><span class="logo" aria-hidden="true"></span><?= e(APP_NAME) ?></a>
  <?php if ($u): ?>
  <nav aria-label="Menu utama">
    <?php foreach (MENU as $href => $label): ?>
      <a href="<?= $href ?>"<?= $aktif === $href ? ' class="aktif" aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <form method="post" action="logout.php" class="keluar">
    <?= csrf_field() ?>
    <span><?= e($u['nama']) ?></span>
    <button type="submit" class="btn tipis">Keluar</button>
  </form>
  <?php endif; ?>
</header>
<main>
  <?php foreach (ambil_flash() as [$jenis, $pesan]): ?>
    <div class="pesan <?= e($jenis) ?>" role="status"><?= e($pesan) ?></div>
  <?php endforeach; ?>
  <h1><?= e($judul) ?></h1>
<?php
}

function akhir_halaman(): void
{
    ?>
</main>
<footer class="bawah"><?= e(APP_NAME) ?> · <?= date('Y') ?></footer>
</body>
</html>
<?php
}

function chip_jenis(string $jenis): string
{
    return '<span class="chip ' . e(kelas_jenis($jenis)) . '">' . e($jenis) . '</span>';
}

/**
 * Tampilkan daftar kegiatan.
 * $opsi: lahan (bool), tanggal (bool), edit (bool), kosong (string)
 */
function daftar_tugas(array $rows, string $kembali, array $opsi = []): void
{
    $tampilLahan = $opsi['lahan'] ?? true;
    $tampilTanggal = $opsi['tanggal'] ?? true;
    $bisaEdit = $opsi['edit'] ?? false;

    if (!$rows) {
        echo '<p class="kosong">' . e($opsi['kosong'] ?? 'Tidak ada kegiatan.') . '</p>';
        return;
    }
    $hariIni = hari_ini();
    echo '<ul class="tugas">';
    foreach ($rows as $r) {
        $selesai = (bool)$r['selesai'];
        $telat = !$selesai && $r['tanggal'] < $hariIni;
        $kelas = trim(($selesai ? 'selesai' : '') . ($telat ? ' telat' : ''));
        ?>
    <li class="<?= $kelas ?>">
      <form method="post" action="tugas_aksi.php">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="toggle">
        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <input type="hidden" name="kembali" value="<?= e($kembali) ?>">
        <button type="submit" class="centang" title="<?= $selesai ? 'Tandai belum selesai' : 'Tandai selesai' ?>"
                aria-label="<?= $selesai ? 'Tandai belum selesai' : 'Tandai selesai' ?>: <?= e($r['judul']) ?>"><?= $selesai ? '✓' : '' ?></button>
      </form>
      <div class="isi">
        <div class="judul"><?= chip_jenis($r['jenis']) ?> <strong><?= e($r['judul']) ?></strong></div>
        <div class="meta">
          <?php if ($tampilTanggal): ?>
            <span><?= e(tgl($r['tanggal'], true)) ?></span>
          <?php endif; ?>
          <?php if ($telat): ?>
            <span class="label-telat">terlambat <?= selisih_hari($r['tanggal'], $hariIni) ?> hari</span>
          <?php endif; ?>
          <?php if ($tampilLahan): ?>
            <span><?= e($r['nama_lahan']) ?><?= !empty($r['nama_tanaman']) ? ' · ' . e($r['nama_tanaman']) : '' ?></span>
          <?php endif; ?>
          <?php if ($r['hari_ke'] !== null): ?>
            <span><?= (int)$r['hari_ke'] ?> HST</span>
          <?php endif; ?>
          <?php if (!empty($r['keterangan'])): ?>
            <span><?= e($r['keterangan']) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($bisaEdit): ?>
      <div class="aksi">
        <form method="post" action="tugas_aksi.php" class="sebaris">
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="pindah">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <input type="hidden" name="kembali" value="<?= e($kembali) ?>">
          <input type="date" name="tanggal" value="<?= e($r['tanggal']) ?>" aria-label="Tanggal baru untuk <?= e($r['judul']) ?>" required>
          <button type="submit" class="btn kecil">Pindahkan</button>
        </form>
        <form method="post" action="tugas_aksi.php" onsubmit="return confirm('Hapus kegiatan ini?')">
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="hapus">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <input type="hidden" name="kembali" value="<?= e($kembali) ?>">
          <button type="submit" class="btn kecil bahaya">Hapus</button>
        </form>
      </div>
      <?php endif; ?>
    </li>
        <?php
    }
    echo '</ul>';
}

function pilihan(array $opsi, ?string $terpilih): string
{
    $html = '';
    foreach ($opsi as $nilai => $label) {
        $nilai = (string)$nilai;
        $html .= '<option value="' . e($nilai) . '"' . ($nilai === (string)$terpilih ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return $html;
}
