<?php
// Admin mengelola seluruh penugasan; guru hanya melihat kelas dan siswa yang diajarnya.
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin', 'guru']);
$isAdmin = $_SESSION['role'] === 'admin';
$guruId = $isAdmin ? 0 : (int) (profil_saya($koneksi)['id'] ?? 0);

// Kelompokkan penugasan per mapel dan kelas agar daftar mendukung pengelolaan kelompok.
$query = "SELECT m.id AS mapel_id, m.kode_mapel, m.nama_mapel, COALESCE(g.nama, '-') AS nama_guru,
        s.kelas AS kelas_diajar
    FROM mengajar j
    JOIN mapel m ON m.id = j.mapel_id
    JOIN siswa s ON s.id = j.siswa_id
    LEFT JOIN guru g ON g.id = m.guru_id";
$data = $isAdmin
    ? ambil($koneksi, "$query GROUP BY m.id, m.kode_mapel, m.nama_mapel, g.id, g.nama, s.kelas ORDER BY g.nama ASC, s.kelas ASC, m.nama_mapel ASC")
    : ambil($koneksi, "$query WHERE m.guru_id = ? GROUP BY m.id, m.kode_mapel, m.nama_mapel, g.id, g.nama, s.kelas ORDER BY s.kelas ASC, m.nama_mapel ASC", 'i', [$guruId]);

kepala('Kelola Mengajar', true, true);
require_once dirname(__DIR__) . '/Navbar.php';
?>
<div class="container-fluid table-container">
    <h1 class="judul">Kelola Mengajar</h1>
    <?php notifikasi(
        ['added' => 'Data mengajar berhasil ditambahkan.', 'updated' => 'Data mengajar berhasil diperbarui.', 'deleted' => 'Data mengajar berhasil dihapus.'],
        ['not_found' => 'Data mengajar tidak ditemukan.', 'has_grades' => 'Penugasan tidak dapat dihapus karena sudah memiliki nilai. Hapus nilai terlebih dahulu jika memang perlu.', 'failed' => 'Operasi gagal. Silakan coba lagi.']
    ); ?>
    <?php if ($isAdmin) { ?><div class="d-flex justify-content-end mb-3">
        <a class="btn btn-primary" href="MENGAJAR/Tambah_mengajar.php">Tambah Mengajar</a>
    </div><?php } ?>
    <table id="tabelMengajar" class="table table-striped table-bordered w-100">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Guru</th>
                <th>Kelas</th>
                <th>Nama Mata Pelajaran</th>
                <th>Kode Mata Pelajaran</th>
                <?php if ($isAdmin) { ?><th>Aksi</th><?php } ?>
            </tr>
        </thead>
        <tbody>
        <?php $no = 1; foreach ($data as $d) { ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= h($d['nama_guru']); ?></td>
                <td><?= h($d['kelas_diajar']); ?></td>
                <td><?= h($d['nama_mapel']); ?></td>
                <td><?= h($d['kode_mapel']); ?></td>
                <?php if ($isAdmin) { ?><td class="text-nowrap">
                    <a class="btn btn-warning btn-sm" href="MENGAJAR/Edit_kelompok.php?mapel_id=<?= (int) $d['mapel_id']; ?>&amp;kelas=<?= urlencode($d['kelas_diajar']); ?>">Edit</a>
                    <form class="d-inline" action="MENGAJAR/Aksi_hapus_kelompok.php" method="post" onsubmit="return confirm('Hapus semua penugasan siswa untuk mapel ini di kelas <?= h($d['kelas_diajar']); ?>? Penugasan yang sudah memiliki nilai akan ditolak.')">
                        <input type="hidden" name="mapel_id" value="<?= (int) $d['mapel_id']; ?>">
                        <input type="hidden" name="kelas" value="<?= h($d['kelas_diajar']); ?>">
                        <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                    </form>
                </td><?php } ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php skrip_tabel('tabelMengajar', $isAdmin ? 5 : -1); require_once dirname(__DIR__) . '/Footer.php'; ?>
