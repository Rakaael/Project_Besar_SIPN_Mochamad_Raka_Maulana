<?php
// Dashboard untuk semua role. Isi kartu ringkasan berbeda sesuai role yang login.
require_once __DIR__ . '/Koneksi.php';
require_once __DIR__ . '/Auth.php';
wajib_role(['admin', 'guru', 'siswa']);

$role = $_SESSION['role'];
$isDashboard = true;
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
    body:has(.dashboard-layout) { padding-top: 0 !important; overflow-x: hidden; }
    .dashboard-layout { min-height: 100vh; display: flex; }
    .dashboard-sidebar {
        position: fixed; inset: 0 auto 0 0; z-index: 1040; width: 264px;
        padding: 1.25rem 1rem; overflow-y: auto; background: var(--sipn-deep);
        color: #fff; box-shadow: 8px 0 24px rgba(5, 62, 96, .16);
    }
    .dashboard-brand { display: flex; align-items: center; gap: .7rem; padding: .25rem .45rem 1.4rem; color: #fff; text-decoration: none; }
    .dashboard-brand .sipn-brand-mark { width: 2.5rem; height: 2.5rem; }
    .dashboard-brand .sipn-brand-name { display: block; font-size: 1.08rem; }
    .dashboard-brand .sipn-brand-caption { display: block; font-size: .7rem; opacity: .68; }
    .dashboard-profile { display: flex; align-items: center; gap: .7rem; padding: .8rem; margin-bottom: 1rem; border: 1px solid rgba(255,255,255,.16); border-radius: 16px; background: rgba(255,255,255,.08); }
    .dashboard-profile .nav-photo, .dashboard-profile .user-avatar { width: 2.45rem !important; height: 2.45rem !important; }
    .dashboard-profile-name { min-width: 0; font-size: .88rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dashboard-profile-role { display: block; margin-top: .15rem; font-size: .7rem; opacity: .7; text-transform: capitalize; }
    .dashboard-nav { display: grid; gap: .28rem; }
    .dashboard-nav a { display: flex; align-items: center; gap: .75rem; min-height: 2.65rem; padding: .65rem .75rem; border-radius: 12px; color: rgba(255,255,255,.72); text-decoration: none; font-size: .92rem; transition: background .15s ease, color .15s ease; }
    .dashboard-nav a:hover, .dashboard-nav a.active { color: #fff; background: var(--sipn-primary); }
    .dashboard-nav .sipn-icon { width: 1.18rem; height: 1.18rem; flex: 0 0 auto; }
    .dashboard-content { width: calc(100% - 264px); margin-left: 264px; }
    .dashboard-topbar { height: 72px; display: flex; align-items: center; justify-content: space-between; padding: 0 clamp(1.25rem, 3vw, 2.5rem); background: var(--sipn-deep); color: #fff; border-bottom: 1px solid rgba(255,255,255,.12); }
    .dashboard-topbar-title { font-size: 1rem; font-weight: 700; }
    .dashboard-topbar-subtitle { display: block; margin-top: .15rem; color: rgba(255,255,255,.62); font-size: .75rem; font-weight: 400; }
    .dashboard-topbar .nav-photo, .dashboard-topbar .user-avatar { width: 2.25rem; height: 2.25rem; }
    .dashboard-page { max-width: none; min-height: calc(100vh - 72px); padding: clamp(2rem, 4vw, 3rem) clamp(1.25rem, 3vw, 2.5rem) !important; }
    .dashboard-page > .judul { text-align: left; font-size: clamp(1.8rem, 3vw, 2.35rem); margin-bottom: .2rem; }
    .dashboard-page > .judul::after { display: none; }
    .dashboard-page .dashboard-greeting { text-align: left !important; margin-bottom: 1.7rem; }
    .dashboard-page .stat { min-height: 156px; padding: 1.35rem 1.4rem; border-radius: 18px; }
    .dashboard-page .stat h2 { margin-top: .75rem; }
    .dashboard-page .stat small { font-size: .95rem; }
    .dashboard-page .stat-icon { display: inline-flex; align-items: center; justify-content: center; width: 2.35rem; height: 2.35rem; margin-bottom: .55rem; border-radius: 50%; background: rgba(255,255,255,.18); }
    .dashboard-page .stat-icon .sipn-icon { width: 1.2rem; height: 1.2rem; }
    /* Keep the dashboard content full-width inside the new shell. */
    .dashboard-layout .dashboard-page { max-width: none; }
    @media (max-width: 991.98px) {
        .dashboard-sidebar { width: 220px; }
        .dashboard-content { width: calc(100% - 220px); margin-left: 220px; }
    }
    @media (max-width: 767.98px) {
        .dashboard-sidebar { position: static; width: 100%; min-height: auto; }
        .dashboard-layout { display: block; }
        .dashboard-content { width: 100%; margin-left: 0; }
        .dashboard-nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dashboard-topbar { height: 64px; }
        .dashboard-page { min-height: auto; padding-top: 1.5rem !important; }
    }
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
    /* Keep dashboard cards aligned with the global ocean theme. */
    .dashboard-page > .judul { color: #064c75 !important; }
    .dashboard-page > .judul::after { background: linear-gradient(90deg, #06618f, #18b7c5, #72d6df) !important; }
    .dashboard-page .stat.bg-primary { background: linear-gradient(135deg, #06466f, #087ea4) !important; }
    .dashboard-page .stat.bg-success { background: linear-gradient(135deg, #087ea4, #22b8c7) !important; }
    .dashboard-page .stat.bg-warning { background: linear-gradient(135deg, #087093, #39c4ca) !important; }
    .dashboard-page .stat.bg-danger { background: linear-gradient(135deg, #06466f, #087ea4) !important; }
    .dashboard-page .stat.bg-info { background: linear-gradient(135deg, #087ea4, #22b8c7) !important; }
</style>
<main class="dashboard-page">
    <h1 class="judul">Dashboard <?= h(ucfirst($role)); ?></h1>
    <p class="dashboard-greeting text-center text-secondary">Selamat datang, <b><?= h($profil['nama'] ?? $_SESSION['username']); ?></b>.</p>
    <div class="row g-4 justify-content-center">
        <?php foreach ($kartu as [$judul, $nilai, $warna, $link]) {
            $icon = match (true) {
                stripos($judul, 'user') !== false => 'users',
                stripos($judul, 'siswa') !== false => 'student',
                stripos($judul, 'guru') !== false => 'teacher',
                stripos($judul, 'mapel') !== false => 'book',
                stripos($judul, 'mengajar') !== false => 'teaching',
                stripos($judul, 'nilai') !== false => 'grades',
                default => 'dashboard',
            };
        ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <a href="<?= h($link); ?>" class="text-decoration-none">
                    <div class="stat bg-<?= $warna; ?>">
                        <span class="stat-icon" aria-hidden="true"><?= $navIcon($icon); ?></span>
                        <small><?= h($judul); ?></small>
                        <h2><?= h($nilai); ?></h2>
                    </div>
                </a>
            </div>
        <?php } ?>
    </div>
</main>
<?php if ($isDashboard) { ?></div></div><?php } ?>
<?php require_once __DIR__ . '/Footer.php'; ?>
