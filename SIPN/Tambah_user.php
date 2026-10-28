<?php
// File ini berisi form untuk menambahkan data siswa baru.
// Siswa mengisi NIS, nama, jenis kelamin, username, dan password; kelas ditetapkan admin.
$errorMessages = [
    'duplicate' => 'NIS atau username sudah digunakan. Silakan periksa kembali.',
    'invalid' => 'Data pendaftaran belum valid. Periksa semua kolom.',
];
$error = $errorMessages[$_GET['error'] ?? ''] ?? null;
// Formulir di bawah mengirim pendaftaran siswa ke handler validasi Aksi_register.php.
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css?v=13">
</head>
<body>
<?php require_once __DIR__ . '/Navbar.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border border-2 border-secondary-subtle rounded-4 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <form action="Aksi_register.php" method="POST">
                        <?php if ($error) { ?>
                            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error); ?></div>
                        <?php } ?>
                        <h1 class="h2 text-center mb-4">Daftar Siswa</h1>

                        <div class="mb-3">
                            <label for="nis" class="form-label">NIS</label>
                            <input type="text" id="nis" name="nis" maxlength="10" class="form-control" placeholder="Contoh: 1234567890" required>
                        </div>

                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama" maxlength="100" class="form-control" placeholder="Masukkan nama lengkap" required>
                        </div>

                        <div class="mb-3">
                        <fieldset class="mb-4">
                            <legend class="form-label d-block mb-2">Jenis Kelamin</legend>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="jenis_kelamin" id="laki" value="L" required>
                                    <label class="form-check-label" for="laki">Laki-laki</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="jenis_kelamin" id="perempuan" value="P">
                                    <label class="form-check-label" for="perempuan">Perempuan</label>
                                </div>
                            </div>
                        </fieldset>

                        <h3 class="fw-bold mb-3">Data Akun Kredensial Siswa</h3>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" maxlength="50" class="form-control" placeholder="Pilih username" autocomplete="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" minlength="8" maxlength="4096" class="form-control" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg px-4">Daftar</button>
                            <a href="Login.php" class="btn btn-secondary btn-lg px-4">Sudah punya akun? Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/Footer.php'; ?>
