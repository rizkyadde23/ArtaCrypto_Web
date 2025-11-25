<?php
// dashboard.php (Modern Full Card - Single Page)
// Pastikan ada file connection.php yang mendefinisikan $conn (mysqli) dan session user sudah login.

include 'connection.php';
include 'auth_refresh.php';
session_start();

function fmtPrice($price) {
    if ($price >= 1) {
        return number_format($price, 2, ',', '.'); 
    } elseif ($price >= 0.1) {
        return number_format($price, 4, ',', '.');
    } else {
        $formatted = rtrim(rtrim(sprintf("%.8f", $price), '0'), '.');
        return str_replace('.', ',', $formatted); 
    }
}

function fmtAmount($amount) {
    if ($amount >= 1) {
        return number_format($amount, 2, ',', '.');
    } elseif ($amount >= 0.1) {
        return number_format($amount, 4, ',', '.');
    } else {
        // small values < 0.1 keep up to 8 decimals but clean zeros
        $formatted = rtrim(rtrim(sprintf("%.8f", $amount), '0'), '.');
        return str_replace('.', ',', $formatted);
    }
}



if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

refreshUserSession($conn);

$user = $_SESSION['user'];
$balance_demo = (float)$user['balance_demo'];
$uid = (int)$user['id'];

// ---------- HANDLE ACTIONS (add_watchlist, remove_watchlist, transact) ----------
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_watchlist' && isset($_POST['coin_id'])) {
        $coin_id = $_POST['coin_id'];
        // avoid duplicates
        $q = "SELECT id FROM watchlist WHERE user_id = ? AND coin_id = ?";
        $st = $conn->prepare($q);
        $st->bind_param("is", $uid, $coin_id);
        $st->execute();
        $r = $st->get_result();
        if ($r->num_rows === 0) {
            $ins = "INSERT INTO watchlist (user_id, coin_id) VALUES (?, ?)";
            $si = $conn->prepare($ins);
            $si->bind_param("is", $uid, $coin_id);
            if ($si->execute()) $flash = "Coin ditambahkan ke watchlist.";
            else $flash = "Gagal menambahkan ke watchlist.";
            $si->close();
        } else {
            $flash = "Coin sudah ada di watchlist.";
        }
        $st->close();
    }

    if ($action === 'remove_watchlist' && isset($_POST['watch_id'])) {
        $watch_id = (int)$_POST['watch_id'];
        $del = "DELETE FROM watchlist WHERE id = ? AND user_id = ?";
        $sd = $conn->prepare($del);
        $sd->bind_param("ii", $watch_id, $uid);
        if ($sd->execute()) $flash = "Dihapus dari watchlist.";
        else $flash = "Gagal menghapus watchlist.";
        $sd->close();
    }

    if ($action === 'transact' && isset($_POST['coin_id'], $_POST['type'], $_POST['amount'], $_POST['price'])) {
    $coin_id = $_POST['coin_id'];
    $type = ($_POST['type'] === 'sell') ? 'sell' : 'buy';
    $amount = (float)$_POST['amount'];
    $price = (float)$_POST['price'];

    if ($amount <= 0 || $price <= 0) {
        $flash = "Jumlah dan harga harus lebih besar dari 0.";
    } else {

        $total = round($amount * $price, 8);

        // mulai transaksi SQL
        $conn->begin_transaction();

        // Lock saldo user
        $qbal = "SELECT balance_demo FROM users WHERE id = ? FOR UPDATE";
        $sbal = $conn->prepare($qbal);
        $sbal->bind_param("i", $uid);
        $sbal->execute();
        $resBal = $sbal->get_result();
        $rowBal = $resBal->fetch_assoc();
        $sbal->close();

        if (!$rowBal) {
            $conn->rollback();
            $flash = "Akun tidak ditemukan.";
        } else {

            $balance = (float)$rowBal['balance_demo'];

            if ($type === 'buy') {

                // ❗ CEK SALDO
                if ($balance < $total) {
                    $conn->rollback();
                    $flash = "Saldo tidak cukup untuk membeli. Saldo: $" . number_format($balance,2);
                } else {
                    // INSERT TRANSAKSI
                    $ins = "INSERT INTO transactions (user_id, coin_id, type, amount, price)
                            VALUES (?, ?, 'buy', ?, ?)";
                    $st = $conn->prepare($ins);
                    $st->bind_param("isdd", $uid, $coin_id, $amount, $price);
                    $ok1 = $st->execute();
                    $st->close();

                    // KURANGI SALDO
                    $upd = "UPDATE users SET balance_demo = balance_demo - ? WHERE id = ?";
                    $su = $conn->prepare($upd);
                    $su->bind_param("di", $total, $uid);
                    $ok2 = $su->execute();
                    $su->close();

                    if ($ok1 && $ok2) {
                        $conn->commit();
                        $flash = "Berhasil BUY sebesar $" . number_format($total,2);
                    } else {
                        $conn->rollback();
                        $flash = "Gagal memproses transaksi BUY.";
                    }
                }

            } else { 
                // ---------------------------
                //          SELL SECTION
                // ---------------------------

                // ❗ CEK HOLDINGS USER
                $qh = "
                    SELECT COALESCE(
                        SUM(CASE WHEN type='buy' THEN amount WHEN type='sell' THEN -amount END),0
                    ) AS hold
                    FROM transactions
                    WHERE user_id = ? AND coin_id = ?
                    FOR UPDATE
                ";
                $sh = $conn->prepare($qh);
                $sh->bind_param("is", $uid, $coin_id);
                $sh->execute();
                $resH = $sh->get_result();
                $rowH = $resH->fetch_assoc();
                $sh->close();

                $holdings = (float)$rowH['hold'];

                if ($holdings < $amount) {
                    $conn->rollback();
                    $flash = "Holdings coin tidak cukup untuk SELL. Kamu punya: " . $holdings;
                } else {

                    // INSERT sell
                    $ins = "INSERT INTO transactions (user_id, coin_id, type, amount, price)
                            VALUES (?, ?, 'sell', ?, ?)";
                    $st = $conn->prepare($ins);
                    $st->bind_param("isdd", $uid, $coin_id, $amount, $price);
                    $ok1 = $st->execute();
                    $st->close();

                    // TAMBAHKAN saldo
                    $upd = "UPDATE users SET balance_demo = balance_demo + ? WHERE id = ?";
                    $su = $conn->prepare($upd);
                    $su->bind_param("di", $total, $uid);
                    $ok2 = $su->execute();
                    $su->close();

                    if ($ok1 && $ok2) {
                        $conn->commit();
                        $flash = "Berhasil SELL, saldo bertambah $" . number_format($total,2);
                    } else {
                        $conn->rollback();
                        $flash = "Gagal memproses transaksi SELL.";
                    }
                }
            }
        }
    }


    $_SESSION['flash'] = $flash;
    header("Location: dashboard.php");
    exit;
}


    // redirect untuk mencegah resubmit form
    $_SESSION['flash'] = $flash;
    header("Location: dashboard.php");
    exit;
}

// Ambil flash dari session (jika ada)
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// ---------- FETCH DATA ----------

// 1) Top coins (limit 12) — dari tabel coins
$coins = [];
$q = "SELECT id, name, symbol, current_price, market_cap, price_change_24h FROM coins ORDER BY market_cap DESC LIMIT 12";
if ($stmt = $conn->prepare($q)) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $coins[] = $row;
    $stmt->close();
}

//2) Coin Image
// --- FETCH API COIN LIST FOR IMAGES ---
// --- FETCH IMAGE DARI API COINGECKO ---
$apiData = @file_get_contents("http://localhost/praktikum%20web/Projek/get_coins.php");
$apiCoins = json_decode($apiData, true);

// map coin id → image url
$apiImages = [];
if ($apiCoins) {
    foreach ($apiCoins as $c) {
        $apiImages[$c['id']] = $c['image'] ?? null;
    }
}


// 3) Watchlist for this user (join coins)
$watchlist = [];
$qw = "SELECT w.id as watch_id, c.id as coin_id, c.name, c.symbol, c.current_price, c.price_change_24h
       FROM watchlist w
       LEFT JOIN coins c ON w.coin_id = c.id
       WHERE w.user_id = ?
       ORDER BY w.added_at DESC";
if ($stmt = $conn->prepare($qw)) {
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $watchlist[] = $row;
    $stmt->close();
}

// 4) Recent transactions for this user (limit 20)
$transactions = [];
$qt = "SELECT t.*, c.name, c.symbol FROM transactions t LEFT JOIN coins c ON t.coin_id = c.id WHERE t.user_id = ? ORDER BY t.created_at DESC LIMIT 50";
if ($stmt = $conn->prepare($qt)) {
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $transactions[] = $row;
    $stmt->close();
}

// 5) Portfolio summary: holdings per coin (SUM buys - SUM sells) and current value
// We'll compute via SQL grouping transactions per coin
$portfolio = [];
$qport = "
SELECT
    t.coin_id,
    c.name,
    c.symbol,
    c.current_price,
    COALESCE(SUM(CASE WHEN t.type='buy' THEN t.amount WHEN t.type='sell' THEN -t.amount END),0) AS holdings_amount,
    COALESCE(SUM(CASE WHEN t.type='buy' THEN t.amount*t.price WHEN t.type='sell' THEN -t.amount*t.price END),0) AS cost_basis
FROM transactions t
LEFT JOIN coins c ON t.coin_id = c.id
WHERE t.user_id = ?
GROUP BY t.coin_id
HAVING holdings_amount <> 0
";

if ($stmt = $conn->prepare($qport)) {
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();

    $total_portfolio_value = 0.0;
    $total_cost_basis = 0.0;
    $portfolio = [];

    while ($row = $res->fetch_assoc()) {
        $row['current_price'] = (float)$row['current_price'];
        $row['holdings_amount'] = (float)$row['holdings_amount'];

        // nilai crypto
        $row['value'] = $row['current_price'] * $row['holdings_amount'];

        $total_portfolio_value += $row['value'];
        $total_cost_basis += (float)$row['cost_basis'];

        $portfolio[] = $row;
    }

    $stmt->close();
} else {
    $total_portfolio_value = 0.0;
    $total_cost_basis = 0.0;
}

// ===== Tambahkan nilai cash (balance_demo) =====
$balance_demo = (float)$user['balance_demo'];
$net_worth = $total_portfolio_value + $balance_demo; 


?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Dashboard | ArtaCrypto</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    :root {
        --bg: #0d0d0d;
        --card: #111216;
        --muted: #99a0aa;
        --accent: #00cf8a;
        --accent-2: #4b8bff;
    }

    body {
        background: var(--bg);
        color: #e8eef6;
        font-family: 'Inter', sans-serif;
    }

    .navbar {
        background: rgba(14, 14, 14, 0.9);
        border-bottom: 1px solid #151517;
        backdrop-filter: blur(6px);
    }

    .navbar .nav-link {
        color: #cfd8e3;
    }

    .navbar .nav-link:hover {
        color: white;
    }

    .container-hero {
        padding: 28px 0 18px 0;
    }

    /* Top summary */
    .summary-card {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
        border: 1px solid rgba(255, 255, 255, 0.03);
        border-radius: 12px;
        padding: 18px;
        min-height: 120px;
        box-shadow: 0 6px 20px rgba(75, 139, 255, 0.03);
    }

    .summary-value {
        font-size: 1.6rem;
        font-weight: 700;
    }

    .summary-sub {
        color: var(--muted);
        font-size: 0.95rem;
    }

    /* MARKET CARDS */
    #market .card-coin {
        background: linear-gradient(180deg, #0f1113, #0c0d0f);
        border: 1px solid rgba(255, 255, 255, 0.03);
        border-radius: 14px;
        padding: 16px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    #market .card-coin .top {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    #market .coin-price {
        font-size: 1.05rem;
        font-weight: 700;
    }

    #market .coin-change {
        font-weight: 700;
    }

    /* Watchlist & Transactions */
    .section-card {
        background: linear-gradient(180deg, #0f1113, #0c0d0f);
        border: 1px solid rgba(255, 255, 255, 0.03);
        padding: 16px;
        border-radius: 12px;
    }

    .btn-accent {
        background: var(--accent);
        border: none;
        color: #001b14;
    }

    .btn-outline-accent {
        border: 1px solid rgba(0, 207, 138, 0.12);
        color: var(--accent);
        background: transparent;
    }

    .small-muted {
        color: var(--muted);
        font-size: 0.9rem;
    }

    /* responsive tweaks */
    @media (max-width: 767px) {
        .summary-value {
            font-size: 1.25rem;
        }
    }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">ArtaCrypto</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="nav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-2"><a class="nav-link" href="index.php">Market</a></li>
                    <li class="nav-item me-2"><a class="nav-link" href="transaction.php">Buy/Sell</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- CONTAINER -->
    <div class="container py-4">

        <?php if(!empty($flash)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <!-- HERO / SUMMARY -->
        <div class="row g-3 align-items-center container-hero">
            <div class="col-md-6">
                <h2 class="mb-1">Halo, <?= htmlspecialchars($user['username']) ?></h2>
                <p class="small-muted">Dashboard ArtaCrypto — belajar & simulasi dengan uang demo.</p>
            </div>

            <div class="col-md-6">
                <div class="row g-3">

                    <!-- Summary Card Dibuat Lebih Lengkap -->
                    <div class="col-6">
                        <div class="summary-card text-center">
                            <div class="small-muted">Net Worth (Crypto + Cash)</div>
                            <div class="summary-value">$<?= number_format($net_worth, 2, '.', ',') ?></div>

                            <div class="small-muted mt-2">Crypto Holdings:
                                $<?= number_format($total_portfolio_value, 2, '.', ',') ?></div>
                            <div class="small-muted">Demo Balance: $<?= number_format($balance_demo, 2, '.', ',') ?>
                            </div>
                            <div class="small-muted">Cost Basis: $<?= number_format($total_cost_basis, 2, '.', ',') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Watchlist card -->
                    <div class="col-6">
                        <div class="summary-card text-center">
                            <div class="small-muted">Watchlist</div>
                            <div class="summary-value"><?= count($watchlist) ?> coins</div>
                            <div class="small-muted">Transactions: <?= count($transactions) ?></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>


        <!-- MAIN GRID -->
        <div class="row g-4 mt-1">

            <!-- MARKET / COINS -->
            <div class="col-lg-7">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Market — Top Coins</h5>
                        <small class="small-muted">Realtime demo (from local DB)</small>
                    </div>

                    <div id="market" class="row g-3">
                        <?php foreach($coins as $coin): 
                        $change = (float)$coin['price_change_24h'];
                        $change_class = ($change >= 0) ? 'text-success coin-change' : 'text-danger coin-change';
                        
                        $img = $apiImages[$coin['id']] ?? 'assets/default-coin.png';
                    ?>
                        <div class="col-md-6">
                            <div class="card-coin">
                                <div>
                                    <div class="top">

                                        <img src="<?= $img ?>" width="40" height="40" class="rounded-circle">
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($coin['name']) ?></div>
                                            <div class="small-muted">
                                                <?= strtoupper(htmlspecialchars($coin['symbol'])) ?></div>
                                        </div>
                                        <div class="ms-auto text-end">
                                            <div class="coin-price">
                                                $<?= number_format((float)$coin['current_price'], 2, '.', ',') ?></div>
                                            <div class="<?= $change_class ?>">
                                                <?= number_format($change, 2, '.', ',') ?>%</div>
                                        </div>
                                    </div>

                                    <div class="mt-3 small-muted">Market Cap:
                                        $<?= number_format((float)$coin['market_cap'], 0, '.', ',') ?></div>
                                </div>

                                <div class="mt-3 d-flex gap-2">
                                    <!-- Add to watchlist form -->
                                    <form method="POST" style="flex:1">
                                        <input type="hidden" name="action" value="add_watchlist">
                                        <input type="hidden" name="coin_id"
                                            value="<?= htmlspecialchars($coin['id']) ?>">
                                        <button class="btn btn-outline-accent w-100" type="submit">+ Watchlist</button>
                                    </form>

                                    <!-- Open transact modal (simple inline small form) -->

                                    <a href="transaction.php?coin_id=<?= $coin['id'] ?>" class="btn btn-accent w-100">
                                        Buy / Sell
                                    </a>


                                </div>

                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: WATCHLIST + TRANSACTIONS -->
            <div class="col-lg-5">
                <!-- Watchlist -->
                <div class="section-card mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Watchlist</h5>
                        <small class="small-muted">Coins kamu tandai</small>
                    </div>

                    <?php if(empty($watchlist)): ?>
                    <div class="text-center small-muted py-4">(Belum ada coin di watchlist)</div>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach($watchlist as $w): 
                            $chg = (float)$w['price_change_24h'];
                            $chg_class = ($chg >= 0) ? 'text-success' : 'text-danger';
                        ?>
                        <div class="list-group-item list-group-item-dark d-flex align-items-center"
                            style="background:transparent;border:none;padding-left:0;padding-right:0;">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($w['name']) ?></div>
                                <div class="small-muted"><?= strtoupper(htmlspecialchars($w['symbol'])) ?></div>
                            </div>
                            <div class="ms-auto text-end">
                                <div class="fw-bold">$<?= number_format((float)$w['current_price'], 2, '.', ',') ?>
                                </div>
                                <div class="<?= $chg_class ?> small"><?= number_format($chg,2) ?>%</div>
                            </div>

                            <div class="ms-3">
                                <form method="POST">
                                    <input type="hidden" name="action" value="remove_watchlist">
                                    <input type="hidden" name="watch_id" value="<?= (int)$w['watch_id'] ?>">
                                    <button class="btn btn-sm btn-outline-light">Remove</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Transactions -->
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Riwayat Transaksi</h5>
                        <small class="small-muted">Terakhir 50</small>
                    </div>

                    <?php if(empty($transactions)): ?>
                    <div class="text-center small-muted py-4">(Belum ada transaksi)</div>
                    <?php else: ?>
                    <div style="max-height:420px; overflow:auto;">
                        <table class="table table-borderless table-sm text-light mb-0">
                            <thead>
                                <tr class="small-muted">
                                    <th>Time</th>
                                    <th>Coin</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($transactions as $t): ?>
                                <tr>
                                    <td class="small-muted"><?= htmlspecialchars($t['created_at']) ?></td>
                                    <td><?= htmlspecialchars($t['name'] ?? $t['coin_id']) ?></td>
                                    <td><?= htmlspecialchars(strtoupper($t['type'])) ?></td>
                                    <td class="text-end">
                                        <?= fmtAmount($t['amount'])?>
                                    </td>

                                    <td class="text-end">
                                        <?= fmtPrice($t['price']) ?>
                                    </td>


                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>

        <!-- PORTFOLIO DETAILS (optional expanded list) -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="section-card">
                    <h5 class="mb-3">Portfolio Detail</h5>
                    <?php if(empty($portfolio)): ?>
                    <div class="text-center small-muted py-4">(Belum ada posisi terbuka)</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped align-middle mb-0">
                            <thead class="small-muted">
                                <tr>
                                    <th>Coin</th>
                                    <th class="text-end">Holdings</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($portfolio as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['name'] . ' (' . strtoupper($p['symbol']) . ')') ?></td>
                                    <td class="text-end">
                                        <?= rtrim(rtrim(number_format((float)$p['holdings_amount'], 8, '.', ','),'0'),'.') ?>
                                    </td>
                                    <td class="text-end">$<?= number_format((float)$p['current_price'], 2, '.', ',') ?>
                                    </td>
                                    <td class="text-end">$<?= number_format((float)$p['value'], 2, '.', ',') ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-end">$<?= number_format($total_portfolio_value, 2, '.', ',') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div> <!-- container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>