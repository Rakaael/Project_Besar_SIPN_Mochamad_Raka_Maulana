<?php
require_once __DIR__ . '/Auth.php'; wajib_role(['admin']);
// File ini berfungsi untuk menghapus data user sekaligus data profil terkait.
// Proses hapus dibuat dalam transaksi agar data tidak setengah-hapus.
require_once __DIR__ . '/Koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid($_POST['csrf_token'] ?? null)) {
    header('Location: Daftar_user.php?error=invalid');
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header('Location: Daftar_user.php?error=invalid');
    exit;
}

if (!mysqli_begin_transaction($koneksi)) {
    header('Location: Daftar_user.php?error=failed');
    exit;
}
$user = ambil($koneksi, 'SELECT id FROM users WHERE id = ? FOR UPDATE', 'i', [$id])[0] ?? null;
if (!$user) {
    mysqli_rollback($koneksi);
    header('Location: Daftar_user.php?error=not_found');
    exit;
}

$profilSiswa = ambil($koneksi, 'SELECT id FROM siswa WHERE user_id = ? FOR UPDATE', 'i', [$id])[0] ?? null;
$profilGuru = ambil($koneksi, 'SELECT id FROM guru WHERE user_id = ? FOR UPDATE', 'i', [$id])[0] ?? null;
$siswaId = (int) ($profilSiswa['id'] ?? 0);
$guruId = (int) ($profilGuru['id'] ?? 0);

// Cegah penghapusan siswa yang masih memiliki nilai rapor.
if ($siswaId > 0) {
    $jumlahNilai = (int) (ambil($koneksi, 'SELECT COUNT(*) AS jumlah FROM nilai_rapor WHERE siswa_id = ?', 'i', [$siswaId])[0]['jumlah'] ?? 0);
    if ($jumlahNilai > 0) {
        mysqli_rollback($koneksi);
        header('Location: Daftar_user.php?error=has_grades');
        exit;
    }
}

$hasil = ['ok' => true];

// Hapus relasi/profil terlebih dahulu, lalu akun utama; semua perubahan dibatalkan jika satu langkah gagal.
if ($siswaId > 0) {
    $hasil = jalankan($koneksi, 'DELETE FROM mengajar WHERE siswa_id = ?', 'i', [$siswaId]);
}
if ($hasil['ok'] && $guruId > 0) {
    $hasil = jalankan($koneksi, 'DELETE j FROM mengajar j
        INNER JOIN mapel m ON m.id = j.mapel_id
        WHERE m.guru_id = ?', 'i', [$guruId]);
}
if ($hasil['ok'] && $guruId > 0) {
    $hasil = jalankan($koneksi, 'UPDATE mapel
        SET guru_id = NULL
        WHERE guru_id = ?
            AND EXISTS (
                SELECT 1 FROM nilai_rapor
                WHERE nilai_rapor.mapel_id = mapel.id
            )', 'i', [$guruId]);
}
if ($hasil['ok'] && $guruId > 0) {
    $hasil = jalankan($koneksi, 'DELETE FROM mapel
        WHERE guru_id = ?
            AND NOT EXISTS (
                SELECT 1 FROM nilai_rapor
                WHERE nilai_rapor.mapel_id = mapel.id
            )', 'i', [$guruId]);
}
if ($hasil['ok']) {
    $hasil = jalankan($koneksi, 'DELETE FROM siswa WHERE user_id = ?', 'i', [$id]);
}
if ($hasil['ok']) {
    $hasil = jalankan($koneksi, 'DELETE FROM guru WHERE user_id = ?', 'i', [$id]);
}
if ($hasil['ok']) {
    $hasil = jalankan($koneksi, 'DELETE FROM users WHERE id = ?', 'i', [$id]);
}

if (!$hasil['ok']) {
    mysqli_rollback($koneksi);
    header('Location: Daftar_user.php?error=failed');
    exit;
}

if (!mysqli_commit($koneksi)) {
    mysqli_rollback($koneksi);
    header('Location: Daftar_user.php?error=failed');
    exit;
}
$foto = foto_profil_path($id);
if ($foto !== null && is_file($foto)) {
    unlink($foto);
}
if ((int) ($_SESSION['user_id'] ?? 0) === (int) $id) {
    $_SESSION = [];
    session_destroy();
    header('Location: Login.php?logout=1');
    exit;
}
header('Location: Daftar_user.php?status=deleted');
exit;
