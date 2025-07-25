<?php
session_start();

include 'Config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    echo 'unauthorized';
    exit;
}

$id_user = $_SESSION['user_id'];
$id_produk = $_POST['id_produk'] ?? null;

if (!$id_produk) {
    echo 'invalid';
    exit;
}

// Cek apakah sudah ada
$cek = $conn->prepare("SELECT * FROM keranjang WHERE id_user = ? AND id_produk = ?");
$cek->bind_param("ii", $id_user, $id_produk);
$cek->execute();
$res = $cek->get_result();

if ($res->num_rows > 0) {
    // Update jumlah
    $conn->query("UPDATE keranjang SET jumlah = jumlah + 1 WHERE id_user = $id_user AND id_produk = $id_produk");
} else {
    // Insert baru
    $stmt = $conn->prepare("INSERT INTO keranjang (id_user, id_produk) VALUES (?, ?)");
    $stmt->bind_param("ii", $id_user, $id_produk);
    $stmt->execute();
}

echo 'success';
