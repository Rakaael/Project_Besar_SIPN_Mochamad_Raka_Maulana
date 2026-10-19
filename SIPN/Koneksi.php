<?php
// File ini berfungsi sebagai pusat koneksi ke database MySQL.
// Semua file lain memanggil file ini agar bisa terhubung ke database tanpa menulis konfigurasi berulang.

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_siacad_smk_2';

mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = mysqli_connect($host, $username, $password, $database);

if (!$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

if (!mysqli_set_charset($koneksi, 'utf8mb4')) {
    die('Gagal mengatur karakter koneksi database: ' . mysqli_error($koneksi));
}

?>