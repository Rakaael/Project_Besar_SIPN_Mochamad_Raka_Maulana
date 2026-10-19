<?php
// Halaman siswa untuk melihat nilai rapor miliknya sendiri (hanya baca).
require_once dirname(__DIR__) . '/Koneksi.php';
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['siswa']);

$siswa = profil_saya($koneksi);
$data = ambil($koneksi, "SELECT n.id, n.nilai_kkm, n.nilai_pengetahuan, n.nilai_keterampilan, n.Keterangan,
        m.kode_mapel, m.nama_mapel, COALESCE(g.nama, '-') AS nama_guru
    FROM mengajar j
    JOIN mapel m ON m.id = j.mapel_id
    LEFT JOIN nilai_rapor n ON n.mapel_id = j.mapel_id AND n.siswa_id = j.siswa_id
    LEFT JOIN guru g ON g.id = m.guru_id
    WHERE j.siswa_id = ?
    ORDER BY m.nama_mapel", 'i', [(int) ($siswa['id'] ?? 0)]);

// Hitung ringkasan nilai dari data rapor yang hanya milik siswa yang sedang login.
$jumlah = count($data);
$total = 0; $tuntas = 0;
foreach ($data as $d) {
    if ($d['id'] !== null) {
        $total += ($d['nilai_pengetahuan'] + $d['nilai_keterampilan']) / 2;
        if ($d['Keterangan'] === 'Tuntas') $tuntas++;
    }
}
$jumlahDinilai = count(array_filter($data, static fn ($nilai) => $nilai['id'] !== null));
$rata = $jumlahDinilai ? $total / $jumlahDinilai : 0;

kepala('Nilai Saya', true, true);
require_once dirname(__DIR__) . '/Navbar.php';
?>
<div class="container-fluid table-container">
    <h1 class="judul">Nilai Rapor Saya</h1>
    <p class="text-center text-secondary"><?= h($siswa['nama'] ?? ''); ?> &middot; Kelas <?= h($siswa['kelas'] ?? '-'); ?> &middot; NIS <?= h($siswa['nis'] ?? '-'); ?></p>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat bg-primary"><small>Mapel Diikuti</small><h2><?= $jumlah; ?></h2></div></div>
        <div class="col-md-4"><div class="stat bg-success"><small>Rata-rata Nilai</small><h2><?= $jumlahDinilai ? number_format($rata, 1) : '-'; ?></h2></div></div>
        <div class="col-md-4"><div class="stat bg-warning"><small>Mapel Tuntas</small><h2><?= $tuntas . ' / ' . $jumlah; ?></h2></div></div>
    </div>
    <table id="tabelNilaiSaya" class="table table-striped table-bordered w-100">
        <thead>
            <tr><th>No</th><th>Kode</th><th>Mata Pelajaran</th><th>Guru</th><th>KKM</th><th>Pengetahuan</th><th>Keterampilan</th><th>Rata-rata</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
        <?php $no = 1; foreach ($data as $d) { ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= h($d['kode_mapel']); ?></td>
                <td><?= h($d['nama_mapel']); ?></td>
                <td><?= h($d['nama_guru']); ?></td>
                <td><?= $d['id'] === null ? '-' : (int) $d['nilai_kkm']; ?></td>
                <td><?= $d['id'] === null ? '-' : (int) $d['nilai_pengetahuan']; ?></td>
                <td><?= $d['id'] === null ? '-' : (int) $d['nilai_keterampilan']; ?></td>
                <td><b><?= $d['id'] === null ? '-' : number_format(($d['nilai_pengetahuan'] + $d['nilai_keterampilan']) / 2, 1); ?></b></td>
                <td><?php if ($d['id'] === null) { ?><span class="badge text-bg-secondary">Belum dinilai</span><?php } else { ?><span class="badge text-bg-<?= $d['Keterangan'] === 'Tuntas' ? 'success' : 'danger'; ?>"><?= h($d['Keterangan']); ?></span><?php } ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php skrip_tabel('tabelNilaiSaya'); require_once dirname(__DIR__) . '/Footer.php'; ?>
