<?php
require_once __DIR__ . '/Koneksi.php';
require_once __DIR__ . '/Auth.php';
wajib_role(['admin', 'guru']);
$isAdmin = $_SESSION['role'] === 'admin';
$csrfToken = $isAdmin ? token_csrf() : '';

// Ambil identitas guru beserta mapel yang diampu untuk tabel daftar.
$data = ambil($koneksi, "SELECT u.id AS user_id, u.username, u.role, g.nama, m.nama_mapel
    FROM guru g
    JOIN users u ON u.id = g.user_id
    LEFT JOIN mapel m ON m.guru_id = g.id
    ORDER BY g.nama ASC, m.nama_mapel ASC");

kepala('Daftar Guru', false, true);
require_once __DIR__ . '/Navbar.php';
// Tabel di bawah merender hasil kueri; kolom tindakan hanya tersedia bagi admin.
?>
<div class="container-fluid table-container">
    <h1 class="judul">Daftar Guru</h1>
    <table id="tabelGuru" class="table table-striped table-bordered w-100">
        <thead>
            <tr>
                <th>No</th>
                <th>Username</th>
                <th>Role</th>
                <th>Nama</th>
                <th>Mapel</th>
                <?php if ($isAdmin) { ?><th>Aksi</th><?php } ?>
            </tr>
        </thead>
        <tbody>
        <?php $no = 1; foreach ($data as $guru) { ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= h($guru['username']); ?></td>
                <td><?= h($guru['role']); ?></td>
                <td><?= h($guru['nama']); ?></td>
                <td><?= h($guru['nama_mapel'] ?? '-'); ?></td>
                <?php if ($isAdmin) { ?>
                <td class="text-nowrap">
                    <a class="btn btn-warning btn-sm" href="edit_user.php?id=<?= (int) $guru['user_id']; ?>">Edit</a>
                    <form action="hapus_user.php" method="post" class="d-inline" onsubmit="return confirm('Hapus guru ini dan semua penugasan mengajarnya? Nilai siswa tetap disimpan; mapel yang memiliki nilai akan dipertahankan tanpa guru pengampu.')">
                        <input type="hidden" name="id" value="<?= (int) $guru['user_id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken); ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </td>
                <?php } ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php skrip_tabel('tabelGuru', $isAdmin ? 5 : -1); require_once __DIR__ . '/Footer.php'; ?>
