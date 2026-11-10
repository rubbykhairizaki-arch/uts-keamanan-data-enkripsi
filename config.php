<?php
// config.php - Konfigurasi Database dan Fungsi Kriptografi AES-256-CBC

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "db_konseling_kampus";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Konfigurasi Kunci & Metode Enkripsi
define('SYSTEM_SYMMETRIC_KEY', 'KAMPUS-RAHASIA-2026');
define('CIPHER_METHOD', 'AES-256-CBC');

/**
 * Fungsi untuk mengenkripsi data (Plaintext -> Ciphertext Base64)
 */
function enkripsiData($plaintext, $key) {
    $key_hash = hash('sha256', $key, true);
    $iv_length = openssl_cipher_iv_length(CIPHER_METHOD);
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted = openssl_encrypt($plaintext, CIPHER_METHOD, $key_hash, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * Fungsi untuk mendekripsi data (Ciphertext Base64 -> Plaintext)
 */
function dekripsiData($ciphertext_base64, $input_key) {
    $decoded = base64_decode($ciphertext_base64, true);
    $iv_length = openssl_cipher_iv_length(CIPHER_METHOD);

    if ($decoded === false || strlen($decoded) <= $iv_length) {
        return ['berhasil' => false, 'hasil' => $ciphertext_base64];
    }

    $iv = substr($decoded, 0, $iv_length);
    $encrypted_raw = substr($decoded, $iv_length);
    $key_hash = hash('sha256', $input_key, true);

    $decrypted = openssl_decrypt($encrypted_raw, CIPHER_METHOD, $key_hash, OPENSSL_RAW_DATA, $iv);

    if ($decrypted === false) {
        return ['berhasil' => false, 'hasil' => $ciphertext_base64];
    }

    return ['berhasil' => true, 'hasil' => $decrypted];
}