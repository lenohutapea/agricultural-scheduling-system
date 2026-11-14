<?php
require __DIR__ . '/includes/bootstrap.php';
wajib_login();

$pdo = db();
$edit = null;
$form = ['nama' => '', 'lokasi' => '', 'luas_m2' => '', 'catatan' => ''];
$error = null;

if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM lahan WHERE id = ?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
    if ($edit) {
        $form = $edit;
    }
}

if (is_post()) {
    $aksi = post('aksi');
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus') {
        $pdo->prepare('DELETE FROM lahan WHERE id = ?')->execute([$id]);
        flash('sukses', 'Lahan beserta musim tanam dan jadwalnya dihapus.');
        redirect('lahan.php');
    }

    $form = ['nama' => post('nama'), 'lokasi' => post('lokasi'), 'luas_m2' => str_replace(',', '.', post('luas_m2')), 'catatan' => post('catatan')];
    if ($form['nama'] === '') {
        $error = 'Nama lahan wajib diisi.';
    } elseif ($form['luas_m2'] !== '' && (!is_numeric($form['luas_m2']) || (float)$form['luas_m2'] < 0)) {
        $error = 'Luas harus berupa angka (dalam m²).';
    } else {
        $data = [$form['nama'], $form['lokasi'] ?: null, $form['luas_m2'] !== '' ? $form['luas_m2'] : null, $form['catatan'] ?: null];
        if ($id > 0) {
            $pdo->prepare('UPDATE lahan SET nama = ?, lokasi = ?, luas_m2 = ?, catatan = ? WHERE id = ?')->execute([...$data, $id]);
            flash('sukses', 'Lahan diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO lahan (nama, lokasi, luas_m2, catatan) VALUES (?, ?, ?, ?)')->execute($data);
            flash('sukses', 'Lahan ditambahkan. Berikutnya, buat musim tanam untuk lahan ini.');
        }
        redirect('lahan.php');
    }
}

$rows = $pdo->query(
    "SELECT l.*,
            (SELECT COUNT(*) FROM musim_tanam m WHERE m.lahan_id = l.id AND m.status = 'Berjalan') AS musim_aktif,
            (SELECT COUNT(*) FROM jadwal j WHERE j.lahan_id = l.id AND j.selesai = 0) AS tugas_belum
       FROM lahan l ORDER BY l.nama"
)->fetchAll();

awal_halaman('Lahan', 'lahan.php');
?>
<div class="dua-kolom">
  <section>
    <?php if (!$rows): ?>
      <p class="kosong">Belum ada lahan. Tambahkan lewat formulir di samping.</p>
    <?php else: ?>
    <div class="tabel-wrap">
    <table class="tabel">
      <thead><tr><th>Nama</th><th>Lokasi</th><th class="kanan">Luas</th><th class="kanan">Musim berjalan</th><th class="kanan">Kegiatan belum selesai</th><th><span class="sr">Aksi</span></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><strong><?= e($r['nama']) ?></strong><?php if ($r['catatan']): ?><br><small><?= e($r['catatan']) ?></small><?php endif; ?></td>
          <td><?= e($r['lokasi'] ?: '-') ?></td>
          <td class="kanan angka-tab"><?= e(format_luas($r['luas_m2'])) ?></td>
          <td class="kanan angka-tab"><?= (int)$r['musim_aktif'] ?></td>
          <td class="kanan angka-tab"><a href="jadwal.php?rentang=semua&amp;lahan=<?= (int)$r['id'] ?>"><?= (int)$r['tugas_belum'] ?></a></td>
          <td class="aksi-sel">
            <a class="btn kecil" href="lahan.php?edit=<?= (int)$r['id'] ?>">Ubah</a>
            <form method="post" onsubmit="return confirm('Hapus lahan ini? Semua musim tanam dan jadwalnya ikut terhapus.')">
              <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button type="submit" class="btn kecil bahaya">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </section>

  <aside class="kartu">
    <h2><?= $edit ? 'Ubah lahan' : 'Tambah lahan' ?></h2>
    <?php if ($error): ?><div class="pesan gagal" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <label for="nama">Nama lahan</label>
      <input id="nama" name="nama" value="<?= e($form['nama']) ?>" required maxlength="100" placeholder="Contoh: Sawah Blok A">
      <label for="lokasi">Lokasi</label>
      <input id="lokasi" name="lokasi" value="<?= e($form['lokasi']) ?>" maxlength="150" placeholder="Desa, kecamatan">
      <label for="luas">Luas (m²)</label>
      <input id="luas" name="luas_m2" value="<?= e($form['luas_m2']) ?>" inputmode="decimal" placeholder="Contoh: 2500">
      <small class="bantu">1 hektare = 10.000 m²</small>
      <label for="catatan">Catatan</label>
      <textarea id="catatan" name="catatan" rows="3"><?= e($form['catatan']) ?></textarea>
      <button type="submit" class="btn utama"><?= $edit ? 'Simpan perubahan' : 'Tambah lahan' ?></button>
      <?php if ($edit): ?><a class="btn tipis" href="lahan.php">Batal</a><?php endif; ?>
    </form>
  </aside>
</div>
<?php akhir_halaman();
