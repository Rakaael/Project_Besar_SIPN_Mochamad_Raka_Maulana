<?php
// Proses simpan Mengajar. Pasangan mapel-siswa yang sudah ada dilewati (tidak dobel).
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin']);

// Periksa mapel dan daftar siswa, lalu tambahkan hanya pasangan yang belum tercatat.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Mengajar.php');
    exit;
}

$mapelId = filter_var($_POST['mapel_id'] ?? null, FILTER_VALIDATE_INT);
$siswaInput = $_POST['siswa_id'] ?? [];
if (!$mapelId || $mapelId < 1 || !is_array($siswaInput) || !$siswaInput) {
    header('Location: Tambah_mengajar.php?error=invalid');
    exit;
}

$siswaIds = [];
foreach ($siswaInput as $siswaInputId) {
    $siswaId = filter_var($siswaInputId, FILTER_VALIDATE_INT);
    if (!$siswaId || $siswaId < 1) {
        header('Location: Tambah_mengajar.php?error=invalid');
        exit;
    }
    $siswaIds[] = $siswaId;
}
$siswaIds = array_values(array_unique($siswaIds));

$mapel = ambil($koneksi, 'SELECT id FROM mapel WHERE id = ?', 'i', [$mapelId]);
$siswa = ambil($koneksi, 'SELECT id FROM siswa WHERE id IN (' . implode(',', array_fill(0, count($siswaIds), '?')) . ')', str_repeat('i', count($siswaIds)), $siswaIds);
if (!$mapel || count($siswa) !== count($siswaIds)) {
    header('Location: Tambah_mengajar.php?error=invalid');
    exit;
}

$tambah = 0;
if (!mysqli_begin_transaction($koneksi)) {
    header('Location: Tambah_mengajar.php?error=failed');
    exit;
}
foreach ($siswaIds as $siswaId) {
    // INSERT ... SELECT: sekaligus memastikan siswa ada dan pasangan belum terdaftar.
    $hasil = jalankan($koneksi,
        'INSERT INTO mengajar (mapel_id, siswa_id) SELECT ?, s.id FROM siswa s WHERE s.id = ?
         AND NOT EXISTS (SELECT 1 FROM mengajar WHERE mapel_id = ? AND siswa_id = ?)',
        'iiii', [$mapelId, $siswaId, $mapelId, $siswaId]);
    if (!$hasil['ok']) {
        mysqli_rollback($koneksi);
        header('Location: Tambah_mengajar.php?error=failed');
        exit;
    }
    $tambah += $hasil['affected'];
}
if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    header('Location: Tambah_mengajar.php?error=failed');
    exit;
}

header('Location: ' . ($tambah > 0 ? 'Mengajar.php?status=added' : 'Tambah_mengajar.php?error=duplicate'));
exit;
