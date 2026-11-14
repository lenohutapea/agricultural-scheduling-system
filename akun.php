<?php
require __DIR__ . '/includes/bootstrap.php';
wajib_login();

$pdo = db();
$saya = pengguna();

if (is_post()) {
    $aksi = post('aksi');

    if ($aksi === 'password') {
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$saya['id']]);
        $hash = (string)$st->fetchColumn();
        $baru = (string)($_POST['baru'] ?? '');
        if (!password_verify((string)($_POST['lama'] ?? ''), $hash)) {
            flash('gagal', 'Password lama salah.');
        } elseif (strlen($baru) < 8) {
            flash('gagal', 'Password baru minimal 8 karakter.');
        } elseif ($baru !== (string)($_POST['konfirmasi'] ?? '')) {
            flash('gagal', 'Konfirmasi password baru tidak sama.');
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($baru, PASSWORD_DEFAULT), $saya['id']]);
            flash('sukses', 'Password diganti.');
        }
    }

    if ($aksi === 'tambah') {
        $nama = post('nama');
        $username = post('username');
        $password = (string)($_POST['password'] ?? '');
        $ada = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $ada->execute([$username]);
        if ($nama === '') {
            flash('gagal', 'Nama wajib diisi.');
        } elseif (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $username)) {
            flash('gagal', 'Username 3–30 karakter, hanya huruf, angka, titik, dan garis bawah.');
        } elseif ((int)$ada->fetchColumn() > 0) {
            flash('gagal', 'Username sudah dipakai.');
        } elseif (strlen($password) < 8) {
            flash('gagal', 'Password minimal 8 karakter.');
        } else {
            $pdo->prepare('INSERT INTO users (nama, username, password_hash) VALUES (?, ?, ?)')
                ->execute([$nama, $username, password_hash($password, PASSWORD_DEFAULT)]);
            flash('sukses', 'Pengguna ' . $username . ' ditambahkan.');
        }
    }

    if ($aksi === 'hapus') {
        $hapusId = (int)($_POST['id'] ?? 0);
        if ($hapusId === (int)$saya['id']) {
            flash('gagal', 'Anda tidak bisa menghapus akun yang sedang dipakai.');
        } else {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$hapusId]);
            flash('sukses', 'Pengguna dihapus.');
        }
    }
    redirect('akun.php');
}

$users = $pdo->query('SELECT id, nama, username, created_at FROM users ORDER BY nama')->fetchAll();

awal_halaman('Akun', 'akun.php');
?>
<div class="dua-kolom">
  <section>
    <h2>Pengguna</h2>
    <p class="bantu">Semua pengguna punya akses yang sama ke seluruh data.</p>
    <div class="tabel-wrap">
    <table class="tabel">
      <thead><tr><th>Nama</th><th>Username</th><th><span class="sr">Aksi</span></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['nama']) ?><?= (int)$u['id'] === (int)$saya['id'] ? ' <small>(Anda)</small>' : '' ?></td>
          <td><?= e($u['username']) ?></td>
          <td class="aksi-sel">
            <?php if ((int)$u['id'] !== (int)$saya['id']): ?>
            <form method="post" onsubmit="return confirm('Hapus pengguna ini?')">
              <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn kecil bahaya">Hapus</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div class="kartu">
      <h2>Tambah pengguna</h2>
      <form method="post" class="form grid">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
        <div><label for="u-nama">Nama</label><input id="u-nama" name="nama" required maxlength="100"></div>
        <div><label for="u-username">Username</label><input id="u-username" name="username" required maxlength="30" autocomplete="off"></div>
        <div><label for="u-password">Password</label><input id="u-password" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
        <div class="lebar"><button type="submit" class="btn utama">Tambah pengguna</button></div>
      </form>
    </div>
  </section>

  <aside class="kartu">
    <h2>Ganti password</h2>
    <form method="post" class="form">
      <?= csrf_field() ?><input type="hidden" name="aksi" value="password">
      <label for="lama">Password lama</label>
      <input id="lama" name="lama" type="password" required autocomplete="current-password">
      <label for="baru">Password baru</label>
      <input id="baru" name="baru" type="password" required minlength="8" autocomplete="new-password">
      <label for="konfirmasi">Ulangi password baru</label>
      <input id="konfirmasi" name="konfirmasi" type="password" required autocomplete="new-password">
      <button type="submit" class="btn utama">Ganti password</button>
    </form>
  </aside>
</div>
<?php akhir_halaman();
