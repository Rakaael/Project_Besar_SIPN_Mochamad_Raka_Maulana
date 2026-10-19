<?php
// Proses simpan profil. Semua perubahan (users + siswa/guru) disimpan dalam satu transaksi.
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin', 'guru', 'siswa']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Profil.php');
    exit;
}

$role = $_SESSION['role'];
$userId = (int) $_SESSION['user_id'];
$usernameInput = $_POST['username'] ?? '';
$username = is_string($usernameInput) ? trim($usernameInput) : '';
$password = $_POST['password'] ?? '';
$profil = profil_saya($koneksi);
$fotoSementara = null;
$ekstensiFoto = null;

// Validasi tipe, ukuran, MIME, dan dimensi foto sebelum menerima unggahan.
$unggahanFoto = $_FILES['foto_profil'] ?? null;
if ($unggahanFoto !== null) {
    if (!is_array($unggahanFoto)) {
        header('Location: Edit_profil.php?error=photo_invalid');
        exit;
    }
    $errorUpload = (int) ($unggahanFoto['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorUpload !== UPLOAD_ERR_NO_FILE) {
        $jalurSementara = (string) ($unggahanFoto['tmp_name'] ?? '');
        $ukuranFoto = (int) ($unggahanFoto['size'] ?? 0);
        if ($errorUpload !== UPLOAD_ERR_OK || $ukuranFoto < 1 || $ukuranFoto > 3 * 1024 * 1024 || !is_uploaded_file($jalurSementara)) {
            header('Location: Edit_profil.php?error=photo_invalid');
            exit;
        }

        $infoFoto = @getimagesize($jalurSementara);
        $mimeFoto = (new finfo(FILEINFO_MIME_TYPE))->file($jalurSementara);
        $ekstensiDiizinkan = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $lebarFoto = (int) ($infoFoto[0] ?? 0);
        $tinggiFoto = (int) ($infoFoto[1] ?? 0);
        if (!is_array($infoFoto) || !isset($ekstensiDiizinkan[$mimeFoto]) || ($infoFoto['mime'] ?? '') !== $mimeFoto
            || $lebarFoto < 1 || $tinggiFoto < 1 || $lebarFoto > 8000 || $tinggiFoto > 8000 || $lebarFoto * $tinggiFoto > 20000000) {
            header('Location: Edit_profil.php?error=photo_invalid');
            exit;
        }
        $fotoSementara = $jalurSementara;
        $ekstensiFoto = $ekstensiDiizinkan[$mimeFoto];
    }
}

$valid = $username !== '' && strlen($username) <= 50 && is_string($password)
    && strlen($password) <= 4096 && ($password === '' || strlen($password) >= 6);
// Validasi field profil tambahan sesuai role, lalu simpan seluruh perubahan akun dalam transaksi.
if ($profil) {
    $namaInput = $_POST['nama'] ?? '';
    $jkInput = $_POST['jenis_kelamin'] ?? '';
    $nama = is_string($namaInput) ? trim($namaInput) : '';
    $jk = is_string($jkInput) ? strtoupper(trim($jkInput)) : '';
    $valid = $valid && $nama !== '' && strlen($nama) <= 100 && in_array($jk, ['L', 'P'], true);
}
if (!$valid) {
    header('Location: Edit_profil.php?error=invalid');
    exit;
}

if (!mysqli_begin_transaction($koneksi)) {
    header('Location: Edit_profil.php?error=failed');
    exit;
}
$hasil = jalankan($koneksi, 'UPDATE users SET username = ? WHERE id = ?', 'si', [$username, $userId]);
if ($hasil['ok'] && $password !== '') {
    $hasil = jalankan($koneksi, 'UPDATE users SET password = ? WHERE id = ?', 'si', [password_hash($password, PASSWORD_DEFAULT), $userId]);
}
if ($hasil['ok'] && $profil) {
    $hasil = $role === 'siswa'
        ? jalankan($koneksi, 'UPDATE siswa SET nama = ?, jenis_kelamin = ? WHERE id = ?', 'ssi', [$nama, $jk, (int) $profil['id']])
        : jalankan($koneksi, 'UPDATE guru SET nama = ?, jenis_kelamin = ? WHERE id = ?', 'ssi', [$nama, $jk, (int) $profil['id']]);
}

if (!$hasil['ok']) {
    mysqli_rollback($koneksi);
    header('Location: Edit_profil.php?error=' . ($hasil['errno'] === 1062 ? 'duplicate' : 'failed'));
    exit;
}
if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    header('Location: Edit_profil.php?error=failed');
    exit;
}

// Setelah data profil tersimpan, pindahkan foto baru dan bersihkan format foto lama.
if ($fotoSementara !== null && $ekstensiFoto !== null) {
    $direktoriFoto = dirname(__DIR__) . '/uploads/profil';
    if ((!is_dir($direktoriFoto) && !mkdir($direktoriFoto, 0755, true) && !is_dir($direktoriFoto))
        || !move_uploaded_file($fotoSementara, $direktoriFoto . '/' . $userId . '.' . $ekstensiFoto)) {
        header('Location: Edit_profil.php?error=photo_failed');
        exit;
    }
    foreach (['jpg', 'png', 'webp'] as $ekstensiLama) {
        if ($ekstensiLama !== $ekstensiFoto) {
            $fotoLama = $direktoriFoto . '/' . $userId . '.' . $ekstensiLama;
            if (is_file($fotoLama)) {
                unlink($fotoLama);
            }
        }
    }
}

$_SESSION['username'] = $username;
header('Location: Profil.php?status=updated');
exit;
