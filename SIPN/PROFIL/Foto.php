<?php
require_once dirname(__DIR__) . '/Auth.php';
wajib_role(['admin', 'guru', 'siswa']);

// Sajikan hanya foto milik akun aktif dan batasi respons ke format gambar yang diizinkan.
$jalurFoto = foto_profil_path((int) $_SESSION['user_id']);
if ($jalurFoto === null) {
    http_response_code(404);
    exit;
}

$jenisFoto = [
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
];
$ekstensiFoto = strtolower(pathinfo($jalurFoto, PATHINFO_EXTENSION));
if (!isset($jenisFoto[$ekstensiFoto])) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $jenisFoto[$ekstensiFoto]);
header('Content-Length: ' . (string) filesize($jalurFoto));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($jalurFoto);
exit;