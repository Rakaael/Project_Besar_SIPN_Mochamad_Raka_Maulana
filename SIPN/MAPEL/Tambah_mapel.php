<?php
require_once dirname(__DIR__) . '/Auth.php'; wajib_role(['admin']);
require_once dirname(__DIR__) . '/Koneksi.php';
// Ambil opsi guru lebih dulu agar dapat dipilih saat mata pelajaran baru dibuat.
$guruQuery = mysqli_query($koneksi, 'SELECT id, nama FROM guru ORDER BY nama ASC');
if (!$guruQuery) {
    die('Gagal mengambil daftar guru: ' . mysqli_error($koneksi));
}
$errorMessages = [
    'duplicate' => 'Kode mapel sudah digunakan.',
    'invalid' => 'Kode dan nama mapel wajib diisi dengan format yang benar.',
    'teacher' => 'Guru yang dipilih tidak ditemukan.',
    'failed' => 'Mapel gagal disimpan. Silakan coba lagi.',
];
$error = $errorMessages[$_GET['error'] ?? ''] ?? null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="../">
    <title>Tambah Mapel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css?v=9">
</head>
<body>
<?php require_once dirname(__DIR__) . '/Navbar.php'; ?>
<main class="container py-4">
    <h1 class="h2 mb-4">Tambah Mapel</h1>
    <?php if ($error) { ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error); ?></div>
    <?php } ?>
    <form action="MAPEL/Aksi_tambah_mapel.php" method="post" class="row g-3">
        <div class="col-md-4">
            <label for="kode_mapel" class="form-label">Kode Mapel</label>
            <input type="text" class="form-control" id="kode_mapel" name="kode_mapel" maxlength="10" pattern="[A-Za-z0-9_-]{1,10}" required>
        </div>
        <div class="col-md-8">
            <label for="nama_mapel" class="form-label">Nama Mapel</label>
            <input type="text" class="form-control" id="nama_mapel" name="nama_mapel" maxlength="100" required>
        </div>
        <div class="col-md-6">
            <label for="guru_id" class="form-label">Guru</label>
            <select class="form-select" id="guru_id" name="guru_id">
                <option value="">Belum ditentukan</option>
                <?php while ($guru = mysqli_fetch_assoc($guruQuery)) { ?>
                    <option value="<?= (int) $guru['id']; ?>"><?= htmlspecialchars($guru['nama']); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan Mapel</button>
            <a href="MAPEL/Mapel.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</main>
<?php require_once dirname(__DIR__) . '/Footer.php'; ?>
