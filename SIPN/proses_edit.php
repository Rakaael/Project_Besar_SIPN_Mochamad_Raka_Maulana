<?php
require_once __DIR__ . '/Koneksi.php';
require_once __DIR__ . '/Auth.php';
wajib_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Daftar_user.php');
    exit;
}

$postText = static function ($key) {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$role = $postText('role');
$nama = $postText('nama');
$username = $postText('username');
$password = $_POST['password'] ?? '';
$jenisKelamin = strtoupper($postText('jenis_kelamin'));
$nis = $postText('nis');
$nip = $postText('nip');
$kelas = $postText('kelas');

$fail = static function ($error) use ($id) {
    $url = 'Daftar_user.php?error=invalid';
    if ($id && $id > 0) {
        $url = 'edit_user.php?id=' . (int) $id . '&error=' . rawurlencode($error);
    }
    header('Location: ' . $url);
    exit;
};

if (!$id || $id < 1 || !in_array($role, ['siswa', 'guru', 'admin'], true) || $username === ''
    || strlen($username) > 50 || !is_string($password) || strlen($password) > 4096
    || ($password !== '' && strlen($password) < 6)) {
    $fail('invalid');
}
if ($role !== 'admin' && ($nama === '' || strlen($nama) > 100 || !in_array($jenisKelamin, ['L', 'P'], true))) {
    $fail('invalid');
}
if ($role === 'siswa' && ($nis === '' || strlen($nis) > 10 || $kelas === '' || strlen($kelas) > 10)) {
    $fail('invalid');
}
if ($role === 'guru' && ($nip === '' || strlen($nip) > 20)) {
    $fail('invalid');
}

if (!mysqli_begin_transaction($koneksi)) {
    $fail('failed');
}

$userRows = ambil($koneksi, 'SELECT role FROM users WHERE id = ? FOR UPDATE', 'i', [$id]);
if (!$userRows) {
    mysqli_rollback($koneksi);
    $fail('not_found');
}
$oldRole = (string) $userRows[0]['role'];

if ($oldRole !== $role && $oldRole === 'siswa') {
    $student = ambil($koneksi, 'SELECT id FROM siswa WHERE user_id = ? FOR UPDATE', 'i', [$id])[0] ?? null;
    if ($student) {
        $studentId = (int) $student['id'];
        $grades = (int) (ambil($koneksi, 'SELECT COUNT(*) AS jumlah FROM nilai_rapor WHERE siswa_id = ?', 'i', [$studentId])[0]['jumlah'] ?? 0);
        $assignments = (int) (ambil($koneksi, 'SELECT COUNT(*) AS jumlah FROM mengajar WHERE siswa_id = ?', 'i', [$studentId])[0]['jumlah'] ?? 0);
        if ($grades > 0 || $assignments > 0) {
            mysqli_rollback($koneksi);
            $fail('role_in_use');
        }
    }
}

if ($oldRole !== $role && $oldRole === 'guru') {
    $teacher = ambil($koneksi, 'SELECT id FROM guru WHERE user_id = ? FOR UPDATE', 'i', [$id])[0] ?? null;
    if ($teacher) {
        $subjects = (int) (ambil($koneksi, 'SELECT COUNT(*) AS jumlah FROM mapel WHERE guru_id = ?', 'i', [(int) $teacher['id']])[0]['jumlah'] ?? 0);
        if ($subjects > 0) {
            mysqli_rollback($koneksi);
            $fail('role_in_use');
        }
    }
}

$userValues = [$username, $role, $id];
$userTypes = 'ssi';
$userSql = 'UPDATE users SET username = ?, role = ? WHERE id = ?';
if ($password !== '') {
    $userSql = 'UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?';
    $userTypes = 'sssi';
    $userValues = [$username, password_hash($password, PASSWORD_DEFAULT), $role, $id];
}
$result = jalankan($koneksi, $userSql, $userTypes, $userValues);
if (!$result['ok']) {
    mysqli_rollback($koneksi);
    $fail($result['errno'] === 1062 ? 'duplicate' : 'failed');
}

if ($oldRole === 'siswa' && $role !== 'siswa') {
    $result = jalankan($koneksi, 'DELETE FROM siswa WHERE user_id = ?', 'i', [$id]);
} elseif ($oldRole === 'guru' && $role !== 'guru') {
    $result = jalankan($koneksi, 'DELETE FROM guru WHERE user_id = ?', 'i', [$id]);
}
if (isset($result) && !$result['ok']) {
    mysqli_rollback($koneksi);
    $fail('failed');
}

if ($role === 'siswa') {
    $student = ambil($koneksi, 'SELECT id FROM siswa WHERE user_id = ? LIMIT 1', 'i', [$id])[0] ?? null;
    $result = $student
        ? jalankan($koneksi, 'UPDATE siswa SET nis = ?, nama = ?, kelas = ?, jenis_kelamin = ? WHERE user_id = ?', 'ssssi', [$nis, $nama, $kelas, $jenisKelamin, $id])
        : jalankan($koneksi, 'INSERT INTO siswa (nis, nama, kelas, jenis_kelamin, user_id) VALUES (?, ?, ?, ?, ?)', 'ssssi', [$nis, $nama, $kelas, $jenisKelamin, $id]);
} elseif ($role === 'guru') {
    $teacher = ambil($koneksi, 'SELECT id FROM guru WHERE user_id = ? LIMIT 1', 'i', [$id])[0] ?? null;
    $result = $teacher
        ? jalankan($koneksi, 'UPDATE guru SET nip = ?, nama = ?, jenis_kelamin = ? WHERE user_id = ?', 'sssi', [$nip, $nama, $jenisKelamin, $id])
        : jalankan($koneksi, 'INSERT INTO guru (nip, nama, jenis_kelamin, user_id) VALUES (?, ?, ?, ?)', 'sssi', [$nip, $nama, $jenisKelamin, $id]);
}

if (isset($result) && !$result['ok']) {
    mysqli_rollback($koneksi);
    $fail($result['errno'] === 1062 ? 'duplicate' : 'failed');
}
if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    $fail('failed');
}

if ((int) ($_SESSION['user_id'] ?? 0) === (int) $id) {
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    if ($oldRole !== $role) {
        session_regenerate_id(true);
    }
}

$redirect = (int) ($_SESSION['user_id'] ?? 0) === (int) $id && $oldRole !== $role
    ? 'Dashboard.php'
    : 'Daftar_user2.php';
echo "<script>alert('Data user berhasil diubah!'); window.location.href='" . $redirect . "';</script>";
exit;
