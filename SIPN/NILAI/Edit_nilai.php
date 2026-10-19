<?php
// Form edit nilai. Guru hanya dapat membuka nilai untuk siswa yang terdaftar pada mapelnya di Mengajar.
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['guru']);

// Ambil nilai hanya jika mapelnya milik guru dan siswa terdaftar pada penugasan tersebut.
$guru = profil_saya($koneksi);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$n = $id ? (ambil($koneksi, "SELECT n.*, s.nama AS nama_siswa, s.kelas, m.nama_mapel
    FROM nilai_rapor n JOIN siswa s ON s.id = n.siswa_id JOIN mapel m ON m.id = n.mapel_id
        WHERE n.id = ? AND m.guru_id = ?
            AND EXISTS (SELECT 1 FROM mengajar j WHERE j.mapel_id = m.id AND j.siswa_id = s.id)", 'ii', [$id, (int) ($guru['id'] ?? 0)])[0] ?? null) : null;
if (!$n) {
    header('Location: Nilai.php?error=not_found');
    exit;
}

kepala('Edit Nilai', true);
require_once dirname(__DIR__) . '/Navbar.php';
?>
<main class="container py-4">
    <h1 class="h2 mb-4">Edit Nilai</h1>
    <?php notifikasi([], ['invalid' => 'Nilai harus angka 0 sampai 100.', 'failed' => 'Operasi gagal. Silakan coba lagi.']); ?>
    <form action="NILAI/Aksi_edit_nilai.php" method="post" class="kartu row g-3">
        <input type="hidden" name="id" value="<?= (int) $n['id']; ?>">
        <div class="col-md-6"><label class="form-label">Siswa</label>
            <input type="text" class="form-control" value="<?= h($n['nama_siswa'] . ' (' . $n['kelas'] . ')'); ?>" disabled></div>
        <div class="col-md-6"><label class="form-label">Mata Pelajaran</label>
            <input type="text" class="form-control" value="<?= h($n['nama_mapel']); ?>" disabled></div>
        <div class="col-md-4"><label class="form-label" for="nilai_kkm">Nilai KKM</label>
            <input type="number" class="form-control" id="nilai_kkm" name="nilai_kkm" min="0" max="100" value="<?= (int) $n['nilai_kkm']; ?>" required></div>
        <div class="col-md-4"><label class="form-label" for="nilai_pengetahuan">Nilai Pengetahuan</label>
            <input type="number" class="form-control" id="nilai_pengetahuan" name="nilai_pengetahuan" min="0" max="100" value="<?= (int) $n['nilai_pengetahuan']; ?>" required></div>
        <div class="col-md-4"><label class="form-label" for="nilai_keterampilan">Nilai Keterampilan</label>
            <input type="number" class="form-control" id="nilai_keterampilan" name="nilai_keterampilan" min="0" max="100" value="<?= (int) $n['nilai_keterampilan']; ?>" required></div>
        <div class="col-12 form-text">Keterangan akan dihitung ulang otomatis.</div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="NILAI/Nilai.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</main>
<?php require_once dirname(__DIR__) . '/Footer.php'; ?>
