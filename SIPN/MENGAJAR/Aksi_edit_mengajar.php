<?php
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin']);

// Pastikan formulir dan ID relasi lengkap sebelum memeriksa potensi pasangan duplikat.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Mengajar.php');
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$mapelId = filter_var($_POST['mapel_id'] ?? null, FILTER_VALIDATE_INT);
$siswaId = filter_var($_POST['siswa_id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1 || !$mapelId || $mapelId < 1 || !$siswaId || $siswaId < 1) {
    header('Location: Mengajar.php?error=not_found');
    exit;
}

if (!mysqli_begin_transaction($koneksi)) {
    header('Location: Edit_mengajar.php?id=' . $id . '&error=failed');
    exit;
}
$current = ambil($koneksi, 'SELECT mapel_id, siswa_id FROM mengajar WHERE id = ? FOR UPDATE', 'i', [$id])[0] ?? null;
if (!$current) {
    mysqli_rollback($koneksi);
    header('Location: Mengajar.php?error=not_found');
    exit;
}

// Tolak jika pasangan mapel-siswa yang sama sudah dipakai baris lain.
$dobel = ambil($koneksi, 'SELECT id FROM mengajar WHERE mapel_id = ? AND siswa_id = ? AND id <> ?', 'iii', [$mapelId, $siswaId, $id]);
if ($dobel) {
    mysqli_rollback($koneksi);
    header('Location: Edit_mengajar.php?id=' . $id . '&error=duplicate');
    exit;
}

$existingGrades = (int) (ambil($koneksi, 'SELECT COUNT(*) AS jumlah FROM nilai_rapor WHERE mapel_id = ? AND siswa_id = ?', 'ii', [(int) $current['mapel_id'], (int) $current['siswa_id']])[0]['jumlah'] ?? 0);
if ($existingGrades > 0 && ((int) $current['mapel_id'] !== $mapelId || (int) $current['siswa_id'] !== $siswaId)) {
    mysqli_rollback($koneksi);
    header('Location: Edit_mengajar.php?id=' . $id . '&error=has_grades');
    exit;
}

$hasil = jalankan($koneksi, 'UPDATE mengajar SET mapel_id = ?, siswa_id = ? WHERE id = ?', 'iii', [$mapelId, $siswaId, $id]);
if (!$hasil['ok']) {
    mysqli_rollback($koneksi);
    header('Location: Edit_mengajar.php?id=' . $id . '&error=failed');
    exit;
}
if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    header('Location: Edit_mengajar.php?id=' . $id . '&error=failed');
    exit;
}
header('Location: Mengajar.php?status=updated');
exit;
