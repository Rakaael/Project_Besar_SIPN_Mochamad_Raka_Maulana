<?php
// Dashboard untuk semua role. Isi kartu ringkasan berbeda sesuai role yang login.
require_once __DIR__ . '/Koneksi.php';
require_once __DIR__ . '/Auth.php';
wajib_role(['admin', 'guru', 'siswa']);

$role = $_SESSION['role'];
$profil = profil_saya($koneksi);
$kartu = [];   // setiap kartu: [judul, nilai, warna, link]

// Ringkasan admin memakai hitungan tabel; guru dan siswa hanya melihat data terkait profilnya.
if ($role === 'admin') {
    foreach ([['Total User', 'users', 'primary', 'Daftar_user.php'], ['Siswa', 'siswa', 'success', 'Daftar_user.php?role=siswa'],
              ['Guru', 'guru', 'warning', 'Daftar_guru.php'], ['Mata Pelajaran', 'mapel', 'danger', 'MAPEL/Mapel.php'],
              ['Data Mengajar', 'mengajar', 'info', 'MENGAJAR/Mengajar.php']] as [$judul, $tabel, $warna, $link]) {
        $kartu[] = [$judul, ambil($koneksi, "SELECT COUNT(*) AS n FROM $tabel")[0]['n'], $warna, $link];
    }
} elseif ($role === 'guru') {
    $gid = (int) ($profil['id'] ?? 0);
    $kartu[] = ['Mapel Diampu', ambil($koneksi, 'SELECT COUNT(*) AS n FROM mapel WHERE guru_id = ?', 'i', [$gid])[0]['n'], 'primary', 'MAPEL/Mapel.php'];
    $kartu[] = ['Siswa Diajar', ambil($koneksi, 'SELECT COUNT(DISTINCT j.siswa_id) AS n FROM mengajar j JOIN mapel m ON m.id = j.mapel_id WHERE m.guru_id = ?', 'i', [$gid])[0]['n'], 'success', 'MENGAJAR/Mengajar.php'];
    $kartu[] = ['Nilai Diinput', ambil($koneksi, 'SELECT COUNT(*) AS n FROM nilai_rapor n JOIN mapel m ON m.id = n.mapel_id WHERE m.guru_id = ?', 'i', [$gid])[0]['n'], 'warning', 'NILAI/Nilai.php'];
} else {
    $sid = (int) ($profil['id'] ?? 0);
    $r = ambil($koneksi, "SELECT COUNT(DISTINCT j.mapel_id) AS jml,
        COUNT(DISTINCT n.mapel_id) AS dinilai,
        AVG((n.nilai_pengetahuan + n.nilai_keterampilan) / 2) AS rata,
        COUNT(DISTINCT CASE WHEN n.Keterangan = 'Tuntas' THEN n.mapel_id END) AS tuntas
        FROM mengajar j
        LEFT JOIN nilai_rapor n ON n.mapel_id = j.mapel_id AND n.siswa_id = j.siswa_id
        WHERE j.siswa_id = ?", 'i', [$sid])[0];
    $kartu[] = ['Mapel Diikuti', ambil($koneksi, 'SELECT COUNT(DISTINCT mapel_id) AS n FROM mengajar WHERE siswa_id = ?', 'i', [$sid])[0]['n'], 'primary', 'NILAI/Nilai_saya.php'];
    $kartu[] = ['Rata-rata Nilai', $r['dinilai'] ? number_format((float) $r['rata'], 1) : '-', 'success', 'NILAI/Nilai_saya.php'];
    $kartu[] = ['Mapel Tuntas', $r['tuntas'] . ' / ' . $r['jml'], 'warning', 'NILAI/Nilai_saya.php'];
}

kepala('Dashboard');
require_once __DIR__ . '/Navbar.php';
// Kartu yang dirender di bawah mengikuti ringkasan yang sudah dihitung sesuai role.
?>
<style>
    .dashboard-page {
        max-width: 1240px;
        min-height: calc(100vh - 7.5rem);
        padding: clamp(2rem, 4vw, 3.5rem) clamp(1rem, 3vw, 2rem) !important;
        position: relative;
        isolation: isolate;
    }
    .dashboard-page::before {
        content: "";
        position: fixed;
        inset: 12.3rem 0 0;
        z-index: -1;
        pointer-events: none;
        opacity: .48;
        background-image: linear-gradient(rgba(16, 137, 101, .075) 1px, transparent 1px),
                          linear-gradient(90deg, rgba(16, 137, 101, .075) 1px, transparent 1px);
        background-size: 38px 38px;
        mask-image: linear-gradient(to bottom, #000, transparent 92%);
    }
    .dashboard-page > .judul { color: #124b3b; font-size: clamp(2rem, 3.5vw, 2.65rem); margin-bottom: .8rem; }
    .dashboard-page > .judul::after { background: linear-gradient(90deg, #08785d, #61b99b); box-shadow: 0 0 12px rgba(8,120,93,.22); }
    .dashboard-page .dashboard-greeting { color: #71847b !important; margin-bottom: 2.3rem; }
    .dashboard-page .stat {
        min-height: 150px;
        padding: 1.65rem;
        border: 1px solid rgba(255,255,255,.5);
        border-radius: 18px;
        box-shadow: 0 12px 28px rgba(9, 83, 62, .12);
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .dashboard-page .row > [class*="col-"] > a:hover .stat { transform: translateY(-4px); box-shadow: 0 16px 32px rgba(9,83,62,.18); }
    .dashboard-page .stat.bg-primary { background: linear-gradient(135deg, #075846, #0c8063) !important; }
    .dashboard-page .stat.bg-success { background: linear-gradient(135deg, #08785d, #20a77e) !important; }
    .dashboard-page .stat.bg-warning { background: linear-gradient(135deg, #116b55, #329c7b) !important; }
    .dashboard-page .stat.bg-danger { background: linear-gradient(135deg, #145b49, #368a70) !important; }
    .dashboard-page .stat.bg-info { background: linear-gradient(135deg, #08785d, #35a98a) !important; }
    @media (max-width: 767.98px) {
        .dashboard-page { padding-top: 1.5rem !important; }
        .dashboard-page .stat { min-height: 120px; padding: 1.35rem; }
    }
</style>
<main class="container py-5 dashboard-page">
    <h1 class="judul">Dashboard <?= h(ucfirst($role)); ?></h1>
    <p class="dashboard-greeting text-center text-secondary">Selamat datang, <b><?= h($profil['nama'] ?? $_SESSION['username']); ?></b>.</p>
    <div class="row g-4 justify-content-center">
        <?php foreach ($kartu as [$judul, $nilai, $warna, $link]) { ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <a href="<?= h($link); ?>" class="text-decoration-none">
                    <div class="stat bg-<?= $warna; ?>">
                        <small><?= h($judul); ?></small>
                        <h2><?= h($nilai); ?></h2>
                    </div>
                </a>
            </div>
        <?php } ?>
    </div>
</main>
<?php require_once __DIR__ . '/Footer.php'; ?>
