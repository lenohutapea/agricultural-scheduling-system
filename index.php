<?php
require __DIR__ . '/includes/bootstrap.php';
wajib_login();

$pdo = db();
$hariIni = hari_ini();
$minggu = tambah_hari($hariIni, 7);

$ambil = function (string $where, array $params, string $urut = 'j.tanggal, j.id') use ($pdo): array {
    $st = $pdo->prepare(SQL_JADWAL . ' WHERE ' . $where . ' ORDER BY ' . $urut);
    $st->execute($params);
    return $st->fetchAll();
};

$terlambat = $ambil('j.selesai = 0 AND j.tanggal < ?', [$hariIni]);
$hariIniList = $ambil('j.tanggal = ?', [$hariIni], 'j.selesai, j.id');
$mendatang = $ambil('j.selesai = 0 AND j.tanggal > ? AND j.tanggal <= ?', [$hariIni, $minggu]);

$jumlahLahan = (int)$pdo->query('SELECT COUNT(*) FROM lahan')->fetchColumn();
$musimBerjalan = $pdo->query(
    "SELECT m.*, l.nama AS nama_lahan, t.nama AS nama_tanaman, t.umur_panen_hari
       FROM musim_tanam m
       JOIN lahan l ON l.id = m.lahan_id
       JOIN tanaman t ON t.id = m.tanaman_id
      WHERE m.status = 'Berjalan'
      ORDER BY m.perkiraan_panen"
)->fetchAll();
$belumHariIni = count(array_filter($hariIniList, fn($r) => !$r['selesai']));

awal_halaman('Beranda', 'index.php');
?>
<p class="lead"><?= e(tgl($hariIni, true)) ?></p>

<div class="angka">
  <a class="kotak <?= $terlambat ? 'awas' : '' ?>" href="jadwal.php?rentang=terlambat">
    <b><?= count($terlambat) ?></b><span>kegiatan terlambat</span>
  </a>
  <a class="kotak" href="jadwal.php?rentang=hari_ini">
    <b><?= $belumHariIni ?></b><span>belum selesai hari ini</span>
  </a>
  <a class="kotak" href="musim.php">
    <b><?= count($musimBerjalan) ?></b><span>musim tanam berjalan</span>
  </a>
  <a class="kotak" href="lahan.php">
    <b><?= $jumlahLahan ?></b><span>lahan</span>
  </a>
</div>

<?php if ($jumlahLahan === 0): ?>
  <div class="kartu mulai">
    <h2>Mulai dari sini</h2>
    <ol>
      <li><a href="lahan.php">Tambahkan lahan</a> yang Anda kelola.</li>
      <li>Periksa <a href="tanaman.php">pola kegiatan tanaman</a> dan sesuaikan harinya.</li>
      <li><a href="musim.php">Buat musim tanam</a>; jadwal penyiraman, pemupukan, sampai panen dibuat otomatis.</li>
    </ol>
  </div>
<?php endif; ?>

<div class="dua-kolom">
  <section>
    <?php if ($terlambat): ?>
      <h2 class="h-telat">Terlambat</h2>
      <?php daftar_tugas($terlambat, 'index.php'); ?>
    <?php endif; ?>

    <h2>Hari ini</h2>
    <?php daftar_tugas($hariIniList, 'index.php', ['tanggal' => false, 'kosong' => 'Tidak ada kegiatan hari ini.']); ?>

    <h2>7 hari ke depan</h2>
    <?php daftar_tugas($mendatang, 'index.php', ['kosong' => 'Tidak ada kegiatan dalam 7 hari ke depan.']); ?>
  </section>

  <aside>
    <h2>Musim tanam berjalan</h2>
    <?php if (!$musimBerjalan): ?>
      <p class="kosong">Belum ada. <a href="musim.php">Buat musim tanam</a>.</p>
    <?php endif; ?>
    <?php foreach ($musimBerjalan as $m):
        $p = progres_musim($m);
        $sisa = selisih_hari($hariIni, $m['perkiraan_panen']); ?>
      <a class="kartu musim-mini" href="musim_detail.php?id=<?= (int)$m['id'] ?>">
        <strong><?= e($m['nama_tanaman']) ?></strong> · <?= e($m['nama_lahan']) ?>
        <div class="bar" role="progressbar" aria-valuenow="<?= $p ?>" aria-valuemin="0" aria-valuemax="100"><i style="width:<?= $p ?>%"></i></div>
        <small>
          <?= $sisa > 0 ? 'Panen sekitar ' . $sisa . ' hari lagi (' . e(tgl($m['perkiraan_panen'])) . ')'
                        : ($sisa === 0 ? 'Perkiraan panen hari ini' : 'Sudah lewat perkiraan panen ' . (-$sisa) . ' hari') ?>
        </small>
      </a>
    <?php endforeach; ?>
  </aside>
</div>
<?php akhir_halaman();
