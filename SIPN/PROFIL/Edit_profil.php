<?php
// Form edit profil. NIS/NIP dan kelas siswa hanya dapat diubah admin lewat Kelola User.
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin', 'guru', 'siswa']);

$role = $_SESSION['role'];
$profil = profil_saya($koneksi);
$fotoUrl = foto_profil_url((int) $_SESSION['user_id']);
$namaTampil = trim((string) ($profil['nama'] ?? $_SESSION['username']));
$inisial = preg_match('/^./us', $namaTampil, $cocokInisial) ? strtoupper($cocokInisial[0]) : '';

// Siapkan tampilan awal nama/foto dan form profil sesuai role pengguna.
kepala('Edit Profil', true);
require_once dirname(__DIR__) . '/Navbar.php';
?>
<main class="container py-4" style="max-width: 640px;">
    <h1 class="h2 mb-4">Edit Profil</h1>
    <?php notifikasi([], [
        'duplicate' => 'Username sudah dipakai pengguna lain.',
        'invalid' => 'Data tidak valid. Password baru minimal 6 karakter.',
        'failed' => 'Operasi gagal. Silakan coba lagi.',
        'photo_invalid' => 'Foto harus berupa JPEG, PNG, atau WebP dengan ukuran maksimal 3 MB.',
        'photo_failed' => 'Data profil tersimpan, tetapi foto gagal disimpan. Silakan unggah kembali.',
    ]); ?>
    <form action="PROFIL/Aksi_edit_profil.php" method="post" enctype="multipart/form-data" class="kartu row g-3">
        <div class="col-12 d-flex align-items-center gap-3">
            <div class="flex-shrink-0">
                <img id="fotoPreview" class="profile-photo" src="<?= h($fotoUrl ?? ''); ?>" alt="Pratinjau foto profil" data-original="<?= h($fotoUrl ?? ''); ?>" <?= $fotoUrl ? '' : 'hidden'; ?>>
                <span id="fotoInisial" class="profile-photo profile-photo-fallback" <?= $fotoUrl ? 'hidden' : ''; ?>><?= h($inisial); ?></span>
            </div>
            <div class="flex-grow-1">
                <label for="foto_profil" class="form-label">Foto Profil</label>
                <input type="file" class="form-control" id="foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/webp">
                <div class="form-text">Format JPG, PNG, atau WebP. Maksimal 3 MB.</div>
            </div>
        </div>
        <div class="col-12">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" maxlength="50" value="<?= h($_SESSION['username']); ?>" required>
        </div>
        <div class="col-12">
            <label for="password" class="form-label">Password Baru</label>
            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
            <div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>
        </div>
        <?php if ($profil) { ?>
            <div class="col-12">
                <label class="form-label"><?= $role === 'siswa' ? 'NIS' : 'NIP'; ?></label>
                <input type="text" class="form-control" value="<?= h($profil['nis'] ?? $profil['nip']); ?>" disabled>
            </div>
            <div class="col-12">
                <label for="nama" class="form-label">Nama</label>
                <input type="text" class="form-control" id="nama" name="nama" maxlength="100" value="<?= h($profil['nama']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                    <option value="L" <?= $profil['jenis_kelamin'] === 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="P" <?= $profil['jenis_kelamin'] === 'P' ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>
        <?php } ?>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="PROFIL/Profil.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</main>
<script>
const inputFoto = document.getElementById('foto_profil');
const fotoPreview = document.getElementById('fotoPreview');
const fotoInisial = document.getElementById('fotoInisial');
inputFoto.addEventListener('change', () => {
    const fileFoto = inputFoto.files[0];
    if (!fileFoto) {
        fotoPreview.hidden = !fotoPreview.dataset.original;
        fotoInisial.hidden = Boolean(fotoPreview.dataset.original);
        fotoPreview.src = fotoPreview.dataset.original;
        return;
    }
    fotoPreview.src = URL.createObjectURL(fileFoto);
    fotoPreview.hidden = false;
    fotoInisial.hidden = true;
});
</script>
<?php require_once dirname(__DIR__) . '/Footer.php'; ?>
