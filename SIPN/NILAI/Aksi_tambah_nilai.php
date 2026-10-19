<?php
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['guru']);

// Validasi data nilai dan pasangan siswa-mapel yang dikirim oleh guru.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Nilai.php');
    exit;
}

$guru = profil_saya($koneksi);
$pasanganInput = $_POST['pasangan'] ?? '';
$bagian = is_string($pasanganInput) ? explode(':', $pasanganInput) : [];
$pasanganValid = count($bagian) === 2;
$mapelId = filter_var($bagian[0] ?? null, FILTER_VALIDATE_INT);
$siswaId = filter_var($bagian[1] ?? null, FILTER_VALIDATE_INT);
$kkm = nilai_valid($_POST['nilai_kkm'] ?? null);
$peng = nilai_valid($_POST['nilai_pengetahuan'] ?? null);
$ket = nilai_valid($_POST['nilai_keterampilan'] ?? null);

if (!$guru || !$pasanganValid || !$mapelId || $mapelId < 1 || !$siswaId || $siswaId < 1
    || $kkm === null || $peng === null || $ket === null) {
    header('Location: Tambah_nilai.php?error=invalid');
    exit;
}

// Pastikan pasangan siswa dan mapel tercatat di Mengajar serta belum memiliki nilai.
$sah = ambil($koneksi, "SELECT m.id FROM mapel m
        JOIN mengajar j ON j.mapel_id = m.id
        WHERE m.guru_id = ? AND m.id = ?
            AND j.siswa_id = ?
            AND NOT EXISTS (SELECT 1 FROM nilai_rapor n WHERE n.mapel_id = m.id AND n.siswa_id = ?)",
        'iiii', [(int) $guru['id'], $mapelId, $siswaId, $siswaId]);
if (!$sah) {
    header('Location: Tambah_nilai.php?error=notyours');
    exit;
}

$keterangan = hitung_keterangan($kkm, $peng, $ket);
if (!mysqli_begin_transaction($koneksi)) {
    header('Location: Tambah_nilai.php?error=failed');
    exit;
}
$sahDalamTransaksi = ambil($koneksi, 'SELECT m.id FROM mapel m
    JOIN mengajar j ON j.mapel_id = m.id
    WHERE m.guru_id = ? AND m.id = ? AND j.siswa_id = ?
        AND NOT EXISTS (SELECT 1 FROM nilai_rapor n WHERE n.mapel_id = m.id AND n.siswa_id = j.siswa_id)
    FOR UPDATE',
    'iii', [(int) $guru['id'], $mapelId, $siswaId]);
if (!$sahDalamTransaksi) {
    mysqli_rollback($koneksi);
    header('Location: Tambah_nilai.php?error=notyours');
    exit;
}
$hasil = jalankan($koneksi,
    'INSERT INTO nilai_rapor (siswa_id, mapel_id, nilai_kkm, nilai_pengetahuan, nilai_keterampilan, Keterangan) VALUES (?, ?, ?, ?, ?, ?)',
    'iiiiis', [$siswaId, $mapelId, $kkm, $peng, $ket, $keterangan]);

if (!$hasil['ok']) {
    mysqli_rollback($koneksi);
    header('Location: Tambah_nilai.php?error=' . ($hasil['errno'] === 1062 ? 'duplicate' : 'failed'));
    exit;
}
if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    header('Location: Tambah_nilai.php?error=failed');
    exit;
}
header('Location: Nilai.php?status=added');
exit;
