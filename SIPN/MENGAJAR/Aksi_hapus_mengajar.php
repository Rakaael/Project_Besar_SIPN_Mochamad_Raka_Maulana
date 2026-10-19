<?php
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin']);

// Hapus satu relasi mengajar dan laporkan apakah barisnya ditemukan.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Mengajar.php');
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header('Location: Mengajar.php?error=not_found');
    exit;
}

$transactionStarted = mysqli_begin_transaction($koneksi);
if (!$transactionStarted) {
    header('Location: Mengajar.php?error=failed');
    exit;
}
$assignment = ambil($koneksi, 'SELECT mapel_id, siswa_id FROM mengajar WHERE id = ? FOR UPDATE', 'i', [$id])[0] ?? null;
if (!$assignment) {
    mysqli_rollback($koneksi);
    header('Location: Mengajar.php?error=not_found');
    exit;
}
$grades = (int) (ambil($koneksi, 'SELECT COUNT(*) AS jumlah FROM nilai_rapor WHERE mapel_id = ? AND siswa_id = ?', 'ii', [(int) $assignment['mapel_id'], (int) $assignment['siswa_id']])[0]['jumlah'] ?? 0);
if ($grades > 0) {
    mysqli_rollback($koneksi);
    header('Location: Mengajar.php?error=has_grades');
    exit;
}

$hasil = jalankan($koneksi, 'DELETE FROM mengajar WHERE id = ?', 'i', [$id]);
if (!$hasil['ok']) {
    mysqli_rollback($koneksi);
    header('Location: Mengajar.php?error=failed');
} elseif ($hasil['affected'] !== 1) {
    mysqli_rollback($koneksi);
    header('Location: Mengajar.php?error=not_found');
} else {
    if (!mysqli_commit($koneksi)) {
        mysqli_rollback($koneksi);
        header('Location: Mengajar.php?error=failed');
    } else {
        header('Location: Mengajar.php?status=deleted');
    }
}
exit;
