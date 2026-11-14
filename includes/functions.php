<?php
// Fungsi bantu umum: keamanan, pesan, tanggal.

const JENIS_KEGIATAN = [
    'Penanaman', 'Penyiraman', 'Pemupukan', 'Pengairan',
    'Penyiangan', 'Pengendalian Hama', 'Panen', 'Lainnya',
];
// Jenis yang boleh dipakai di template (Penanaman dan Panen dibuat otomatis).
const JENIS_TEMPLATE = ['Penyiraman', 'Pemupukan', 'Pengairan', 'Penyiangan', 'Pengendalian Hama', 'Lainnya'];

const STATUS_MUSIM = ['Berjalan', 'Selesai', 'Dibatalkan'];

const NAMA_BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const BULAN_PENDEK = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const NAMA_HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function e(mixed $s): string
{
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

// Hanya izinkan kembali ke halaman di aplikasi ini.
function aman_kembali(?string $url, string $default = 'index.php'): string
{
    return ($url !== null && preg_match('~^[a-z_]+\.php(\?[^\s<>"]*)?$~i', $url)) ? $url : $default;
}

function flash(string $jenis, string $pesan): void
{
    $_SESSION['flash'][] = [$jenis, $pesan];
}

function ambil_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function cek_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Formulir sudah kedaluwarsa. Kembali, muat ulang halaman, lalu coba lagi.');
    }
}

function pengguna(): ?array
{
    return $_SESSION['user'] ?? null;
}

// Dipanggil di awal setiap halaman yang butuh login.
function wajib_login(): void
{
    if (!pengguna()) {
        redirect('login.php');
    }
    cek_csrf();
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function post(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

// ---------- tanggal ----------

function hari_ini(): string
{
    return date('Y-m-d');
}

function tanggal_valid(string $s): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $s);
    return $d !== false && $d->format('Y-m-d') === $s;
}

function tambah_hari(string $tanggal, int $n): string
{
    return (new DateTime($tanggal))->modify(($n >= 0 ? '+' : '') . $n . ' days')->format('Y-m-d');
}

// Jumlah hari dari $dari ke $ke (bisa negatif).
function selisih_hari(string $dari, string $ke): int
{
    $diff = (new DateTime($dari))->diff(new DateTime($ke));
    return $diff->invert ? -$diff->days : $diff->days;
}

function tgl(?string $tanggal, bool $denganHari = false): string
{
    if (!$tanggal) {
        return '-';
    }
    $t = strtotime($tanggal);
    $s = date('j', $t) . ' ' . BULAN_PENDEK[(int)date('n', $t)] . ' ' . date('Y', $t);
    return $denganHari ? NAMA_HARI[(int)date('w', $t)] . ', ' . $s : $s;
}

function kelas_jenis(string $jenis): string
{
    return 'j-' . strtolower(str_replace(' ', '-', $jenis));
}

function format_luas(?string $m2): string
{
    if ($m2 === null || $m2 === '') {
        return '-';
    }
    $v = (float)$m2;
    if ($v >= 10000) {
        return rtrim(rtrim(number_format($v / 10000, 2, ',', '.'), '0'), ',') . ' ha';
    }
    return number_format($v, 0, ',', '.') . ' m²';
}
