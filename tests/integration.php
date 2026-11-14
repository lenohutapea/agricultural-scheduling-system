<?php
// Integration test against a real database (run by CI inside the app container):
//   php tests/integration.php
// Creates a field and a rice season, checks the generated schedule, then cleans up.
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

$fail = function (string $msg): never {
    fwrite(STDERR, "FAIL $msg\n");
    exit(1);
};

$pdo = db();

$crops = (int)$pdo->query('SELECT COUNT(*) FROM tanaman')->fetchColumn();
$crops === 3 || $fail("expected 3 sample crops, got $crops");
echo "ok   sample crops loaded\n";

$padi = $pdo->query("SELECT id, umur_panen_hari FROM tanaman WHERE nama = 'Padi sawah'")->fetch();
$padi || $fail('rice crop not found');

$pdo->prepare('INSERT INTO lahan (nama, lokasi, luas_m2) VALUES (?, ?, ?)')->execute(['CI test field', 'CI', 1000]);
$lahanId = (int)$pdo->lastInsertId();

try {
    $tanam = '2027-03-01';
    $panen = tambah_hari($tanam, (int)$padi['umur_panen_hari']);
    $pdo->prepare('INSERT INTO musim_tanam (lahan_id, tanaman_id, tanggal_tanam, perkiraan_panen) VALUES (?, ?, ?, ?)')
        ->execute([$lahanId, $padi['id'], $tanam, $panen]);
    $musimId = (int)$pdo->lastInsertId();

    $n = buat_jadwal_musim($musimId);
    $n === 29 || $fail("expected 29 tasks for a rice season, got $n");
    echo "ok   rice season generated 29 tasks\n";

    $st = $pdo->prepare('SELECT MIN(tanggal) AS awal, MAX(tanggal) AS akhir, COUNT(*) AS n FROM jadwal WHERE musim_id = ?');
    $st->execute([$musimId]);
    $r = $st->fetch();
    ($r['awal'] === '2027-03-01' && $r['akhir'] === '2027-06-24' && (int)$r['n'] === 29)
        || $fail('unexpected schedule range: ' . json_encode($r));
    echo "ok   schedule runs from planting 2027-03-01 to harvest 2027-06-24\n";

    $st = $pdo->prepare("SELECT tanggal, hari_ke FROM jadwal WHERE musim_id = ? AND judul = 'Pemupukan kedua'");
    $st->execute([$musimId]);
    $p = $st->fetch();
    ($p && $p['tanggal'] === '2027-03-26' && (int)$p['hari_ke'] === 25) || $fail('second fertilizing on wrong day: ' . json_encode($p));
    echo "ok   second fertilizing on day 25 (2027-03-26)\n";
} finally {
    // deleting the field cascades to its season and tasks
    $pdo->prepare('DELETE FROM lahan WHERE id = ?')->execute([$lahanId]);
}

$left = $pdo->prepare('SELECT COUNT(*) FROM jadwal WHERE lahan_id = ?');
$left->execute([$lahanId]);
(int)$left->fetchColumn() === 0 || $fail('cascade delete left tasks behind');
echo "ok   deleting a field removes its seasons and tasks\n";

echo "\nintegration test passed\n";
