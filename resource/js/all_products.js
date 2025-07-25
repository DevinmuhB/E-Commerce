let currentPage = 1;
let isLoading = false;
let currentKategori = '';
let currentSearch = '';

document.addEventListener('DOMContentLoaded', function() {
    // Get search query from global variable or URL
    const urlParams = new URLSearchParams(window.location.search);
    currentSearch = urlParams.get('search') || '';
    
    loadProducts();
    setupFilters();
    loadCart(); // Load cart count on page load
});

// Setup filter event listeners
function setupFilters() {
    const filterKategori = document.querySelectorAll('.filter-kategori');
    filterKategori.forEach(filter => {
        filter.addEventListener('change', function() {
            currentKategori = this.value;
            currentPage = 1;
            document.getElementById('produkContainer').innerHTML = '';
            loadProducts();
        });
    });
}

// Load products function
function loadProducts() {
    if (isLoading) return;
    
    isLoading = true;
    const loader = document.getElementById('produkLoader');
    const container = document.getElementById('produkContainer');
    
    if (loader) loader.style.display = 'block';
    
    // Build URL with parameters
    let url = `load_product.php?page=${currentPage}`;
    if (currentKategori) url += `&kategori=${encodeURIComponent(currentKategori)}`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    
    fetch(url)
        .then(response => response.text())
        .then(data => {
            if (loader) loader.style.display = 'none';
            
            if (data.trim() === '' && currentPage === 1) {
                container.innerHTML = '<p style="text-align:center;padding:20px;">Tidak ada produk ditemukan</p>';
            } else if (data.trim() !== '') {
                container.innerHTML += data;
                bindCartButtons(); // Bind cart buttons after loading products
            }
            
            isLoading = false;
        })
        .catch(error => {
            console.error('Error:', error);
            if (loader) loader.style.display = 'none';
            isLoading = false;
        });
}

// Bind cart buttons to newly loaded products
function bindCartButtons() {
    document.querySelectorAll(".btnKeranjang").forEach(btn => {
        // Remove existing event listeners to prevent duplicates
        btn.replaceWith(btn.cloneNode(true));
    });
    
    // Add new event listeners
    document.querySelectorAll(".btnKeranjang").forEach(btn => {
        btn.addEventListener("click", function () {
            const id_produk = this.dataset.id;
            
            fetch("add-to-cart.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "id_produk=" + id_produk
            })
            .then(res => res.text())
            .then(response => {
                if (response.trim() === "success") {
                    loadCart(); // update keranjang tanpa refresh
                    document.querySelector(".cart").classList.add("active"); // buka popup cart
                } else {
                    alert("Gagal menambahkan ke keranjang: " + response);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Terjadi kesalahan saat menambahkan produk ke keranjang");
            });
        });
    });
}

// Load more products on scroll (optional)
window.addEventListener('scroll', function() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;
    
    if (scrollTop + windowHeight >= documentHeight - 100 && !isLoading) {
        currentPage++;
        loadProducts();
    }
});

// Filter toggle for mobile
function open_close_filter() {
    const filter = document.querySelector('.filter');
    filter.classList.toggle('active');
}

// Load cart function (same as main.js)
function loadCart() {
    fetch("get-cart.php")
        .then(res => res.json())
        .then(data => {
            const container = document.querySelector(".items_in_cart");
            const count = document.querySelector(".count_item_cart");
            const total = document.querySelector(".price_cart_total");
            const countHeader = document.querySelector(".count_item");

            if (container) container.innerHTML = "";
            let jumlahItem = 0;

            data.items.forEach(item => {
                jumlahItem += Number(item.jumlah);

                if (container) {
                    container.innerHTML += `
                        <div class="cart_item" style="display:flex; gap:10px; margin-bottom:10px;">
                            <img src="admin/${item.path_foto}" width="50">
                            <div>
                                <p>${item.nama_produk}</p>
                                <p>
                                    Rp ${item.harga.toLocaleString('id-ID')} × ${item.jumlah}
                                </p>
                                <div class="qty_control" style="margin-top:5px;">
                                    <button class="btnKurang" data-id="${item.id_produk}">−</button>
                                    <button class="btnTambah" data-id="${item.id_produk}">+</button>
                                </div>
                            </div>
                        </div>
                    `;
                }
            });

            if (count) count.textContent = `(${data.items.length} Item in Cart)`;
            if (total) total.textContent = `Rp ${data.total.toLocaleString('id-ID')}`;
            if (countHeader) countHeader.textContent = Number(jumlahItem);

            // Re-bind button event setelah isi keranjang di-refresh
            document.querySelectorAll(".btnTambah").forEach(btn => {
                btn.addEventListener("click", function () {
                    updateCart(btn.dataset.id, 'tambah');
                });
            });

            document.querySelectorAll(".btnKurang").forEach(btn => {
                btn.addEventListener("click", function () {
                    updateCart(btn.dataset.id, 'kurang');
                });
            });
        })
        .catch(error => {
            console.error('Error loading cart:', error);
        });
}

// Update cart function (same as main.js)
function updateCart(id_produk, aksi) {
    fetch("update-cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id_produk=${id_produk}&aksi=${aksi}`
    })
    .then(res => res.text())
    .then(response => {
        if (response.trim() === "success") {
            loadCart(); // ini update isi cart langsung
        } else {
            alert("Gagal memperbarui keranjang: " + response);
        }
    })
    .catch(error => {
        console.error('Error updating cart:', error);
        alert("Terjadi kesalahan saat memperbarui keranjang");
    });
}
