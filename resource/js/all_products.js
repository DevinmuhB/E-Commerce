(function () {
    let page = 1;
    let loading = false;
    let hasMore = true;
    let selectedCategory = "";
    const produkContainer = document.getElementById("produkContainer");
    const produkLoader = document.getElementById("produkLoader");
    let observer;

    function loadMoreProduk(reset = false) {
        if (loading || !hasMore) return;
        loading = true;
        produkLoader.style.display = "block";

        if (reset) {
            page = 1;
            produkContainer.innerHTML = "";
            hasMore = true;
        }

        const url = new URL("load_product.php", window.location.href);
        url.searchParams.set("page", page);
        url.searchParams.set("limit", 9);
        if (selectedCategory !== "") {
            url.searchParams.set("kategori", selectedCategory);
        }

        fetch(url)
            .then(res => res.text())
            .then(html => {
                if (reset) produkContainer.innerHTML = "";
                produkContainer.insertAdjacentHTML("beforeend", html);
                produkLoader.style.display = "none";
                loading = false;
                bindBtnKeranjang && bindBtnKeranjang();
                // Cek jika produk yang didapat < 9, berarti sudah habis
                if (html.trim() === '' || (produkContainer.children.length % 9 !== 0)) {
                    hasMore = false;
                } else {
                    observeLastProduct();
                }
            })
            .catch(err => {
                console.error("Gagal load produk:", err);
                produkLoader.style.display = "none";
                loading = false;
            });
    }

    function observeLastProduct() {
        if (observer) observer.disconnect();
        const items = produkContainer.children;
        if (items.length === 0) return;
        const lastItem = items[items.length - 1];
        observer = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && !loading && hasMore) {
                page++;
                loadMoreProduk();
            }
        }, { threshold: 1 });
        observer.observe(lastItem);
    }

    document.querySelectorAll('.filter-kategori').forEach(cb => {
        cb.addEventListener("change", () => {
            selectedCategory = document.querySelector(".filter-kategori:checked").value;
            loadMoreProduk(true);
            bindBtnKeranjang && bindBtnKeranjang();
        });
    });

    document.addEventListener("DOMContentLoaded", () => {
        loadMoreProduk();
    });
})();