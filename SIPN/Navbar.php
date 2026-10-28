<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
$navRole = $_SESSION['role'] ?? '';
$navDisplayName = trim((string) ($_SESSION['username'] ?? ''));
if ($isLoggedIn && isset($koneksi) && function_exists('profil_saya')) {
    $navProfile = profil_saya($koneksi);
    if (!empty($navProfile['nama'])) {
        $navDisplayName = trim((string) $navProfile['nama']);
    }
}
$navInitial = '';
if (preg_match('/^./us', $navDisplayName, $initialMatch)) {
    $navInitial = strtoupper($initialMatch[0]);
}
$navDisplayNameEscaped = htmlspecialchars($navDisplayName, ENT_QUOTES, 'UTF-8');
$navFotoUrl = $isLoggedIn && function_exists('foto_profil_url') ? foto_profil_url((int) $_SESSION['user_id']) : null;
$isDashboard = $isDashboard ?? basename($_SERVER['PHP_SELF']) === 'Dashboard.php';
// Peta ikon SVG dipakai ulang oleh tautan navigasi sesuai role dan status login.
$navIcon = static function (string $name): string {
    $paths = [
        'school' => '<path d="m3 9 9-5 9 5-9 5-9-5Z"/><path d="M7 11.2V16c2.8 2.2 7.2 2.2 10 0v-4.8M21 9v6"/>',
        'dashboard' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'home' => '<path d="m3 10 9-7 9 7"/><path d="M5 9v11h14V9M9 20v-6h6v6"/>',
        'users' => '<path d="M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20"/><circle cx="9.5" cy="7" r="4"/><path d="M17 11a4 4 0 0 0 0-8M21 20v-1.5a4 4 0 0 0-3-3.87"/>',
        'student' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0M8 3.8a6.5 6.5 0 0 1 8 0"/>',
        'teacher' => '<path d="M4 20v-1.5a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4V20"/><circle cx="10" cy="7" r="3.5"/><path d="m16 8 2 2 4-4"/>',
        'book' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Z"/><path d="M4 17.5A2.5 2.5 0 0 1 6.5 15H20M8 7h8"/>',
        'teaching' => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="m8 21 4-4 4 4M8 9h8M8 12h5"/>',
        'grades' => '<path d="M5 3h14v18l-7-4-7 4V3Z"/><path d="m9 10 2 2 4-4"/>',
        'list' => '<path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6h.01M4 12h.01M4 18h.01"/>',
        'add' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'profile' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'edit' => '<path d="m15 5 4 4M4 20l4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z"/>',
        'logout' => '<path d="M10 17 15 12 10 7M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>',
        'login' => '<path d="M14 17 19 12 14 7M19 12H7"/><path d="M11 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6"/>',
    ];
    return '<svg class="sipn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
};
?>
<!-- File Navbar.php berfungsi sebagai template navigasi utama website. -->
<!-- Menu berubah sesuai role (admin / guru / siswa). Semua link relatif terhadap folder SIPN (halaman subfolder memakai <base href="../">). -->

<script>
    (function () {
        try {
            var savedTheme = localStorage.getItem('sipn-theme');
            if (savedTheme === 'dark' || savedTheme === 'light') {
                document.documentElement.setAttribute('data-theme', savedTheme);
            }
        } catch (error) {
            // Tema default tetap digunakan jika localStorage tidak tersedia.
        }
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.querySelector('[data-theme-toggle]');
            var root = document.documentElement;
            if (!toggle || toggle.dataset.themeBound === 'true') return;
            toggle.dataset.themeBound = 'true';
            function updateToggle() {
                var dark = root.getAttribute('data-theme') === 'dark';
                toggle.setAttribute('aria-pressed', String(dark));
                toggle.setAttribute('aria-label', dark ? 'Aktifkan tema terang' : 'Aktifkan tema gelap');
                toggle.querySelector('.sipn-theme-icon').textContent = dark ? '☀' : '☾';
                toggle.querySelector('.sipn-theme-label').textContent = dark ? 'Light' : 'Dark';
            }
            updateToggle();
            toggle.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                try { localStorage.setItem('sipn-theme', next); } catch (error) {}
                updateToggle();
            });
        });
    }());
</script>
<?php if (!$isDashboard) { ?>
<nav class="navbar navbar-expand-lg navbar-dark sipn-navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="Dashboard.php">
            <span class="sipn-brand-mark" aria-hidden="true"><?= $navIcon('school'); ?></span>
            <span><span class="sipn-brand-name">SIPN</span><span class="sipn-brand-caption">Sistem Informasi Pengelolaan Nilai</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($isLoggedIn) { ?>
                <li class="nav-item"><a class="nav-link" href="Dashboard.php"><?= $navIcon('dashboard'); ?>Dashboard</a></li>
                <?php } ?>
                <?php if ($navRole === 'admin') { ?>
                <li class="nav-item"><a class="nav-link" href="Daftar_user.php"><?= $navIcon('home'); ?>Home</a></li>
                <?php } elseif ($navRole === 'guru') { ?>
                <li class="nav-item"><a class="nav-link" href="Daftar_guru.php"><?= $navIcon('home'); ?>Home</a></li>
                <?php } elseif ($navRole === 'siswa') { ?>
                <li class="nav-item"><a class="nav-link" href="Daftar_user.php?role=siswa"><?= $navIcon('home'); ?>Home</a></li>
                <?php } ?>
                <?php if ($navRole === 'admin') { ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= $navIcon('users'); ?>User</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="Daftar_user.php"><?= $navIcon('list'); ?>Daftar User 1</a></li>
                        <li><a class="dropdown-item" href="Daftar_user2.php"><?= $navIcon('list'); ?>Daftar User 2</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="Tambah_user2.php"><?= $navIcon('add'); ?>Tambah user1</a></li>
                        <li><a class="dropdown-item" href="Tambah_user.php"><?= $navIcon('add'); ?>Tambah user2</a></li>
                        <li><a class="dropdown-item" href="Tambah_user3.php"><?= $navIcon('add'); ?>Tambah user3</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= $navIcon('student'); ?>Siswa</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="Daftar_user.php?role=siswa"><?= $navIcon('list'); ?>Daftar Siswa</a></li>
                        <li><a class="dropdown-item" href="Tambah_user.php"><?= $navIcon('add'); ?>Tambah Siswa</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= $navIcon('teacher'); ?>Guru</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="Daftar_guru.php"><?= $navIcon('list'); ?>Daftar Guru</a></li>
                        <li><a class="dropdown-item" href="Tambah_user3.php?role=guru"><?= $navIcon('add'); ?>Tambah Guru</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= $navIcon('book'); ?>Mapel</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="MAPEL/Mapel.php"><?= $navIcon('list'); ?>Daftar Mapel</a></li>
                        <li><a class="dropdown-item" href="MAPEL/Tambah_mapel.php"><?= $navIcon('add'); ?>Tambah Mapel</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= $navIcon('teaching'); ?>Mengajar</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="MENGAJAR/Mengajar.php"><?= $navIcon('list'); ?>Daftar Mengajar</a></li>
                        <li><a class="dropdown-item" href="MENGAJAR/Tambah_mengajar.php"><?= $navIcon('add'); ?>Tambah Mengajar</a></li>
                    </ul>
                </li>
                <?php } elseif ($navRole === 'guru') { ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= $navIcon('grades'); ?>Kelola Nilai</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="NILAI/Nilai.php"><?= $navIcon('list'); ?>Daftar Nilai</a></li>
                        <li><a class="dropdown-item" href="NILAI/Tambah_nilai.php"><?= $navIcon('add'); ?>Input Nilai</a></li>
                    </ul>
                </li>
                <?php } elseif ($navRole === 'siswa') { ?>
                <li class="nav-item"><a class="nav-link" href="NILAI/Nilai_saya.php"><?= $navIcon('grades'); ?>Nilai Saya</a></li>
                <?php } ?>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <button class="sipn-theme-toggle" type="button" data-theme-toggle aria-label="Aktifkan tema gelap" aria-pressed="false">
                    <span class="sipn-theme-icon" aria-hidden="true">☾</span>
                    <span class="sipn-theme-label">Dark</span>
                </button>
                <?php if ($isLoggedIn) { ?>
                    <div class="dropdown">
                        <button class="btn btn-link nav-link dropdown-toggle d-inline-flex align-items-center gap-2 text-decoration-none p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if ($navFotoUrl) { ?>
                                <img src="<?= htmlspecialchars($navFotoUrl, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-circle nav-photo" alt="" aria-hidden="true">
                            <?php } else { ?>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle user-avatar fw-semibold flex-shrink-0"
                                    style="width: 2.25rem; height: 2.25rem;"><?= htmlspecialchars($navInitial, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php } ?>
                            <span><?= $navDisplayNameEscaped; ?> (<?= htmlspecialchars($navRole, ENT_QUOTES, 'UTF-8'); ?>)</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="PROFIL/Profil.php"><?= $navIcon('profile'); ?>Profil</a></li>
                            <li><a class="dropdown-item" href="PROFIL/Edit_profil.php"><?= $navIcon('edit'); ?>Edit Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="Logout.php"><?= $navIcon('logout'); ?>Logout</a></li>
                        </ul>
                    </div>
                <?php } else { ?>
                    <a class="btn btn-outline-primary" href="Login.php"><?= $navIcon('login'); ?>Masuk</a>
                    <a class="btn btn-primary" href="Tambah_user.php"><?= $navIcon('add'); ?>Daftar</a>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>
<?php } else { ?>
<div class="dashboard-layout">
    <aside class="dashboard-sidebar" aria-label="Navigasi dashboard">
        <a class="dashboard-brand" href="Dashboard.php">
            <span class="sipn-brand-mark" aria-hidden="true"><?= $navIcon('school'); ?></span>
            <span><span class="sipn-brand-name">SIPN</span><span class="sipn-brand-caption">Sistem Informasi Pengelolaan Nilai</span></span>
        </a>
        <div class="dashboard-profile">
            <?php if ($navFotoUrl) { ?>
                <img src="<?= htmlspecialchars($navFotoUrl, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-circle nav-photo" alt="">
            <?php } else { ?>
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle user-avatar fw-semibold flex-shrink-0"><?= htmlspecialchars($navInitial, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php } ?>
            <span class="dashboard-profile-name"><?= $navDisplayNameEscaped; ?><small class="dashboard-profile-role"><?= htmlspecialchars($navRole, ENT_QUOTES, 'UTF-8'); ?></small></span>
        </div>
        <nav class="dashboard-nav">
            <a class="active" href="Dashboard.php"><?= $navIcon('dashboard'); ?>Dashboard</a>
            <?php if ($navRole === 'admin') { ?>
                <a href="Daftar_user.php"><?= $navIcon('users'); ?>User</a>
                <a href="Daftar_user.php?role=siswa"><?= $navIcon('student'); ?>Siswa</a>
                <a href="Daftar_guru.php"><?= $navIcon('teacher'); ?>Guru</a>
                <a href="MAPEL/Mapel.php"><?= $navIcon('book'); ?>Mata Pelajaran</a>
                <a href="MENGAJAR/Mengajar.php"><?= $navIcon('teaching'); ?>Data Mengajar</a>
            <?php } elseif ($navRole === 'guru') { ?>
                <a href="MAPEL/Mapel.php"><?= $navIcon('book'); ?>Mata Pelajaran</a>
                <a href="MENGAJAR/Mengajar.php"><?= $navIcon('teaching'); ?>Siswa Diajar</a>
                <a href="NILAI/Nilai.php"><?= $navIcon('grades'); ?>Kelola Nilai</a>
            <?php } else { ?>
                <a href="NILAI/Nilai_saya.php"><?= $navIcon('grades'); ?>Nilai Saya</a>
                <a href="PROFIL/Profil.php"><?= $navIcon('profile'); ?>Profil</a>
            <?php } ?>
            <a href="PROFIL/Edit_profil.php"><?= $navIcon('edit'); ?>Edit Profil</a>
            <a href="Logout.php"><?= $navIcon('logout'); ?>Logout</a>
        </nav>
    </aside>
    <div class="dashboard-content">
        <header class="dashboard-topbar">
            <div><span class="dashboard-topbar-title">Portal Akademik</span><small class="dashboard-topbar-subtitle">Sistem Informasi Pengelolaan Nilai</small></div>
            <?php if ($navFotoUrl) { ?><img src="<?= htmlspecialchars($navFotoUrl, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-circle nav-photo" alt="">
            <?php } else { ?><span class="d-inline-flex align-items-center justify-content-center rounded-circle user-avatar fw-semibold"><?= htmlspecialchars($navInitial, ENT_QUOTES, 'UTF-8'); ?></span><?php } ?>
        </header>
<?php } ?>
