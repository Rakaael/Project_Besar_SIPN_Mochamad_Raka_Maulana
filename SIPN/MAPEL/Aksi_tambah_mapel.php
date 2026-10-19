<?php
require_once dirname(__DIR__) . '/Auth.php'; wajib_role(['admin']);
mysqli_report(MYSQLI_REPORT_OFF);
require_once dirname(__DIR__) . '/Koneksi.php';

// Pastikan pengiriman formulir serta penetapan guru dan identitas mapel valid.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Mapel.php');
    exit;
}

$kodeInput = $_POST['kode_mapel'] ?? '';
$namaInput = $_POST['nama_mapel'] ?? '';
$guruInputValue = $_POST['guru_id'] ?? '';
$kode = is_string($kodeInput) ? strtoupper(trim($kodeInput)) : '';
$nama = is_string($namaInput) ? trim($namaInput) : '';
$guruInput = is_string($guruInputValue) ? trim($guruInputValue) : '';
$guruId = 0;

if ($guruInput !== '') {
    if (!ctype_digit($guruInput) || (int) $guruInput < 1) {
        header('Location: Tambah_mapel.php?error=teacher');
        exit;
    }
    $guruId = (int) $guruInput;
    $teacherCheck = mysqli_prepare($koneksi, 'SELECT id FROM guru WHERE id = ?');
    if (!$teacherCheck) {
        header('Location: Tambah_mapel.php?error=failed');
        exit;
    }
    mysqli_stmt_bind_param($teacherCheck, 'i', $guruId);
    if (!mysqli_stmt_execute($teacherCheck)) {
        mysqli_stmt_close($teacherCheck);
        header('Location: Tambah_mapel.php?error=failed');
        exit;
    }
    mysqli_stmt_store_result($teacherCheck);
    $teacherExists = mysqli_stmt_num_rows($teacherCheck) === 1;
    mysqli_stmt_close($teacherCheck);
    if (!$teacherExists) {
        header('Location: Tambah_mapel.php?error=teacher');
        exit;
    }
}

if (!preg_match('/^[A-Z0-9_-]{1,10}$/', $kode) || $nama === '' || strlen($nama) > 100) {
    header('Location: Tambah_mapel.php?error=invalid');
    exit;
}

// Simpan mapel; rollback dipakai jika insert gagal, termasuk saat kode sudah digunakan.
$stmt = mysqli_prepare($koneksi, 'INSERT INTO mapel (kode_mapel, nama_mapel, guru_id) VALUES (?, ?, NULLIF(?, 0))');
if (!$stmt) {
    header('Location: Tambah_mapel.php?error=failed');
    exit;
}
if (!mysqli_begin_transaction($koneksi)) {
    mysqli_stmt_close($stmt);
    header('Location: Tambah_mapel.php?error=failed');
    exit;
}
mysqli_stmt_bind_param($stmt, 'ssi', $kode, $nama, $guruId);
$inserted = mysqli_stmt_execute($stmt);
$errorCode = mysqli_stmt_errno($stmt);
mysqli_stmt_close($stmt);

if (!$inserted) {
    mysqli_rollback($koneksi);
    header('Location: Tambah_mapel.php?error=' . ($errorCode === 1062 ? 'duplicate' : 'failed'));
    exit;
}
if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    header('Location: Tambah_mapel.php?error=failed');
    exit;
}

header('Location: Mapel.php?status=added');
exit;
