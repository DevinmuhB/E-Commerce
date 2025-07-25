<?php
session_start();
include 'Config/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login/index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$pesanan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data pesanan
$stmt = $conn->prepare("
    SELECT p.*, pr.nama_produk, pr.harga, u.username
    FROM pesanan p
    JOIN produk pr ON p.id_produk = pr.id_produk
    JOIN users u ON p.id_user = u.id
    WHERE p.id = ? AND p.id_user = ?
");
$stmt->bind_param("ii", $pesanan_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pesanan = $result->fetch_assoc();

if (!$pesanan) {
    header('Location: index.php');
    exit;
}

// Proses upload bukti pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $catatan = $_POST['catatan'] ?? '';
    
    $bukti_pembayaran = null;
    
    // Upload file bukti pembayaran jika ada
    if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'Admin/uploads/bukti_pembayaran/';
        
        // Buat direktori jika belum ada
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $filename = 'bukti_' . $pesanan_id . '_' . time() . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $filepath)) {
                $bukti_pembayaran = $filepath;
            }
        }
    }
    
    // Update status pesanan
    $update_stmt = $conn->prepare("
        UPDATE pesanan 
        SET status = 'Diproses Admin', 
            metode_pembayaran = ?, 
            catatan_pembayaran = ?,
            bukti_pembayaran = ?,
            tanggal_pembayaran = NOW()
        WHERE id = ? AND id_user = ?
    ");
    $update_stmt->bind_param("sssii", $metode_pembayaran, $catatan, $bukti_pembayaran, $pesanan_id, $user_id);
    
    if ($update_stmt->execute()) {
        echo "<script>
            alert('Bukti pembayaran berhasil diupload! Admin akan memverifikasi pembayaran Anda.');
            window.location='my_orders.php';
        </script>";
        exit;
    } else {
        $error = "Gagal mengupload bukti pembayaran. Silakan coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - TechShop</title>
    <link rel="stylesheet" href="resource/css/style.css">
    <script src="https://kit.fontawesome.com/fd85fb070c.js" crossorigin="anonymous"></script>
    <style>
        .payment-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .payment-methods {
            margin-bottom: 30px;
        }
        
        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: #007bff;
        }
        
        .payment-method.selected {
            border-color: #007bff;
            background: #f8f9ff;
        }
        
        .payment-method input[type="radio"] {
            margin-right: 10px;
        }
        
        .bank-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        
        .upload-section {
            margin: 20px 0;
        }
        
        .file-input {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .file-input:hover {
            border-color: #007bff;
        }
        
        .btn-pay {
            background: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-pay:hover {
            background: #218838;
        }
        
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <header>
        <div class="container top-nav">
            <a href="index.php" class="logo"><img src="resource/img/logo-black.png" alt=""></a>
            <div class="cart_header">
                <div onclick="open_cart()" class="icon_cart">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span class="count_item"></span>
                </div>
                <div class="total_price">
                    <p>My Cart:</p>
                    <p class="price_cart_Head">$0</p>
                </div>
            </div>
        </div>

        <nav>
            <div class="links container">
                <i onclick="open_menu()" class="fa-solid fa-bars btn_open_menu"></i>
                <ul id="menu">
                    <span onclick="close_menu()" class="bg-overlay"></span>
                    <i onclick="close_menu()" class="fa-regular fa-circle-xmark btn_close_menu"></i>
                    <img class="logo_menu" src="resource/img/logo-black.png" alt="">
                    <li><a href="index.php">home</a></li>
                    <li><a href="all_products.php">all products</a></li>
                </ul>

                <div class="loging_signup">
                    <?php if (isset($_SESSION['username'])): ?>
                        <div class="profile_dropdown" id="profileDropdown">
                            <a href="#" id="profileBtn"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                            <div class="dropdown_content" id="dropdownContent">
                                <a href="profile.php">Profil Saya</a>
                                <a href="my_orders.php">Pesanan Saya</a>
                                <a href="#" onclick="konfirmasiLogout()">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login/index.php">login <i class="fa-solid fa-right-to-bracket"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="payment-container">
            <div class="payment-header">
                <h1><i class="fa-solid fa-credit-card"></i> Pembayaran Pesanan</h1>
                <p>Silakan pilih metode pembayaran dan upload bukti pembayaran</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="order-details">
                <h3>Detail Pesanan #<?= $pesanan_id ?></h3>
                <p><strong>Produk:</strong> <?= htmlspecialchars($pesanan['nama_produk']) ?></p>
                <p><strong>Harga:</strong> Rp <?= number_format($pesanan['harga'], 0, ',', '.') ?></p>
                <p><strong>Nama Penerima:</strong> <?= htmlspecialchars($pesanan['nama_penerima']) ?></p>
                <p><strong>Alamat:</strong> <?= htmlspecialchars($pesanan['alamat']) ?></p>
                <p><strong>Tanggal Pesanan:</strong> <?= date('d/m/Y H:i', strtotime($pesanan['tanggal_pesanan'])) ?></p>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="payment-methods">
                    <h3>Pilih Metode Pembayaran</h3>
                    
                    <div class="payment-method" onclick="selectPayment('Transfer Bank')">
                        <input type="radio" name="metode_pembayaran" value="Transfer Bank" id="transfer" checked>
                        <label for="transfer">
                            <i class="fa-solid fa-university"></i> Transfer Bank
                        </label>
                    </div>
                    
                    <div class="payment-method" onclick="selectPayment('E-Wallet')">
                        <input type="radio" name="metode_pembayaran" value="E-Wallet" id="ewallet">
                        <label for="ewallet">
                            <i class="fa-solid fa-mobile-alt"></i> E-Wallet (DANA, OVO, GoPay)
                        </label>
                    </div>
                    
                    <div class="payment-method" onclick="selectPayment('COD')">
                        <input type="radio" name="metode_pembayaran" value="COD" id="cod">
                        <label for="cod">
                            <i class="fa-solid fa-money-bill-wave"></i> Cash on Delivery (COD)
                        </label>
                    </div>
                </div>

                <div id="bank-info" class="bank-info">
                    <h4><i class="fa-solid fa-university"></i> Informasi Rekening Bank</h4>
                    <p><strong>Bank BCA:</strong> 1234567890</p>
                    <p><strong>Atas Nama:</strong> TechShop Indonesia</p>
                    <p><strong>Jumlah Transfer:</strong> Rp <?= number_format($pesanan['harga'], 0, ',', '.') ?></p>
                    <p><em>Harap transfer sesuai jumlah yang tertera dan simpan bukti transfer</em></p>
                </div>

                <div id="ewallet-info" class="bank-info" style="display:none;">
                    <h4><i class="fa-solid fa-mobile-alt"></i> Informasi E-Wallet</h4>
                    <p><strong>DANA:</strong> 081234567890</p>
                    <p><strong>OVO:</strong> 081234567890</p>
                    <p><strong>GoPay:</strong> 081234567890</p>
                    <p><strong>Jumlah Transfer:</strong> Rp <?= number_format($pesanan['harga'], 0, ',', '.') ?></p>
                </div>

                <div id="cod-info" class="bank-info" style="display:none;">
                    <h4><i class="fa-solid fa-money-bill-wave"></i> Informasi COD</h4>
                    <p>Pembayaran dilakukan saat barang diterima</p>
                    <p><strong>Jumlah yang harus dibayar:</strong> Rp <?= number_format($pesanan['harga'], 0, ',', '.') ?></p>
                </div>

                <div class="upload-section">
                    <h3>Upload Bukti Pembayaran</h3>
                    <div class="file-input">
                        <input type="file" name="bukti_pembayaran" accept="image/*,.pdf" required>
                        <p><small>Format: JPG, PNG, PDF (Max: 5MB)</small></p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="catatan">Catatan (Opsional):</label>
                    <textarea name="catatan" id="catatan" rows="3" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                </div>

                <button type="submit" name="submit_payment" class="btn-pay">
                    <i class="fa-solid fa-check"></i> Konfirmasi Pembayaran
                </button>
            </form>
        </div>
    </main>

    <script>
        function selectPayment(method) {
            // Reset semua payment method
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Select yang dipilih
            event.currentTarget.classList.add('selected');
            
            // Update radio button
            document.querySelector(`input[value="${method}"]`).checked = true;
            
            // Tampilkan info sesuai metode
            document.getElementById('bank-info').style.display = 'none';
            document.getElementById('ewallet-info').style.display = 'none';
            document.getElementById('cod-info').style.display = 'none';
            
            if (method === 'Transfer Bank') {
                document.getElementById('bank-info').style.display = 'block';
            } else if (method === 'E-Wallet') {
                document.getElementById('ewallet-info').style.display = 'block';
            } else if (method === 'COD') {
                document.getElementById('cod-info').style.display = 'block';
            }
        }
        
        // Select payment method saat halaman load
        document.addEventListener('DOMContentLoaded', function() {
            selectPayment('Transfer Bank');
        });
    </script>
</body>
</html> 