<?php
// Logika inti: membuat jadwal kegiatan dari template tanaman.

/**
 * Hitung hari-hari (HST) untuk satu template.
 * Contoh: mulai 7, ulangi tiap 14, sampai 50  ->  [7, 21, 35, 49]
 */
function hari_template(array $t, int $umurPanen): array
{
    $mulai = (int)$t['hari_mulai'];
    $ulang = (int)$t['ulangi_tiap_hari'];
    if ($mulai < 0 || $mulai > $umurPanen) {
        return [];
    }
    if ($ulang <= 0) {
        return [$mulai];
    }
    $akhir = ($t['hari_selesai'] !== null && $t['hari_selesai'] !== '')
        ? min((int)$t['hari_selesai'], $umurPanen - 1)
        : $umurPanen - 1;

    $hari = [];
    for ($h = $mulai; $h <= $akhir; $h += $ulang) {
        $hari[] = $h;
    }
    return $hari;
}

function ringkas_hari(array $hari): string
{
    if (!$hari) {
        return 'Tidak dijadwalkan (di luar umur panen)';
    }
    if (count($hari) === 1) {
        return 'Hari ke-' . $hari[0];
    }
    $tampil = count($hari) > 5
        ? implode(', ', array_slice($hari, 0, 4)) . ', …, ' . end($hari)
        : implode(', ', $hari);
    return 'Hari ke-' . $tampil . ' (' . count($hari) . ' kali)';
}

/**
 * Buat semua kegiatan untuk satu musim tanam: penanaman, kegiatan dari template, lalu panen.
 * Mengembalikan jumlah kegiatan yang dibuat.
 */
function buat_jadwal_musim(int $musimId): int
{
    $pdo = db();
    $st = $pdo->prepare(
        'SELECT m.*, t.nama AS nama_tanaman, t.umur_panen_hari
           FROM musim_tanam m JOIN tanaman t ON t.id = m.tanaman_id
          WHERE m.id = ?'
    );
    $st->execute([$musimId]);
    $m = $st->fetch();
    if (!$m) {
        return 0;
    }

    $umur = (int)$m['umur_panen_hari'];
    $tanam = $m['tanggal_tanam'];

    $tpl = $pdo->prepare('SELECT * FROM template_kegiatan WHERE tanaman_id = ? ORDER BY hari_mulai, id');
    $tpl->execute([$m['tanaman_id']]);

    $ins = $pdo->prepare(
        'INSERT INTO jadwal (musim_id, lahan_id, jenis, judul, tanggal, hari_ke, keterangan)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $tambah = function (string $jenis, string $judul, int $hari, ?string $ket) use ($ins, $musimId, $m, $tanam): void {
        $ins->execute([$musimId, $m['lahan_id'], $jenis, $judul, tambah_hari($tanam, $hari), $hari, $ket]);
    };

    $n = 0;
    $pdo->beginTransaction();
    try {
        $tambah('Penanaman', 'Penanaman ' . $m['nama_tanaman'], 0, null);
        $n++;
        foreach ($tpl->fetchAll() as $t) {
            foreach (hari_template($t, $umur) as $h) {
                $tambah($t['jenis'], $t['judul'], $h, $t['keterangan'] ?: null);
                $n++;
            }
        }
        $tambah('Panen', 'Panen ' . $m['nama_tanaman'], $umur, 'Perkiraan, sesuaikan dengan kondisi tanaman');
        $n++;
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return $n;
}

// Persentase perjalanan musim tanam (0–100).
function progres_musim(array $m): int
{
    $umur = max(1, (int)$m['umur_panen_hari']);
    $jalan = selisih_hari($m['tanggal_tanam'], hari_ini());
    return (int)round(max(0, min($umur, $jalan)) / $umur * 100);
}

// Query dasar daftar kegiatan beserta nama lahan & tanaman.
const SQL_JADWAL = 'SELECT j.*, l.nama AS nama_lahan, t.nama AS nama_tanaman
                      FROM jadwal j
                      JOIN lahan l ON l.id = j.lahan_id
                      LEFT JOIN musim_tanam m ON m.id = j.musim_id
                      LEFT JOIN tanaman t ON t.id = m.tanaman_id';
