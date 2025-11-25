<?php
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
        background: #0d0d0d;
        color: #ffffff;
        font-family: "Inter", sans-serif;
    }

    /* Navbar */
    .navbar {
        background: rgba(20, 20, 20, 0.9) !important;
        backdrop-filter: blur(6px);
    }

    /* Hero section */
    .hero {
        padding: 120px 0;
        text-align: center;
    }

    .hero h1 {
        color: #fff;
        font-weight: 700;
    }

    .hero p {
        color: #cfcfcf;
        font-size: 1.1rem;
    }

    /* Card coins */
    .card-coin {
        background: #161616;
        border-radius: 14px;
        padding: 18px;
        border: 1px solid #242424;
        transition: 0.25s;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .card-coin:hover {
        transform: translateY(-6px);
        box-shadow: 0px 0px 18px rgba(0, 255, 157, 0.15);
    }

    /* Fitur card */
    .feature-card {
        background: #181818;
        border: 1px solid #262626;
        border-radius: 14px;
        padding: 22px;
        transition: 0.25s;
    }

    .feature-card:hover {
        transform: translateY(-6px);
        box-shadow: 0px 0px 14px rgba(255, 255, 255, 0.08);
    }

    /* Popular section */
    #popular {
        background: #111 !important;
        color: #fff !important;
    }

    #popular h2 {
        color: #fff !important;
    }

    #popular .card-coin small {
        color: #bdbdbd;
    }


    /* Buttons */
    .btn-primary {
        background: #00cf8a;
        border: none;
        font-weight: 600;
    }

    .btn-primary:hover {
        background: #00b77a;
    }

    .btn-outline-light {
        border-color: #2c2c2c;
        color: #eaeaea;
    }

    .btn-outline-light:hover {
        background: #eaeaea;
        color: #000;
    }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">

            <!-- KIRI -->
            <a class="navbar-brand fw-bold" href="#">ArtaCrypto</a>

            <!-- TOGGLER -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- KANAN -->
            <div class="collapse navbar-collapse justify-content-end" id="nav">
                <ul class="navbar-nav">
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



    <!-- HERO -->
    <section class="hero">
        <div class="container">
            <h1>Kelola Aset Crypto Kamu — Mudah, Cepat, Real-time</h1>
            <p class="mt-3">
                Pantau harga, buat watchlist, dan kelola portfolio dengan tampilan modern dan ringan.
            </p>
            <a href="#popular" class="btn btn-primary mt-3">Lihat Popular Coins</a>
        </div>
    </section>


    <!-- POPULAR -->
    <section id="popular" class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">🔥 Popular Coins</h2>
            <div id="coin-container" class="row g-4"></div>
        </div>
    </section>


    <!-- FEATURES -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Fitur ArtaCrypto</h2>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <h4>📊 Portfolio Tracking</h4>
                        <p>Pantau seluruh asetmu dengan dashboard rapi dan clean.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <h4>💱 Riwayat Transaksi</h4>
                        <p>Cek transaksi beli/jual lengkap dengan filtering yang nyaman.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="feature-card">
                        <h4>🔔 Notifikasi Harga</h4>
                        <p>Dapatkan alert otomatis saat harga coin mencapai targetmu.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- REASON -->
    <section class="py-5 text-center">
        <div class="container">
            <h3>Mengapa pilih ArtaCrypto?</h3>
            <p class="text-secondary mt-2" style="max-width:700px;margin:auto;">
                Data real-time, tampilan simpel, dan fitur yang memudahkan pemula hingga expert trader.
            </p>
        </div>
    </section>


    <!-- FOOTER -->
    <footer class="py-4 text-center">
        <small>© <?= date('Y') ?> ArtaCrypto</small>
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
                <div class="card card-coin shadow-sm">
                    
                    <div>
                    <div class="d-flex align-items-center mb-3">
                        <img src="${coin.image}" width="40" class="me-2 rounded-circle">
                        <div>
                        <h6 class="mb-0 fw-semibold text-light">${coin.name}</h6>
                        <small class="text-light">${coin.symbol.toUpperCase()}</small>
                        </div>
                    </div>

                    <h5 class="mb-1 text-light">$${coin.current_price.toLocaleString()}</h5>
                    <small class="${color}">${change ? change.toFixed(2) : '0.00'}%</small>
                    </div>

                    <div class="mt-3">
                    <a href="coin.php?id=${coin.id}" class="btn btn-outline-light w-100">Detail</a>
                    </div>

                </div>
                </div>`;

            });
        })
        .catch(err => console.error(err));
    </script>

</body>

</html>