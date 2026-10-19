<?php
// Form edit satu baris Mengajar (ganti mapel atau siswa).
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin']);

// Ambil satu relasi mengajar dan opsi mapel/siswa untuk mengisi form perubahan.
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$baris = $id ? (ambil($koneksi, 'SELECT * FROM mengajar WHERE id = ?', 'i', [$id])[0] ?? null) : null;
if (!$baris) {
    header('Location: Mengajar.php?error=not_found');
    exit;
}
$mapel = ambil($koneksi, 'SELECT id, kode_mapel, nama_mapel FROM mapel ORDER BY nama_mapel');
$siswa = ambil($koneksi, 'SELECT id, nama, kelas FROM siswa ORDER BY kelas, nama');

kepala('Edit Mengajar', true);
require_once dirname(__DIR__) . '/Navbar.php';
?>
<main class="container py-4">
    <h1 class="h2 mb-4">Edit Mengajar</h1>
    <?php notifikasi([], ['duplicate' => 'Pasangan mapel dan siswa tersebut sudah ada.', 'invalid' => 'Data tidak valid.', 'has_grades' => 'Penugasan tidak dapat diubah karena sudah memiliki nilai.', 'failed' => 'Operasi gagal. Silakan coba lagi.']); ?>
    <form action="MENGAJAR/Aksi_edit_mengajar.php" method="post" class="kartu row g-3">
        <input type="hidden" name="id" value="<?= (int) $baris['id']; ?>">
        <div class="col-md-6">
            <label for="mapel_id" class="form-label">Mata Pelajaran</label>
            <select class="form-select" id="mapel_id" name="mapel_id" required>
                <?php foreach ($mapel as $m) { ?>
                    <option value="<?= (int) $m['id']; ?>" <?= (int) $m['id'] === (int) $baris['mapel_id'] ? 'selected' : ''; ?>><?= h($m['kode_mapel'] . ' - ' . $m['nama_mapel']); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-6">
            <label for="siswa_id" class="form-label">Siswa</label>
            <select class="form-select" id="siswa_id" name="siswa_id" required>
                <?php foreach ($siswa as $s) { ?>
                    <option value="<?= (int) $s['id']; ?>" <?= (int) $s['id'] === (int) $baris['siswa_id'] ? 'selected' : ''; ?>><?= h($s['nama'] . ' (' . $s['kelas'] . ')'); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="MENGAJAR/Mengajar.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</main>
<?php require_once dirname(__DIR__) . '/Footer.php'; ?>
