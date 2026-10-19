<?php
// Form input nilai (guru). Siswa berasal dari pasangan yang tercatat di Mengajar.
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['guru']);

// Ambil hanya pasangan mapel-siswa tugas guru yang belum memiliki nilai.
$guru = profil_saya($koneksi);
$mapelTerpilih = filter_input(INPUT_GET, 'mapel_id', FILTER_VALIDATE_INT) ?: 0;
$siswaTerpilih = filter_input(INPUT_GET, 'siswa_id', FILTER_VALIDATE_INT) ?: 0;
$pilihan = ambil($koneksi, "SELECT penugasan.mapel_id, penugasan.siswa_id, s.nama, s.kelas, m.nama_mapel
        FROM mengajar AS penugasan
        JOIN mapel m ON m.id = penugasan.mapel_id
        JOIN siswa s ON s.id = penugasan.siswa_id
    WHERE m.guru_id = ?
            AND NOT EXISTS (SELECT 1 FROM nilai_rapor n WHERE n.mapel_id = penugasan.mapel_id AND n.siswa_id = penugasan.siswa_id)
    ORDER BY m.nama_mapel, s.nama", 'i', [(int) ($guru['id'] ?? 0)]);

kepala('Input Nilai', true);
require_once dirname(__DIR__) . '/Navbar.php';
?>
<main class="container py-4">
    <h1 class="h2 mb-4">Input Nilai Siswa</h1>
    <?php notifikasi([], ['invalid' => 'Data tidak valid. Nilai harus angka 0 sampai 100.', 'notyours' => 'Siswa/mapel tersebut bukan milik Anda atau sudah dinilai.', 'duplicate' => 'Nilai untuk pasangan siswa dan mata pelajaran tersebut sudah ada.', 'failed' => 'Operasi gagal. Silakan coba lagi.']); ?>
    <?php if (!$pilihan) { ?>
        <div class="alert alert-info">Tidak ada siswa yang perlu dinilai. Semua siswa yang terdaftar pada mata pelajaran Anda di menu Mengajar sudah memiliki nilai.</div>
    <?php } ?>
    <form action="NILAI/Aksi_tambah_nilai.php" method="post" class="kartu row g-3">
        <div class="col-12">
            <label for="pasangan" class="form-label">Siswa &amp; Mata Pelajaran</label>
            <select class="form-select" id="pasangan" name="pasangan" required>
                <option value="">-- pilih --</option>
                <?php foreach ($pilihan as $p) { ?>
                    <option value="<?= (int) $p['mapel_id'] . ':' . (int) $p['siswa_id']; ?>" <?= (int) $p['mapel_id'] === $mapelTerpilih && (int) $p['siswa_id'] === $siswaTerpilih ? 'selected' : ''; ?>><?= h($p['nama'] . ' (' . $p['kelas'] . ') - ' . $p['nama_mapel']); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-4"><label class="form-label" for="nilai_kkm">Nilai KKM</label>
            <input type="number" class="form-control" id="nilai_kkm" name="nilai_kkm" min="0" max="100" value="75" required></div>
        <div class="col-md-4"><label class="form-label" for="nilai_pengetahuan">Nilai Pengetahuan</label>
            <input type="number" class="form-control" id="nilai_pengetahuan" name="nilai_pengetahuan" min="0" max="100" required></div>
        <div class="col-md-4"><label class="form-label" for="nilai_keterampilan">Nilai Keterampilan</label>
            <input type="number" class="form-control" id="nilai_keterampilan" name="nilai_keterampilan" min="0" max="100" required></div>
        <div class="col-12 form-text">Keterangan terisi otomatis: <b>Tuntas</b> jika rata-rata pengetahuan &amp; keterampilan &ge; KKM.</div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan Nilai</button>
            <a href="NILAI/Nilai.php" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</main>
<?php require_once dirname(__DIR__) . '/Footer.php'; ?>
