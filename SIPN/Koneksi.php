<?php
// Koneksi database dipusatkan di file ini agar tidak ditulis ulang di setiap halaman.

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_siacad_smk_2';

mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = mysqli_connect($host, $username, $password, $database);

if (!$koneksi) {
    error_log('[SIPN] Database connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    exit('Aplikasi tidak dapat terhubung ke database.');
}

if (!mysqli_set_charset($koneksi, 'utf8mb4')) {
    error_log('[SIPN] Failed to set database connection charset: ' . mysqli_error($koneksi));
    http_response_code(500);
    exit('Koneksi database tidak dapat disiapkan.');
}

?>
