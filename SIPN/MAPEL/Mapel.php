<?php
require_once dirname(__DIR__) . '/Auth.php'; wajib_role(['admin', 'guru']);
require_once dirname(__DIR__) . '/Koneksi.php';
$isAdmin = $_SESSION['role'] === 'admin';
$guruId = $isAdmin ? 0 : (int) (profil_saya($koneksi)['id'] ?? 0);
$statusMessages = [
    'added' => 'Mapel berhasil ditambahkan.',
    'updated' => 'Mapel berhasil diperbarui.',
    'deleted' => 'Mapel berhasil dihapus.',
];
$errorMessages = [
    'invalid' => 'Permintaan tidak valid atau sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.',
    'not_found' => 'Data mapel tidak ditemukan.',
    'has_grades' => 'Mapel tidak dapat dihapus karena masih memiliki nilai rapor. Hapus nilai tersebut terlebih dahulu jika memang diperlukan.',
    'has_assignments' => 'Mapel tidak dapat dihapus karena masih memiliki penugasan mengajar.',
    'failed' => 'Operasi mapel gagal. Silakan coba lagi.',
];
$status = $statusMessages[$_GET['status'] ?? ''] ?? null;
$error = $errorMessages[$_GET['error'] ?? ''] ?? null;
// Guru hanya melihat mapel yang diampunya; pengelolaan tetap khusus admin.
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="../">
    <title>Daftar Mapel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css?v=13">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>
<body>
<?php require_once dirname(__DIR__) . '/Navbar.php'; ?>
<div class="container-fluid table-container">
    <h1 class="judul">Daftar Mapel</h1>
    <?php if ($status) { ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($status); ?></div>
    <?php } ?>
    <?php if ($error) { ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error); ?></div>
    <?php } ?>
    <?php if ($isAdmin) { ?>
    <div class="d-flex justify-content-end mb-3">
        <a class="btn btn-primary" href="MAPEL/Tambah_mapel.php">Tambah Mapel</a>
    </div>
    <?php } ?>
    <table id="tabelMapel" class="table table-striped table-bordered w-100">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Mapel</th>
                <th>Nama Mapel</th>
                <th>Guru</th>
                <?php if ($isAdmin) { ?><th>Aksi</th><?php } ?>
            </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        // Gabungkan setiap mapel dengan guru pengampunya.
        if ($isAdmin) {
            $dataMapel = ambil($koneksi, "SELECT m.id, m.kode_mapel, m.nama_mapel,
                COALESCE(g.nama, '-') AS nama_guru
                FROM mapel m
                LEFT JOIN guru g ON g.id = m.guru_id
                ORDER BY m.id ASC");
        } else {
            $dataMapel = ambil($koneksi, "SELECT m.id, m.kode_mapel, m.nama_mapel,
                COALESCE(g.nama, '-') AS nama_guru
                FROM mapel m
                LEFT JOIN guru g ON g.id = m.guru_id
                WHERE m.guru_id = ?
                ORDER BY m.id ASC", 'i', [$guruId]);
        }
        foreach ($dataMapel as $data) {
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars((string) $data['kode_mapel']); ?></td>
                <td><?= htmlspecialchars((string) $data['nama_mapel']); ?></td>
                <td><?= htmlspecialchars((string) $data['nama_guru']); ?></td>
                <?php if ($isAdmin) { ?><td class="text-nowrap">
                    <a class="btn btn-warning btn-sm" href="MAPEL/Edit_mapel.php?id=<?= urlencode((string) $data['id']); ?>">Edit</a>
                    <form class="d-inline" action="MAPEL/Aksi_hapus_mapel.php" method="post" onsubmit="return confirm('Yakin ingin menghapus mapel ini?')">
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $data['id']); ?>">
                        <input type="hidden" name="csrf_token" value="<?= h(token_csrf()); ?>">
                        <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                    </form>
                </td><?php } ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function () {
    $('#tabelMapel').DataTable({
        pageLength: 10,
        columnDefs: [{ orderable: false, targets: 4 }],
        language: {
            lengthMenu: 'Tampilkan _MENU_ data per halaman',
            search: '',
            searchPlaceholder: 'Search',
            zeroRecords: 'Data tidak ditemukan',
            info: 'Menampilkan halaman _PAGE_ dari _PAGES_',
            infoEmpty: 'Menampilkan halaman 0 dari 0',
            paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' }
        }
    });
});
</script>
<?php require_once dirname(__DIR__) . '/Footer.php'; ?>
