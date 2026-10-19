<?php
require_once dirname(__DIR__) . '/Auth.php'; wajib_role(['admin']);
mysqli_report(MYSQLI_REPORT_OFF);
require_once dirname(__DIR__) . '/Koneksi.php';

// Terima hanya penghapusan POST dengan ID mapel yang valid.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Mapel.php');
    exit;
}
if (!csrf_valid($_POST['csrf_token'] ?? null)) {
    header('Location: Mapel.php?error=invalid');
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header('Location: Mapel.php?error=not_found');
    exit;
}

$transactionStarted = mysqli_begin_transaction($koneksi);
if (!$transactionStarted) {
    header('Location: Mapel.php?error=failed');
    exit;
}
$mapel = ambil($koneksi, 'SELECT id FROM mapel WHERE id = ? FOR UPDATE', 'i', [$id]);
if (!$mapel) {
    mysqli_rollback($koneksi);
    header('Location: Mapel.php?error=not_found');
    exit;
}
$nilai = ambil($koneksi, 'SELECT id FROM nilai_rapor WHERE mapel_id = ? LIMIT 1', 'i', [$id]);
if ($nilai) {
    mysqli_rollback($koneksi);
    header('Location: Mapel.php?error=has_grades');
    exit;
}
$penugasan = ambil($koneksi, 'SELECT id FROM mengajar WHERE mapel_id = ? LIMIT 1', 'i', [$id]);
if ($penugasan) {
    mysqli_rollback($koneksi);
    header('Location: Mapel.php?error=has_assignments');
    exit;
}

$hasil = jalankan($koneksi, 'DELETE FROM mapel WHERE id = ?', 'i', [$id]);
if (!$hasil['ok']) {
    mysqli_rollback($koneksi);
    header('Location: Mapel.php?error=failed');
    exit;
}
if ($hasil['affected'] !== 1) {
    mysqli_rollback($koneksi);
    header('Location: Mapel.php?error=not_found');
    exit;
}
if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    header('Location: Mapel.php?error=failed');
    exit;
}

header('Location: Mapel.php?status=deleted');
exit;
