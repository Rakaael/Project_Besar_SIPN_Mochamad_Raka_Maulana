<?php

// Konfigurasi database InfinityFree
$host = "sql312.infinityfree.com";
$user = "if0_43364739";
$pass = "RakaProject2026";
$db   = "if0_43364739_Sipn";

// Matikan laporan error mysqli
mysqli_report(MYSQLI_REPORT_OFF);

// Hubungkan ke database
$koneksi = mysqli_connect(
    $host,
    $user,
    $pass,
    $db,
    3306
);

// Periksa koneksi
if (!$koneksi) {
    error_log("[SIPN] Database connection failed: " . mysqli_connect_error());
    http_response_code(500);
    exit("Aplikasi tidak dapat terhubung ke database.");
}

// Atur karakter database
if (!mysqli_set_charset($koneksi, "utf8mb4")) {
    error_log("[SIPN] Gagal mengatur charset database.");
    http_response_code(500);
    exit("Koneksi database tidak dapat disiapkan.");
}

?>