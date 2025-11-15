<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>ArtaCrypto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background: #0b0b0b;
        color: #fff;
    }

    .hero {
        padding: 100px 0;
        text-align: center
    }

    .card-coin {
        background: #111;
        border: none
    }

    .feature-card {
        background: #1b1b1b;
        border: none;
        transition: 0.3s;
    }

    .feature-card:hover {
        transform: translateY(-5px);
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">ArtaCrypto</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <?php if(isset($_SESSION['user'])): ?>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container">
            <h1 class="display-5 fw-bold">Kelola Aset Crypto Kamu — Mudah & Real-time</h1>
            <p class="lead mt-3">ArtaCrypto bantu kamu pantau harga, simpan watchlist, dan catat transaksi.</p>
            <a href="#popular" class="btn btn-primary btn-lg">Lihat Popular Coins</a>
        </div>
    </header>

    <section id="popular" class="py-5 bg-light text-dark">
        <div class="container">
            <h2 class="text-center mb-4">🔥 Popular Coins</h2>
            <div id="coin-container" class="row g-3">
                <!-- cards injected by JS -->
            </div>
        </div>
    </section>

    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">Fitur ArtaCrypto</h2>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card p-3">
                        <h4 class="mb-2">📊 Portfolio Tracking</h4>
                        <p>Pantau nilai aset kamu secara real-time dengan tampilan dashboard yang clean.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card feature-card p-3">
                        <h4 class="mb-2">💱 Riwayat Transaksi</h4>
                        <p>Lihat transaksi beli/jual crypto lengkap dengan filter yang rapi.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card feature-card p-3">
                        <h4 class="mb-2">🔔 Notifikasi Harga</h4>
                        <p>Dapatkan alert ketika harga coin mencapai target tertentu.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section class="py-5">
        <div class="container text-center">
            <h3>Kenapa pilih ArtaCrypto?</h3>
            <p class="mx-auto" style="max-width:700px">Realtime data dari CoinGecko, tampilan simpel, cocok buat belajar
                dan portfolio.</p>
        </div>
    </section>

    <footer class="py-4 text-center" style="background:#0a0a0a">
        <div class="container">
            <small>© <?= date('Y') ?> ArtaCrypto</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    fetch('get_coins.php')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('coin-container');
            data.forEach(coin => {
                const change = coin.price_change_percentage_24h;
                const color = (change >= 0) ? 'text-success' : 'text-danger';
                container.innerHTML += `
        <div class="col-md-3">
          <div class="card card-coin p-3 h-100 shadow-sm">
            <div class="d-flex align-items-center mb-2">
              <img src="${coin.image}" width="36" class="me-2">
              <div>
                <h6 class="mb-0">${coin.name}</h6>
                <small class="text-muted">${coin.symbol.toUpperCase()}</small>
              </div>
            </div>
            <div class="mt-auto">
              <h5 class="mb-0">$${coin.current_price.toLocaleString()}</h5>
              <small class="${color}">${change ? change.toFixed(2) : '0.00'}%</small>
              <div class="mt-2">
                <a href="coin.php?id=${coin.id}" class="btn btn-sm btn-outline-light">Detail</a>
              </div>
            </div>
          </div>
        </div>`;
            });
        })
        .catch(err => console.error(err));
    </script>
</body>

</html>