<?php
// Halaman lihat profil untuk semua role (admin, guru, siswa).
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin', 'guru', 'siswa']);

// Muat akun dan profil sesi aktif untuk merakit ringkasan yang tampil di halaman.
$role = $_SESSION['role'];
$user = ambil($koneksi, 'SELECT username, role, created_at FROM users WHERE id = ?', 'i', [(int) $_SESSION['user_id']])[0] ?? null;
if (!$user) {
    header('Location: ../Logout.php');
    exit;
}
$profil = profil_saya($koneksi);
$displayName = trim((string) ($profil['nama'] ?? $user['username']));
$initial = preg_match('/^./us', $displayName, $initialMatch) ? strtoupper($initialMatch[0]) : '';
$fotoUrl = foto_profil_url((int) $_SESSION['user_id']);
$roleBadgeClass = match (strtolower((string) $user['role'])) {
    'admin' => 'role-badge-admin',
    'guru' => 'role-badge-guru',
    'siswa' => 'role-badge-siswa',
    default => 'role-badge-default',
};
// Ubah kode jenis kelamin menjadi label ringkas untuk tabel profil.
$jk = fn($v) => $v === 'L' ? 'Laki-laki' : ($v === 'P' ? 'Perempuan' : '-');

$baris = ['Username' => $user['username'], 'Role' => ucfirst($user['role']), 'Terdaftar sejak' => $user['created_at']];
if ($profil) {
    $baris[$role === 'siswa' ? 'NIS' : 'NIP'] = $profil['nis'] ?? $profil['nip'];
    $baris['Nama'] = $profil['nama'];
    $baris['Jenis Kelamin'] = $jk($profil['jenis_kelamin']);
    if ($role === 'siswa') $baris['Kelas'] = $profil['kelas'];
}

kepala('Profil Saya', true);
require_once dirname(__DIR__) . '/Navbar.php';
?>
<main class="container py-4 profile-page">
    <h1 class="h2 mb-4 profile-heading">Profil Saya</h1>
    <?php notifikasi(['updated' => 'Profil berhasil diperbarui.'], []); ?>
    <div class="kartu profile-card">
        <div class="d-flex justify-content-center mb-3">
            <?php if ($fotoUrl) { ?>
                <img src="<?= h($fotoUrl); ?>" class="profile-photo" alt="Foto profil <?= h($displayName); ?>">
            <?php } else { ?>
                <span class="profile-photo profile-photo-fallback" aria-hidden="true"><?= h($initial); ?></span>
            <?php } ?>
        </div>
        <div class="profile-details mb-4">
            <?php foreach ($baris as $label => $isi) { ?>
                <div class="profile-detail-row">
                    <div class="profile-detail-label"><?= h($label); ?></div>
                    <div class="profile-detail-value"><?php if ($label === 'Role') { ?><span class="role-badge <?= h($roleBadgeClass); ?>"><?= h($isi); ?></span><?php } else { ?><?= h($isi); ?><?php } ?></div>
                </div>
            <?php } ?>
        </div>
        <a href="PROFIL/Edit_profil.php" class="btn btn-primary">Edit Profil</a>
    </div>
</main>
<?php require_once dirname(__DIR__) . '/Footer.php'; ?>
