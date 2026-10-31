<?php
require_once __DIR__ . '/Auth.php'; wajib_role(['admin']);
// File ini menampilkan form edit data user berdasarkan id yang dikirim melalui URL.
// Form ini di-load dulu dengan data lama agar user dapat memperbarui isi form.
require_once __DIR__ . '/Koneksi.php';

// Ambil profil terkait agar form dapat menampilkan data siswa atau guru yang sedang diedit.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: Daftar_user.php?error=invalid');
    exit;
}

$query = mysqli_prepare($koneksi, "SELECT u.id, u.username, u.role,
        s.nis, s.nama AS siswa_nama, s.kelas, s.jenis_kelamin AS siswa_jk,
        g.nip, g.nama AS guru_nama, g.jenis_kelamin AS guru_jk
    FROM users u
    LEFT JOIN siswa s ON s.user_id = u.id
    LEFT JOIN guru g ON g.user_id = u.id
    WHERE u.id = ?");

if (!$query) {
    die('Error query: ' . mysqli_error($koneksi));
}

mysqli_stmt_bind_param($query, 'i', $id);
if (!mysqli_stmt_execute($query)) {
    die('Error query: ' . mysqli_stmt_error($query));
}
$result = mysqli_stmt_get_result($query);
$user = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($query);

if (!$user) {
    header('Location: Daftar_user.php?error=not_found');
    exit;
}

$role = $user['role'] ?? 'siswa';
$nama = $role === 'guru' ? ($user['guru_nama'] ?? '') : ($user['siswa_nama'] ?? '');
$nis = $user['nis'] ?? '';
$nip = $user['nip'] ?? '';
$kelas = $user['kelas'] ?? '';
$jenis_kelamin = $role === 'guru' ? ($user['guru_jk'] ?? '') : ($user['siswa_jk'] ?? '');

// Helper output agar aman di HTML dan opsi role terpilih sesuai nilai data.
$e = fn($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$selected = fn($value, $option) => ($value ?? '') === $option ? 'selected' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css?v=14">
    <style>
        body { background-color: #f4f6f9; min-height: 100vh; }
        .wrapper { max-width: 700px; margin: 50px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 18px rgba(0,0,0,0.08); padding: 30px; }
        h2 { margin-bottom: 25px; font-weight: 600; }
        .form-label { font-weight: 600; margin-bottom: 8px; }
        .form-control, .form-select { margin-bottom: 16px; }
        .btn-group { display: flex; gap: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/Navbar.php'; ?>
    <div class="container">
        <div class="wrapper">
            <h2>Edit User</h2>
            <?php
            $errorMessages = [
                'duplicate' => 'Username, NIS, atau NIP sudah digunakan.',
                'invalid' => 'Data tidak valid. Periksa kembali semua kolom.',
                'not_found' => 'Akun tidak ditemukan.',
                'role_in_use' => 'Role tidak dapat diubah karena akun masih memiliki nilai, penugasan siswa, atau mata pelajaran terkait.',
                'failed' => 'Data gagal disimpan. Silakan periksa database dan coba lagi.',
            ];
            $error = $errorMessages[$_GET['error'] ?? ''] ?? null;
            if ($error) { ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php } ?>

            <form action="proses_edit.php" method="POST">
                <input type="hidden" name="id" value="<?= $e($user['id']); ?>">

                <div id="profile-fields" style="display: <?= $role === 'admin' ? 'none' : 'block'; ?>;">
                    <div class="row" id="nis-row" style="display: <?= $role === 'siswa' ? 'flex' : 'none'; ?>;">
                        <div class="col-md-6">
                            <label for="nis" class="form-label">NIS</label>
                            <input type="text" class="form-control" id="nis" name="nis" maxlength="10" value="<?= $e($nis); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="kelas" class="form-label">Kelas</label>
                            <input type="text" class="form-control" id="kelas" name="kelas" maxlength="10" value="<?= $e($kelas); ?>">
                        </div>
                    </div>

                    <div class="row" id="nip-row" style="display: <?= $role === 'guru' ? 'flex' : 'none'; ?>;">
                        <div class="col-md-12">
                            <label for="nip" class="form-label">NIP</label>
                            <input type="text" class="form-control" id="nip" name="nip" maxlength="20" value="<?= $e($nip); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="nama" class="form-label">Nama</label>
                            <input type="text" class="form-control" id="nama" name="nama" maxlength="100" value="<?= $e($nama); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label">Jenis Kelamin</label>
                            <div class="mt-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="jenis_kelamin" id="laki_laki" value="L" <?= ($jenis_kelamin ?? '') === 'L' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="laki_laki">Laki-laki</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="jenis_kelamin" id="perempuan" value="P" <?= ($jenis_kelamin ?? '') === 'P' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="perempuan">Perempuan</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" maxlength="50" value="<?= $e($user['username']); ?>" required>

                <label for="password" class="form-label">Password Baru</label>
                <input type="password" class="form-control" id="password" name="password" minlength="6" maxlength="4096" placeholder="Kosongkan jika tidak ingin mengubah">

                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="siswa" <?= $selected($user['role'], 'siswa'); ?>>Siswa</option>
                    <option value="guru" <?= $selected($user['role'], 'guru'); ?>>Guru</option>
                    <option value="admin" <?= $selected($user['role'], 'admin'); ?>>Admin</option>
                </select>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="Daftar_user2.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const roleSelect = document.getElementById('role');
            const nisRow = document.getElementById('nis-row');
            const nipRow = document.getElementById('nip-row');
            const nisInput = document.getElementById('nis');
            const nipInput = document.getElementById('nip');
            const kelasInput = document.getElementById('kelas');
            const namaInput = document.getElementById('nama');
            const genderInputs = document.querySelectorAll('input[name="jenis_kelamin"]');

            function toggleRoleFields() {
                const role = roleSelect.value;
                const showProfile = role === 'siswa' || role === 'guru';
                document.getElementById('profile-fields').style.display = showProfile ? 'block' : 'none';
                nisRow.style.display = role === 'siswa' ? 'flex' : 'none';
                nipRow.style.display = role === 'guru' ? 'flex' : 'none';

                if (nisInput) nisInput.required = role === 'siswa';
                if (nipInput) nipInput.required = role === 'guru';
                if (kelasInput) kelasInput.required = role === 'siswa';
                namaInput.required = showProfile;
                genderInputs.forEach(function (input) { input.required = showProfile; });
            }

            roleSelect.addEventListener('change', toggleRoleFields);
            toggleRoleFields();
        });
    </script>
</body>
</html>
