<?php
/**
 * config.php
 * File koneksi ke database MySQL (InfinityFree)
 * Universitas Semantik - Data Mahasiswa Per Program Studi
 */

// ================================
// KONFIGURASI DATABASE
// ================================
define('DB_HOST', 'ISI_PASSWORD_DISINI');
define('DB_USER', 'ISI_PASSWORD_DISINI');
define('DB_PASS', 'ISI_PASSWORD_DISINI'); 
define('DB_NAME', 'ISI_PASSWORD_DISINI');

// ================================
// MEMBUAT KONEKSI (MySQLi)
// ================================
$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek apakah koneksi berhasil
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Set karakter encoding agar teks (nama, alamat, dll) tidak rusak
mysqli_set_charset($koneksi, "utf8mb4");

// ================================
// MULAI SESSION (dibutuhkan untuk sistem login nanti)
// ================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>