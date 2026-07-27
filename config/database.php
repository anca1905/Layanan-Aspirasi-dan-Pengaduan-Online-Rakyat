<?php
// Konfigurasi Database
$host     = 'localhost';
$dbname   = 'ana';   // Sesuai dengan nama database di ana.sql
$username = 'root';  // Username default XAMPP/Laragon
$password = '';      // Password default (kosongkan jika tidak ada)

try {
    // Membuat koneksi menggunakan PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Setting error mode agar memunculkan pesan jika ada query yang salah
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Jika koneksi gagal, hentikan program dan tampilkan error
    die("Koneksi database gagal: " . $e->getMessage());
}
