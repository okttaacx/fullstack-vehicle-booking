<?php
$db = new mysqli("localhost", "root", "", "vehicle_booking_db");

if ($db->connect_error) {
    die("Koneksi gagal: " . $db->connect_error);
}

$hash = '$2y$10$NozjnyX.7IjDJqeGQEbFVe4sejcbIMnO3lDKn4MCUBtat4msXJyMi';

$stmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->bind_param("s", $hash);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Password admin berhasil diupdate!";
} else {
    echo "Tidak ada baris yang terupdate. Cek lagi username-nya.";
}

$stmt->close();
$db->close();