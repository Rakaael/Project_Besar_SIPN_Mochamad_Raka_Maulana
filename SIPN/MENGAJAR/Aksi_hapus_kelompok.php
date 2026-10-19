<?php
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin']);

// Hapus seluruh penugasan untuk satu mapel dan kelas berdasarkan data POST yang valid.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Mengajar.php');
    exit;
}

$mapelId = filter_var($_POST['mapel_id'] ?? null, FILTER_VALIDATE_INT);
$kelasInput = $_POST['kelas'] ?? '';
$kelas = is_string($kelasInput) ? trim($kelasInput) : '';
if (!$mapelId || $mapelId < 1 || $kelas === '') {
    header('Location: Mengajar.php?error=invalid');
    exit;
}

$transactionStarted = mysqli_begin_transaction($koneksi);
if (!$transactionStarted) {
    header('Location: Mengajar.php?error=failed');
    exit;
}
if (!ambil($koneksi, 'SELECT id FROM mapel WHERE id = ? FOR UPDATE', 'i', [$mapelId])) {
    mysqli_rollback($koneksi);
    header('Location: Mengajar.php?error=not_found');
    exit;
}
$nilaiTerkait = ambil($koneksi, 'SELECT n.id FROM nilai_rapor n
    JOIN mengajar j ON j.mapel_id = n.mapel_id AND j.siswa_id = n.siswa_id
    JOIN siswa s ON s.id = j.siswa_id
    WHERE j.mapel_id = ? AND s.kelas = ? LIMIT 1', 'is', [$mapelId, $kelas]);
if ($nilaiTerkait) {
    mysqli_rollback($koneksi);
    header('Location: Mengajar.php?error=has_grades');
    exit;
}

$hasil = jalankan($koneksi, 'DELETE j FROM mengajar j JOIN siswa s ON s.id = j.siswa_id WHERE j.mapel_id = ? AND s.kelas = ?', 'is', [$mapelId, $kelas]);
if (!$hasil['ok']) {
    mysqli_rollback($koneksi);
    header('Location: Mengajar.php?error=failed');
} elseif ($hasil['affected'] < 1) {
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
