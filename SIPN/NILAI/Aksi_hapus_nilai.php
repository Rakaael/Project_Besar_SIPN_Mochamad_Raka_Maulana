<?php
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['guru']);

// Verifikasi kepemilikan nilai oleh guru sebelum menghapus catatan rapor.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Nilai.php');
    exit;
}

$guru = profil_saya($koneksi);
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$transactionStarted = mysqli_begin_transaction($koneksi);
if (!$transactionStarted) {
    header('Location: Nilai.php?error=failed');
    exit;
}
$pasanganSaatIni = $id ? (ambil($koneksi, 'SELECT mapel_id, siswa_id FROM nilai_rapor WHERE id = ?', 'i', [$id])[0] ?? null) : null;
$penugasan = $pasanganSaatIni
    ? ambil($koneksi, 'SELECT id FROM mengajar WHERE mapel_id = ? AND siswa_id = ? FOR UPDATE', 'ii', [(int) $pasanganSaatIni['mapel_id'], (int) $pasanganSaatIni['siswa_id']])
    : [];
$milik = $id ? ambil($koneksi, "SELECT n.id FROM nilai_rapor n
        JOIN mapel m ON m.id = n.mapel_id
        JOIN siswa s ON s.id = n.siswa_id
        WHERE n.id = ? AND m.guru_id = ?
            AND EXISTS (SELECT 1 FROM mengajar j WHERE j.mapel_id = m.id AND j.siswa_id = s.id)
        FOR UPDATE", 'ii', [$id, (int) ($guru['id'] ?? 0)]) : [];
if (!$pasanganSaatIni || !$penugasan || !$milik) {
    mysqli_rollback($koneksi);
    header('Location: Nilai.php?error=not_found');
    exit;
}

$hasil = jalankan($koneksi, 'DELETE FROM nilai_rapor WHERE id = ?', 'i', [$id]);
if (!$hasil['ok']) {
    mysqli_rollback($koneksi);
    header('Location: Nilai.php?error=failed');
    exit;
}
if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    header('Location: Nilai.php?error=failed');
    exit;
}
header('Location: Nilai.php?status=deleted');
exit;
