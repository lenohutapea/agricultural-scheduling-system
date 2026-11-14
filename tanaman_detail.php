<?php
require __DIR__ . '/includes/bootstrap.php';
wajib_login();

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM tanaman WHERE id = ?');
$st->execute([$id]);
$tanaman = $st->fetch();
if (!$tanaman) {
    flash('gagal', 'Tanaman tidak ditemukan.');
    redirect('tanaman.php');
}

$error = null;
$pola = ['jenis' => 'Penyiraman', 'judul' => '', 'hari_mulai' => '1', 'ulangi_tiap_hari' => '0', 'hari_selesai' => '', 'keterangan' => ''];
$diri = 'tanaman_detail.php?id=' . $id;

if (is_post()) {
    $aksi = post('aksi');

    if ($aksi === 'simpan_tanaman') {
        $nama = post('nama');
        $umur = filter_var(post('umur_panen_hari'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3650]]);
        $sama = $pdo->prepare('SELECT COUNT(*) FROM tanaman WHERE nama = ? AND id <> ?');
        $sama->execute([$nama, $id]);
        if ($nama === '' || $umur === false) {
            flash('gagal', 'Nama dan umur panen (1–3650 hari) wajib diisi.');
        } elseif ((int)$sama->fetchColumn() > 0) {
            flash('gagal', 'Tanaman dengan nama itu sudah ada.');
        } else {
            $pdo->prepare('UPDATE tanaman SET nama = ?, umur_panen_hari = ?, catatan = ? WHERE id = ?')
                ->execute([$nama, $umur, post('catatan') ?: null, $id]);
            flash('sukses', 'Data tanaman disimpan.');
        }
        redirect($diri);
    }

    if ($aksi === 'hapus_pola') {
        $pdo->prepare('DELETE FROM template_kegiatan WHERE id = ? AND tanaman_id = ?')->execute([(int)($_POST['pola_id'] ?? 0), $id]);
        flash('sukses', 'Pola kegiatan dihapus.');
        redirect($diri);
    }

    if ($aksi === 'tambah_pola') {
        $pola = [
            'jenis' => post('jenis'), 'judul' => post('judul'), 'hari_mulai' => post('hari_mulai'),
            'ulangi_tiap_hari' => post('ulangi_tiap_hari') ?: '0', 'hari_selesai' => post('hari_selesai'), 'keterangan' => post('keterangan'),
        ];
        $mulai = filter_var($pola['hari_mulai'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $ulang = filter_var($pola['ulangi_tiap_hari'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $selesai = $pola['hari_selesai'] === '' ? null : filter_var($pola['hari_selesai'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if (!in_array($pola['jenis'], JENIS_TEMPLATE, true)) {
            $error = 'Pilih jenis kegiatan.';
        } elseif ($pola['judul'] === '') {
            $error = 'Nama kegiatan wajib diisi.';
        } elseif ($mulai === false || $ulang === false || $selesai === false) {
            $error = 'Hari harus berupa angka 0 atau lebih.';
        } elseif ($mulai > (int)$tanaman['umur_panen_hari']) {
            $error = 'Hari mulai melewati umur panen (' . (int)$tanaman['umur_panen_hari'] . ' hari).';
        } elseif ($selesai !== null && $selesai < $mulai) {
            $error = 'Hari terakhir tidak boleh sebelum hari mulai.';
        } else {
            $pdo->prepare('INSERT INTO template_kegiatan (tanaman_id, jenis, judul, hari_mulai, ulangi_tiap_hari, hari_selesai, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$id, $pola['jenis'], $pola['judul'], $mulai, $ulang, $ulang > 0 ? $selesai : null, $pola['keterangan'] ?: null]);
            flash('sukses', 'Pola kegiatan ditambahkan.');
            redirect($diri);
        }
    }
}

$st = $pdo->prepare('SELECT * FROM template_kegiatan WHERE tanaman_id = ? ORDER BY hari_mulai, id');
$st->execute([$id]);
$daftarPola = $st->fetchAll();
$umur = (int)$tanaman['umur_panen_hari'];
$total = 2; // penanaman + panen
foreach ($daftarPola as $p) {
    $total += count(hari_template($p, $umur));
}

awal_halaman($tanaman['nama'], 'tanaman.php');
?>
<p><a href="tanaman.php">← Semua tanaman</a></p>

<div class="dua-kolom">
  <section>
    <h2>Pola kegiatan</h2>
    <p class="bantu">Hari dihitung dari tanggal tanam (HST, hari setelah tanam). Penanaman (hari ke-0) dan panen (hari ke-<?= $umur ?>)
      ditambahkan otomatis. Satu musim tanam <?= e($tanaman['nama']) ?> akan berisi <b><?= $total ?> kegiatan</b>.
      Perubahan pola hanya berlaku untuk musim tanam yang dibuat setelahnya.</p>

    <div class="tabel-wrap">
    <table class="tabel">
      <thead><tr><th>Kegiatan</th><th>Jadwal</th><th><span class="sr">Aksi</span></th></tr></thead>
      <tbody>
        <tr class="otomatis"><td><?= chip_jenis('Penanaman') ?> Penanaman</td><td>Hari ke-0</td><td><small>otomatis</small></td></tr>
        <?php foreach ($daftarPola as $p): ?>
        <tr>
          <td><?= chip_jenis($p['jenis']) ?> <?= e($p['judul']) ?><?php if ($p['keterangan']): ?><br><small><?= e($p['keterangan']) ?></small><?php endif; ?></td>
          <td><?= e(ringkas_hari(hari_template($p, $umur))) ?>
            <?php if ((int)$p['ulangi_tiap_hari'] > 0): ?><br><small>tiap <?= (int)$p['ulangi_tiap_hari'] ?> hari</small><?php endif; ?></td>
          <td class="aksi-sel">
            <form method="post" onsubmit="return confirm('Hapus pola kegiatan ini?')">
              <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus_pola"><input type="hidden" name="pola_id" value="<?= (int)$p['id'] ?>">
              <button type="submit" class="btn kecil bahaya">Hapus</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <tr class="otomatis"><td><?= chip_jenis('Panen') ?> Panen</td><td>Hari ke-<?= $umur ?></td><td><small>otomatis</small></td></tr>
      </tbody>
    </table>
    </div>

    <div class="kartu">
      <h2>Tambah pola kegiatan</h2>
      <?php if ($error): ?><div class="pesan gagal" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form method="post" class="form grid">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="tambah_pola">
        <div><label for="p-jenis">Jenis</label>
          <select id="p-jenis" name="jenis"><?= pilihan(array_combine(JENIS_TEMPLATE, JENIS_TEMPLATE), $pola['jenis']) ?></select></div>
        <div><label for="p-judul">Nama kegiatan</label>
          <input id="p-judul" name="judul" value="<?= e($pola['judul']) ?>" required maxlength="150" placeholder="Contoh: Pemupukan kedua"></div>
        <div><label for="p-mulai">Mulai hari ke-</label>
          <input id="p-mulai" name="hari_mulai" type="number" min="0" max="<?= $umur ?>" value="<?= e($pola['hari_mulai']) ?>" required></div>
        <div><label for="p-ulang">Ulangi tiap … hari</label>
          <input id="p-ulang" name="ulangi_tiap_hari" type="number" min="0" value="<?= e($pola['ulangi_tiap_hari']) ?>">
          <small class="bantu">Isi 0 jika hanya sekali.</small></div>
        <div><label for="p-selesai">Sampai hari ke- (opsional)</label>
          <input id="p-selesai" name="hari_selesai" type="number" min="0" max="<?= $umur ?>" value="<?= e($pola['hari_selesai']) ?>">
          <small class="bantu">Kosongkan untuk berulang sampai sebelum panen.</small></div>
        <div><label for="p-ket">Keterangan (opsional)</label>
          <input id="p-ket" name="keterangan" value="<?= e($pola['keterangan']) ?>" maxlength="255"></div>
        <div class="lebar"><button type="submit" class="btn utama">Tambah pola</button></div>
      </form>
    </div>
  </section>

  <aside class="kartu">
    <h2>Data tanaman</h2>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan_tanaman">
      <label for="nama">Nama tanaman</label>
      <input id="nama" name="nama" value="<?= e($tanaman['nama']) ?>" required maxlength="100">
      <label for="umur">Umur panen (hari)</label>
      <input id="umur" name="umur_panen_hari" type="number" min="1" max="3650" value="<?= $umur ?>" required>
      <label for="catatan">Catatan</label>
      <textarea id="catatan" name="catatan" rows="3"><?= e($tanaman['catatan']) ?></textarea>
      <button type="submit" class="btn utama">Simpan</button>
    </form>
  </aside>
</div>
<?php akhir_halaman();
