<?php
// File ini berisi fungsi bantu yang dipakai halaman baru: cek login & role, ambil data, dan kerangka tampilan.
// Cara pakai di awal halaman: require_once 'Koneksi.php'; require_once 'Auth.php'; wajib_role(['admin']);
mysqli_report(MYSQLI_REPORT_OFF);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Alamat folder SIPN (otomatis), supaya redirect benar dari folder mana pun.
// DOCUMENT_ROOT tidak selalu tersedia (misalnya saat CLI atau server tertentu).
$appPath = str_replace('\\', '/', realpath(__DIR__) ?: __DIR__);
$documentRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
$docPath = str_replace('\\', '/', realpath($documentRoot) ?: $documentRoot);
$appUrl = '';
if ($docPath !== '') {
    $rootPrefix = rtrim($docPath, '/') . '/';
    if (stripos($appPath, $rootPrefix) === 0) {
        $appUrl = '/' . ltrim(substr($appPath, strlen($rootPrefix)), '/');
    }
}
if (!defined('APP_URL')) {
    define('APP_URL', rtrim($appUrl, '/'));
}

// Normalisasi role agar form seperti 'guru&kelas' atau 'Guru & Kelas' tetap diperlakukan sebagai guru.
function normalisasi_role($role)
{
    $nilai = strtolower(trim((string) $role));
    $nilai = preg_replace('/[^a-z0-9]+/u', '', $nilai);
    return $nilai;
}

// Cocokkan role sesi dengan daftar role yang berhak membuka halaman.
function cocok_role($role, array $izin)
{
    $nilai = normalisasi_role($role);
    if ($nilai === '') {
        return false;
    }
    foreach ($izin as $target) {
        $targetNorm = normalisasi_role($target);
        if ($targetNorm !== '' && ($nilai === $targetNorm || str_contains($nilai, $targetNorm))) {
            return true;
        }
    }
    return false;
}

// Ubah nilai role internal menjadi label yang lebih mudah dibaca.
function label_role($role)
{
    $nilai = normalisasi_role($role);
    $map = [
        'admin' => 'Admin',
        'guru' => 'Guru',
        'gurukelas' => 'Guru & Kelas',
        'siswa' => 'Siswa',
    ];
    return $map[$nilai] ?? ucfirst((string) $role);
}

// Membatasi halaman hanya untuk role tertentu. Belum login -> ke Login, salah role -> ke Dashboard.
function wajib_role(array $roles)
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/Login.php?error=required');
        exit;
    }
    if (!cocok_role($_SESSION['role'] ?? '', $roles)) {
        header('Location: ' . APP_URL . '/Dashboard.php');
        exit;
    }
}

// Singkatan htmlspecialchars agar aman dari XSS.
function h($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function token_csrf()
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_valid($token)
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// Menjalankan SELECT (prepared statement) dan mengembalikan semua baris sebagai array.
function ambil($koneksi, $sql, $tipe = '', $param = [])
{
    $param = is_array($param) ? array_values($param) : [];
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        die('Query gagal: ' . mysqli_error($koneksi));
    }
    if ($tipe !== '') {
        if (!is_string($tipe) || strlen($tipe) !== count($param)) {
            mysqli_stmt_close($stmt);
            die('Parameter query tidak valid.');
        }
        if (!mysqli_stmt_bind_param($stmt, $tipe, ...$param)) {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            die('Parameter query tidak valid: ' . $error);
        }
    }
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        die('Query gagal dijalankan: ' . $error);
    }
    $hasil = mysqli_stmt_get_result($stmt);
    if (!$hasil) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        die('Hasil query gagal dibaca: ' . $error);
    }
    $baris = mysqli_fetch_all($hasil, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $baris;
}

// Menjalankan INSERT/UPDATE/DELETE (prepared statement). Mengembalikan ok, errno, affected.
function jalankan($koneksi, $sql, $tipe = '', $param = [])
{
    $param = is_array($param) ? array_values($param) : [];
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        return ['ok' => false, 'errno' => mysqli_errno($koneksi), 'affected' => 0];
    }
    if ($tipe !== '') {
        if (!is_string($tipe) || strlen($tipe) !== count($param)) {
            mysqli_stmt_close($stmt);
            return ['ok' => false, 'errno' => 0, 'affected' => 0];
        }
        if (!mysqli_stmt_bind_param($stmt, $tipe, ...$param)) {
            $hasil = ['ok' => false, 'errno' => mysqli_stmt_errno($stmt), 'affected' => 0];
            mysqli_stmt_close($stmt);
            return $hasil;
        }
    }
    $ok = mysqli_stmt_execute($stmt);
    $hasil = ['ok' => $ok, 'errno' => mysqli_stmt_errno($stmt), 'affected' => mysqli_stmt_affected_rows($stmt)];
    mysqli_stmt_close($stmt);
    return $hasil;
}

// Profil siswa/guru milik user yang sedang login (admin tidak punya profil -> null).
function profil_saya($koneksi)
{
    $role = normalisasi_role($_SESSION['role'] ?? '');
    if (!in_array($role, ['siswa', 'guru'], true)) {
        return null;
    }
    $baris = ambil($koneksi, "SELECT * FROM $role WHERE user_id = ? LIMIT 1", 'i', [(int) $_SESSION['user_id']]);
    return $baris[0] ?? null;
}

// Temukan berkas foto profil berdasarkan ID user dan ekstensi yang didukung.
function foto_profil_path($userId)
{
    $userId = (int) $userId;
    if ($userId <= 0) {
        return null;
    }
    foreach (['jpg', 'png', 'webp'] as $ekstensi) {
        $jalurFoto = __DIR__ . '/uploads/profil/' . $userId . '.' . $ekstensi;
        if (is_file($jalurFoto)) {
            return $jalurFoto;
        }
    }
    return null;
}

// Bentuk URL foto profil dan tambahkan versi berkas agar cache ikut diperbarui.
function foto_profil_url($userId)
{
    $jalurFoto = foto_profil_path($userId);
    if ($jalurFoto === null) {
        return null;
    }
    return APP_URL . '/PROFIL/Foto.php?v=' . (int) filemtime($jalurFoto);
}              

// Keterangan otomatis: Tuntas jika rata-rata nilai pengetahuan & keterampilan >= KKM.
function hitung_keterangan($kkm, $pengetahuan, $keterampilan)
{
    return (($pengetahuan + $keterampilan) / 2) >= $kkm ? 'Tuntas' : 'Belum Tuntas';
}

// Membaca nilai 0-100 dari input; mengembalikan null jika tidak valid.
function nilai_valid($nilai)
{
    $angka = filter_var($nilai, FILTER_VALIDATE_INT);
    return ($angka !== false && $angka >= 0 && $angka <= 100) ? $angka : null;
}

// Menampilkan alert status/error dari parameter URL (?status=... atau ?error=...).
function notifikasi(array $statusPesan, array $errorPesan)
{
    $s = $statusPesan[$_GET['status'] ?? ''] ?? null;
    $e = $errorPesan[$_GET['error'] ?? ''] ?? null;
    if ($s) echo '<div class="alert alert-success" role="status">' . h($s) . '</div>';
    if ($e) echo '<div class="alert alert-danger" role="alert">' . h($e) . '</div>';
}

// Kerangka <head>. $sub = true untuk halaman di dalam subfolder (memakai <base href="../">).
function kepala($judul, $sub = false, $datatables = false)
{ ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($sub) { ?><base href="../"><?php } ?>
    <title><?= h($judul); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= h(APP_URL); ?>/assets/css/navbar.css?v=5" rel="stylesheet">
    <?php if ($datatables) { ?><link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"><?php } ?>
    <style>
        body { background: radial-gradient(ellipse at 92% 2%, rgba(28,157,119,0.12), transparent 27rem), linear-gradient(180deg, #eaf6ef 0, #f4f8f4 260px, #f8faf7 100%); color: #173f35; min-height: 100vh; font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif; -webkit-font-smoothing: antialiased; }
        .table-container { max-width: 1600px; margin-inline: auto; padding: 2rem clamp(1rem, 4vw, 3.5rem); }
        .judul { color: #075846; text-align: center; font-size: 42px; font-weight: 750; letter-spacing: -0.035em; margin-top: 20px; margin-bottom: 40px; }
        .judul::after { content: ""; display: block; width: 56px; height: 4px; margin: 14px auto 0; border-radius: 4px; background: linear-gradient(90deg, #08785d, #19a0ad); box-shadow: 0 0 10px rgba(25,160,173,0.45); }
        .kartu { background: #fff; border: 1px solid #d4e8df; border-radius: 14px; box-shadow: 0 12px 32px rgba(16, 64, 130, 0.09), 0 0 0 1px rgba(25,160,173,0.035); padding: 24px; }
        .profile-photo { display: inline-flex; align-items: center; justify-content: center; width: 5rem; height: 5rem; border: 3px solid #fff; border-radius: 50%; object-fit: cover; box-shadow: 0 3px 12px rgba(16, 64, 130, 0.18); }
        .profile-photo[hidden] { display: none !important; }
        .profile-photo-fallback { color: #08785d; background: #e3f4eb; font-size: 1.75rem; font-weight: 700; }
        .profile-photo-clickable { cursor: pointer; transition: transform 0.18s ease, box-shadow 0.18s ease; }
        .profile-photo-clickable:hover { transform: scale(1.04); }
        .profile-photo-zoom-modal { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background: rgba(6, 19, 37, 0.8); padding: 1.25rem; }
        .profile-photo-zoom-modal.hidden { display: none; }
        .profile-photo-zoom-modal img { max-width: min(90vw, 640px); max-height: 85vh; border-radius: 1rem; background: #fff; object-fit: contain; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3); }
        .profile-photo-zoom-close { position: absolute; top: 1rem; right: 1rem; border: 0; width: 2.75rem; height: 2.75rem; border-radius: 50%; font-size: 1.6rem; line-height: 1; background: rgba(255, 255, 255, 0.9); color: #075846; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12); cursor: pointer; }
        .stat { color: #fff; border: 0; border-radius: 14px; padding: 22px; box-shadow: 0 8px 20px rgba(11, 61, 145, 0.16), 0 0 14px rgba(25,160,173,0.08); transition: transform 160ms ease, box-shadow 160ms ease; }
        .stat:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(11, 61, 145, 0.22), 0 0 18px rgba(25,160,173,0.16); }
        .stat small { font-weight: 600; opacity: 0.9; color: #fff !important; }
        .stat h2 { margin: 4px 0 0; font-weight: 700; color: #fff !important; }
        .stat.bg-primary { background: linear-gradient(135deg, #075846, #0c8063) !important; }
        .stat.bg-success { background: linear-gradient(135deg, #08785d, #159879) !important; }
        .stat.bg-warning { background: linear-gradient(135deg, #0b6a52, #17896b) !important; }
        .stat.bg-danger { background: linear-gradient(135deg, #075846, #16866a) !important; }
        .stat.bg-info { background: linear-gradient(135deg, #08785d, #2aa889) !important; }
        .stat.bg-secondary { background: linear-gradient(135deg, #356b5b, #63917f) !important; }
        .table { --bs-table-bg: #fff; --bs-table-striped-bg: #eff8f3; }
        .table thead th { background: linear-gradient(100deg, #08785d, #0c8063 72%, #19a0ad); color: #fff; border-color: rgba(255,255,255,0.12); }
        .btn-primary { --bs-btn-bg: #08785d; --bs-btn-border-color: #08785d; --bs-btn-hover-bg: #083779; --bs-btn-hover-border-color: #083779; }
        .form-control, .form-select { min-height: 2.75rem; border-radius: 8px; transition: border-color 140ms ease, box-shadow 140ms ease, background-color 140ms ease; }
        .form-control:focus, .form-select:focus { border-color: #2aa889; box-shadow: 0 0 0 0.22rem rgba(36,112,196,0.14), 0 0 12px rgba(25,160,173,0.12); }
        .btn { border-radius: 8px; font-weight: 650; transition: transform 140ms ease, box-shadow 140ms ease; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(11,74,162,0.16); }
        .btn:focus-visible, a:focus-visible, button:focus-visible { outline: 3px solid rgba(25,160,173,0.55); outline-offset: 2px; }
        .table tbody td, .table tbody th { padding-block: 0.8rem; vertical-align: middle; }
        .table tbody tr { transition: background-color 120ms ease; }
        .table tbody tr:hover > * { --bs-table-bg-state: #e6f5ed; }
        .alert { border-radius: 9px; }
        .alert { border-width: 1px 1px 1px 4px; box-shadow: 0 4px 14px rgba(16,64,130,0.045); }
        .alert-success { border-left-color: #36a77b; }
        .alert-danger { border-left-color: #d05b6c; }
        .alert-info { border-left-color: #3199b0; }
        .form-label { margin-bottom: 0.45rem; }
        .form-control::placeholder { color: #8799ad; opacity: 1; }
        .form-control:disabled, .form-select:disabled { background-color: #edf2f8; color: #71839a; cursor: not-allowed; }
        .form-check-input { border-color: #aebfd3; }
        .form-check-input:checked { background-color: #08785d; border-color: #08785d; }
        .form-check-input:focus { border-color: #2aa889; box-shadow: 0 0 0 0.2rem rgba(36,112,196,0.14), 0 0 10px rgba(25,160,173,0.12); }
        .form-check-label { color: #405b7b; cursor: pointer; }
        .table-container { overflow-x: auto; }
        .table-container .table { border-collapse: separate; border-spacing: 0; }
        .table-container .table thead th:first-child { border-top-left-radius: 10px; }
        .table-container .table thead th:last-child { border-top-right-radius: 10px; }
        .table-container .table tbody tr:last-child > * { border-bottom-width: 0; }
        .table-container .table .btn { margin-block: 0.1rem; }
        .dataTables_wrapper .dataTables_filter input { min-width: min(14rem, 55vw); }
        .dataTables_wrapper .page-link { min-width: 2.25rem; text-align: center; }
        .dataTables_wrapper .page-link:focus { box-shadow: 0 0 0 0.18rem rgba(36,112,196,0.15); }
        .kartu table tr:last-child > * { border-bottom: 0; }
        .kartu table th { color: #24466f; font-weight: 650; }
        .kartu table td { color: #526b88; }
        .profile-photo { transition: transform 180ms ease, box-shadow 180ms ease; }
        .profile-photo-clickable:hover { box-shadow: 0 6px 18px rgba(16,64,130,0.22), 0 0 14px rgba(25,160,173,0.2); }
        main.container > form.row.g-3 { max-width: 980px; margin-inline: auto; padding: 1.5rem; border: 1px solid #d4e8df; border-radius: 14px; background: rgba(255,255,255,0.96); box-shadow: 0 12px 32px rgba(16,64,130,0.09); }
        @media (max-width: 768px) {
            .table-container { padding: 1.5rem 1rem; }
            .judul { font-size: 32px; margin-bottom: 24px; }
            .stat { padding: 18px; }
            main.container > form.row.g-3 { padding: 1.1rem; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; } }
    </style>
</head>
<body>
<?php }

// Script DataTables untuk tabel dengan id tertentu. $kolomAksi = indeks kolom tombol (tidak bisa diurutkan), -1 jika tidak ada.
function skrip_tabel($id, $kolomAksi = -1)
{ ?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function () {
    $('#<?= $id; ?>').DataTable({
        pageLength: 10,
        columnDefs: <?= $kolomAksi >= 0 ? '[{ orderable: false, targets: ' . (int) $kolomAksi . ' }]' : '[]'; ?>,
        language: {
            lengthMenu: 'Tampilkan _MENU_ data per halaman',
            search: '',
            searchPlaceholder: 'Search',
            zeroRecords: 'Data tidak ditemukan',
            info: 'Menampilkan halaman _PAGE_ dari _PAGES_',
            infoEmpty: 'Menampilkan halaman 0 dari 0',
            paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' }
        }
    });
});
</script>
<?php }
