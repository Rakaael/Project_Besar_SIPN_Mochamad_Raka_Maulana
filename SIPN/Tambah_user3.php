<?php
require_once __DIR__ . '/Auth.php'; wajib_role(['admin']);
// File ini berisi form tambah user yang fleksibel untuk siswa, guru, maupun admin.
// Ketika role dipilih, field profil akan otomatis tampil sesuai kebutuhan.
// Role awal dibatasi ke nilai yang dikenali agar parameter URL tidak mengisi opsi sembarang.
$roleAwal = in_array($_GET['role'] ?? '', ['siswa', 'guru', 'admin'], true) ? $_GET['role'] : '';
$errorMessages = [
    'duplicate' => 'Username, NIS, atau NIP sudah digunakan. Periksa kembali datanya.',
    'invalid' => 'Data belum lengkap atau melebihi batas panjang kolom.',
    'failed' => 'Data gagal disimpan. Periksa konfigurasi database atau coba lagi.',
];
$error = $errorMessages[$_GET['error'] ?? ''] ?? null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css?v=5">
</head>
<body>
<?php require_once __DIR__ . '/Navbar.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border border-2 border-secondary-subtle rounded-4 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <form action="proses_tambah.php" method="POST">
                        <h1 class="h2 text-center mb-4">Form Tambah User</h1>
                        <?php if ($error) { ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" maxlength="50" class="form-control" placeholder="Masukkan username" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" minlength="6" maxlength="4096" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>

                        <div class="mb-4">
                            <label for="role" class="form-label fw-semibold">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="" <?= $roleAwal === '' ? 'selected' : ''; ?>>-- Pilih Role --</option>
                                <option value="siswa" <?= $roleAwal === 'siswa' ? 'selected' : ''; ?>>Siswa</option>
                                <option value="guru" <?= $roleAwal === 'guru' ? 'selected' : ''; ?>>Guru</option>
                                <option value="admin" <?= $roleAwal === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>

                        <div id="profile-fields" style="display: none;">
                            <div class="mb-3" id="nis-wrapper" style="display: none;">
                                <label for="nis" class="form-label">NIS</label>
                                <input type="text" name="nis" id="nis" maxlength="10" class="form-control" placeholder="Contoh: 1234567890">
                            </div>

                            <div class="mb-3" id="nip-wrapper" style="display: none;">
                                <label for="nip" class="form-label">NIP</label>
                                <input type="text" name="nip" id="nip" maxlength="20" class="form-control" placeholder="Contoh: 1987654321">
                            </div>

                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama" id="nama" maxlength="100" class="form-control" placeholder="Masukkan nama lengkap">
                            </div>


                            <div class="mb-3" id="kelas-wrapper" style="display: none;">
                                <label for="kelas" class="form-label">Kelas</label>
                                <input type="text" name="kelas" id="kelas" maxlength="10" class="form-control" placeholder="Contoh: XI RPL 1">
                            </div>

                            <fieldset class="mb-4" id="jenis-kelamin-wrapper" style="display: none;">
                                <legend class="form-label d-block mb-2">Jenis Kelamin</legend>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="jenis_kelamin" id="laki3" value="L">
                                        <label class="form-check-label" for="laki3">Laki-laki</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="jenis_kelamin" id="perempuan3" value="P">
                                        <label class="form-check-label" for="perempuan3">Perempuan</label>
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-lg px-4">Submit</button>
                            <a href="Daftar_user.php" class="btn btn-secondary btn-lg px-4">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role');
        const profileFields = document.getElementById('profile-fields');
        const nisWrapper = document.getElementById('nis-wrapper');
        const nipWrapper = document.getElementById('nip-wrapper');
        const kelasWrapper = document.getElementById('kelas-wrapper');
        const jenisKelaminWrapper = document.getElementById('jenis-kelamin-wrapper');
        const nisInput = document.getElementById('nis');
        const nipInput = document.getElementById('nip');
        const namaInput = document.getElementById('nama');
        const kelasInput = document.getElementById('kelas');
        const genderInputs = document.querySelectorAll('input[name="jenis_kelamin"]');

        function toggleProfileFields() {
            const role = roleSelect.value;
            const showProfile = role === 'siswa' || role === 'guru';

            profileFields.style.display = showProfile ? 'block' : 'none';
            nisWrapper.style.display = role === 'siswa' ? 'block' : 'none';
            nipWrapper.style.display = role === 'guru' ? 'block' : 'none';
            kelasWrapper.style.display = role === 'siswa' ? 'block' : 'none';
            jenisKelaminWrapper.style.display = showProfile ? 'block' : 'none';

            if (nisInput) nisInput.required = role === 'siswa';
            if (nipInput) nipInput.required = role === 'guru';
            namaInput.required = showProfile;
            if (kelasInput) kelasInput.required = role === 'siswa';
            genderInputs.forEach(function (input) {
                input.required = showProfile;
            });
        }

        roleSelect.addEventListener('change', toggleProfileFields);
        toggleProfileFields();
    });
</script>

<?php require_once __DIR__ . '/Footer.php'; ?>