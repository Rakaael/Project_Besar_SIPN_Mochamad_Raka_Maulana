<?php
// Form tambah Mengajar: pilih satu mapel lalu centang satu atau banyak siswa sekaligus.
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin']);

// Muat daftar mapel dan siswa untuk dipilih pada formulir penugasan.
$mapel = ambil($koneksi, "SELECT m.id, m.kode_mapel, m.nama_mapel, COALESCE(g.nama, '-') AS nama_guru FROM mapel m LEFT JOIN guru g ON g.id = m.guru_id ORDER BY m.nama_mapel");
$siswa = ambil($koneksi, 'SELECT id, nis, nama, kelas FROM siswa ORDER BY kelas, nama');

kepala('Tambah Mengajar', true);
require_once dirname(__DIR__) . '/Navbar.php';
?>
<main class="container py-4">
    <h1 class="h2 mb-4">Tambah Mengajar</h1>
    <?php notifikasi([], ['invalid' => 'Pilih mata pelajaran yang tersedia dan minimal satu siswa yang valid.', 'duplicate' => 'Semua siswa yang dipilih sudah terdaftar di mapel tersebut.', 'failed' => 'Operasi gagal. Silakan coba lagi.']); ?>
    <form action="MENGAJAR/Aksi_tambah_mengajar.php" method="post" class="kartu">
        <div class="mb-3">
            <label for="mapel_id" class="form-label">Mata Pelajaran</label>
            <select class="form-select" id="mapel_id" name="mapel_id" required>
                <option value="">-- pilih mapel --</option>
                <?php foreach ($mapel as $m) { ?>
                    <option value="<?= (int) $m['id']; ?>"><?= h($m['kode_mapel'] . ' - ' . $m['nama_mapel'] . ' (Guru: ' . $m['nama_guru'] . ')'); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="mb-2 d-flex justify-content-between align-items-center">
            <label class="form-label mb-0">Siswa</label>
            <div class="form-check"><input class="form-check-input" type="checkbox" id="pilihSemua"><label class="form-check-label" for="pilihSemua">Pilih semua</label></div>
        </div>
        <div class="row g-2 mb-3" style="max-height: 320px; overflow-y: auto;">
            <?php foreach ($siswa as $s) { ?>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input cek-siswa" type="checkbox" name="siswa_id[]" value="<?= (int) $s['id']; ?>" id="s<?= (int) $s['id']; ?>">
                        <label class="form-check-label" for="s<?= (int) $s['id']; ?>"><?= h($s['nama'] . ' (' . $s['kelas'] . ')'); ?></label>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="MENGAJAR/Mengajar.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</main>
<script>
document.getElementById('pilihSemua').addEventListener('change', function () {
    document.querySelectorAll('.cek-siswa').forEach(function (c) { c.checked = this.checked; }, this);
});
</script>
<?php require_once dirname(__DIR__) . '/Footer.php'; ?>
