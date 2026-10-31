<?php
// Dashboard untuk semua role. Isi kartu ringkasan berbeda sesuai role yang login.
require_once __DIR__ . '/Koneksi.php';
require_once __DIR__ . '/Auth.php';
wajib_role(['admin', 'guru', 'siswa']);

$role = $_SESSION['role'];
$profil = profil_saya($koneksi);
$kartu = [];   // setiap kartu: [judul, nilai, warna, link]
$siswaMapel = [];
$siswaChartLabels = [];
$siswaChartValues = [];
$siswaPencapaian = ['tuntas' => 0, 'nilai_terbaik' => null, 'mapel_terbaik' => null];
$guruMapel = [];
$guruRecentNilai = [];
$guruActivityLabels = [];
$guruActivityValues = [];
$guruRingkasan = ['siswa' => 0, 'dinilai' => 0, 'belum' => 0, 'persentase' => 0];
$adminRecentUsers = [];
$adminAcademicCounts = ['users' => 0, 'siswa' => 0, 'guru' => 0, 'mapel' => 0, 'mengajar' => 0];
$adminActivityLabels = [];
$adminActivityValues = [];

// Ringkasan admin memakai hitungan tabel; guru dan siswa hanya melihat data terkait profilnya.
if ($role === 'admin') {
    foreach ([['Total User', 'users', 'primary', 'Daftar_user.php'], ['Siswa', 'siswa', 'success', 'Daftar_user.php?role=siswa'],
              ['Guru', 'guru', 'warning', 'Daftar_guru.php'], ['Mata Pelajaran', 'mapel', 'danger', 'MAPEL/Mapel.php'],
              ['Data Mengajar', 'mengajar', 'info', 'MENGAJAR/Mengajar.php']] as [$judul, $tabel, $warna, $link]) {
        $jumlah = (int) (ambil($koneksi, "SELECT COUNT(*) AS n FROM $tabel")[0]['n'] ?? 0);
        $kartu[] = [$judul, $jumlah, $warna, $link];
        $adminAcademicCounts[
            $tabel === 'users' ? 'users' : $tabel
        ] = $jumlah;
    }
    $adminRecentUsers = ambil($koneksi, "SELECT u.username, u.role, u.created_at,
            COALESCE(s.nama, g.nama, u.username) AS nama
        FROM users u
        LEFT JOIN siswa s ON s.user_id = u.id
        LEFT JOIN guru g ON g.user_id = u.id
        ORDER BY u.created_at DESC, u.id DESC
        LIMIT 5");
    $activityRows = ambil($koneksi, "SELECT DATE(created_at) AS tanggal, COUNT(*) AS jumlah
        FROM users
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
        ORDER BY tanggal ASC");
    $activityByDate = [];
    foreach ($activityRows as $activityRow) {
        $activityByDate[$activityRow['tanggal']] = (int) $activityRow['jumlah'];
    }
    for ($dayOffset = 6; $dayOffset >= 0; $dayOffset--) {
        $dateKey = date('Y-m-d', strtotime('-' . $dayOffset . ' days'));
        $adminActivityLabels[] = date('d M', strtotime($dateKey));
        $adminActivityValues[] = $activityByDate[$dateKey] ?? 0;
    }
} elseif ($role === 'guru') {
    $gid = (int) ($profil['id'] ?? 0);
    $guruMapel = ambil($koneksi, 'SELECT id, nama_mapel FROM mapel WHERE guru_id = ? ORDER BY nama_mapel', 'i', [$gid]);
    $guruSiswa = ambil($koneksi, 'SELECT COUNT(DISTINCT j.siswa_id) AS jumlah FROM mengajar j JOIN mapel m ON m.id = j.mapel_id WHERE m.guru_id = ?', 'i', [$gid])[0]['jumlah'] ?? 0;
    $guruNilai = ambil($koneksi, 'SELECT COUNT(*) AS jumlah FROM nilai_rapor n JOIN mapel m ON m.id = n.mapel_id WHERE m.guru_id = ?', 'i', [$gid])[0]['jumlah'] ?? 0;
    $guruCakupan = ambil($koneksi, "SELECT COUNT(*) AS total, COUNT(n.id) AS dinilai FROM mengajar j JOIN mapel m ON m.id = j.mapel_id LEFT JOIN nilai_rapor n ON n.mapel_id = j.mapel_id AND n.siswa_id = j.siswa_id WHERE m.guru_id = ?", 'i', [$gid])[0] ?? ['total' => 0, 'dinilai' => 0];
    $guruRingkasan['siswa'] = (int) $guruSiswa;
    $guruRingkasan['dinilai'] = (int) ($guruCakupan['dinilai'] ?? 0);
    $guruRingkasan['belum'] = max(0, (int) ($guruCakupan['total'] ?? 0) - $guruRingkasan['dinilai']);
    $guruRingkasan['persentase'] = (int) ($guruCakupan['total'] ?? 0) > 0 ? round(($guruRingkasan['dinilai'] / (int) $guruCakupan['total']) * 100) : 0;
    $guruRecentNilai = ambil($koneksi, "SELECT s.nama AS nama_siswa, s.kelas, m.nama_mapel, n.nilai_pengetahuan, n.nilai_keterampilan, n.Keterangan FROM nilai_rapor n JOIN siswa s ON s.id = n.siswa_id JOIN mapel m ON m.id = n.mapel_id WHERE m.guru_id = ? ORDER BY n.id DESC LIMIT 6", 'i', [$gid]);
    $guruActivity = ambil($koneksi, "SELECT m.nama_mapel, COUNT(n.id) AS jumlah
        FROM nilai_rapor n
        JOIN mapel m ON m.id = n.mapel_id
        WHERE m.guru_id = ?
        GROUP BY m.id, m.nama_mapel
        ORDER BY m.nama_mapel ASC", 'i', [$gid]);
    foreach ($guruActivity as $activity) {
        $guruActivityLabels[] = $activity['nama_mapel'];
        $guruActivityValues[] = (int) $activity['jumlah'];
    }
    // Guru hanya diarahkan ke halaman nilai yang memang mengizinkan akses guru.
    $kartu[] = ['Mapel Diampu', count($guruMapel), 'primary', 'NILAI/Nilai.php'];
    $kartu[] = ['Siswa Diajar', $guruRingkasan['siswa'], 'success', 'NILAI/Nilai.php'];
    $kartu[] = ['Nilai Diinput', $guruNilai, 'warning', 'NILAI/Nilai.php'];
} else {
    $sid = (int) ($profil['id'] ?? 0);
    $siswaMapel = ambil($koneksi, "SELECT m.id, m.nama_mapel, COALESCE(g.nama, 'Guru belum ditentukan') AS nama_guru,
        n.nilai_kkm, n.nilai_pengetahuan, n.nilai_keterampilan, n.Keterangan,
        CASE WHEN n.id IS NULL THEN NULL ELSE (n.nilai_pengetahuan + n.nilai_keterampilan) / 2 END AS nilai_rata
        FROM mengajar j
        JOIN mapel m ON m.id = j.mapel_id
        LEFT JOIN guru g ON g.id = m.guru_id
        LEFT JOIN nilai_rapor n ON n.mapel_id = j.mapel_id AND n.siswa_id = j.siswa_id
        WHERE j.siswa_id = ? ORDER BY m.nama_mapel ASC", 'i', [$sid]);

    $nilaiTerisi = [];
    foreach ($siswaMapel as $mapel) {
        if ($mapel['nilai_rata'] !== null) {
            $nilai = (float) $mapel['nilai_rata'];
            $nilaiTerisi[] = $nilai;
            $siswaChartLabels[] = $mapel['nama_mapel'];
            $siswaChartValues[] = round($nilai, 1);
            if ($siswaPencapaian['nilai_terbaik'] === null || $nilai > $siswaPencapaian['nilai_terbaik']) {
                $siswaPencapaian['nilai_terbaik'] = $nilai;
                $siswaPencapaian['mapel_terbaik'] = $mapel['nama_mapel'];
            }
        }
        if (($mapel['Keterangan'] ?? '') === 'Tuntas') {
            $siswaPencapaian['tuntas']++;
        }
    }
    $rataRata = count($nilaiTerisi) ? array_sum($nilaiTerisi) / count($nilaiTerisi) : null;
    $jumlahMapel = count($siswaMapel);
    $kartu[] = ['Rata-rata Nilai', $rataRata === null ? '-' : number_format($rataRata, 1), 'success', 'NILAI/Nilai_saya.php'];
    $kartu[] = ['Mapel Tuntas', $siswaPencapaian['tuntas'] . ' / ' . $jumlahMapel, 'warning', 'NILAI/Nilai_saya.php'];
}

kepala('Dashboard');
require_once __DIR__ . '/Navbar.php';
// Kartu yang dirender di bawah mengikuti ringkasan yang sudah dihitung sesuai role.
?>
<style>
    .dashboard-page {
        width: 100%;
        max-width: none;
        min-height: calc(100vh - 7.5rem);
        padding: clamp(2rem, 4vw, 3.5rem) clamp(1rem, 3vw, 2rem) !important;
        position: relative;
        isolation: isolate;
    }
    .dashboard-page > .student-dashboard,
    .dashboard-page > .teacher-dashboard {
        width: 100%;
        max-width: none;
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
    .dashboard-page .stat { position: relative; overflow: hidden; }
    .dashboard-page .stat::after { position: absolute; right: -1.2rem; bottom: -2.5rem; width: 8rem; height: 8rem; border: 1px solid rgba(255,255,255,.16); border-radius: 50%; content: ""; }
    .dashboard-page .stat small { position: relative; z-index: 1; letter-spacing: .02em; }
    .dashboard-page .stat h2 { position: relative; z-index: 1; font-size: clamp(1.8rem, 3vw, 2.35rem); letter-spacing: -.04em; }
    @media (max-width: 767.98px) {
        .dashboard-page { padding-top: 1.5rem !important; }
        .dashboard-page .stat { min-height: 120px; padding: 1.35rem; }
    }
    /* Keep dashboard cards aligned with the global ocean theme. */
    .dashboard-page > .judul { color: #064c75 !important; }
    .dashboard-page > .judul::after { background: linear-gradient(90deg, #06618f, #18b7c5, #72d6df) !important; }
    .dashboard-page .stat.bg-primary { background: linear-gradient(135deg, #06466f, #087ea4) !important; }
    .dashboard-page .stat.bg-success,
    .dashboard-page .stat.bg-warning,
    .dashboard-page .stat.bg-danger,
    .dashboard-page .stat.bg-info { background: linear-gradient(135deg, #06466f, #087ea4) !important; }
    /* Semua kartu dashboard memakai warna yang sama seperti kartu Total User. */
    .dashboard-page .stat { background: linear-gradient(135deg, #06466f, #087ea4) !important; }
    html[data-theme="dark"] .dashboard-page .stat { background: linear-gradient(135deg, #073b66, #087ea4) !important; }
    .student-dashboard { --student-bg: #07111f; --student-card: #12243a; --student-cyan: #22d3ee; --student-blue: #3b82f6; --student-text: #f8fafc; --student-muted: #94a3b8; max-width: 1240px; }
    body:has(.student-dashboard) { background: #07111f !important; color: var(--student-text) !important; }
    html[data-theme="light"] body:has(.student-dashboard), html[data-theme="dark"] body:has(.student-dashboard) { background: #07111f !important; color: #f8fafc !important; }
    body:has(.student-dashboard)::before { display: none; }
    .student-welcome { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; margin: .5rem 0 1.75rem; padding: clamp(1.5rem, 4vw, 2.6rem); border: 1px solid rgba(34,211,238,.28); border-radius: 24px; background: linear-gradient(118deg, #0b2640 0%, #123761 56%, #0d6c86 100%); box-shadow: 0 20px 42px rgba(0,0,0,.28), inset 0 1px rgba(255,255,255,.1); }
    .student-welcome::before, .student-welcome::after { position: absolute; border: 1px solid rgba(34,211,238,.2); border-radius: 50%; content: ""; pointer-events: none; }
    .student-welcome::before { width: 21rem; height: 21rem; right: -7rem; top: -12rem; }
    .student-welcome::after { width: 14rem; height: 14rem; right: 2rem; bottom: -10rem; border-color: rgba(59,130,246,.3); }
    .student-welcome-copy { position: relative; z-index: 1; }
    .student-eyebrow { margin-bottom: .65rem; color: #a5f3fc; font-size: .72rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
    .student-welcome h2 { margin: 0 0 .55rem; color: #fff; font-size: clamp(1.7rem, 3vw, 2.55rem); font-weight: 780; letter-spacing: -.055em; }
    .student-welcome p { max-width: 39rem; margin: 0 0 1.1rem; color: #c9e7f3; line-height: 1.7; }
    .student-date { display: inline-flex; align-items: center; gap: .45rem; color: #a5f3fc; font-size: .84rem; font-weight: 700; }
    .student-welcome-art { position: relative; z-index: 1; display: grid; width: 5.5rem; height: 5.5rem; flex: 0 0 auto; place-items: center; border: 1px solid rgba(165,243,252,.38); border-radius: 22px; background: rgba(7,17,31,.28); color: #a5f3fc; box-shadow: 0 0 0 10px rgba(34,211,238,.06), 0 12px 26px rgba(0,0,0,.18); }
    .student-welcome-art .sipn-icon { width: 2.7rem; height: 2.7rem; }
    .student-section-title { display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin: 2rem 0 .9rem; }
    .student-section-title h2 { margin: 0; color: var(--student-text); font-size: 1.15rem; letter-spacing: -.025em; }
    .student-section-title span { color: var(--student-muted); font-size: .8rem; }
    .student-dashboard .stat { min-height: 156px; border: 1px solid rgba(148,163,184,.14); border-radius: 18px; background: linear-gradient(145deg, #142b45, #102137) !important; box-shadow: 0 15px 28px rgba(0,0,0,.2); }
    .student-dashboard .stat::after { border-color: rgba(34,211,238,.16); }
    .student-dashboard .stat small { color: var(--student-muted) !important; font-size: .78rem; }
    .student-dashboard .stat h2 { margin-top: .7rem; color: #fff !important; font-size: 2rem; }
    .student-dashboard .row > [class*="col-"] > a:hover .stat { border-color: rgba(34,211,238,.48); box-shadow: 0 18px 34px rgba(0,0,0,.3), 0 0 24px rgba(34,211,238,.08); }
    .student-content-grid { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(260px, .8fr); gap: 1rem; margin-top: 1.4rem; }
    .student-panel, .student-tip, .student-subject-card { border: 1px solid rgba(148,163,184,.14); border-radius: 18px; background: linear-gradient(145deg, #12243a, #0e1d30); box-shadow: 0 15px 30px rgba(0,0,0,.18); }
    .student-panel { padding: 1.2rem; }
    .student-panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
    .student-panel-heading h3 { margin: 0; color: #f8fafc; font-size: 1rem; }
    .student-panel-heading span { color: var(--student-muted); font-size: .76rem; }
    .student-chart-wrap { min-height: 255px; }
    .student-chart-empty { display: grid; min-height: 245px; place-items: center; color: var(--student-muted); font-size: .85rem; text-align: center; }
    .student-achievement-list { display: grid; gap: .75rem; }
    .student-achievement { display: flex; align-items: center; gap: .7rem; padding: .75rem; border: 1px solid rgba(148,163,184,.12); border-radius: 12px; background: rgba(7,17,31,.28); }
    .student-achievement-icon { display: grid; width: 2rem; height: 2rem; flex: 0 0 auto; place-items: center; border-radius: 9px; background: rgba(34,211,238,.12); color: var(--student-cyan); }
    .student-achievement-icon .sipn-icon { width: 1rem; height: 1rem; }
    .student-achievement small { display: block; color: var(--student-muted); font-size: .7rem; }
    .student-achievement strong { display: block; margin-top: .15rem; color: #f8fafc; font-size: .86rem; }
    .student-tip { position: relative; overflow: hidden; margin-top: 1rem; padding: 1.2rem 1.35rem; border-color: rgba(34,211,238,.22); background: linear-gradient(120deg, #132c45, #10233a); }
    .student-tip::after { position: absolute; right: -2rem; top: -3rem; width: 9rem; height: 9rem; border: 1px solid rgba(34,211,238,.2); border-radius: 50%; content: ""; }
    .student-tip-label { color: var(--student-cyan); font-size: .7rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .student-tip p { position: relative; z-index: 1; margin: .45rem 0 0; color: #d6e8f2; font-size: .9rem; line-height: 1.6; }
    .student-subject-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
    .student-subject-card { min-width: 0; padding: 1.1rem; transition: transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease; }
    .student-subject-card:hover { transform: translateY(-4px); border-color: rgba(34,211,238,.42); box-shadow: 0 18px 32px rgba(0,0,0,.25); }
    .student-subject-top { display: flex; align-items: start; justify-content: space-between; gap: .75rem; }
    .student-subject-card h3 { margin: 0; color: #f8fafc; font-size: .95rem; line-height: 1.35; }
    .student-subject-card .student-teacher { margin: .35rem 0 1.2rem; color: var(--student-muted); font-size: .76rem; }
    .student-status { display: inline-flex; border-radius: 999px; padding: .28rem .5rem; font-size: .65rem; font-weight: 800; white-space: nowrap; }
    .student-status.done { background: rgba(34,197,94,.14); color: #86efac; }
    .student-status.pending { background: rgba(245,158,11,.14); color: #fcd34d; }
    .student-grade { display: flex; align-items: end; justify-content: space-between; gap: .5rem; border-top: 1px solid rgba(148,163,184,.12); padding-top: .8rem; }
    .student-grade small { color: var(--student-muted); font-size: .7rem; }
    .student-grade strong { color: var(--student-cyan); font-size: 1.35rem; }
    .student-detail-link { display: inline-block; margin-top: .9rem; color: #7dd3fc; font-size: .75rem; font-weight: 700; text-decoration: none; }
    .student-detail-link:hover { color: #fff; }
    @media (max-width: 900px) { .student-content-grid { grid-template-columns: 1fr; } .student-subject-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575.98px) { .student-welcome { align-items: start; padding: 1.35rem; } .student-welcome-art { display: none; } .student-subject-grid { grid-template-columns: 1fr; } .student-section-title { align-items: start; flex-direction: column; gap: .25rem; } }
    @media (prefers-reduced-motion: reduce) { .student-subject-card, .student-dashboard .stat { transition: none; } }
    html[data-theme="light"] body:has(.student-dashboard) { background: linear-gradient(135deg, #eef7fb 0%, #f8fbfd 52%, #e8f3f8 100%) !important; color: #173b55 !important; }
    html[data-theme="light"] body:has(.student-dashboard)::before { display: none; }
    html[data-theme="light"] .student-dashboard { --student-text: #153b56; --student-muted: #64798a; }
    html[data-theme="light"] .student-section-title h2 { color: #153b56; }
    html[data-theme="light"] .student-section-title span { color: #64798a; }
    html[data-theme="light"] .student-dashboard .stat { border-color: #cfe4ed; background: linear-gradient(145deg, #ffffff, #edf7fb) !important; box-shadow: 0 14px 28px rgba(8,91,132,.1); }
    html[data-theme="light"] .student-dashboard .stat small { color: #607789 !important; }
    html[data-theme="light"] .student-dashboard .stat h2 { color: #064c75 !important; }
    html[data-theme="light"] .student-dashboard .stat::after { border-color: rgba(8,126,164,.16); }
    html[data-theme="light"] .student-panel, html[data-theme="light"] .student-tip, html[data-theme="light"] .student-subject-card { border-color: #cfe4ed; background: linear-gradient(145deg, #ffffff, #f0f8fb); box-shadow: 0 14px 30px rgba(8,91,132,.09); }
    html[data-theme="light"] .student-panel-heading h3, html[data-theme="light"] .student-subject-card h3 { color: #153b56; }
    html[data-theme="light"] .student-panel-heading span, html[data-theme="light"] .student-subject-card .student-teacher, html[data-theme="light"] .student-grade small { color: #64798a; }
    html[data-theme="light"] .student-achievement { border-color: #d9eaf1; background: #f5fbfd; }
    html[data-theme="light"] .student-achievement strong { color: #153b56; }
    html[data-theme="light"] .student-tip { background: linear-gradient(120deg, #e7f8fb, #f4faff); }
    html[data-theme="light"] .student-tip p { color: #31556c; }
    html[data-theme="light"] .student-detail-link { color: #087ea4; }
    html[data-theme="light"] .student-subject-card:hover { border-color: #22b8c7; box-shadow: 0 16px 30px rgba(8,91,132,.14); }
    .teacher-dashboard { --teacher-bg: #07111f; --teacher-card: #0d2340; --teacher-cyan: #22d3ee; --teacher-blue: #3b82f6; --teacher-text: #f8fafc; --teacher-muted: #94a3b8; }
    body:has(.teacher-dashboard) { background: #07111f !important; color: var(--teacher-text) !important; }
    body:has(.teacher-dashboard)::before { display: none; }
    .teacher-welcome { position: relative; overflow: hidden; margin: 1.4rem 0 1.5rem; padding: clamp(1.4rem, 3vw, 2.2rem); border: 1px solid rgba(34,211,238,.28); border-radius: 20px; background: linear-gradient(118deg, #0b2540, #123761 60%, #0b7289); box-shadow: 0 18px 38px rgba(0,0,0,.25); }
    .teacher-welcome::after { position: absolute; right: -4rem; top: -7rem; width: 17rem; height: 17rem; border: 1px solid rgba(34,211,238,.22); border-radius: 50%; content: ""; }
    .teacher-welcome small { position: relative; z-index: 1; color: #a5f3fc; font-size: .7rem; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
    .teacher-welcome h2 { position: relative; z-index: 1; margin: .45rem 0 .5rem; color: #fff; font-size: clamp(1.65rem, 3vw, 2.45rem); letter-spacing: -.055em; }
    .teacher-welcome p { position: relative; z-index: 1; margin: 0; color: #c9e7f3; line-height: 1.6; }
    .teacher-welcome-date { position: relative; z-index: 1; display: inline-block; margin-top: .8rem; color: #a5f3fc; font-size: .78rem; font-weight: 700; }
    .teacher-welcome { display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; min-height: 108px; margin: .2rem 0 1rem; padding: 1.15rem 1.45rem; border-radius: 13px; background: linear-gradient(105deg, #07507d 0%, #087ea4 55%, #0cc0d0 115%); }
    .teacher-welcome-copy { position: relative; z-index: 1; display: flex; align-items: center; gap: .9rem; }
    .teacher-welcome-wave { font-size: 2.1rem; filter: drop-shadow(0 5px 8px rgba(0,0,0,.18)); }
    .teacher-welcome h2 { margin: .2rem 0 .15rem; font-size: clamp(1.2rem, 2.2vw, 1.65rem); letter-spacing: -.035em; }
    .teacher-welcome p { font-size: .78rem; }
    .teacher-welcome-art { position: relative; z-index: 1; display: flex; align-items: center; gap: .55rem; margin-right: 2rem; }
    .teacher-welcome-art span { display: grid; width: 4.4rem; height: 4.4rem; place-items: center; border: 2px solid rgba(170,246,248,.7); border-radius: 8px; background: rgba(3,38,73,.55); transform: rotate(-4deg); box-shadow: 8px 8px 0 rgba(3,38,73,.25); }
    .teacher-welcome-art .sipn-icon { width: 2.2rem; height: 2.2rem; color: #d9fbff; }
    .teacher-welcome-art i { display: block; width: 2.8rem; height: .7rem; border-radius: 3px; background: #f7c75d; box-shadow: 0 -.9rem 0 #f28f5b, 0 .9rem 0 #e9f6ff; transform: translateX(-.3rem); }
    .teacher-stat-row { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem !important; margin-inline: 0; }
    .teacher-stat-row .teacher-stat-col { width: auto; max-width: none; padding-inline: 0; }
    .teacher-stat-row .stat { min-height: 94px; padding: 1rem 1.1rem 1rem 4.35rem; border: 1px solid rgba(34,211,238,.55); border-radius: 11px; background: linear-gradient(135deg, #06466f, #087ea4) !important; box-shadow: 0 9px 20px rgba(0,0,0,.2); }
    .teacher-stat-row .stat h2 { margin: .1rem 0 0; font-size: 1.65rem; }
    .teacher-stat-row .stat small { font-size: .72rem; }
    .teacher-stat-row .stat:hover { box-shadow: 0 13px 25px rgba(0,0,0,.28), 0 0 15px rgba(34,211,238,.15); }
    .teacher-stat-icon { position: absolute; left: 1rem; top: 50%; display: grid; width: 2.55rem; height: 2.55rem; place-items: center; border: 1px solid rgba(165,243,252,.45); border-radius: 50%; background: rgba(34,211,238,.25); color: #d9fbff; transform: translateY(-50%); }
    .teacher-stat-icon .sipn-icon { width: 1.35rem; height: 1.35rem; }
    .teacher-stat-arrow { position: absolute; right: .8rem; top: 50%; color: #a5f3fc; font-size: 1.5rem; transform: translateY(-50%); }
    .teacher-stat-subtitle { display: block; margin-top: .1rem; color: #b7e5ee; font-size: .6rem; }
    .teacher-activity-chart { min-height: 210px; }
    .teacher-dashboard .stat { min-height: 145px; border: 1px solid rgba(148,163,184,.16); border-radius: 17px; background: linear-gradient(145deg, #142b45, #102137) !important; box-shadow: 0 14px 28px rgba(0,0,0,.2); }
    .teacher-dashboard .stat small { color: #94a3b8 !important; }
    .teacher-dashboard .stat h2 { margin-top: .7rem; color: #fff !important; }
    .teacher-dashboard .stat:hover { border-color: rgba(34,211,238,.48); box-shadow: 0 18px 34px rgba(0,0,0,.3), 0 0 22px rgba(34,211,238,.08); }
    .teacher-grid { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(270px, .8fr); gap: 1rem; margin-top: 1.4rem; }
    .teacher-panel { min-width: 0; padding: 1.15rem; border: 1px solid rgba(148,163,184,.15); border-radius: 17px; background: linear-gradient(145deg, #0d2340, #0b1c31); box-shadow: 0 14px 28px rgba(0,0,0,.18); }
    .teacher-panel h3 { margin: 0 0 .25rem; color: #f8fafc; font-size: 1rem; }
    .teacher-panel-subtitle { margin: 0 0 1rem; color: #94a3b8; font-size: .76rem; }
    .teacher-chart-empty { display: grid; min-height: 210px; place-items: center; color: #94a3b8; font-size: .84rem; text-align: center; }
    .teacher-actions { display: grid; gap: .7rem; }
    .teacher-action { display: flex; align-items: center; gap: .7rem; padding: .75rem; border: 1px solid rgba(34,211,238,.18); border-radius: 12px; color: #f8fafc; background: rgba(7,17,31,.28); text-decoration: none; transition: transform .2s ease, border-color .2s ease, background .2s ease; }
    .teacher-action:hover { transform: translateX(3px); border-color: rgba(34,211,238,.5); background: rgba(34,211,238,.1); color: #fff; }
    .teacher-action-icon { display: grid; width: 2rem; height: 2rem; flex: 0 0 auto; place-items: center; border-radius: 9px; background: rgba(34,211,238,.12); color: #22d3ee; }
    .teacher-action-icon .sipn-icon { width: 1rem; height: 1rem; }
    .teacher-action strong, .teacher-action small { display: block; }
    .teacher-action strong { font-size: .8rem; }
    .teacher-action small { margin-top: .12rem; color: #94a3b8; font-size: .68rem; }
    .teacher-lower-grid { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(270px, .8fr); gap: 1rem; margin-top: 1rem; }
    .teacher-table-wrap { overflow-x: auto; }
    .teacher-table { width: 100%; min-width: 520px; border-collapse: collapse; color: #dceaf4; font-size: .78rem; }
    .teacher-table th { padding: .65rem .5rem; color: #8ddfeb; border-bottom: 1px solid rgba(148,163,184,.2); font-size: .68rem; text-align: left; text-transform: uppercase; }
    .teacher-table td { padding: .7rem .5rem; border-bottom: 1px solid rgba(148,163,184,.1); }
    .teacher-table tr:last-child td { border-bottom: 0; }
    .teacher-badge { display: inline-block; border-radius: 999px; padding: .25rem .45rem; font-size: .64rem; font-weight: 800; }
    .teacher-badge.done { color: #86efac; background: rgba(34,197,94,.14); }
    .teacher-badge.pending { color: #fcd34d; background: rgba(245,158,11,.14); }
    .teacher-summary-list { display: grid; gap: .7rem; }
    .teacher-summary-item { display: flex; justify-content: space-between; gap: 1rem; padding-bottom: .65rem; border-bottom: 1px solid rgba(148,163,184,.12); color: #94a3b8; font-size: .78rem; }
    .teacher-summary-item:last-child { border-bottom: 0; padding-bottom: 0; }
    .teacher-summary-item strong { color: #f8fafc; }
    .teacher-progress { height: .45rem; margin-top: .35rem; overflow: hidden; border-radius: 999px; background: #1e344d; }
    .teacher-progress span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #3b82f6, #22d3ee); }
    .teacher-empty { padding: 1rem; color: #94a3b8; font-size: .8rem; text-align: center; }
    @media (max-width: 900px) { .teacher-grid, .teacher-lower-grid { grid-template-columns: 1fr; } }
    @media (max-width: 575.98px) { .teacher-welcome { margin-top: 1rem; } }

    /* Dashboard admin: panel gelap, ringkas, dan responsif sesuai komposisi portal. */
    .admin-welcome { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; min-height: 108px; margin: .2rem 0 1rem; padding: 1.15rem 1.45rem; overflow: hidden; border: 1px solid #087ea4; border-radius: 13px; background: linear-gradient(105deg, #07507d 0%, #087ea4 55%, #0cc0d0 115%); box-shadow: 0 15px 30px rgba(5,62,96,.24); }
    .admin-welcome::after { position: absolute; width: 13rem; height: 13rem; right: 10%; top: -9rem; border: 1px solid rgba(109,239,247,.32); border-radius: 50%; content: ''; }
    .admin-welcome-copy { position: relative; z-index: 1; display: flex; align-items: center; gap: .9rem; }
    .admin-welcome-wave { font-size: 2.1rem; filter: drop-shadow(0 5px 8px rgba(0,0,0,.18)); }
    .admin-welcome small { color: #b7f5f7; font-size: .65rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .admin-welcome h2 { margin: .2rem 0 .15rem; color: #fff; font-size: clamp(1.2rem, 2.2vw, 1.65rem); letter-spacing: -.035em; }
    .admin-welcome p { margin: 0; color: #d6f5f7; font-size: .78rem; }
    .admin-welcome-date { display: inline-block; margin-top: .5rem; color: #a8eef3; font-size: .68rem; font-weight: 700; }
    .admin-welcome-art { position: relative; z-index: 1; display: flex; align-items: center; gap: .55rem; margin-right: 2rem; }
    .admin-welcome-art span { display: grid; width: 4.4rem; height: 4.4rem; place-items: center; border: 2px solid rgba(170,246,248,.7); border-radius: 8px; background: rgba(3,38,73,.55); transform: rotate(-4deg); box-shadow: 8px 8px 0 rgba(3,38,73,.25); }
    .admin-welcome-art .sipn-icon { width: 2.2rem; height: 2.2rem; color: #d9fbff; }
    .admin-welcome-art i, .admin-welcome-art b { display: block; width: 2.8rem; height: .7rem; border-radius: 3px; background: linear-gradient(90deg, #f7c75d, #fff0a6); box-shadow: 0 -.9rem 0 #f28f5b, 0 .9rem 0 #e9f6ff; transform: translateX(-.3rem); }
    .admin-welcome-art b { display: none; }
    .admin-stat-row { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .75rem !important; margin-inline: 0; }
    .admin-stat-row .admin-stat-col { width: auto; max-width: none; padding-inline: 0; }
    .admin-stat-row .stat { min-height: 94px; padding: 1rem 1.1rem 1rem 4.35rem; border: 1px solid rgba(34,211,238,.55); border-radius: 11px; background: linear-gradient(135deg, #06466f, #087ea4) !important; box-shadow: 0 9px 20px rgba(0,0,0,.2); }
    .admin-stat-row .stat:hover { box-shadow: 0 13px 25px rgba(0,0,0,.28), 0 0 15px rgba(34,211,238,.15); }
    .admin-stat-row .stat small { font-size: .72rem; }
    .admin-stat-row .stat h2 { margin: .1rem 0 0; font-size: 1.65rem; }
    .admin-stat-icon { position: absolute; left: 1rem; top: 50%; display: grid; width: 2.55rem; height: 2.55rem; place-items: center; border: 1px solid rgba(165,243,252,.45); border-radius: 50%; background: rgba(34,211,238,.25); color: #d9fbff; transform: translateY(-50%); }
    .admin-stat-icon .sipn-icon { width: 1.35rem; height: 1.35rem; }
    .admin-stat-arrow { position: absolute; right: .8rem; top: 50%; color: #a5f3fc; font-size: 1.5rem; transform: translateY(-50%); }
    .admin-stat-subtitle { display: block; margin-top: .1rem; color: #b7e5ee; font-size: .6rem; }
    .admin-dashboard { margin-top: 1rem; }
    .admin-dashboard-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(260px, .8fr); gap: 1rem; }
    .admin-dashboard-lower { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(260px, .8fr); gap: 1rem; margin-top: 1rem; }
    .admin-panel { min-width: 0; padding: 1.2rem; border: 1px solid #1d5269; border-radius: 17px; background: linear-gradient(145deg, #0d2233, #0a1b2a); box-shadow: 0 16px 32px rgba(0,0,0,.24); color: #dceff5; }
    .admin-panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
    .admin-panel h2 { margin: 0; color: #b8f1f4; font-size: 1.05rem; letter-spacing: -.02em; }
    .admin-panel-subtitle { margin: .3rem 0 0; color: #8eafbd; font-size: .76rem; line-height: 1.5; }
    .admin-activity-chart { min-height: 220px; }
    .admin-chart-empty { display: grid; min-height: 180px; place-items: center; padding: 1rem; border: 1px dashed rgba(117,213,225,.25); border-radius: 12px; color: #8eafbd; font-size: .82rem; text-align: center; }
    .admin-quick-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; }
    .admin-quick-action { display: flex; align-items: center; gap: .6rem; min-height: 3.3rem; padding: .7rem; border: 1px solid rgba(34,211,238,.2); border-radius: 11px; color: #dceff5; background: rgba(7,31,49,.68); text-decoration: none; transition: transform .18s ease, border-color .18s ease, background .18s ease; }
    .admin-quick-action:hover { transform: translateY(-2px); border-color: #22d3ee; color: #fff; background: rgba(8,126,164,.35); }
    .admin-quick-action .sipn-icon { width: 1.15rem; height: 1.15rem; flex: 0 0 auto; color: #22d3ee; }
    .admin-quick-action span { font-size: .75rem; font-weight: 700; }
    .admin-users-table-wrap { overflow-x: auto; }
    .admin-users-table { width: 100%; min-width: 430px; border-collapse: collapse; color: #dceff5; font-size: .78rem; }
    .admin-users-table th { padding: .65rem .5rem; color: #8ddfeb; border-bottom: 1px solid rgba(148,163,184,.2); font-size: .68rem; text-align: left; text-transform: uppercase; }
    .admin-users-table td { padding: .7rem .5rem; border-bottom: 1px solid rgba(148,163,184,.1); }
    .admin-users-table tr:last-child td { border-bottom: 0; }
    .admin-role-badge { display: inline-block; padding: .25rem .5rem; border-radius: 999px; font-size: .64rem; font-weight: 800; text-transform: capitalize; }
    .admin-role-badge.admin { color: #c4b5fd; background: rgba(139,92,246,.16); }
    .admin-role-badge.guru { color: #67e8f9; background: rgba(34,211,238,.14); }
    .admin-role-badge.siswa { color: #86efac; background: rgba(34,197,94,.14); }
    .admin-view-all { display: inline-block; margin-top: .85rem; color: #67e8f9; font-size: .75rem; font-weight: 700; text-decoration: none; }
    .admin-view-all:hover { color: #fff; }
    .admin-academic-chart { min-height: 245px; }
    .admin-empty { padding: 1rem 0; color: #8eafbd; font-size: .8rem; text-align: center; }
    html[data-theme="light"] body:has(.admin-dashboard) { background: linear-gradient(145deg, #071522 0%, #0b2740 58%, #073b66 100%) !important; color: #dceff5 !important; }
    @media (max-width: 1100px) { .admin-stat-row { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 900px) { .admin-dashboard-grid, .admin-dashboard-lower { grid-template-columns: 1fr; } }
    @media (max-width: 575.98px) { .admin-stat-row { grid-template-columns: 1fr; } .admin-quick-actions { grid-template-columns: 1fr; } .admin-panel { padding: 1rem; } .admin-welcome-art { display: none; } }

    /* Tema terang dashboard: pastikan semua panel tetap terang dan mudah dibaca. */
    html[data-theme="light"] .dashboard-page::before {
        opacity: .28;
        background-image: linear-gradient(rgba(8, 126, 164, .08) 1px, transparent 1px),
                          linear-gradient(90deg, rgba(8, 126, 164, .08) 1px, transparent 1px);
    }
    html[data-theme="light"] .student-welcome {
        border-color: #b9dce8;
        background: linear-gradient(118deg, #e8f8fb 0%, #d8f0f6 56%, #c8e9ef 100%);
        box-shadow: 0 16px 34px rgba(8, 91, 132, .12), inset 0 1px rgba(255,255,255,.8);
    }
    html[data-theme="light"] .student-eyebrow,
    html[data-theme="light"] .student-date { color: #087ea4; }
    html[data-theme="light"] .student-welcome h2 { color: #153b56; }
    html[data-theme="light"] .student-welcome p { color: #31556c; }
    html[data-theme="light"] .student-welcome-art { border-color: #9bcfdd; background: rgba(255,255,255,.5); color: #087ea4; }

    html[data-theme="light"] body:has(.teacher-dashboard) {
        background: linear-gradient(135deg, #eef7fb 0%, #f8fbfd 52%, #e8f3f8 100%) !important;
        color: #173b55 !important;
    }
    html[data-theme="light"] .teacher-welcome {
        border-color: #b9dce8;
        background: linear-gradient(118deg, #e8f8fb, #d8f0f6 60%, #c8e9ef);
        box-shadow: 0 16px 34px rgba(8, 91, 132, .12);
    }
    html[data-theme="light"] .teacher-welcome small,
    html[data-theme="light"] .teacher-welcome-date { color: #087ea4; }
    html[data-theme="light"] .teacher-welcome h2 { color: #153b56; }
    html[data-theme="light"] .teacher-welcome p { color: #31556c; }
    html[data-theme="light"] .teacher-dashboard .stat {
        border-color: #cfe4ed;
        background: linear-gradient(145deg, #ffffff, #edf7fb) !important;
        box-shadow: 0 14px 28px rgba(8,91,132,.1);
    }
    html[data-theme="light"] .teacher-dashboard .stat small { color: #607789 !important; }
    html[data-theme="light"] .teacher-dashboard .stat h2 { color: #064c75 !important; }
    html[data-theme="light"] .teacher-panel {
        border-color: #cfe4ed;
        background: linear-gradient(145deg, #ffffff, #f0f8fb);
        box-shadow: 0 14px 30px rgba(8,91,132,.09);
    }
    html[data-theme="light"] .teacher-panel h3 { color: #153b56; }
    html[data-theme="light"] .teacher-panel-subtitle,
    html[data-theme="light"] .teacher-chart-empty,
    html[data-theme="light"] .teacher-empty,
    html[data-theme="light"] .teacher-summary-item { color: #64798a; }
    html[data-theme="light"] .teacher-action {
        border-color: #cfe4ed;
        color: #153b56;
        background: #f5fbfd;
    }
    html[data-theme="light"] .teacher-action:hover { background: #e7f8fb; color: #064c75; }
    html[data-theme="light"] .teacher-action small { color: #64798a; }
    html[data-theme="light"] .teacher-table { color: #31556c; }
    html[data-theme="light"] .teacher-table th { color: #087ea4; border-bottom-color: #cfe4ed; }
    html[data-theme="light"] .teacher-table td { border-bottom-color: #d9eaf1; }
    html[data-theme="light"] .teacher-summary-item { border-bottom-color: #d9eaf1; }
    html[data-theme="light"] .teacher-summary-item strong { color: #153b56; }
    html[data-theme="light"] .teacher-progress { background: #d9eaf1; }
</style>
<main class="container py-5 dashboard-page">
    <h1 class="judul">Dashboard <?= h(ucfirst($role)); ?></h1>
    <?php if ($role === 'admin') { ?>
        <section class="admin-welcome" aria-label="Sambutan dashboard admin">
            <div class="admin-welcome-copy">
                <span class="admin-welcome-wave" aria-hidden="true">👋</span>
                <div><small>Portal Administrasi SIPN</small><h2>Selamat datang, <?= h($profil['nama'] ?? $_SESSION['username'] ?? 'Admin'); ?>!</h2><p>Pantau dan kelola data akademik dalam satu tempat.</p><span class="admin-welcome-date">◷ <?= h(date('D, d M Y')); ?> &nbsp;|&nbsp; <?= h(date('H:i')); ?> WIB</span></div>
            </div>
            <div class="admin-welcome-art" aria-hidden="true"><span><?= $navIcon('school'); ?></span><i></i><b></b></div>
        </section>
    <?php } ?>
    <?php if ($role === 'guru') { ?>
        <section class="teacher-welcome" aria-label="Sambutan dashboard guru">
            <div class="teacher-welcome-copy"><span class="teacher-welcome-wave" aria-hidden="true">👋</span><div><small>Teacher workspace</small><h2>Selamat datang, <?= h($profil['nama'] ?? $_SESSION['username'] ?? 'Guru'); ?>!</h2><p>Kelola pembelajaran dan pantau perkembangan siswa dengan mudah.</p><span class="teacher-welcome-date">◷ <?= h(date('D, d M Y')); ?> &nbsp;|&nbsp; <?= h(date('H:i')); ?> WIB</span></div></div>
            <div class="teacher-welcome-art" aria-hidden="true"><span><?= $navIcon('school'); ?></span><i></i><b></b></div>
        </section>
    <?php } ?>
    <div class="row g-4 justify-content-center <?= $role === 'admin' ? 'admin-stat-row' : ($role === 'guru' ? 'teacher-stat-row' : ''); ?>">
        <?php foreach ($kartu as [$judul, $nilai, $warna, $link]) {
            if ($role === 'siswa' && in_array($judul, ['Rata-rata Nilai', 'Mapel Tuntas'], true)) continue;
            $adminIconMap = ['Total User' => 'users', 'Siswa' => 'student', 'Guru' => 'teacher', 'Mata Pelajaran' => 'book', 'Data Mengajar' => 'teaching'];
            $adminSubtitleMap = ['Total User' => 'Semua pengguna sistem', 'Siswa' => 'Total siswa terdaftar', 'Guru' => 'Total guru aktif', 'Mata Pelajaran' => 'Total mata pelajaran', 'Data Mengajar' => 'Total data mengajar'];
            $teacherIconMap = ['Mapel Diampu' => 'book', 'Siswa Diajar' => 'student', 'Nilai Diinput' => 'grades'];
            $teacherSubtitleMap = ['Mapel Diampu' => 'Mata pelajaran yang Anda ampu', 'Siswa Diajar' => 'Jumlah siswa dalam kelas Anda', 'Nilai Diinput' => 'Nilai yang sudah Anda input'];
        ?>
            <div class="col-12 col-sm-6 col-lg-4 <?= $role === 'admin' ? 'admin-stat-col' : ($role === 'guru' ? 'teacher-stat-col' : ''); ?>">
                <a href="<?= h($link); ?>" class="text-decoration-none">
                    <div class="stat bg-<?= $warna; ?>">
                        <?php if ($role === 'admin') { ?><span class="admin-stat-icon"><?= $navIcon($adminIconMap[$judul] ?? 'dashboard'); ?></span><span class="admin-stat-arrow">›</span><?php } elseif ($role === 'guru') { ?><span class="teacher-stat-icon"><?= $navIcon($teacherIconMap[$judul] ?? 'dashboard'); ?></span><span class="teacher-stat-arrow">›</span><?php } ?>
                        <small><?= h($judul); ?></small>
                        <h2><?= h($nilai); ?></h2>
                        <?php if ($role === 'admin') { ?><span class="admin-stat-subtitle"><?= h($adminSubtitleMap[$judul] ?? 'Ringkasan data SIPN'); ?></span><?php } elseif ($role === 'guru') { ?><span class="teacher-stat-subtitle"><?= h($teacherSubtitleMap[$judul] ?? 'Ringkasan data guru'); ?></span><?php } ?>
                    </div>
                </a>
            </div>
        <?php } ?>
    </div>
    <?php if ($role === 'admin') { ?>
        <section class="admin-dashboard" aria-label="Ringkasan dashboard admin">
            <div class="admin-dashboard-grid">
                <section class="admin-panel" aria-labelledby="admin-activity-title">
                    <div class="admin-panel-heading">
                        <div><h2 id="admin-activity-title">Aktivitas Sistem</h2><p class="admin-panel-subtitle">Jumlah pengguna baru dalam 7 hari terakhir.</p></div>
                    </div>
                    <div class="admin-activity-chart"><canvas id="adminActivityChart" aria-label="Grafik pengguna baru tujuh hari terakhir"></canvas></div>
                </section>
                <section class="admin-panel" aria-labelledby="admin-actions-title">
                    <div class="admin-panel-heading"><div><h2 id="admin-actions-title">Aksi Cepat</h2><p class="admin-panel-subtitle">Akses fitur administrasi yang sering digunakan.</p></div></div>
                    <div class="admin-quick-actions">
                        <a class="admin-quick-action" href="Tambah_user3.php?role=admin"><?= $navIcon('add'); ?><span>Tambah User</span></a>
                        <a class="admin-quick-action" href="Tambah_user3.php?role=siswa"><?= $navIcon('student'); ?><span>Tambah Siswa</span></a>
                        <a class="admin-quick-action" href="Tambah_user3.php?role=guru"><?= $navIcon('teacher'); ?><span>Tambah Guru</span></a>
                        <a class="admin-quick-action" href="MAPEL/Tambah_mapel.php"><?= $navIcon('book'); ?><span>Tambah Mapel</span></a>
                    </div>
                </section>
            </div>
            <div class="admin-dashboard-lower">
                <section class="admin-panel" aria-labelledby="admin-users-title">
                    <div class="admin-panel-heading"><div><h2 id="admin-users-title">Pengguna Terbaru</h2><p class="admin-panel-subtitle">Lima akun terbaru berdasarkan waktu pembuatan.</p></div></div>
                    <?php if ($adminRecentUsers) { ?>
                        <div class="admin-users-table-wrap"><table class="admin-users-table"><thead><tr><th>Nama</th><th>Role</th><th>Dibuat</th></tr></thead><tbody>
                            <?php foreach ($adminRecentUsers as $user) { ?>
                                <tr><td><?= h($user['nama'] ?? $user['username']); ?><br><small><?= h($user['username']); ?></small></td><td><span class="admin-role-badge <?= h($user['role']); ?>"><?= h($user['role']); ?></span></td><td><?= h(date('d M Y', strtotime((string) $user['created_at']))); ?></td></tr>
                            <?php } ?>
                        </tbody></table></div>
                    <?php } else { ?><div class="admin-empty">Belum ada pengguna yang tersedia.</div><?php } ?>
                    <a class="admin-view-all" href="Daftar_user.php">Lihat semua pengguna →</a>
                </section>
                <section class="admin-panel" aria-labelledby="admin-academic-title">
                    <div class="admin-panel-heading"><div><h2 id="admin-academic-title">Ringkasan Data Akademik</h2><p class="admin-panel-subtitle">Distribusi data dari database SIPN.</p></div></div>
                    <div class="admin-academic-chart"><canvas id="adminAcademicChart" aria-label="Grafik ringkasan data akademik"></canvas></div>
                </section>
            </div>
        </section>
    <?php } ?>
    <?php if ($role === 'guru') { ?>
        <section class="teacher-dashboard">
            <div class="teacher-grid">
                <section class="teacher-panel"><h3>Aktivitas Penilaian</h3><p class="teacher-panel-subtitle">Jumlah nilai yang diinput per mata pelajaran.</p><?php if ($guruActivityValues) { ?><div class="teacher-activity-chart"><canvas id="teacherActivityChart" aria-label="Grafik jumlah nilai per mata pelajaran"></canvas></div><?php } else { ?><div class="teacher-chart-empty">Belum ada data nilai yang dapat ditampilkan.</div><?php } ?></section>
                <section class="teacher-panel"><h3>Aksi Cepat</h3><p class="teacher-panel-subtitle">Akses fitur yang paling sering digunakan.</p><div class="teacher-actions"><a class="teacher-action" href="NILAI/Tambah_nilai.php"><span class="teacher-action-icon"><?= $navIcon('add'); ?></span><span><strong>Input Nilai</strong><small>Masukkan nilai siswa dengan cepat.</small></span></a><a class="teacher-action" href="NILAI/Nilai.php"><span class="teacher-action-icon"><?= $navIcon('grades'); ?></span><span><strong>Lihat Data Siswa</strong><small>Lihat penilaian sesuai cakupan Anda.</small></span></a><a class="teacher-action" href="Daftar_guru.php"><span class="teacher-action-icon"><?= $navIcon('book'); ?></span><span><strong>Daftar Guru</strong><small>Lihat informasi guru dalam sistem.</small></span></a></div></section>
            </div>
            <div class="teacher-lower-grid">
                <section class="teacher-panel"><h3>Nilai Terbaru Diinput</h3><p class="teacher-panel-subtitle">Entri terakhir berdasarkan ID data karena kolom waktu belum tersedia.</p><?php if ($guruRecentNilai) { ?><div class="teacher-table-wrap"><table class="teacher-table"><thead><tr><th>Siswa</th><th>Mapel</th><th>Nilai</th><th>Status</th></tr></thead><tbody><?php foreach ($guruRecentNilai as $nilai) { $rataGuru = ($nilai['nilai_pengetahuan'] + $nilai['nilai_keterampilan']) / 2; ?><tr><td><?= h($nilai['nama_siswa']); ?><br><small><?= h($nilai['kelas']); ?></small></td><td><?= h($nilai['nama_mapel']); ?></td><td><?= h(number_format($rataGuru, 1)); ?></td><td><span class="teacher-badge <?= $nilai['Keterangan'] === 'Tuntas' ? 'done' : 'pending'; ?>"><?= h($nilai['Keterangan']); ?></span></td></tr><?php } ?></tbody></table></div><?php } else { ?><div class="teacher-empty">Belum ada data nilai yang diinput.</div><?php } ?></section>
                <section class="teacher-panel"><h3>Ringkasan Penilaian Kelas</h3><p class="teacher-panel-subtitle">Cakupan pasangan siswa dan mata pelajaran.</p><div class="teacher-summary-list"><div class="teacher-summary-item"><span>Total siswa relevan</span><strong><?= h($guruRingkasan['siswa']); ?></strong></div><div class="teacher-summary-item"><span>Sudah dinilai</span><strong><?= h($guruRingkasan['dinilai']); ?></strong></div><div class="teacher-summary-item"><span>Belum dinilai</span><strong><?= h($guruRingkasan['belum']); ?></strong></div><div><div class="teacher-summary-item"><span>Penyelesaian</span><strong><?= h($guruRingkasan['persentase']); ?>%</strong></div><div class="teacher-progress"><span style="width: <?= h($guruRingkasan['persentase']); ?>%"></span></div></div></div></section>
            </div>
        </section>
    <?php } ?>
    <?php if ($role === 'siswa') { ?>
        <section class="student-dashboard">
            <section class="student-welcome" aria-label="Sambutan dashboard siswa">
                <div class="student-welcome-copy">
                    <div class="student-eyebrow">Student learning space</div>
                    <h2>Halo, <?= h($profil['nama'] ?? $_SESSION['username']); ?> 👋</h2>
                    <p>Terus pantau perkembangan belajarmu dan jadikan setiap nilai sebagai langkah menuju target berikutnya.</p>
                    <span class="student-date"><span aria-hidden="true">◷</span><span data-student-date>Memuat tanggal...</span></span>
                </div>
                <div class="student-welcome-art" aria-hidden="true"><?= $navIcon('school'); ?></div>
            </section>
            <div class="student-section-title"><h2>Perkembangan belajar</h2><span>Ringkasan nilai dari data akademikmu</span></div>
            <div class="student-content-grid">
                <section class="student-panel" aria-labelledby="chart-title">
                    <div class="student-panel-heading"><h3 id="chart-title">Grafik perkembangan nilai</h3><span>Rata-rata per mata pelajaran</span></div>
                    <?php if (count($siswaChartValues)) { ?><div class="student-chart-wrap"><canvas id="studentGradeChart" aria-label="Grafik nilai siswa"></canvas></div><?php } else { ?><div class="student-chart-empty">Belum ada nilai yang dapat ditampilkan.<br>Grafik akan muncul setelah nilai diinput.</div><?php } ?>
                </section>
                <div>
                    <section class="student-panel" aria-labelledby="achievement-title">
                        <div class="student-panel-heading"><h3 id="achievement-title">Pencapaianmu</h3><span>Data aktual</span></div>
                        <div class="student-achievement-list">
                            <div class="student-achievement"><span class="student-achievement-icon"><?= $navIcon('grades'); ?></span><span><small>Mapel tuntas</small><strong><?= h($siswaPencapaian['tuntas']); ?> dari <?= h(count($siswaMapel)); ?></strong></span></div>
                            <div class="student-achievement"><span class="student-achievement-icon"><?= $navIcon('dashboard'); ?></span><span><small>Nilai terbaik</small><strong><?= $siswaPencapaian['nilai_terbaik'] === null ? 'Belum tersedia' : h(number_format($siswaPencapaian['nilai_terbaik'], 1) . ' · ' . $siswaPencapaian['mapel_terbaik']); ?></strong></span></div>
                        </div>
                    </section>
                    <section class="student-tip" aria-label="Tips belajar"><div class="student-tip-label">Study spark ✦</div><p data-study-tip>Atur waktu belajar dalam sesi singkat agar tetap fokus dan konsisten.</p></section>
                </div>
            </div>
            <div class="student-section-title"><h2>Mata pelajaranmu</h2><span><?= h(count($siswaMapel)); ?> mata pelajaran terdaftar</span></div>
            <?php if (count($siswaMapel)) { ?>
                <div class="student-subject-grid">
                    <?php foreach ($siswaMapel as $mapel) { $dinilai = $mapel['nilai_rata'] !== null; $tuntas = ($mapel['Keterangan'] ?? '') === 'Tuntas'; ?>
                        <article class="student-subject-card">
                            <div class="student-subject-top"><h3><?= h($mapel['nama_mapel']); ?></h3><span class="student-status <?= $tuntas ? 'done' : 'pending'; ?>"><?= $dinilai ? h($mapel['Keterangan']) : 'Belum dinilai'; ?></span></div>
                            <p class="student-teacher">Pengajar: <?= h($mapel['nama_guru']); ?></p>
                            <div class="student-grade"><small>Nilai rata-rata</small><strong><?= $dinilai ? h(number_format((float) $mapel['nilai_rata'], 1)) : '—'; ?></strong></div>
                            <a class="student-detail-link" href="NILAI/Nilai_saya.php">Lihat detail nilai →</a>
                        </article>
                    <?php } ?>
                </div>
            <?php } else { ?><div class="student-panel student-chart-empty">Belum ada mata pelajaran yang terdaftar untuk akunmu.</div><?php } ?>
        </section>
    <?php } ?>
</main>
<?php if ($role === 'admin') { ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var activityCanvas = document.getElementById('adminActivityChart');
        if (activityCanvas && typeof Chart !== 'undefined') {
            new Chart(activityCanvas, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($adminActivityLabels); ?>,
                    datasets: [{ label: 'Pengguna baru', data: <?= json_encode($adminActivityValues); ?>, borderRadius: 5, backgroundColor: '#16c7e7', hoverBackgroundColor: '#67e8f9', maxBarThickness: 34 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { displayColors: false } },
                    scales: {
                        x: { ticks: { color: '#9fc4d1', font: { size: 10 } }, grid: { color: 'rgba(148,163,184,.08)' } },
                        y: { beginAtZero: true, ticks: { color: '#9fc4d1', precision: 0 }, grid: { color: 'rgba(148,163,184,.14)' } }
                    }
                }
            });
        }
        var canvas = document.getElementById('adminAcademicChart');
        if (!canvas || typeof Chart === 'undefined') return;
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['User', 'Siswa', 'Guru', 'Mata Pelajaran', 'Data Mengajar'],
                datasets: [{
                    data: <?= json_encode(array_values($adminAcademicCounts)); ?>,
                    backgroundColor: ['#3b82f6', '#22d3ee', '#8b5cf6', '#14b8a6', '#f59e0b'],
                    borderColor: '#0d2233',
                    borderWidth: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '64%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#cfe8f0', padding: 14, usePointStyle: true, font: { size: 11 } } },
                    tooltip: { callbacks: { label: function (context) { return ' ' + context.label + ': ' + context.formattedValue; } } }
                }
            }
        });
    }());
</script>
<?php } ?>
<?php if ($role === 'siswa') { ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var dateTarget = document.querySelector('[data-student-date]');
        if (dateTarget) dateTarget.textContent = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
        var tipTarget = document.querySelector('[data-study-tip]');
        var tips = ['Pecah target besar menjadi langkah kecil yang bisa kamu selesaikan hari ini.', 'Baca kembali catatan setelah belajar agar pemahaman lebih melekat.', 'Konsistensi 30 menit setiap hari lebih kuat daripada belajar terburu-buru.', 'Tandai materi yang belum dipahami dan tanyakan pada guru di waktu yang tepat.'];
        if (tipTarget) tipTarget.textContent = tips[Math.floor(Math.random() * tips.length)];
        var canvas = document.getElementById('studentGradeChart');
        if (!canvas || typeof Chart === 'undefined') return;
        new Chart(canvas, {
            type: 'line',
            data: { labels: <?= json_encode($siswaChartLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, datasets: [{ label: 'Nilai rata-rata', data: <?= json_encode($siswaChartValues); ?>, borderColor: '#22D3EE', backgroundColor: 'rgba(34,211,238,.16)', pointBackgroundColor: '#3B82F6', pointBorderColor: '#a5f3fc', pointRadius: 4, pointHoverRadius: 6, borderWidth: 2, fill: true, tension: .35 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { displayColors: false, callbacks: { label: function (context) { return ' Nilai: ' + context.parsed.y; } } } }, scales: { x: { ticks: { color: '#94A3B8', maxRotation: 35, minRotation: 0 }, grid: { color: 'rgba(148,163,184,.1)' } }, y: { min: 0, max: 100, ticks: { color: '#94A3B8' }, grid: { color: 'rgba(148,163,184,.1)' } } } }
        });
    }());
</script>
<?php } ?>
<?php if ($role === 'guru') { ?>
<script>
    (function () {
        var dateTarget = document.querySelector('[data-teacher-date]');
        if (dateTarget) dateTarget.textContent = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
    }());
</script>
<?php } ?>
<?php require_once __DIR__ . '/Footer.php'; ?>
