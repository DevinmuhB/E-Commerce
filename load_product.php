<?php
include 'Config/koneksi.php';

$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$id_kategori = isset($_GET['kategori']) && $_GET['kategori'] !== '' ? (int)$_GET['kategori'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build base query
$sql = "SELECT p.*, GROUP_CONCAT(f.path_foto ORDER BY f.id_foto ASC) AS all_foto
        FROM produk p
        LEFT JOIN foto_produk f ON p.id_produk = f.id_produk";

$conditions = [];
$params = [];
$types = "";

// Add category filter
if ($id_kategori !== null) {
    $conditions[] = "p.id_kategori = ?";
    $params[] = $id_kategori;
    $types .= "i";
}

// Add search filter
if (!empty($search)) {
    $conditions[] = "(p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

// Add WHERE clause if there are conditions
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY p.id_produk
          ORDER BY p.created_at DESC
          LIMIT $limit OFFSET $offset";

// Use prepared statement if there are parameters
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        echo "Query preparation error: " . $conn->error;
        exit;
    }
} else {
    $result = $conn->query($sql);
}

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

// Check if no results found
if ($result->num_rows === 0 && $page === 1) {
    if (!empty($search)) {
        echo '<div style="text-align:center;padding:40px;background:#fff;border-radius:8px;">';
        echo '<i class="fa fa-search" style="font-size:48px;color:#ccc;margin-bottom:20px;"></i>';
        echo '<h3 style="color:#666;margin-bottom:10px;">Tidak ada produk ditemukan</h3>';
        echo '<p style="color:#999;">Coba gunakan kata kunci yang berbeda atau lihat semua produk</p>';
        echo '<a href="all_products.php" style="color:var(--main-color);text-decoration:none;font-weight:bold;">← Lihat Semua Produk</a>';
        echo '</div>';
    } else {
        echo '<div style="text-align:center;padding:40px;background:#fff;border-radius:8px;">';
        echo '<p style="color:#666;">Tidak ada produk ditemukan</p>';
        echo '</div>';
    }
    exit;
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
