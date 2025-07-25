<?php
include 'Config/koneksi.php';

$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$id_kategori = isset($_GET['kategori']) && $_GET['kategori'] !== '' ? (int)$_GET['kategori'] : null;

$sql = "SELECT p.*, GROUP_CONCAT(f.path_foto ORDER BY f.id_foto ASC) AS all_foto
        FROM produk p
        LEFT JOIN foto_produk f ON p.id_produk = f.id_produk";

if ($id_kategori !== null) {
    $sql .= " WHERE p.id_kategori = $id_kategori";
}

$sql .= " GROUP BY p.id_produk
          ORDER BY p.created_at DESC
          LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

if (!$result) {
    echo "Query error: " . $conn->error;
    exit;
}

// Ambil rata-rata rating semua produk
$ratingMap = [];
$res = $conn->query("SELECT id_produk, AVG(rating) as avg_rating FROM ulasan GROUP BY id_produk");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $ratingMap[$row['id_produk']] = round($row['avg_rating'], 1);
    }
}

while ($produk = $result->fetch_assoc()):
    $gambarArray = explode(',', $produk['all_foto'] ?? '');
    $foto1 = $gambarArray[0] ?? 'uploads/default.png';
    $foto2 = $gambarArray[1] ?? $foto1;

    if (!file_exists(__DIR__ . '/admin/' . $foto1)) $foto1 = 'uploads/default.png';
    if (!file_exists(__DIR__ . '/admin/' . $foto2)) $foto2 = $foto1;

    $foto1 = 'admin/' . $foto1;
    $foto2 = 'admin/' . $foto2;
?>
<div class="product-card">
    <div class="img_product">
        <img class="img_main" src="<?= htmlspecialchars($foto1) ?>" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
        <img class="img_hover" src="<?= htmlspecialchars($foto2) ?>" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
    </div>
    
    <div class="name_product">
        <a href="detail_produk.php?id=<?= $produk['id_produk'] ?>"><?= htmlspecialchars($produk['nama_produk']) ?></a>
    </div>
    
    <div class="stars">
        <?php
        $avg = $ratingMap[$produk['id_produk']] ?? 0;
        $full = floor($avg);
        $half = ($avg - $full) >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;
        for ($i = 0; $i < $full; $i++): ?>
            <i class="fa fa-star"></i>
        <?php endfor; ?>
        <?php if ($half): ?><i class="fa fa-star-half-alt"></i><?php endif; ?>
        <?php for ($i = 0; $i < $empty; $i++): ?>
            <i class="fa fa-star-o"></i>
        <?php endfor; ?>
    </div>
    
    <div class="price">
        <p>Rp <?= number_format($produk['harga'], 0, ',', '.') ?></p>
    </div>
    
    <!-- Button container with proper ordering -->
    <div class="button-container">
        <button type="button" class="btnKeranjang btn-action" data-id="<?= $produk['id_produk'] ?>">
            <i class="fa fa-shopping-cart"></i> Tambah ke Keranjang
        </button>
        <form method="post" action="checkout.php" style="margin: 0;">
            <input type="hidden" name="id_produk" value="<?= $produk['id_produk'] ?>">
            <button type="submit" class="btn-action btn-beli">
                <i class="fa fa-bolt"></i> Beli Sekarang
            </button>
        </form>
    </div>
</div>
<?php endwhile; ?>
