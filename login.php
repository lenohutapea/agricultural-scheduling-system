<?php
require __DIR__ . '/includes/bootstrap.php';
cek_csrf();

if (pengguna()) {
    redirect('index.php');
}

$pdo = db();
$adaPengguna = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
$error = null;
$username = '';
$nama = '';

if (is_post()) {
    $username = post('username');
    $password = (string)($_POST['password'] ?? '');

    if (!$adaPengguna) {
        // Pertama kali dipakai: buat akun pertama.
        $nama = post('nama');
        $konfirmasi = (string)($_POST['konfirmasi'] ?? '');
        if ($nama === '') {
            $error = 'Nama wajib diisi.';
        } elseif (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $username)) {
            $error = 'Username 3–30 karakter, hanya huruf, angka, titik, dan garis bawah.';
        } elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi password tidak sama.';
        } else {
            $st = $pdo->prepare('INSERT INTO users (nama, username, password_hash) VALUES (?, ?, ?)');
            $st->execute([$nama, $username, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)$pdo->lastInsertId(), 'nama' => $nama, 'username' => $username];
            flash('sukses', 'Akun dibuat. Mulai dengan menambahkan lahan Anda.');
            redirect('lahan.php');
        }
    } else {
        $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
        if ($u && password_verify($password, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)$u['id'], 'nama' => $u['nama'], 'username' => $u['username']];
            redirect('index.php');
        }
        $error = 'Username atau password salah.';
    }
}

awal_halaman($adaPengguna ? 'Masuk' : 'Buat akun pertama');
?>
<div class="kartu sempit">
  <?php if (!$adaPengguna): ?>
    <p class="bantu">Belum ada akun di sistem ini. Buat akun pertama untuk mulai memakai aplikasi.</p>
  <?php endif; ?>
  <?php if ($error): ?><div class="pesan gagal" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <?php if (!$adaPengguna): ?>
      <label for="nama">Nama lengkap</label>
      <input id="nama" name="nama" value="<?= e($nama) ?>" required autocomplete="name">
    <?php endif; ?>
    <label for="username">Username</label>
    <input id="username" name="username" value="<?= e($username) ?>" required autocomplete="username" autofocus>
    <label for="password">Password</label>
    <input id="password" name="password" type="password" required
           autocomplete="<?= $adaPengguna ? 'current-password' : 'new-password' ?>">
    <?php if (!$adaPengguna): ?>
      <label for="konfirmasi">Ulangi password</label>
      <input id="konfirmasi" name="konfirmasi" type="password" required autocomplete="new-password">
    <?php endif; ?>
    <button type="submit" class="btn utama"><?= $adaPengguna ? 'Masuk' : 'Buat akun' ?></button>
  </form>
</div>
<?php akhir_halaman();
