<?php
require_once dirname(__DIR__) . '/Auth.php'; wajib_role(['admin']);
require_once dirname(__DIR__) . '/Koneksi.php';

// Muat data mapel dan opsi guru untuk mengisi formulir edit.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    header('Location: Mapel.php?error=not_found');
    exit;
}

$stmt = mysqli_prepare($koneksi, 'SELECT id, kode_mapel, nama_mapel, guru_id FROM mapel WHERE id = ?');
if (!$stmt) {
    die('Gagal menyiapkan data mapel: ' . mysqli_error($koneksi));
}
mysqli_stmt_bind_param($stmt, 'i', $id);
$executed = mysqli_stmt_execute($stmt);
$result = $executed ? mysqli_stmt_get_result($stmt) : false;
$mapel = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);
if (!$executed || !$result) {
    die('Gagal mengambil data mapel.');
}

if (!$mapel) {
    header('Location: Mapel.php?error=not_found');
    exit;
}

$guruQuery = mysqli_query($koneksi, 'SELECT id, nama FROM guru ORDER BY nama ASC');
if (!$guruQuery) {
    die('Gagal mengambil daftar guru: ' . mysqli_error($koneksi));
}
$errorMessages = [
    'duplicate' => 'Kode mapel sudah digunakan oleh mapel lain.',
    'invalid' => 'Kode dan nama mapel wajib diisi dengan format yang benar.',
    'teacher' => 'Guru yang dipilih tidak ditemukan.',
    'failed' => 'Perubahan mapel gagal disimpan. Silakan coba lagi.',
];
$error = $errorMessages[$_GET['error'] ?? ''] ?? null;
// Escape nilai database sebelum dirender kembali sebagai isi/atribut HTML.
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="../">
    <title>Edit Mapel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css?v=13">
</head>
<body>
<?php require_once dirname(__DIR__) . '/Navbar.php'; ?>
<main class="container py-4">
    <h1 class="h2 mb-4">Edit Mapel</h1>
    <?php if ($error) { ?>
        <div class="alert alert-danger" role="alert"><?= $escape($error); ?></div>
    <?php } ?>
    <form action="MAPEL/Aksi_edit_mapel.php" method="post" class="row g-3">
        <input type="hidden" name="id" value="<?= (int) $mapel['id']; ?>">
        <div class="col-md-4">
            <label for="kode_mapel" class="form-label">Kode Mapel</label>
            <input type="text" class="form-control" id="kode_mapel" name="kode_mapel" maxlength="10" pattern="[A-Za-z0-9_-]{1,10}" value="<?= $escape($mapel['kode_mapel']); ?>" required>
        </div>
        <div class="col-md-8">
            <label for="nama_mapel" class="form-label">Nama Mapel</label>
            <input type="text" class="form-control" id="nama_mapel" name="nama_mapel" maxlength="100" value="<?= $escape($mapel['nama_mapel']); ?>" required>
        </div>
        <div class="col-md-6">
            <label for="guru_id" class="form-label">Guru</label>
            <select class="form-select" id="guru_id" name="guru_id">
                <option value="">Belum ditentukan</option>
                <?php while ($guru = mysqli_fetch_assoc($guruQuery)) { ?>
                    <option value="<?= (int) $guru['id']; ?>" <?= (int) ($mapel['guru_id'] ?? 0) === (int) $guru['id'] ? 'selected' : ''; ?>><?= $escape($guru['nama']); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="MAPEL/Mapel.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</main>
<?php require_once dirname(__DIR__) . '/Footer.php'; ?>
