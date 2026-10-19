<?php
// Terima pendaftaran siswa publik hanya melalui formulir POST dengan data yang valid.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Login.php');
    exit;
}

$usernameInput = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$nisInput = $_POST['nis'] ?? '';
$namaInput = $_POST['nama'] ?? '';
$jenisKelaminInput = $_POST['jenis_kelamin'] ?? '';
$username = is_string($usernameInput) ? trim($usernameInput) : '';
$nis = is_string($nisInput) ? trim($nisInput) : '';
$nama = is_string($namaInput) ? trim($namaInput) : '';
$jenisKelamin = is_string($jenisKelaminInput) ? strtoupper(trim($jenisKelaminInput)) : '';

// Validasi batas panjang dan pilihan jenis kelamin sebelum meneruskan data ke proses simpan.
$isValid = $username !== ''
    && strlen($username) <= 50
    && is_string($password)
    && strlen($password) >= 8
    && strlen($password) <= 4096
    && $nis !== ''
    && strlen($nis) <= 10
    && $nama !== ''
    && strlen($nama) <= 100
    && in_array($jenisKelamin, ['L', 'P'], true);

if (!$isValid) {
    header('Location: Tambah_user.php?error=invalid');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['public_student_registration'] = true;
$_POST['role'] = 'siswa';
$_POST['kelas'] = '';
unset($_POST['only_user']);
require __DIR__ . '/proses_tambah.php';
