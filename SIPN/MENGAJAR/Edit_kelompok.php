<?php
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin']);

// Pastikan kelompok mapel-kelas valid, lalu siapkan pilihan dan status siswa saat ini.
$mapelId = filter_input(INPUT_GET, 'mapel_id', FILTER_VALIDATE_INT);
$kelas = trim((string) ($_GET['kelas'] ?? ''));
if (!$mapelId || $mapelId < 1 || $kelas === '') {
    header('Location: Mengajar.php?error=not_found');
    exit;
}

$kelompok = ambil($koneksi, "SELECT m.id, m.nama_mapel, m.kode_mapel, s.kelas
    FROM mengajar j
    JOIN mapel m ON m.id = j.mapel_id
    JOIN siswa s ON s.id = j.siswa_id
    WHERE m.id = ? AND s.kelas = ?
    GROUP BY m.id, m.nama_mapel, m.kode_mapel, s.kelas
    LIMIT 1", 'is', [$mapelId, $kelas])[0] ?? null;
if (!$kelompok) {
    header('Location: Mengajar.php?error=not_found');
    exit;
}

$siswa = ambil($koneksi, 'SELECT id, nama FROM siswa WHERE kelas = ? ORDER BY nama', 's', [$kelas]);
$terdaftar = array_map('intval', array_column(
    ambil($koneksi, 'SELECT j.siswa_id FROM mengajar j JOIN siswa s ON s.id = j.siswa_id WHERE j.mapel_id = ? AND s.kelas = ?', 'is', [$mapelId, $kelas]),
    'siswa_id'
));

kepala('Edit Penugasan Mengajar', true);
require_once dirname(__DIR__) . '/Navbar.php';
?>
<main class="container py-4">
    <h1 class="h2 mb-4">Edit Penugasan Mengajar</h1>
    <?php notifikasi([], [
        'invalid' => 'Pilihan siswa tidak valid untuk kelas ini.',
        'empty' => 'Pilih minimal satu siswa. Untuk menghapus seluruh penugasan gunakan tombol Hapus pada daftar.',
        'has_grades' => 'Penugasan siswa yang memiliki nilai tidak dapat dihapus. Hapus nilai terlebih dahulu jika memang perlu.',
        'failed' => 'Perubahan gagal disimpan. Tidak ada perubahan yang diterapkan.',
    ]); ?>
    <div class="kartu">
        <p><strong>Mata Pelajaran:</strong> <?= h($kelompok['kode_mapel'] . ' - ' . $kelompok['nama_mapel']); ?><br>
            <strong>Kelas:</strong> <?= h($kelas); ?></p>
        <p class="text-secondary">Pilih siswa yang terdaftar pada mata pelajaran ini di kelas tersebut. Penugasan yang sudah memiliki nilai tidak dapat dihapus.</p>
        <form action="MENGAJAR/Aksi_edit_kelompok.php" method="post">
            <input type="hidden" name="mapel_id" value="<?= (int) $mapelId; ?>">
            <input type="hidden" name="kelas" value="<?= h($kelas); ?>">
            <div class="row g-2 mb-3" style="max-height: 360px; overflow-y: auto;">
                <?php foreach ($siswa as $s) { ?>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="siswa_id[]" value="<?= (int) $s['id']; ?>" id="s<?= (int) $s['id']; ?>" <?= in_array((int) $s['id'], $terdaftar, true) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="s<?= (int) $s['id']; ?>"><?= h($s['nama']); ?></label>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="MENGAJAR/Mengajar.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</main>
<?php require_once dirname(__DIR__) . '/Footer.php'; ?>
