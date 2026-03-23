<?php
// Pengaturan koneksi database
$host = "localhost:3307";
$user = "root";
$pass = "";
$db   = "fzone_team";

$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>