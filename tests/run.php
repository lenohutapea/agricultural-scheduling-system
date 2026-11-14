<?php
// Unit tests for the scheduling and helper logic. No framework needed:
//   php tests/run.php
// Exits with code 1 if any test fails, so CI marks the build red.
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/jadwal.php';

$passed = 0;
$failed = 0;

function check(string $name, mixed $actual, mixed $expected): void
{
    global $passed, $failed;
    if ($actual === $expected) {
        $passed++;
        echo "  ok   $name\n";
    } else {
        $failed++;
        echo "  FAIL $name\n       expected " . var_export($expected, true) . "\n       got      " . var_export($actual, true) . "\n";
    }
}

$pola = fn(int $mulai, int $ulang = 0, ?int $selesai = null) =>
    ['hari_mulai' => $mulai, 'ulangi_tiap_hari' => $ulang, 'hari_selesai' => $selesai];

echo "hari_template\n";
check('one-off task', hari_template($pola(7), 115), [7]);
check('repeat with end day', hari_template($pola(21, 21, 42), 115), [21, 42]);
check('repeat without end stops before harvest', hari_template($pola(1, 30), 100), [1, 31, 61, 91]);
check('end day is capped at day before harvest', hari_template($pola(90, 5, 500), 100), [90, 95]);
check('start after harvest gives nothing', hari_template($pola(120), 115), []);
check('negative start gives nothing', hari_template($pola(-1), 115), []);
check('end day as empty string means no end', hari_template(['hari_mulai' => 95, 'ulangi_tiap_hari' => 2, 'hari_selesai' => ''], 100), [95, 97, 99]);

echo "bundled rice pattern produces 29 tasks\n";
$padi = [$pola(1, 7, 100), $pola(7), $pola(25), $pola(45), $pola(21, 21, 42), $pola(14, 14, 98)];
$total = 2; // planting + harvest
foreach ($padi as $p) {
    $total += count(hari_template($p, 115));
}
check('rice season task count', $total, 29);

echo "ringkas_hari\n";
check('single day', ringkas_hari([7]), 'Hari ke-7');
check('long list is shortened', ringkas_hari([1, 8, 15, 22, 29, 36]), 'Hari ke-1, 8, 15, 22, …, 36 (6 kali)');

echo "dates\n";
check('add days across month end', tambah_hari('2026-01-30', 3), '2026-02-02');
check('add days across leap day', tambah_hari('2028-02-28', 1), '2028-02-29');
check('subtract days', tambah_hari('2026-03-01', -1), '2026-02-28');
check('rice harvest estimate', tambah_hari('2027-03-01', 115), '2027-06-24');
check('difference forward', selisih_hari('2026-11-01', '2026-11-14'), 13);
check('difference backward', selisih_hari('2026-11-14', '2026-11-01'), -13);
check('valid date', tanggal_valid('2026-02-28'), true);
check('invalid day rejected', tanggal_valid('2026-02-30'), false);
check('wrong format rejected', tanggal_valid('14/11/2026'), false);
check('indonesian date format', tgl('2026-11-14', true), 'Sabtu, 14 Nov 2026');

echo "security helpers\n";
check('escapes html', e('<script>"x"</script>'), '&lt;script&gt;&quot;x&quot;&lt;/script&gt;');
check('allows local return url', aman_kembali('jadwal.php?rentang=semua'), 'jadwal.php?rentang=semua');
check('blocks external return url', aman_kembali('https://evil.example/'), 'index.php');
check('blocks protocol-relative url', aman_kembali('//evil.example/x.php'), 'index.php');
check('blocks path traversal', aman_kembali('../x.php'), 'index.php');

echo "formatting\n";
check('area in m2', format_luas('2500'), '2.500 m²');
check('area in hectares', format_luas('12000'), '1,2 ha');
check('activity css class', kelas_jenis('Pengendalian Hama'), 'j-pengendalian-hama');

echo "\n$passed passed, $failed failed\n";
exit($failed > 0 ? 1 : 0);
