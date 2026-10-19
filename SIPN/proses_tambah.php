<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/Koneksi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$isPublicRegistration = ($_SESSION['public_student_registration'] ?? false) === true;
unset($_SESSION['public_student_registration']);
if (!$isPublicRegistration) {
    require_once __DIR__ . '/Auth.php';
    wajib_role(['admin']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Daftar_user.php');
    exit;
}

$postText = static function ($key) {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};
$username = $postText('username');
$password = $_POST['password'] ?? '';
$role = $isPublicRegistration ? 'siswa' : $postText('role');
$onlyUser = !$isPublicRegistration && $postText('only_user') === '1';
$namaAdmin = $onlyUser ? $postText('nama_admin') : '';
$usernameDasarAdmin = '';

if ($onlyUser && $role === 'admin' && $namaAdmin !== '' && strlen($namaAdmin) <= 100) {
    $bagianNama = preg_split('/\s+/u', $namaAdmin, 2);
    $usernameDasarAdmin = strtolower(preg_replace('/[^a-z0-9]/', '', $bagianNama[0] ?? ''));
    $usernameDasarAdmin = substr($usernameDasarAdmin, 0, 46);
    $password = $_POST['password'] ?? '';
}

$targetForm = static function ($error) use ($isPublicRegistration, $onlyUser, $role) {
    if ($isPublicRegistration) {
        return 'Tambah_user.php?error=' . rawurlencode($error);
    }
    if ($onlyUser) {
        return 'Tambah_user2.php?error=' . rawurlencode($error);
    }
    return 'Tambah_user3.php?role=' . rawurlencode($role) . '&error=' . rawurlencode($error);
};
$fail = static function ($error) use ($targetForm) {
    header('Location: ' . $targetForm($error));
    exit;
};

$validAdminForm = $onlyUser
    && $role === 'admin'
    && $namaAdmin !== ''
    && strlen($namaAdmin) <= 100
    && $usernameDasarAdmin !== ''
    && is_string($password)
    && strlen($password) >= 6
    && strlen($password) <= 4096;
$validCredentials = $onlyUser
    ? $validAdminForm
    : (is_string($password) && $username !== '' && strlen($username) <= 50 && $password !== ''
        && strlen($password) <= 4096 && ($isPublicRegistration ? strlen($password) >= 8 : strlen($password) >= 6));

if (!$validCredentials || !in_array($role, ['siswa', 'guru', 'admin'], true)) {
    $fail('invalid');
}

$nis = $postText('nis');
$nip = $postText('nip');
$nama = $postText('nama');
$kelas = $postText('kelas');
$jenisKelamin = strtoupper($postText('jenis_kelamin'));

if ($role === 'siswa' && ($nis === '' || strlen($nis) > 10 || $nama === '' || strlen($nama) > 100
    || (!$isPublicRegistration && ($kelas === '' || strlen($kelas) > 10))
    || !in_array($jenisKelamin, ['L', 'P'], true))) {
    $fail('invalid');
}
if ($role === 'guru' && ($nip === '' || strlen($nip) > 20 || $nama === '' || strlen($nama) > 100
    || !in_array($jenisKelamin, ['L', 'P'], true))) {
    $fail('invalid');
}

if (!mysqli_begin_transaction($koneksi)) {
    $fail('failed');
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$userStmt = mysqli_prepare($koneksi, 'INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())');
if (!$userStmt) {
    mysqli_rollback($koneksi);
    $fail('failed');
}
mysqli_stmt_bind_param($userStmt, 'sss', $username, $passwordHash, $role);
$userSaved = false;
$errorCode = 0;
$attemptLimit = $onlyUser ? 10 : 1;
for ($attempt = 0; $attempt < $attemptLimit; $attempt++) {
    if ($onlyUser) {
        $username = $usernameDasarAdmin . random_int(1000, 9999);
    }
    $userSaved = mysqli_stmt_execute($userStmt);
    $errorCode = mysqli_stmt_errno($userStmt);
    if ($userSaved || $errorCode !== 1062) {
        break;
    }
}
mysqli_stmt_close($userStmt);

if (!$userSaved) {
    mysqli_rollback($koneksi);
    $fail($errorCode === 1062 ? 'duplicate' : 'failed');
}

$userId = (int) mysqli_insert_id($koneksi);
if ($role === 'siswa') {
    $profileStmt = mysqli_prepare($koneksi, 'INSERT INTO siswa (nis, nama, kelas, jenis_kelamin, user_id) VALUES (?, ?, ?, ?, ?)');
    if (!$profileStmt) {
        mysqli_rollback($koneksi);
        $fail('failed');
    }
    mysqli_stmt_bind_param($profileStmt, 'ssssi', $nis, $nama, $kelas, $jenisKelamin, $userId);
} elseif ($role === 'guru') {
    $profileStmt = mysqli_prepare($koneksi, 'INSERT INTO guru (nip, nama, jenis_kelamin, user_id) VALUES (?, ?, ?, ?)');
    if (!$profileStmt) {
        mysqli_rollback($koneksi);
        $fail('failed');
    }
    mysqli_stmt_bind_param($profileStmt, 'sssi', $nip, $nama, $jenisKelamin, $userId);
}

if ($role !== 'admin') {
    $profileSaved = mysqli_stmt_execute($profileStmt);
    $profileErrorCode = mysqli_stmt_errno($profileStmt);
    mysqli_stmt_close($profileStmt);
    if (!$profileSaved) {
        mysqli_rollback($koneksi);
        $fail($profileErrorCode === 1062 ? 'duplicate' : 'failed');
    }
}

if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    $fail('failed');
}

if ($isPublicRegistration) {
    header('Location: Login.php?registered=1');
    exit;
}

if ($onlyUser) {
    $pesanSukses = json_encode(
        "Akun Admin berhasil dibuat.\nUsername: $username",
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    echo "<script>alert($pesanSukses); window.location.href='Daftar_user.php';</script>";
    exit;
}

echo "<script>alert('Berhasil! Data $role tersimpan.'); window.location.href='Daftar_user.php';</script>";
exit;
