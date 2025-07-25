<?php
include 'Config/koneksi.php';

// Query untuk menambahkan kolom baru ke tabel pesanan
$queries = [
    "ALTER TABLE `pesanan` ADD COLUMN `bukti_pembayaran` VARCHAR(255) DEFAULT NULL AFTER `admin_konfirmasi`",
    "ALTER TABLE `pesanan` ADD COLUMN `metode_pembayaran` ENUM('Transfer Bank', 'E-Wallet', 'COD') DEFAULT 'Transfer Bank' AFTER `bukti_pembayaran`",
    "ALTER TABLE `pesanan` ADD COLUMN `catatan_pembayaran` TEXT DEFAULT NULL AFTER `metode_pembayaran`"
];

$success = true;
$errors = [];

foreach ($queries as $query) {
    if (!$conn->query($query)) {
        $success = false;
        $errors[] = $conn->error;
    }
}

if ($success) {
    echo "<h2>Database berhasil diperbarui!</h2>";
    echo "<p>Kolom baru telah ditambahkan ke tabel pesanan:</p>";
    echo "<ul>";
    echo "<li>bukti_pembayaran</li>";
    echo "<li>metode_pembayaran</li>";
    echo "<li>catatan_pembayaran</li>";
    echo "</ul>";
    echo "<p><a href='index.php'>Kembali ke Beranda</a></p>";
} else {
    echo "<h2>Error saat memperbarui database:</h2>";
    foreach ($errors as $error) {
        echo "<p style='color: red;'>$error</p>";
    }
}
?> 