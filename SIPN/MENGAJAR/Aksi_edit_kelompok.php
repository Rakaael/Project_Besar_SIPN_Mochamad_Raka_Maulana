<?php
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin']);

// Validasi mapel, kelas, dan pilihan siswa sebelum mengganti seluruh penugasan kelompok.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Mengajar.php');
    exit;
}

$mapelId = filter_var($_POST['mapel_id'] ?? null, FILTER_VALIDATE_INT);
$kelasInput = $_POST['kelas'] ?? '';
$kelas = is_string($kelasInput) ? trim($kelasInput) : '';
$siswaInput = $_POST['siswa_id'] ?? [];
if (!$mapelId || $mapelId < 1 || $kelas === '' || !is_array($siswaInput)) {
    header('Location: Mengajar.php?error=invalid');
    exit;
}

$siswaIds = [];
foreach ($siswaInput as $siswaId) {
    $id = filter_var($siswaId, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        header('Location: Edit_kelompok.php?mapel_id=' . $mapelId . '&kelas=' . urlencode($kelas) . '&error=invalid');
        exit;
    }
    $siswaIds[] = $id;
}
$siswaIds = array_values(array_unique($siswaIds));
if (!$siswaIds) {
    header('Location: Edit_kelompok.php?mapel_id=' . $mapelId . '&kelas=' . urlencode($kelas) . '&error=empty');
    exit;
}

$kelompok = ambil($koneksi, "SELECT j.id
    FROM mengajar j
    JOIN siswa s ON s.id = j.siswa_id
    WHERE j.mapel_id = ? AND s.kelas = ?
    LIMIT 1", 'is', [$mapelId, $kelas]);
if (!$kelompok) {
    header('Location: Mengajar.php?error=not_found');
    exit;
}

$siswaValid = ambil($koneksi, 'SELECT id FROM siswa WHERE kelas = ?', 's', [$kelas]);
$idValid = array_map('intval', array_column($siswaValid, 'id'));
if (array_diff($siswaIds, $idValid)) {
    header('Location: Edit_kelompok.php?mapel_id=' . $mapelId . '&kelas=' . urlencode($kelas) . '&error=invalid');
    exit;
}

if (!mysqli_begin_transaction($koneksi)) {
    header('Location: Edit_kelompok.php?mapel_id=' . $mapelId . '&kelas=' . urlencode($kelas) . '&error=failed');
    exit;
}
$relasiSaatIni = ambil($koneksi, 'SELECT j.siswa_id FROM mengajar j JOIN siswa s ON s.id = j.siswa_id WHERE j.mapel_id = ? AND s.kelas = ? FOR UPDATE', 'is', [$mapelId, $kelas]);
$idSaatIni = array_map('intval', array_column($relasiSaatIni, 'siswa_id'));
$akanDihapus = array_diff($idSaatIni, $siswaIds);
foreach ($akanDihapus as $siswaId) {
    $nilai = ambil($koneksi, 'SELECT id FROM nilai_rapor WHERE mapel_id = ? AND siswa_id = ? LIMIT 1', 'ii', [$mapelId, $siswaId]);
    if ($nilai) {
        mysqli_rollback($koneksi);
        header('Location: Edit_kelompok.php?mapel_id=' . $mapelId . '&kelas=' . urlencode($kelas) . '&error=has_grades');
        exit;
    }
}
$hasil = ['ok' => true];
foreach ($akanDihapus as $siswaId) {
    $hasil = jalankan($koneksi, 'DELETE FROM mengajar WHERE mapel_id = ? AND siswa_id = ?', 'ii', [$mapelId, $siswaId]);
    if (!$hasil['ok']) {
        break;
    }
}
foreach ($siswaIds as $siswaId) {
    if (!$hasil['ok']) {
        break;
    }
    if (!in_array($siswaId, $idSaatIni, true)) {
        $hasil = jalankan($koneksi, 'INSERT INTO mengajar (mapel_id, siswa_id) VALUES (?, ?)', 'ii', [$mapelId, $siswaId]);
    }
}
if (!$hasil['ok']) {
    mysqli_rollback($koneksi);
    header('Location: Edit_kelompok.php?mapel_id=' . $mapelId . '&kelas=' . urlencode($kelas) . '&error=failed');
    exit;
}

if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    header('Location: Edit_kelompok.php?mapel_id=' . $mapelId . '&kelas=' . urlencode($kelas) . '&error=failed');
    exit;
}
header('Location: Mengajar.php?status=updated');
exit;
