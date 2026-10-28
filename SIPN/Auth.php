<?php
// Fungsi umum untuk login, hak akses, query database, dan tampilan halaman.
mysqli_report(MYSQLI_REPORT_OFF);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Tentukan alamat aplikasi agar redirect tetap benar dari subfolder.
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

// Samakan format role sebelum dibandingkan.
function normalisasi_role($role)
{
    $nilai = strtolower(trim((string) $role));
    $nilai = preg_replace('/[^a-z0-9]+/u', '', $nilai);
    return $nilai;
}

// Cek apakah role pengguna termasuk role yang diizinkan.
function cocok_role($role, array $izin)
{
    $nilai = normalisasi_role($role);
    if ($nilai === '') {
        return false;
    }
    foreach ($izin as $target) {
        $targetNorm = normalisasi_role($target);
        // Role harus sama persis. Guru & Kelas tetap dianggap sebagai guru.
        $guruKelas = $targetNorm === 'guru' && $nilai === 'gurukelas';
        if ($targetNorm !== '' && ($nilai === $targetNorm || $guruKelas)) {
            return true;
        }
    }
    return false;
}

// Ubah kode role menjadi teks yang ditampilkan di halaman.
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

// Batasi akses halaman berdasarkan role pengguna.
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

// Escape output sebelum ditampilkan ke HTML.
function h($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function token_csrf()
{
    // Buat token sekali per sesi untuk melindungi form dari CSRF.
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_valid($token)
{
    // Bandingkan token form dengan token yang tersimpan di sesi.
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// Jalankan query SELECT dan kembalikan hasilnya sebagai array.
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

// Jalankan query perubahan data dan kembalikan statusnya.
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

// Ambil profil siswa atau guru yang sedang login.
function profil_saya($koneksi)
{
    $role = normalisasi_role($_SESSION['role'] ?? '');
    if (!in_array($role, ['siswa', 'guru'], true)) {
        return null;
    }
    $baris = ambil($koneksi, "SELECT * FROM $role WHERE user_id = ? LIMIT 1", 'i', [(int) $_SESSION['user_id']]);
    return $baris[0] ?? null;
}

// Cari file foto profil berdasarkan ID user.
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

// Buat URL foto profil dengan versi file untuk menghindari cache lama.
function foto_profil_url($userId)
{
    $jalurFoto = foto_profil_path($userId);
    if ($jalurFoto === null) {
        return null;
    }
    return APP_URL . '/PROFIL/Foto.php?v=' . (int) filemtime($jalurFoto);
}              

// Tentukan keterangan nilai berdasarkan rata-rata dan KKM.
function hitung_keterangan($kkm, $pengetahuan, $keterampilan)
{
    return (($pengetahuan + $keterampilan) / 2) >= $kkm ? 'Tuntas' : 'Belum Tuntas';
}

// Validasi nilai agar berada pada rentang 0 sampai 100.
function nilai_valid($nilai)
{
    $angka = filter_var($nilai, FILTER_VALIDATE_INT);
    return ($angka !== false && $angka >= 0 && $angka <= 100) ? $angka : null;
}

// Tampilkan pesan berhasil atau gagal dari parameter URL.
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
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('sipn-theme');
                if (theme === 'dark' || theme === 'light') document.documentElement.setAttribute('data-theme', theme);
            } catch (error) {}
        }());
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= h(APP_URL); ?>/assets/css/navbar.css?v=13" rel="stylesheet">
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
        /* Final ocean palette for every authenticated role page. */
        body { background: linear-gradient(rgba(8,126,164,.075) 1px, transparent 1px), linear-gradient(90deg, rgba(8,126,164,.075) 1px, transparent 1px), radial-gradient(ellipse at 8% 0%, rgba(34,184,199,.18), transparent 34rem), radial-gradient(ellipse at 96% 14%, rgba(17,112,177,.14), transparent 30rem), linear-gradient(180deg, #eaf7fb 0%, #f8fcfd 48%, #eef7fa 100%) !important; background-size: 36px 36px, 36px 36px, auto, auto, auto !important; color: #153b56 !important; }
        .judul { color: #064c75 !important; }
        .judul::after { background: linear-gradient(90deg, #06618f, #18b7c5, #72d6df) !important; }
        .kartu { background: linear-gradient(145deg, rgba(255,255,255,.98), rgba(239,249,252,.97)) !important; border-color: #cfe4ed !important; box-shadow: 0 12px 30px rgba(8,91,132,.1) !important; }
        .stat.bg-primary, .dashboard-page .stat.bg-primary { background: linear-gradient(135deg, #06466f, #087ea4) !important; }
        .stat.bg-success, .dashboard-page .stat.bg-success { background: linear-gradient(135deg, #087ea4, #22b8c7) !important; }
        .stat.bg-warning, .dashboard-page .stat.bg-warning { background: linear-gradient(135deg, #087093, #39c4ca) !important; }
        .stat.bg-danger, .dashboard-page .stat.bg-danger { background: linear-gradient(135deg, #06466f, #087ea4) !important; }
        .stat.bg-info, .dashboard-page .stat.bg-info { background: linear-gradient(135deg, #087ea4, #22b8c7) !important; }
        .stat.bg-secondary, .dashboard-page .stat.bg-secondary { background: linear-gradient(135deg, #346b88, #71b9c6) !important; }
        .table thead th { background: linear-gradient(105deg, #063b63 0%, #07658d 35%, #0892ad 68%, #19b7c2 100%) !important; }
        .btn-primary { --bs-btn-bg: #087ea4; --bs-btn-border-color: #087ea4; --bs-btn-hover-bg: #06567e; --bs-btn-hover-border-color: #06567e; background-image: linear-gradient(115deg, #064c75, #087ea4 55%, #22b8c7) !important; border-color: #087ea4 !important; }
        .form-control, .form-select { border-color: #bddbe7 !important; background: linear-gradient(180deg, #fff, #f1fafd) !important; }
        .form-control:focus, .form-select:focus { border-color: #15aabe !important; box-shadow: 0 0 0 .2rem rgba(21,170,190,.16) !important; }
        .profile-photo-fallback { color: #087ea4 !important; background: #e0f5f8 !important; }
        .alert-info { color: #075d78 !important; background: #e5f7fa !important; border-color: #b9e1e8 !important; }
        html[data-theme="dark"] body { background: #101b24 !important; color: #dceaf0 !important; }
        html[data-theme="dark"] .kartu, html[data-theme="dark"] main.container > form.row.g-3 { background: linear-gradient(145deg, #1c303b, #172832) !important; border-color: #355460 !important; color: #dceaf0 !important; }
        html[data-theme="dark"] .judul { color: #9de5eb !important; }
        html[data-theme="dark"] .form-label, html[data-theme="dark"] .kartu table th, html[data-theme="dark"] .form-check-label { color: #b9d9df !important; }
        html[data-theme="dark"] .form-control, html[data-theme="dark"] .form-select { background: #13232c !important; border-color: #416572 !important; color: #e4f2f4 !important; }
        html[data-theme="dark"] .table { --bs-table-bg: #1b2d37; --bs-table-color: #d5e8ec; --bs-table-striped-bg: #203640; --bs-table-hover-bg: #29454f; background: #1b2d37 !important; border-color: #3b5a64 !important; }
        html[data-theme="dark"] .table > :not(caption) > * > * { background: #1b2d37 !important; background-image: none !important; color: #d5e8ec !important; border-color: #34515c !important; }
        html[data-theme="dark"] .table.table-striped > tbody > tr:nth-of-type(odd) > * > * { background: #1b2d37 !important; color: #d5e8ec !important; }
        html[data-theme="dark"] .table.table-striped > tbody > tr:nth-of-type(even) > * > * { background: #1b2d37 !important; color: #d5e8ec !important; }
        html[data-theme="dark"] .table.table-hover > tbody > tr:hover > * > * { background: #29454f !important; color: #f2fbfc !important; }
        html[data-theme="dark"] .dataTables_wrapper label, html[data-theme="dark"] .dataTables_wrapper .dataTables_info, html[data-theme="dark"] .dataTables_wrapper .dataTables_length, html[data-theme="dark"] .dataTables_wrapper .dataTables_filter { color: #c7e0e5 !important; }
        html[data-theme="dark"] .dataTables_wrapper .dataTables_filter input, html[data-theme="dark"] .dataTables_wrapper .dataTables_length select { color: #e4f2f4 !important; background: #13232c !important; }
        html[data-theme="dark"] .dataTables_wrapper .page-link { background: #1b303a; border-color: #3b5a64; color: #9de5eb !important; }
        html[data-theme="dark"] .dataTables_wrapper .page-item.disabled .page-link { background: #15262f; color: #76939b !important; }
        html[data-theme="dark"] .dropdown-menu { background: #1b303a !important; border-color: #3b5a64 !important; }
        html[data-theme="dark"] .dropdown-item { color: #d5e8ec !important; }
        html[data-theme="dark"] .dropdown-item:hover, html[data-theme="dark"] .dropdown-item:focus { background: #2b4b56 !important; color: #fff !important; }
        html[data-theme="dark"] .text-secondary { color: #9bb7bf !important; }
        html[data-theme="dark"] a, html[data-theme="dark"] .register-link { color: #83d9e3; }
        html[data-theme="dark"] table.table.table-striped tbody tr td, html[data-theme="dark"] table.table.table-striped tbody tr th { background: #111a20 !important; background-image: none !important; color: #f1fbfd !important; border-color: #3d5d66 !important; }
        html[data-theme="dark"] .card, html[data-theme="dark"] .wrapper, html[data-theme="dark"] .kartu, html[data-theme="dark"] main.container > form.row.g-3 { background: #294652 !important; border: 2px solid #79aeb9 !important; color: #f1fbfd !important; box-shadow: 0 14px 34px rgba(0,0,0,.42), 0 0 0 1px rgba(164,220,226,.1) !important; }
        html[data-theme="dark"] .wrapper { padding: 30px !important; }
        html[data-theme="dark"] .form-control, html[data-theme="dark"] .form-select { background: #152831 !important; border: 1px solid #6c9da8 !important; color: #f5feff !important; }
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
