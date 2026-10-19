<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/Koneksi.php';

// Batasi pemrosesan kredensial pada permintaan POST dari formulir login.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Login.php');
    exit;
}

$usernameInput = $_POST['username'] ?? '';
$username = is_string($usernameInput) ? trim($usernameInput) : '';
$password = $_POST['password'] ?? '';
// Tolak input kosong sebelum mengakses database.
if ($username === '' || !is_string($password) || $password === '') {
    header('Location: Login.php?error=invalid');
    exit;
}

$stmt = mysqli_prepare($koneksi, 'SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    exit('Login sementara tidak dapat diproses.');
}
mysqli_stmt_bind_param($stmt, 's', $username);
$executed = mysqli_stmt_execute($stmt);
$result = $executed ? mysqli_stmt_get_result($stmt) : false;
if (!$executed || !$result) {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    exit('Login sementara tidak dapat diproses.');
}
$user = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

// Verifikasi hash; jika akun lama masih menyimpan teks biasa, migrasikan setelah cocok.
$passwordValid = false;
if ($user) {
    $storedPassword = (string) $user['password'];
    $passwordValid = password_verify($password, $storedPassword);
    $passwordInfo = password_get_info($storedPassword);

    // Migrasi password lama yang tersimpan sebagai teks biasa saat login berhasil.
    if (!$passwordValid
        && ($passwordInfo['algoName'] ?? 'unknown') === 'unknown'
        && hash_equals($storedPassword, $password)) {
        $passwordValid = true;
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userId = (int) $user['id'];
        $updateStmt = mysqli_prepare($koneksi, 'UPDATE users SET password = ? WHERE id = ?');
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, 'si', $passwordHash, $userId);
            if (!mysqli_stmt_execute($updateStmt)) {
                mysqli_stmt_close($updateStmt);
                http_response_code(500);
                exit('Pembaruan keamanan akun gagal. Silakan coba lagi.');
            }
            mysqli_stmt_close($updateStmt);
        } else {
            http_response_code(500);
            exit('Pembaruan keamanan akun gagal. Silakan coba lagi.');
        }
    }
}

if (!$passwordValid) {
    header('Location: Login.php?error=invalid');
    exit;
}

// Buat sesi baru setelah autentikasi berhasil untuk mencegah session fixation.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

header('Location: Dashboard.php');
exit;
