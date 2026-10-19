<?php
require_once __DIR__ . '/Auth.php'; wajib_role(['admin']);
// File ini adalah form khusus untuk membuat akun admin dengan username otomatis.
$errorMessages = [
    'duplicate' => 'Username otomatis sedang bentrok. Silakan kirim ulang formulir.',
    'invalid' => 'Nama atau password tidak valid. Password minimal 6 karakter.',
    'failed' => 'Akun gagal disimpan. Periksa konfigurasi database atau coba lagi.',
];
$error = $errorMessages[$_GET['error'] ?? ''] ?? null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Akun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css?v=5">
</head>
<body>
<?php require_once __DIR__ . '/Navbar.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border border-2 border-secondary-subtle rounded-4 shadow-sm" style="background: #f7f7f7;">
                <div class="card-body p-4 p-md-5">
                    <form action="proses_tambah.php" method="POST">
                        <?php if ($error) { ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
                        <h3 class="mb-3">Tambah Akun Admin</h3>
                        <label for="nama_admin" class="form-label">Nama Lengkap</label>
                        <input type="text" id="nama_admin" name="nama_admin" maxlength="100" class="form-control mb-3" placeholder="Contoh: Mochamad Raka Maulana" autocomplete="name" required>
                        <label for="password" class="form-label">Password awal</label>
                        <input type="password" id="password" name="password" minlength="6" maxlength="4096" class="form-control mb-3" autocomplete="new-password" required>
                        <p class="form-text">Username dibuat dari nama depan dan 4 angka acak.</p>
                        <input type="hidden" name="role" value="admin">
                        <input type="hidden" name="only_user" value="1">

                        <button type="submit" class="btn btn-primary">Submit</button>
                        <a href="Daftar_user.php" class="btn btn-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/Footer.php'; ?>