<?php
require __DIR__ . '/includes/bootstrap.php';
wajib_login();

$pdo = db();
$hariIni = hari_ini();

$RENTANG = [
    'terlambat' => 'Terlambat',
    'hari_ini' => 'Hari ini',
    '7_hari' => '7 hari ke depan',
    '30_hari' => '30 hari ke depan',
    'semua' => 'Semua tanggal',
];
$STATUS = ['belum' => 'Belum selesai', 'selesai' => 'Sudah selesai', 'semua' => 'Semua status'];

$rentang = array_key_exists($_GET['rentang'] ?? '', $RENTANG) ? $_GET['rentang'] : '7_hari';
$status = array_key_exists($_GET['status'] ?? '', $STATUS) ? $_GET['status'] : 'belum';
$lahanId = (int)($_GET['lahan'] ?? 0);
$jenis = in_array($_GET['jenis'] ?? '', JENIS_KEGIATAN, true) ? $_GET['jenis'] : '';
$tanggal = (isset($_GET['tanggal']) && tanggal_valid($_GET['tanggal'])) ? $_GET['tanggal'] : '';

$where = [];
$params = [];
if ($tanggal !== '') {
    $where[] = 'j.tanggal = ?';
    $params[] = $tanggal;
    $status = $_GET['status'] ?? 'semua';
    $status = array_key_exists($status, $STATUS) ? $status : 'semua';
} else {
    switch ($rentang) {
        case 'terlambat':
            $where[] = 'j.tanggal < ?';
            $params[] = $hariIni;
            $status = 'belum';
            break;
        case 'hari_ini':
            $where[] = 'j.tanggal = ?';
            $params[] = $hariIni;
            break;
        case '7_hari':
        case '30_hari':
            // Termasuk yang terlambat dan belum selesai, supaya tidak terlewat.
            $where[] = '(j.tanggal BETWEEN ? AND ? OR (j.tanggal < ? AND j.selesai = 0))';
            array_push($params, $hariIni, tambah_hari($hariIni, $rentang === '7_hari' ? 7 : 30), $hariIni);
            break;
    }
}
if ($status === 'belum') {
    $where[] = 'j.selesai = 0';
} elseif ($status === 'selesai') {
    $where[] = 'j.selesai = 1';
}
if ($lahanId > 0) {
    $where[] = 'j.lahan_id = ?';
    $params[] = $lahanId;
}
if ($jenis !== '') {
    $where[] = 'j.jenis = ?';
    $params[] = $jenis;
}

$sql = SQL_JADWAL . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY j.tanggal, j.id LIMIT 500';
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$lahanList = $pdo->query('SELECT id, nama FROM lahan ORDER BY nama')->fetchAll();
$opsiLahan = ['0' => 'Semua lahan'] + array_column($lahanList, 'nama', 'id');
$opsiJenis = ['' => 'Semua jenis'] + array_combine(JENIS_KEGIATAN, JENIS_KEGIATAN);

$qs = $_SERVER['QUERY_STRING'] ?? '';
$kembali = 'jadwal.php' . ($qs !== '' ? '?' . $qs : '');

awal_halaman($tanggal ? 'Jadwal ' . tgl($tanggal, true) : 'Jadwal kegiatan', 'jadwal.php');
?>
<form method="get" class="filter" aria-label="Saring jadwal">
  <?php if ($tanggal): ?>
    <input type="hidden" name="tanggal" value="<?= e($tanggal) ?>">
    <a class="btn tipis" href="jadwal.php">Tampilkan semua tanggal</a>
  <?php else: ?>
    <label>Rentang <select name="rentang"><?= pilihan($RENTANG, $rentang) ?></select></label>
  <?php endif; ?>
  <label>Status <select name="status"><?= pilihan($STATUS, $status) ?></select></label>
  <label>Lahan <select name="lahan"><?= pilihan($opsiLahan, (string)$lahanId) ?></select></label>
  <label>Jenis <select name="jenis"><?= pilihan($opsiJenis, $jenis) ?></select></label>
  <button type="submit" class="btn">Terapkan</button>
</form>

<p class="bantu"><?= count($rows) ?> kegiatan<?= count($rows) === 500 ? ' (ditampilkan 500 pertama)' : '' ?>.
  Klik kotak di kiri untuk menandai selesai.</p>
<?php daftar_tugas($rows, $kembali, ['edit' => true, 'kosong' => 'Tidak ada kegiatan untuk saringan ini.']); ?>

<section class="kartu">
  <h2>Tambah kegiatan manual</h2>
  <?php if (!$lahanList): ?>
    <p class="kosong">Tambahkan <a href="lahan.php">lahan</a> dulu sebelum membuat kegiatan.</p>
  <?php else: ?>
  <form method="post" action="tugas_aksi.php" class="form grid">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="tambah">
    <input type="hidden" name="kembali" value="<?= e($kembali) ?>">
    <div><label for="t-lahan">Lahan</label>
      <select id="t-lahan" name="lahan_id" required><?= pilihan(array_column($lahanList, 'nama', 'id'), (string)$lahanId) ?></select></div>
    <div><label for="t-jenis">Jenis</label>
      <select id="t-jenis" name="jenis"><?= pilihan(array_combine(JENIS_KEGIATAN, JENIS_KEGIATAN), $jenis ?: 'Lainnya') ?></select></div>
    <div><label for="t-judul">Nama kegiatan</label>
      <input id="t-judul" name="judul" required maxlength="150" placeholder="Contoh: Perbaiki saluran air"></div>
    <div><label for="t-tanggal">Tanggal</label>
      <input id="t-tanggal" name="tanggal" type="date" value="<?= e($tanggal ?: $hariIni) ?>" required></div>
    <div class="lebar"><label for="t-ket">Keterangan (opsional)</label>
      <input id="t-ket" name="keterangan" maxlength="255"></div>
    <div class="lebar"><button type="submit" class="btn utama">Tambah kegiatan</button></div>
  </form>
  <?php endif; ?>
</section>
<?php akhir_halaman();
