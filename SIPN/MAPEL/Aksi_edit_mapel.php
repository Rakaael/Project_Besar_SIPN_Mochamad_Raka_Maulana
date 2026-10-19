<?php
require_once dirname(__DIR__) . '/Auth.php'; wajib_role(['admin']);
mysqli_report(MYSQLI_REPORT_OFF);
require_once dirname(__DIR__) . '/Koneksi.php';

// Perubahan mapel hanya diproses dari formulir POST dengan ID yang valid.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Mapel.php');
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header('Location: Mapel.php?error=not_found');
    exit;
}
$backUrl = 'Edit_mapel.php?id=' . urlencode((string) $id);

$existsStmt = mysqli_prepare($koneksi, 'SELECT id FROM mapel WHERE id = ?');
if (!$existsStmt) {
    header('Location: ' . $backUrl . '&error=failed');
    exit;
}
mysqli_stmt_bind_param($existsStmt, 'i', $id);
if (!mysqli_stmt_execute($existsStmt)) {
    mysqli_stmt_close($existsStmt);
    header('Location: ' . $backUrl . '&error=failed');
    exit;
}
mysqli_stmt_store_result($existsStmt);
$mapelExists = mysqli_stmt_num_rows($existsStmt) === 1;
mysqli_stmt_close($existsStmt);
if (!$mapelExists) {
    header('Location: Mapel.php?error=not_found');
    exit;
}

$kodeInput = $_POST['kode_mapel'] ?? '';
$namaInput = $_POST['nama_mapel'] ?? '';
$guruInputValue = $_POST['guru_id'] ?? '';
$kode = is_string($kodeInput) ? strtoupper(trim($kodeInput)) : '';
$nama = is_string($namaInput) ? trim($namaInput) : '';
$guruInput = is_string($guruInputValue) ? trim($guruInputValue) : '';
$guruId = 0;

// Pastikan guru yang dipilih benar-benar tersedia sebelum mengaitkannya ke mapel.
if ($guruInput !== '') {
    if (!ctype_digit($guruInput) || (int) $guruInput < 1) {
        header('Location: ' . $backUrl . '&error=teacher');
        exit;
    }
    $guruId = (int) $guruInput;
    $teacherCheck = mysqli_prepare($koneksi, 'SELECT id FROM guru WHERE id = ?');
    if (!$teacherCheck) {
        header('Location: ' . $backUrl . '&error=failed');
        exit;
    }
    mysqli_stmt_bind_param($teacherCheck, 'i', $guruId);
    if (!mysqli_stmt_execute($teacherCheck)) {
        mysqli_stmt_close($teacherCheck);
        header('Location: ' . $backUrl . '&error=failed');
        exit;
    }
    mysqli_stmt_store_result($teacherCheck);
    $teacherExists = mysqli_stmt_num_rows($teacherCheck) === 1;
    mysqli_stmt_close($teacherCheck);
    if (!$teacherExists) {
        header('Location: ' . $backUrl . '&error=teacher');
        exit;
    }
}

if (!preg_match('/^[A-Z0-9_-]{1,10}$/', $kode) || $nama === '' || strlen($nama) > 100) {
    header('Location: ' . $backUrl . '&error=invalid');
    exit;
}

// Jalankan pembaruan dalam transaksi dan bedakan kode duplikat dari kegagalan lain.
$stmt = mysqli_prepare($koneksi, 'UPDATE mapel SET kode_mapel = ?, nama_mapel = ?, guru_id = NULLIF(?, 0) WHERE id = ?');
if (!$stmt) {
    header('Location: ' . $backUrl . '&error=failed');
    exit;
}
if (!mysqli_begin_transaction($koneksi)) {
    mysqli_stmt_close($stmt);
    header('Location: ' . $backUrl . '&error=failed');
    exit;
}
mysqli_stmt_bind_param($stmt, 'ssii', $kode, $nama, $guruId, $id);
$updated = mysqli_stmt_execute($stmt);
$errorCode = mysqli_stmt_errno($stmt);
mysqli_stmt_close($stmt);

if (!$updated) {
    mysqli_rollback($koneksi);
    header('Location: ' . $backUrl . '&error=' . ($errorCode === 1062 ? 'duplicate' : 'failed'));
    exit;
}
if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    header('Location: ' . $backUrl . '&error=failed');
    exit;
}

header('Location: Mapel.php?status=updated');
exit;
