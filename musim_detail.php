<?php
require __DIR__ . '/includes/bootstrap.php';
wajib_login();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$diri = 'musim_detail.php?id=' . $id;

$st = $pdo->prepare(
    'SELECT m.*, l.nama AS nama_lahan, t.nama AS nama_tanaman, t.umur_panen_hari
       FROM musim_tanam m JOIN lahan l ON l.id = m.lahan_id JOIN tanaman t ON t.id = m.tanaman_id
      WHERE m.id = ?'
);
$st->execute([$id]);
$m = $st->fetch();
if (!$m) {
    flash('gagal', 'Musim tanam tidak ditemukan.');
    redirect('musim.php');
}

if (is_post()) {
    $aksi = post('aksi');
    if ($aksi === 'selesai') {
        $pdo->prepare("UPDATE musim_tanam SET status = 'Selesai' WHERE id = ?")->execute([$id]);
        flash('sukses', 'Musim tanam ditandai selesai.');
    } elseif ($aksi === 'batal') {
        $pdo->prepare("UPDATE musim_tanam SET status = 'Dibatalkan' WHERE id = ?")->execute([$id]);
        $hapus = $pdo->prepare('DELETE FROM jadwal WHERE musim_id = ? AND selesai = 0 AND tanggal >= ?');
        $hapus->execute([$id, hari_ini()]);
        flash('sukses', 'Musim tanam dibatalkan. ' . $hapus->rowCount() . ' kegiatan mendatang yang belum dikerjakan dihapus.');
    } elseif ($aksi === 'lanjutkan') {
        $pdo->prepare("UPDATE musim_tanam SET status = 'Berjalan' WHERE id = ?")->execute([$id]);
        flash('sukses', 'Musim tanam kembali berjalan.');
    } elseif ($aksi === 'hapus') {
        $pdo->prepare('DELETE FROM musim_tanam WHERE id = ?')->execute([$id]);
        flash('sukses', 'Musim tanam dan seluruh jadwalnya dihapus.');
        redirect('musim.php');
    }
    redirect($diri);
}

$st = $pdo->prepare(SQL_JADWAL . ' WHERE j.musim_id = ? ORDER BY j.tanggal, j.id');
$st->execute([$id]);
$semua = $st->fetchAll();
$perBulan = [];
foreach ($semua as $r) {
    $perBulan[substr($r['tanggal'], 0, 7)][] = $r;
}
$beres = count(array_filter($semua, fn($r) => $r['selesai']));
$p = progres_musim($m);
$hst = selisih_hari($m['tanggal_tanam'], hari_ini());

awal_halaman($m['nama_tanaman'] . ' · ' . $m['nama_lahan'], 'musim.php');
?>
<p><a href="musim.php">← Semua musim tanam</a></p>

<div class="ringkas kartu">
  <div><small>Status</small><span class="status s-<?= strtolower(e($m['status'])) ?>"><?= e($m['status']) ?></span></div>
  <div><small>Tanggal tanam</small><b><?= e(tgl($m['tanggal_tanam'])) ?></b></div>
  <div><small>Perkiraan panen</small><b><?= e(tgl($m['perkiraan_panen'])) ?></b></div>
  <div><small>Umur sekarang</small><b><?= $hst >= 0 ? $hst . ' HST' : 'Tanam ' . (-$hst) . ' hari lagi' ?></b></div>
  <div><small>Kegiatan selesai</small><b><?= $beres ?> / <?= count($semua) ?></b></div>
  <div class="lebar">
    <div class="bar" role="progressbar" aria-valuenow="<?= $p ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Perjalanan musim"><i style="width:<?= $p ?>%"></i></div>
  </div>
  <?php if ($m['catatan']): ?><p class="lebar"><?= e($m['catatan']) ?></p><?php endif; ?>
  <div class="lebar tombol-baris">
    <?php
    $tombol = $m['status'] === 'Berjalan'
        ? [['selesai', 'Tandai musim selesai', '', null],
           ['batal', 'Batalkan musim', 'bahaya', 'Batalkan musim tanam ini? Kegiatan mendatang yang belum dikerjakan akan dihapus.']]
        : [['lanjutkan', 'Jalankan lagi', '', null]];
    $tombol[] = ['hapus', 'Hapus musim', 'bahaya', 'Hapus musim tanam ini beserta seluruh jadwalnya?'];
    foreach ($tombol as [$aksi, $label, $kelas, $konfirmasi]): ?>
      <form method="post"<?= $konfirmasi ? ' onsubmit="return confirm(\'' . e($konfirmasi) . '\')"' : '' ?>>
        <?= csrf_field() ?><input type="hidden" name="aksi" value="<?= $aksi ?>">
        <button type="submit" class="btn <?= $kelas ?>"><?= e($label) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
</div>

<h2>Jadwal kegiatan</h2>
<?php if (!$semua): ?><p class="kosong">Tidak ada kegiatan.</p><?php endif; ?>
<?php foreach ($perBulan as $bulan => $rows): ?>
  <h3><?= NAMA_BULAN[(int)substr($bulan, 5, 2)] . ' ' . substr($bulan, 0, 4) ?></h3>
  <?php daftar_tugas($rows, $diri, ['lahan' => false, 'edit' => true]); ?>
<?php endforeach; ?>
<?php akhir_halaman();
