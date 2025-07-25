<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
include 'Config/koneksi.php';

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';

// Catat waktu mulai
$start_time = microtime(true);

// Ambil data produk dari database
$produkList = [];

// Ambil semua kategori
$kategoriQuery = $conn->query("SELECT id_kategori, nama_kategori FROM kategori");

while ($kategori = $kategoriQuery->fetch_assoc()) {
    $kategori_id = $kategori['id_kategori'];
    $nama_kategori = htmlspecialchars($kategori['nama_kategori']);

    // Ambil maksimal 2 produk dari setiap kategori
    $produkQuery = $conn->prepare("SELECT p.id_produk, p.nama_produk, p.harga, p.stok, fp.path_foto 
                                  FROM produk p 
                                  LEFT JOIN foto_produk fp ON p.id_produk = fp.id_produk 
                                  WHERE p.id_kategori = ? 
                                  LIMIT 2");
    $produkQuery->bind_param("i", $kategori_id);
    $produkQuery->execute();
    $produkResult = $produkQuery->get_result();

    while ($row = $produkResult->fetch_assoc()) {
        $nama = htmlspecialchars($row['nama_produk']);
        $harga = number_format($row['harga'], 0, ',', '.');
        $stok = htmlspecialchars($row['stok']);
        $gambar = htmlspecialchars($row['path_foto']);
        $link = "detail_produk.php?id=" . $row['id_produk'];

        // Gunakan gambar default jika tidak ada foto
        $imgSrc = $gambar ? "Admin/uploads/{$gambar}" : "Admin/uploads/default-product.jpg";
        $imgAlt = $nama;

        $produkList[] = "<div style='background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border: 1px solid #e9ecef; border-radius: 16px; padding: 20px; margin: 0 0 15px 0; box-shadow: 0 8px 25px rgba(0,0,0,0.08); transition: all 0.3s ease; position: relative; overflow: hidden; width: 100%; max-width: 100%; box-sizing: border-box;'>
            <div style='position: absolute; top: 0; right: 0; background: linear-gradient(45deg, #007bff, #0056b3); color: white; padding: 8px 12px; border-radius: 0 16px 0 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;'>{$nama_kategori}</div>
            
            <div style='text-align: center; margin-bottom: 15px;'>
                <img src='{$imgSrc}' alt='{$imgAlt}' style='width: 100%; max-width: 100%; height: auto; object-fit: contain; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s ease;' onmouseover=\"this.style.transform='scale(1.05)'\" onmouseout=\"this.style.transform='scale(1)'\">
            </div>
            
            <div style='font-weight: 700; font-size: 18px; color: #2c3e50; margin-bottom: 10px; line-height: 1.3; text-align: center;'>{$nama}</div>
            
            <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;'>
                <div style='font-size: 20px; color: #27ae60; font-weight: 800;'>Rp{$harga}</div>
                <div style='background: #e8f5e8; color: #27ae60; padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;'>Stok: {$stok}</div>
            </div>
            
            <a href='{$link}' style='display: block; width: 100%; padding: 12px; background: linear-gradient(45deg, #007bff, #0056b3); color: white; border-radius: 12px; text-decoration: none; font-size: 14px; font-weight: 600; text-align: center; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,123,255,0.3);' onmouseover=\"this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,123,255,0.4)'\" onmouseout=\"this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,123,255,0.3)'\">Lihat Detail</a>
        </div>";
    }
}

$produkString = "<div style='display: flex; flex-direction: column; gap: 16px; width: 100%;'>" . implode("", $produkList) . "</div>";

// Ambil data keranjang user jika ada
$keranjangList = [];
$keranjangQuery = $conn->prepare("SELECT k.id_keranjang, p.id_produk, p.nama_produk, p.harga, fp.path_foto, k.jumlah 
                                 FROM keranjang k 
                                 JOIN produk p ON k.id_produk = p.id_produk 
                                 LEFT JOIN foto_produk fp ON p.id_produk = fp.id_produk 
                                 WHERE k.id_user = ?");
$keranjangQuery->bind_param("i", $user_id);
$keranjangQuery->execute();
$keranjangResult = $keranjangQuery->get_result();

while ($row = $keranjangResult->fetch_assoc()) {
    $nama = htmlspecialchars($row['nama_produk']);
    $harga = number_format($row['harga'], 0, ',', '.');
    $jumlah = $row['jumlah'];
    $gambar = htmlspecialchars($row['path_foto']);
    $link = "detail_produk.php?id=" . $row['id_produk'];

    // Gunakan gambar default jika tidak ada foto
    $imgSrc = $gambar ? "Admin/uploads/{$gambar}" : "Admin/uploads/default-product.jpg";
    $imgAlt = $nama;

    $keranjangList[] = "<div style='background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 1px solid #dee2e6; border-radius: 16px; padding: 18px; margin: 12px 0; box-shadow: 0 6px 20px rgba(0,0,0,0.06); transition: all 0.3s ease; position: relative;'>
        <div style='position: absolute; top: 12px; right: 12px; background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 6px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;'>Keranjang</div>
        
        <div style='display: flex; align-items: center; gap: 15px;'>
            <div style='position: relative;'>
                <img src='{$imgSrc}' alt='{$imgAlt}' style='width: 80px; height: 80px; object-fit: cover; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: transform 0.3s ease;' onmouseover=\"this.style.transform='scale(1.1)'\" onmouseout=\"this.style.transform='scale(1)'\">
                <div style='position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;'>{$jumlah}</div>
            </div>
            
            <div style='flex: 1;'>
                <div style='font-weight: 700; font-size: 16px; color: #2c3e50; margin-bottom: 6px; line-height: 1.3;'>{$nama}</div>
                <div style='font-size: 13px; color: #6c757d; margin-bottom: 4px;'>Harga Satuan: Rp{$harga}</div>
                <div style='font-size: 13px; color: #6c757d; margin-bottom: 8px;'>Jumlah: {$jumlah} item</div>
                <div style='font-size: 16px; color: #27ae60; font-weight: 800;'>Total: Rp" . number_format($row['harga'] * $jumlah, 0, ',', '.') . "</div>
            </div>
        </div>
        
        <div style='margin-top: 15px; display: flex; gap: 10px;'>
            <a href='{$link}' style='flex: 1; padding: 10px; background: linear-gradient(45deg, #28a745, #20c997); color: white; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 600; text-align: center; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(40,167,69,0.3);' onmouseover=\"this.style.transform='translateY(-2px)'\" onmouseout=\"this.style.transform='translateY(0)'\">Lihat Produk</a>
            <button onclick='addToCart({$row['id_produk']})' style='flex: 1; padding: 10px; background: linear-gradient(45deg, #007bff, #0056b3); color: white; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0,123,255,0.3);' onmouseover=\"this.style.transform='translateY(-2px)'\" onmouseout=\"this.style.transform='translateY(0)'\">+ Keranjang</button>
        </div>
    </div>";
}

$keranjangString = "<div style='display: flex; flex-direction: column; gap: 15px; padding: 10px;'>" . implode("", $keranjangList) . "</div>";

$prompt = "Kamu adalah chatbot asisten toko online yang cerdas dan ramah. Tugasmu adalah menjawab pertanyaan pelanggan dengan sopan dan informatif.\n\n" .
          "INFORMASI PRODUK:\n" .
          "Berikut daftar produk yang tersedia:\n$produkString\n\n" .
          "KERANJANG USER:\n" .
          "Produk dalam keranjang user:\n$keranjangString\n\n" .
          "PANDUAN JAWABAN:\n" .
          "1. Jika user bertanya tentang produk, berikan informasi detail dan sarankan untuk menambahkan ke keranjang\n" .
          "2. Jika user bertanya tentang keranjang, tunjukkan produk yang ada di keranjangnya\n" .
          "3. Jika user tidak bertanya tentang produk, jangan tampilkan daftar produk\n" .
          "4. Gunakan format HTML card untuk menampilkan produk dengan style yang menarik\n" .
          "5. Berikan jawaban yang detail dan informatif\n" .
          "6. Jangan gunakan tanda ** untuk formatting, gunakan HTML tags saja\n\n" .
          "FORMAT CARD PRODUK:\n" .
          "Setiap card produk WAJIB menampilkan gambar produk di bagian atas card menggunakan tag <img src='Admin/uploads/1752822142-1.jpg' ...>. JANGAN menambah kata 'uploads' pada path gambar. Jika path gambar sudah mengandung 'Admin/uploads/', langsung gunakan tanpa perubahan. Contoh path gambar yang BENAR: Admin/uploads/1752822142-1.jpg. Contoh path gambar yang SALAH: Admin/uploads/uploads/1752822142-1.jpg\n" .
          "<div style='background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>\n" .
          "  <img src='Admin/uploads/1752822142-1.jpg' alt='ASUS ROG Strix G16 i7-13650HX RTX 5070 TI 32GB' style='width: 100%; max-height: 180px; object-fit: contain; border-radius: 8px; margin-bottom: 10px;'>\n" .
          "  <div style='font-weight: bold; font-size: 16px; color: #333; margin-bottom: 8px;'>ASUS ROG Strix G16 i7-13650HX RTX 5070 TI 32GB</div>\n" .
          "  <div style='font-size: 14px; color: #666; margin-bottom: 5px;'>Kategori: Laptop</div>\n" .
          "  <div style='font-size: 14px; color: #28a745; font-weight: bold;'>Rp38.800.000</div>\n" .
          "  <div style='font-size: 12px; color: #888;'>Stok: 150</div>\n" .
          "  <a href='detail_produk.php?id=1' style='display:inline-block; margin-top:8px; padding:6px 12px; background:#007bff; color:#fff; border-radius:5px; text-decoration:none; font-size:12px;'>Lihat Detail</a>\n" .
          "</div>\n\n" .
          "PEMBAYARAN:\n" .
          "Untuk pembayaran, kami menerima QRIS dan transfer bank\n\n" .
          "KONTAK ADMIN:\n" .
          "Jika user ingin menghubungi admin, tampilkan:\n" .
          "<div style='margin-top: 10px;'>Silakan hubungi admin toko melalui:</div>\n" .
          "<a href='https://wa.me/6281385176186' target='_blank' style='display: inline-block; padding: 10px 15px; margin-right: 10px; background-color: #25D366; color: white; border-radius: 5px; text-decoration: none;'>\n" .
          "<img src='https://img.icons8.com/ios-filled/20/ffffff/whatsapp.png' style='vertical-align: middle; margin-right: 5px;'/> WhatsApp\n</a>\n" .
          "<a href='https://mail.google.com/mail/?view=cm&fs=1&to=devinbomas80@gmail.com' target='_blank' style='display: inline-block; padding: 10px 15px; background-color: #FF3737; color: white; border-radius: 5px; text-decoration: none;'>\n" .
          "<img src='https://img.icons8.com/ios-filled/20/ffffff/new-post.png' style='vertical-align: middle; margin-right: 5px;'/> Email\n</a>\n\n" .
          "LOKASI TOKO:\n" .
          "Jika user bertanya tentang lokasi toko, tampilkan:\n" .
          "<div style='margin-top:15px;'>\n" .
          "  <iframe src='https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.2499566799834!2d106.85026297425168!3d-6.361686962232608!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69ede936ed87ab%3A0x953058a58e04f47d!2sVilla%20Permata%20Alamanda!5e0!3m2!1sen!2sid!4v1746779970827!5m2!1sen!2sid' width='100%' height='300' style='border:0; border-radius:8px;' allowfullscreen='' loading='lazy' referrerpolicy='no-referrer-when-downgrade'></iframe>\n" .
          "</div>\n\n" .
          "Pertanyaan dari pelanggan:\n\"$message\"\n\n" .
          "Jawablah dengan sopan, ramah, dan informatif. Gunakan format HTML card yang menarik untuk menampilkan produk.";

try {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=AIzaSyCRDAnP_Rsp34OHqsK4eXONUhSxGfCg5Sw";

    $postData = [
        'contents' => [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("HTTP Error: $httpCode - " . $response);
    }

    $responseData = json_decode($response, true);
    
    if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception("Invalid response format from Gemini API");
    }
    
    $aiResponse = $responseData['candidates'][0]['content']['parts'][0]['text'];

    // Hitung response time
    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000, 2); // dalam milidetik

    // Simpan ke chat_history dengan response time
    $stmt = $conn->prepare("INSERT INTO chat_history (user_message, ai_response, user_id, response_time, timestamp) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssid", $message, $aiResponse, $user_id, $response_time);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'response' => $aiResponse,
        'response_time' => $response_time,
        'success' => true
    ]);

} catch (Exception $e) {
    // Hitung response time untuk error juga
    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000, 2);
    
    $errorResponse = 'Maaf, terjadi gangguan sistem. Silakan coba lagi nanti.';
    
    // Log error untuk debugging
    error_log("Gemini Proxy Error: " . $e->getMessage());
    
    try {
        // Simpan error ke database
        $stmt = $conn->prepare("INSERT INTO chat_history (user_message, ai_response, user_id, response_time, timestamp) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssid", $message, $errorResponse, $user_id, $response_time);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $dbError) {
        error_log("Database Error: " . $dbError->getMessage());
    }

    echo json_encode([
        'response' => $errorResponse,
        'response_time' => $response_time,
        'error' => true,
        'debug' => $e->getMessage()
    ]);
}
?>
