<?php
// Daftar nilai rapor.
// - Guru : hanya nilai mapel yang diampunya, bisa Tambah / Edit / Hapus.
// - Admin: melihat seluruh nilai (hanya baca).
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin', 'guru']);

$isGuru = $_SESSION['role'] === 'guru';
if ($isGuru) {
    $guru = profil_saya($koneksi);
    $guruId = (int) ($guru['id'] ?? 0);
    $mapelTerpilih = filter_var($_GET['mapel_id'] ?? null, FILTER_VALIDATE_INT);
    $kelasTerpilih = trim((string) ($_GET['kelas'] ?? ''));
    $lihatKelas = $mapelTerpilih !== false && $mapelTerpilih !== null && $mapelTerpilih > 0 && $kelasTerpilih !== '';
    $sqlPenugasan = 'mengajar';

    if ($lihatKelas) {
        $kelasValid = ambil($koneksi, "SELECT 1
            FROM $sqlPenugasan AS penugasan
            JOIN siswa s ON s.id = penugasan.siswa_id
            JOIN mapel m ON m.id = penugasan.mapel_id
            WHERE m.guru_id = ? AND m.id = ? AND s.kelas = ?
            LIMIT 1", 'iis', [$guruId, $mapelTerpilih, $kelasTerpilih]);
        $lihatKelas = (bool) $kelasValid;
    }

    if ($lihatKelas) {
        $data = ambil($koneksi, "SELECT n.id, penugasan.mapel_id, penugasan.siswa_id, s.nama AS nama_siswa, s.kelas, m.nama_mapel, n.nilai_kkm, n.nilai_pengetahuan, n.nilai_keterampilan, n.Keterangan
        FROM $sqlPenugasan AS penugasan
        JOIN siswa s ON s.id = penugasan.siswa_id
        JOIN mapel m ON m.id = penugasan.mapel_id
        LEFT JOIN nilai_rapor n ON n.mapel_id = penugasan.mapel_id AND n.siswa_id = penugasan.siswa_id
        WHERE m.guru_id = ? AND m.id = ? AND s.kelas = ?
        ORDER BY s.nama", 'iis', [$guruId, $mapelTerpilih, $kelasTerpilih]);
    } else {
        $kelasMapel = ambil($koneksi, "SELECT m.id AS mapel_id, s.kelas, m.nama_mapel, COUNT(DISTINCT s.id) AS jumlah_siswa
            FROM $sqlPenugasan AS penugasan
            JOIN siswa s ON s.id = penugasan.siswa_id
            JOIN mapel m ON m.id = penugasan.mapel_id
            WHERE m.guru_id = ?
            GROUP BY m.id, s.kelas, m.nama_mapel
            ORDER BY s.kelas, m.nama_mapel", 'i', [$guruId]);
        $data = [];
        if (isset($_GET['mapel_id']) || isset($_GET['kelas'])) {
            $_GET['error'] = 'not_found';
        }
    }
} else {
    $data = ambil($koneksi, "SELECT n.id, s.nama AS nama_siswa, s.kelas, m.nama_mapel, n.nilai_kkm, n.nilai_pengetahuan, n.nilai_keterampilan, n.Keterangan
        FROM nilai_rapor n
        JOIN siswa s ON s.id = n.siswa_id
        JOIN mapel m ON m.id = n.mapel_id
        ORDER BY m.nama_mapel, s.nama");
}

kepala('Nilai Siswa', true, true);
require_once dirname(__DIR__) . '/Navbar.php';
// Tampilan berikut berganti antara ringkasan kelas guru, detail nilai, dan daftar admin.
?>
<div class="container-fluid table-container">
    <h1 class="judul"><?= $isGuru ? ($lihatKelas ? 'Kelola Nilai Siswa' : 'Daftar Kelas yang Diajar') : 'Seluruh Nilai Siswa'; ?></h1>
    <?php notifikasi(
        ['added' => 'Nilai berhasil ditambahkan.', 'updated' => 'Nilai berhasil diperbarui.', 'deleted' => 'Nilai berhasil dihapus.'],
        ['not_found' => 'Data nilai atau kelas tidak ditemukan atau bukan milik Anda.', 'failed' => 'Operasi gagal. Silakan coba lagi.']
    ); ?>
    <?php if ($isGuru && !$lihatKelas) { ?>
        <?php if (!$kelasMapel) { ?>
            <div class="alert alert-info">Belum ada siswa yang didaftarkan pada mata pelajaran Anda di menu Mengajar.</div>
        <?php } ?>
        <table id="tabelKelasNilai" class="table table-striped table-bordered w-100">
            <thead>
                <tr><th>No</th><th>Kelas</th><th>Mata Pelajaran</th><th>Jumlah Siswa</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php $no = 1; foreach ($kelasMapel as $kelasMapelItem) { ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= h($kelasMapelItem['kelas']); ?></td>
                    <td><?= h($kelasMapelItem['nama_mapel']); ?></td>
                    <td><?= (int) $kelasMapelItem['jumlah_siswa']; ?> siswa</td>
                    <td><a class="btn btn-primary btn-sm" href="NILAI/Nilai.php?mapel_id=<?= (int) $kelasMapelItem['mapel_id']; ?>&amp;kelas=<?= urlencode($kelasMapelItem['kelas']); ?>">Lihat</a></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php } elseif ($isGuru) { ?>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div><strong><?= h($kelasTerpilih); ?></strong> &middot; <?= h($data[0]['nama_mapel'] ?? 'Mata Pelajaran'); ?></div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="NILAI/Nilai.php">Kembali ke Daftar Kelas</a>
                <a class="btn btn-primary" href="NILAI/Tambah_nilai.php?mapel_id=<?= (int) $mapelTerpilih; ?>">Input Nilai</a>
            </div>
        </div>
    <?php } ?>
    <?php if (!$isGuru || $lihatKelas) { ?>
    <?php if (!$isGuru) { ?>
        <div class="d-flex justify-content-end mb-3">
            <span class="text-secondary">Mode lihat saja</span>
        </div>
    <?php } ?>
    <table id="tabelNilai" class="table table-striped table-bordered w-100">
        <thead>
            <tr>
                <th>No</th><th>Siswa</th><th>Kelas</th><th>Mata Pelajaran</th><th>KKM</th>
                <th>Pengetahuan</th><th>Keterampilan</th><th>Rata-rata</th><th>Keterangan</th>
                <?php if ($isGuru) { ?><th>Aksi</th><?php } ?>
            </tr>
        </thead>
        <tbody>
        <?php $no = 1; foreach ($data as $d) { ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= h($d['nama_siswa']); ?></td>
                <td><?= h($d['kelas']); ?></td>
                <td><?= h($d['nama_mapel']); ?></td>
                <td><?= $d['id'] === null ? '-' : (int) $d['nilai_kkm']; ?></td>
                <td><?= $d['id'] === null ? '-' : (int) $d['nilai_pengetahuan']; ?></td>
                <td><?= $d['id'] === null ? '-' : (int) $d['nilai_keterampilan']; ?></td>
                <td><?= $d['id'] === null ? '-' : number_format(($d['nilai_pengetahuan'] + $d['nilai_keterampilan']) / 2, 1); ?></td>
                <td><?php if ($d['id'] === null) { ?><span class="badge text-bg-secondary">Belum dinilai</span><?php } else { ?><span class="badge text-bg-<?= $d['Keterangan'] === 'Tuntas' ? 'success' : 'danger'; ?>"><?= h($d['Keterangan']); ?></span><?php } ?></td>
                <?php if ($isGuru) { ?>
                <td class="text-nowrap">
                    <?php if ($d['id'] === null) { ?>
                        <a class="btn btn-primary btn-sm" href="NILAI/Tambah_nilai.php?mapel_id=<?= (int) $d['mapel_id']; ?>&amp;siswa_id=<?= (int) $d['siswa_id']; ?>">Input Nilai</a>
                    <?php } else { ?>
                    <a class="btn btn-warning btn-sm" href="NILAI/Edit_nilai.php?id=<?= (int) $d['id']; ?>">Edit</a>
                    <form class="d-inline" action="NILAI/Aksi_hapus_nilai.php" method="post" onsubmit="return confirm('Yakin ingin menghapus nilai ini?')">
                        <input type="hidden" name="id" value="<?= (int) $d['id']; ?>">
                        <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                    </form>
                    <?php } ?>
                </td>
                <?php } ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <?php } ?>
</div>
<?php
if ($isGuru && !$lihatKelas) {
    skrip_tabel('tabelKelasNilai', 4);
} elseif (!$isGuru || $lihatKelas) {
    skrip_tabel('tabelNilai', $isGuru ? 9 : -1);
}
require_once dirname(__DIR__) . '/Footer.php';
?>
