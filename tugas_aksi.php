<?php
// Aksi untuk satu kegiatan: tandai selesai, pindahkan tanggal, hapus, atau tambah manual.
require __DIR__ . '/includes/bootstrap.php';
wajib_login();

if (!is_post()) {
    redirect('jadwal.php');
}

$pdo = db();
$kembali = aman_kembali($_POST['kembali'] ?? null, 'jadwal.php');
$aksi = post('aksi');
$id = (int)($_POST['id'] ?? 0);

switch ($aksi) {
    case 'toggle':
        $st = $pdo->prepare('SELECT selesai FROM jadwal WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if ($row) {
            $baru = $row['selesai'] ? 0 : 1;
            $pdo->prepare('UPDATE jadwal SET selesai = ?, selesai_pada = ? WHERE id = ?')
                ->execute([$baru, $baru ? date('Y-m-d H:i:s') : null, $id]);
        }
        break;

    case 'pindah':
        $tanggal = post('tanggal');
        if (!tanggal_valid($tanggal)) {
            flash('gagal', 'Tanggal tidak valid.');
            break;
        }
        // Hitung ulang HST jika kegiatan ini bagian dari musim tanam.
        $st = $pdo->prepare('SELECT m.tanggal_tanam FROM jadwal j LEFT JOIN musim_tanam m ON m.id = j.musim_id WHERE j.id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if ($row) {
            $hst = $row['tanggal_tanam'] ? selisih_hari($row['tanggal_tanam'], $tanggal) : null;
            $pdo->prepare('UPDATE jadwal SET tanggal = ?, hari_ke = ? WHERE id = ?')->execute([$tanggal, $hst, $id]);
            flash('sukses', 'Kegiatan dipindahkan ke ' . tgl($tanggal, true) . '.');
        }
        break;

    case 'hapus':
        $pdo->prepare('DELETE FROM jadwal WHERE id = ?')->execute([$id]);
        flash('sukses', 'Kegiatan dihapus.');
        break;

    case 'tambah':
        $lahanId = (int)($_POST['lahan_id'] ?? 0);
        $jenis = post('jenis');
        $judul = post('judul');
        $tanggal = post('tanggal');
        $ket = post('keterangan');

        $cek = $pdo->prepare('SELECT id FROM lahan WHERE id = ?');
        $cek->execute([$lahanId]);
        if (!$cek->fetch()) {
            flash('gagal', 'Pilih lahan terlebih dahulu.');
        } elseif (!in_array($jenis, JENIS_KEGIATAN, true)) {
            flash('gagal', 'Jenis kegiatan tidak valid.');
        } elseif ($judul === '') {
            flash('gagal', 'Nama kegiatan wajib diisi.');
        } elseif (!tanggal_valid($tanggal)) {
            flash('gagal', 'Tanggal tidak valid.');
        } else {
            // Kaitkan ke musim tanam yang sedang berjalan di lahan itu, jika ada.
            $ms = $pdo->prepare("SELECT id, tanggal_tanam FROM musim_tanam WHERE lahan_id = ? AND status = 'Berjalan'
                                   AND tanggal_tanam <= ? ORDER BY tanggal_tanam DESC LIMIT 1");
            $ms->execute([$lahanId, $tanggal]);
            $musim = $ms->fetch();
            $pdo->prepare('INSERT INTO jadwal (musim_id, lahan_id, jenis, judul, tanggal, hari_ke, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([
                    $musim['id'] ?? null, $lahanId, $jenis, $judul, $tanggal,
                    $musim ? selisih_hari($musim['tanggal_tanam'], $tanggal) : null,
                    $ket !== '' ? $ket : null,
                ]);
            flash('sukses', 'Kegiatan ditambahkan pada ' . tgl($tanggal, true) . '.');
        }
        break;
}

redirect($kembali);
