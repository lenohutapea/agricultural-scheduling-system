<?php
require __DIR__ . '/includes/bootstrap.php';
wajib_login();

$pdo = db();
$bulanParam = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $bulanParam) || !tanggal_valid($bulanParam . '-01')) {
    $bulanParam = date('Y-m');
}
$awal = $bulanParam . '-01';
$jumlahHari = (int)date('t', strtotime($awal));
$akhir = $bulanParam . '-' . str_pad((string)$jumlahHari, 2, '0', STR_PAD_LEFT);
$lahanId = (int)($_GET['lahan'] ?? 0);

$sql = SQL_JADWAL . ' WHERE j.tanggal BETWEEN ? AND ?' . ($lahanId ? ' AND j.lahan_id = ?' : '') . ' ORDER BY j.tanggal, j.selesai, j.id';
$st = $pdo->prepare($sql);
$st->execute($lahanId ? [$awal, $akhir, $lahanId] : [$awal, $akhir]);
$perTanggal = [];
foreach ($st->fetchAll() as $r) {
    $perTanggal[$r['tanggal']][] = $r;
}

$lahanList = $pdo->query('SELECT id, nama FROM lahan ORDER BY nama')->fetchAll();
$sebelum = date('Y-m', strtotime($awal . ' -1 month'));
$sesudah = date('Y-m', strtotime($awal . ' +1 month'));
$qLahan = $lahanId ? '&lahan=' . $lahanId : '';
$kosongDepan = (int)date('N', strtotime($awal)) - 1; // Senin = kolom pertama
$hariIni = hari_ini();
$MAKS = 3;

awal_halaman('Kalender ' . NAMA_BULAN[(int)substr($bulanParam, 5, 2)] . ' ' . substr($bulanParam, 0, 4), 'kalender.php');
?>
<div class="filter">
  <a class="btn tipis" href="kalender.php?bulan=<?= $sebelum . $qLahan ?>">← Bulan sebelumnya</a>
  <a class="btn tipis" href="kalender.php?bulan=<?= date('Y-m') . $qLahan ?>">Bulan ini</a>
  <a class="btn tipis" href="kalender.php?bulan=<?= $sesudah . $qLahan ?>">Bulan berikutnya →</a>
  <form method="get" class="sebaris">
    <input type="hidden" name="bulan" value="<?= e($bulanParam) ?>">
    <label>Lahan <select name="lahan" onchange="this.form.submit()"><?= pilihan(['0' => 'Semua lahan'] + array_column($lahanList, 'nama', 'id'), (string)$lahanId) ?></select></label>
    <noscript><button class="btn kecil">Terapkan</button></noscript>
  </form>
</div>

<div class="kalender-wrap">
<table class="kalender">
  <thead><tr><?php foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h): ?><th scope="col"><?= $h ?></th><?php endforeach; ?></tr></thead>
  <tbody>
  <tr>
  <?php
  $kolom = 0;
  for ($i = 0; $i < $kosongDepan; $i++, $kolom++) {
      echo '<td class="luar"></td>';
  }
  for ($d = 1; $d <= $jumlahHari; $d++, $kolom++) {
      if ($kolom > 0 && $kolom % 7 === 0) {
          echo "</tr>\n<tr>";
      }
      $tgl = $bulanParam . '-' . str_pad((string)$d, 2, '0', STR_PAD_LEFT);
      $isi = $perTanggal[$tgl] ?? [];
      $kelas = ($tgl === $hariIni ? 'hari-ini' : '');
      echo '<td class="' . $kelas . '"><a class="nomor" href="jadwal.php?tanggal=' . $tgl . '" aria-label="Jadwal ' . e(tgl($tgl, true)) . '">' . $d . '</a>';
      foreach (array_slice($isi, 0, $MAKS) as $r) {
          $telat = !$r['selesai'] && $tgl < $hariIni;
          echo '<div class="ev ' . e(kelas_jenis($r['jenis'])) . ($r['selesai'] ? ' selesai' : '') . ($telat ? ' telat' : '') . '" title="'
              . e($r['judul'] . ' · ' . $r['nama_lahan']) . '">' . e($r['judul']) . '</div>';
      }
      if (count($isi) > $MAKS) {
          echo '<a class="lagi" href="jadwal.php?tanggal=' . $tgl . '">+' . (count($isi) - $MAKS) . ' lainnya</a>';
      }
      echo '</td>';
  }
  while ($kolom % 7 !== 0) {
      echo '<td class="luar"></td>';
      $kolom++;
  }
  ?>
  </tr>
  </tbody>
</table>
</div>

<p class="legenda" aria-label="Keterangan warna">
  <?php foreach (JENIS_KEGIATAN as $j): ?><?= chip_jenis($j) ?> <?php endforeach; ?>
</p>
<?php akhir_halaman();
