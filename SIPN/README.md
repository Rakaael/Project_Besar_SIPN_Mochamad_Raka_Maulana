# SIPN – Sistem Informasi Pengelolaan Nilai
Tugas Besar Pemrograman Web (SMK Sangkuriang 1 Cimahi, RPL 2026/2027). PHP native (mysqli) + MySQL + Bootstrap 5.

## Menjalankan (Laragon)
1. Letakkan folder `SIPN` di `C:\laragon\www\`, jalankan Apache & MySQL.
2. Database yang dipakai aplikasi saat ini adalah `db_siacad_smk_2` (lihat `Koneksi.php`). `database/sipn.sql` membuat/menggunakan database bernama sama dan menghapus lalu membuat ulang tabel di database itu. Jangan impor ulang ke database aktif tanpa backup. Untuk lingkungan lain, koneksi dapat dioverride dengan environment `SIPN_DB_HOST`, `SIPN_DB_USERNAME`, `SIPN_DB_PASSWORD`, dan `SIPN_DB_NAME`.
3. Untuk checkout ini, buka `http://localhost/Tugas_besar_Mochamad_Raka_Maulana/SIPN/`, lalu login. Jika folder `SIPN` dipindahkan langsung ke `C:\laragon\www\SIPN`, gunakan `http://localhost/SIPN/`.

### Memperbarui database yang sudah ada
Jangan jalankan ulang `database/sipn.sql` pada database yang berisi data karena skrip tersebut menghapus dan membuat ulang tabel. Indeks unik kode mapel dan pasangan nilai sudah didefinisikan pada skema untuk database baru di `database/sipn.sql`. Menghapus file migrasi tidak mengubah tabel atau indeks yang sudah ada pada database aktif.

## Fitur per role
| Fitur | Admin | Guru | Siswa |
|---|:-:|:-:|:-:|
| Login / Logout / Dashboard / Profil (lihat & edit) | ✔ | ✔ | ✔ |
| Register (siswa; kelas ditetapkan admin) | | | ✔ (`Tambah_user.php`) |
| Kelola User, Siswa, Guru (CRUD) | ✔ | | |
| Kelola Mata Pelajaran (CRUD) — `MAPEL/` | ✔ | | |
| Kelola Mengajar (CRUD) — `MENGAJAR/` | ✔ | | |
| Kelola Nilai Siswa (CRUD, hanya siswa yang terdaftar pada mapel di `mengajar`) — `NILAI/Nilai.php` | | ✔ | |
| Lihat seluruh nilai (tambahan) | ✔ | | |
| Lihat nilai sendiri — `NILAI/Nilai_saya.php` | | | ✔ |

`Keterangan` nilai otomatis: **Tuntas** jika rata-rata (pengetahuan, keterampilan) ≥ KKM.
`SIPN/Auth.php` berisi pengecekan login/role dan fungsi bantu yang dipakai halaman baru.
Penugasan siswa ke guru/mapel menggunakan tabel `mengajar`; aplikasi tidak memerlukan tabel penugasan kelas tambahan.
