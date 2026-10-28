<?php
require_once __DIR__ . '/Auth.php'; wajib_role(['admin', 'siswa']);
require_once __DIR__ . '/Koneksi.php';
$isAdmin = $_SESSION['role'] === 'admin';
$daftarSiswa = !$isAdmin || ($_GET['role'] ?? '') === 'siswa';
$csrfToken = $isAdmin ? token_csrf() : '';
$kelasSaatIni = '';
// Siswa hanya melihat daftar teman sekelas; kelas ditentukan dari profil sesi aktif.
if ($daftarSiswa && !$isAdmin) {
    $profilSaya = profil_saya($koneksi);
    $kelasSaatIni = trim((string) ($profilSaya['kelas'] ?? ''));
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $daftarSiswa ? ($isAdmin ? 'Daftar Siswa' : 'Daftar Siswa Kelas') : 'Daftar User'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css?v=13">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>

<body>
<?php require_once __DIR__ . '/Navbar.php'; ?>
<div class="container-fluid table-container">
    <h1 class="judul"><?= $daftarSiswa ? ($isAdmin ? 'Daftar Siswa' : 'Daftar Siswa Kelas ' . htmlspecialchars($kelasSaatIni, ENT_QUOTES, 'UTF-8')) : 'Daftar User'; ?></h1>
    <?php if ($isAdmin) { notifikasi(['deleted' => 'User berhasil dihapus.'], [
        'invalid' => 'Permintaan tidak valid atau sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.',
        'not_found' => 'User tidak ditemukan.',
        'has_grades' => 'User siswa tidak dapat dihapus karena masih memiliki riwayat nilai. Hapus atau pindahkan nilai terlebih dahulu.',
        'failed' => 'User gagal dihapus karena masih memiliki data terkait. Tidak ada perubahan yang disimpan.',
    ]); } ?>

    <table id="tabelUser" class="table table-striped table-bordered w-100">
        <thead>
            <tr>
                <th>No</th>
                <th>Username</th>
                <th>Role</th>
                <th>Nama</th>
                <?php if ($isAdmin) { ?>
                <th>Aksi</th>
                <?php } else { ?>
                <th>Kelas</th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;

        // Pilih kueri sesuai hak akses: siswa dibatasi ke kelasnya, admin dapat melihat semua akun.
        if ($daftarSiswa && !$isAdmin) {
            if ($kelasSaatIni === '') {
                echo '<tr><td colspan="5" class="text-center text-secondary">Kelas Anda belum tersedia.</td></tr>';
            } else {
                $dataSiswa = ambil($koneksi, "SELECT u.id, u.username, u.role, s.nama, s.kelas
                    FROM users u
                    JOIN siswa s ON s.user_id = u.id
                    WHERE s.kelas = ?
                    ORDER BY s.nama ASC", 's', [$kelasSaatIni]);

                foreach ($dataSiswa as $data) {
                    $id_user = (int) ($data['id'] ?? 0);
                    $username = $data['username'] ?? '';
                    $role = $data['role'] ?? '';
                    $nama = $data['nama'] ?? '-';
                    $kelas = $data['kelas'] ?? '-';
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($username); ?></td>
                        <td><?= htmlspecialchars($role); ?></td>
                        <td><?= htmlspecialchars($nama); ?></td>
                        <td><?= htmlspecialchars($kelas); ?></td>
                    </tr>
                    <?php
                }
            }
        } else {
            // Filter daftar admin menurut role yang diminta, lalu gabungkan data profil siswa/guru.
            $filterRole = $daftarSiswa ? "WHERE u.role = 'siswa'" : '';
            $query = mysqli_query($koneksi, "SELECT u.id, u.username, u.role,
                    COALESCE(s.nama, g.nama) AS nama,
                    COALESCE(s.kelas, '-') AS kelas
                FROM users u
                LEFT JOIN siswa s ON s.user_id = u.id
                LEFT JOIN guru g ON g.user_id = u.id
                $filterRole
                ORDER BY u.id DESC");

            if (!$query) {
                die('Query gagal: ' . mysqli_error($koneksi));
            }

            while ($data = mysqli_fetch_assoc($query)) {
                $id_user = $data['id'] ?? null;
                $username = $data['username'] ?? '';
                $role = $data['role'] ?? '';
                $nama = $data['nama'] ?? '-';
                $kelas = $data['kelas'] ?? '-';
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($username); ?></td>
                    <td><?= htmlspecialchars($role); ?></td>
                    <td><?= htmlspecialchars($nama); ?></td>
                    <?php if ($isAdmin) { ?>
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
                    <?php } else { ?>
                    <td><?= htmlspecialchars($kelas); ?></td>
                    <?php } ?>
                </tr>
                <?php
            }
        }
        ?>
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
        columnDefs: <?= $isAdmin ? '[{ orderable: false, targets: 4 }]' : '[]'; ?>
    });
});
</script>
</body>
</html>
