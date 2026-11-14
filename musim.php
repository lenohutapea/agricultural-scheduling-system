<?php
require __DIR__ . '/includes/bootstrap.php';
wajib_login();

$pdo = db();
$error = null;
$form = ['lahan_id' => (string)($_GET['lahan'] ?? ''), 'tanaman_id' => '', 'tanggal_tanam' => hari_ini(), 'catatan' => ''];

if (is_post()) {
    $form = ['lahan_id' => post('lahan_id'), 'tanaman_id' => post('tanaman_id'), 'tanggal_tanam' => post('tanggal_tanam'), 'catatan' => post('catatan')];
    $l = $pdo->prepare('SELECT id FROM lahan WHERE id = ?');
    $l->execute([(int)$form['lahan_id']]);
    $t = $pdo->prepare('SELECT * FROM tanaman WHERE id = ?');
    $t->execute([(int)$form['tanaman_id']]);
    $tanaman = $t->fetch();

    if (!$l->fetch()) {
        $error = 'Pilih lahan.';
    } elseif (!$tanaman) {
        $error = 'Pilih tanaman.';
    } elseif (!tanggal_valid($form['tanggal_tanam'])) {
        $error = 'Tanggal tanam tidak valid.';
    } else {
        $panen = tambah_hari($form['tanggal_tanam'], (int)$tanaman['umur_panen_hari']);
        $pdo->prepare('INSERT INTO musim_tanam (lahan_id, tanaman_id, tanggal_tanam, perkiraan_panen, catatan) VALUES (?, ?, ?, ?, ?)')
            ->execute([(int)$form['lahan_id'], (int)$form['tanaman_id'], $form['tanggal_tanam'], $panen, $form['catatan'] ?: null]);
        $musimId = (int)$pdo->lastInsertId();
        $n = buat_jadwal_musim($musimId);
        flash('sukses', $n . ' kegiatan dijadwalkan, dari penanaman ' . tgl($form['tanggal_tanam']) . ' sampai perkiraan panen ' . tgl($panen) . '.');
        redirect('musim_detail.php?id=' . $musimId);
    }
}

$rows = $pdo->query(
    'SELECT m.*, l.nama AS nama_lahan, t.nama AS nama_tanaman, t.umur_panen_hari,
            (SELECT COUNT(*) FROM jadwal j WHERE j.musim_id = m.id) AS total,
            (SELECT COUNT(*) FROM jadwal j WHERE j.musim_id = m.id AND j.selesai = 1) AS beres
       FROM musim_tanam m
       JOIN lahan l ON l.id = m.lahan_id
       JOIN tanaman t ON t.id = m.tanaman_id
      ORDER BY CASE m.status WHEN \'Berjalan\' THEN 0 ELSE 1 END, m.tanggal_tanam DESC'
)->fetchAll();

$lahanList = $pdo->query('SELECT id, nama FROM lahan ORDER BY nama')->fetchAll();
$tanamanList = $pdo->query('SELECT id, nama, umur_panen_hari FROM tanaman ORDER BY nama')->fetchAll();
$opsiTanaman = [];
foreach ($tanamanList as $t) {
    $opsiTanaman[$t['id']] = $t['nama'] . ' (' . $t['umur_panen_hari'] . ' hari)';
}

awal_halaman('Musim tanam', 'musim.php');
?>
<div class="dua-kolom">
  <section>
    <?php if (!$rows): ?>
      <p class="kosong">Belum ada musim tanam. Buat lewat formulir di samping.</p>
    <?php else: ?>
    <div class="tabel-wrap">
    <table class="tabel">
      <thead><tr><th>Tanaman · lahan</th><th>Tanam</th><th>Perkiraan panen</th><th>Perjalanan</th><th class="kanan">Kegiatan selesai</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $m): $p = progres_musim($m); ?>
        <tr>
          <td><a href="musim_detail.php?id=<?= (int)$m['id'] ?>"><strong><?= e($m['nama_tanaman']) ?></strong></a><br><small><?= e($m['nama_lahan']) ?></small></td>
          <td><?= e(tgl($m['tanggal_tanam'])) ?></td>
          <td><?= e(tgl($m['perkiraan_panen'])) ?></td>
          <td><?php if ($m['status'] === 'Berjalan'): ?>
              <div class="bar" role="progressbar" aria-valuenow="<?= $p ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Perjalanan musim"><i style="width:<?= $p ?>%"></i></div><small><?= $p ?>%</small>
              <?php else: ?>-<?php endif; ?></td>
          <td class="kanan angka-tab"><?= (int)$m['beres'] ?> / <?= (int)$m['total'] ?></td>
          <td><span class="status s-<?= strtolower(e($m['status'])) ?>"><?= e($m['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </section>

  <aside class="kartu">
    <h2>Buat musim tanam</h2>
    <?php if ($error): ?><div class="pesan gagal" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if (!$lahanList): ?>
      <p class="kosong">Tambahkan <a href="lahan.php">lahan</a> dulu.</p>
    <?php else: ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label for="lahan">Lahan</label>
      <select id="lahan" name="lahan_id" required><option value="">Pilih lahan…</option><?= pilihan(array_column($lahanList, 'nama', 'id'), $form['lahan_id']) ?></select>
      <label for="tanaman">Tanaman</label>
      <select id="tanaman" name="tanaman_id" required><option value="">Pilih tanaman…</option><?= pilihan($opsiTanaman, $form['tanaman_id']) ?></select>
      <label for="tgl">Tanggal tanam</label>
      <input id="tgl" name="tanggal_tanam" type="date" value="<?= e($form['tanggal_tanam']) ?>" required>
      <label for="catatan">Catatan</label>
      <textarea id="catatan" name="catatan" rows="2" placeholder="Contoh: varietas, asal benih"><?= e($form['catatan']) ?></textarea>
      <p class="bantu">Jadwal penanaman, perawatan, sampai panen dibuat otomatis dari <a href="tanaman.php">pola tanaman</a>. Anda bisa memindahkan atau menghapus kegiatannya nanti.</p>
      <button type="submit" class="btn utama">Buat musim dan jadwal</button>
    </form>
    <?php endif; ?>
  </aside>
</div>
<?php akhir_halaman();
