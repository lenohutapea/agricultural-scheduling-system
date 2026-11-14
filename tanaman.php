<?php
require __DIR__ . '/includes/bootstrap.php';
wajib_login();

$pdo = db();
$error = null;
$form = ['nama' => '', 'umur_panen_hari' => '', 'catatan' => ''];

if (is_post()) {
    if (post('aksi') === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $dipakai = $pdo->prepare('SELECT COUNT(*) FROM musim_tanam WHERE tanaman_id = ?');
        $dipakai->execute([$id]);
        if ((int)$dipakai->fetchColumn() > 0) {
            flash('gagal', 'Tanaman ini dipakai di musim tanam, jadi tidak bisa dihapus. Hapus musim tanamnya dulu.');
        } else {
            $pdo->prepare('DELETE FROM tanaman WHERE id = ?')->execute([$id]);
            flash('sukses', 'Tanaman dihapus.');
        }
        redirect('tanaman.php');
    }

    $form = ['nama' => post('nama'), 'umur_panen_hari' => post('umur_panen_hari'), 'catatan' => post('catatan')];
    $umur = filter_var($form['umur_panen_hari'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3650]]);
    $ada = $pdo->prepare('SELECT COUNT(*) FROM tanaman WHERE nama = ?');
    $ada->execute([$form['nama']]);
    if ($form['nama'] === '') {
        $error = 'Nama tanaman wajib diisi.';
    } elseif ($umur === false) {
        $error = 'Umur panen harus berupa angka hari (1–3650).';
    } elseif ((int)$ada->fetchColumn() > 0) {
        $error = 'Tanaman dengan nama itu sudah ada.';
    } else {
        $pdo->prepare('INSERT INTO tanaman (nama, umur_panen_hari, catatan) VALUES (?, ?, ?)')
            ->execute([$form['nama'], $umur, $form['catatan'] ?: null]);
        flash('sukses', 'Tanaman ditambahkan. Sekarang atur pola kegiatannya.');
        redirect('tanaman_detail.php?id=' . $pdo->lastInsertId());
    }
}

$rows = $pdo->query(
    'SELECT t.*,
            (SELECT COUNT(*) FROM template_kegiatan k WHERE k.tanaman_id = t.id) AS jumlah_pola,
            (SELECT COUNT(*) FROM musim_tanam m WHERE m.tanaman_id = t.id) AS jumlah_musim
       FROM tanaman t ORDER BY t.nama'
)->fetchAll();

awal_halaman('Tanaman', 'tanaman.php');
?>
<p class="lead">Setiap tanaman punya umur panen dan pola kegiatan. Saat Anda membuat musim tanam, pola ini dipakai untuk menyusun jadwal secara otomatis.</p>
<div class="dua-kolom">
  <section>
    <div class="tabel-wrap">
    <table class="tabel">
      <thead><tr><th>Tanaman</th><th class="kanan">Umur panen</th><th class="kanan">Pola kegiatan</th><th class="kanan">Dipakai</th><th><span class="sr">Aksi</span></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="tanaman_detail.php?id=<?= (int)$r['id'] ?>"><strong><?= e($r['nama']) ?></strong></a><?php if ($r['catatan']): ?><br><small><?= e($r['catatan']) ?></small><?php endif; ?></td>
          <td class="kanan angka-tab"><?= (int)$r['umur_panen_hari'] ?> hari</td>
          <td class="kanan angka-tab"><?= (int)$r['jumlah_pola'] ?></td>
          <td class="kanan angka-tab"><?= (int)$r['jumlah_musim'] ?> musim</td>
          <td class="aksi-sel">
            <a class="btn kecil" href="tanaman_detail.php?id=<?= (int)$r['id'] ?>">Atur pola</a>
            <?php if (!(int)$r['jumlah_musim']): ?>
            <form method="post" onsubmit="return confirm('Hapus tanaman ini beserta polanya?')">
              <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button type="submit" class="btn kecil bahaya">Hapus</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="kosong">Belum ada tanaman.</td></tr><?php endif; ?>
      </tbody>
    </table>
    </div>
  </section>

  <aside class="kartu">
    <h2>Tambah tanaman</h2>
    <?php if ($error): ?><div class="pesan gagal" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label for="nama">Nama tanaman</label>
      <input id="nama" name="nama" value="<?= e($form['nama']) ?>" required maxlength="100" placeholder="Contoh: Kacang tanah">
      <label for="umur">Umur panen (hari setelah tanam)</label>
      <input id="umur" name="umur_panen_hari" type="number" min="1" max="3650" value="<?= e($form['umur_panen_hari']) ?>" required>
      <label for="catatan">Catatan</label>
      <textarea id="catatan" name="catatan" rows="2"><?= e($form['catatan']) ?></textarea>
      <button type="submit" class="btn utama">Tambah tanaman</button>
    </form>
  </aside>
</div>
<?php akhir_halaman();
