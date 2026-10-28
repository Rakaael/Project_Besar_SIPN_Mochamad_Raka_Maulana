<?php
require_once __DIR__ . '/Auth.php'; wajib_role(['admin']);
// File ini menampilkan daftar user versi kedua.
// Versi ini menampilkan informasi akun dan waktu pembuatan.
require_once __DIR__ . '/Koneksi.php';
$csrfToken = token_csrf();
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar User 2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css?v=13">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>

<body>
<?php require_once __DIR__ . '/Navbar.php'; ?>
<div class="container-fluid table-container">
    <h1 class="judul">Daftar User 2</h1>
    <?php notifikasi(['deleted' => 'User berhasil dihapus.'], [
        'invalid' => 'Permintaan tidak valid atau sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.',
        'not_found' => 'User tidak ditemukan.',
        'has_grades' => 'User siswa tidak dapat dihapus karena masih memiliki riwayat nilai. Hapus atau pindahkan nilai terlebih dahulu.',
        'failed' => 'User gagal dihapus karena masih memiliki data terkait. Tidak ada perubahan yang disimpan.',
    ]); ?>

    <table id="tabelUser" class="table table-striped table-bordered w-100">
        <thead>
            <tr>
                <th>No</th>
                <th>Username</th>
                <th>Password</th>
                <th>Role</th>
                <th>Created</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Nomor urut untuk daftar.
        $no = 1;

        // Password ditampilkan sebagai hash sesuai permintaan, bukan sebagai password asli.
        $query = mysqli_query($koneksi, "SELECT u.id, u.username, u.password, u.role, u.created_at
            FROM users u
            ORDER BY u.id DESC");

        if (!$query) {
            die('Query gagal: ' . mysqli_error($koneksi));
        }

        while ($data = mysqli_fetch_assoc($query)) {
            $id_user = $data['id'] ?? null;
            $username = $data['username'] ?? '';
            $passwordHash = $data['password'] ?? '';
            $role = $data['role'] ?? '';
            $created_at = $data['created_at'] ?? '';
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($passwordHash, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?= htmlspecialchars($created_at, ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php if ($id_user !== null && $id_user !== '') { ?>
                        <a href="edit_user.php?id=<?= urlencode((string) $id_user); ?>" class="btn btn-warning btn-sm">Edit</a>
                        <form action="hapus_user.php" method="post" class="d-inline" onsubmit="return confirm(<?= json_encode($role === 'guru' ? 'Hapus guru ini dan semua penugasan mengajarnya? Nilai siswa tetap disimpan; mapel yang memiliki nilai akan dipertahankan tanpa guru pengampu.' : 'Yakin ingin menghapus data ini?', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)">
                            <input type="hidden" name="id" value="<?= (int) $id_user; ?>">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken); ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function () {
    $('#tabelUser').DataTable({
        pageLength: 10,
        language: {
            lengthMenu: 'Tampilkan _MENU_ data per halaman',
            search: '',
            searchPlaceholder: 'Search',
            zeroRecords: 'Data tidak ditemukan',
            info: 'Menampilkan halaman _PAGE_ dari _PAGES_',
            infoEmpty: 'Menampilkan halaman 0 dari 0',
            paginate: {
                previous: 'Sebelumnya',
                next: 'Selanjutnya'
            }
        },
        columnDefs: [{ orderable: false, targets: 5 }]
    });
});
</script>
</body>
</html>
