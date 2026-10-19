
<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("
    SELECT
        sp.numero_officiel,
        COUNT(DISTINCT sp.id) AS nombre_parties,
        COUNT(f.id) AS nombre_fitandremana
    FROM synodam_paritany sp
    LEFT JOIN fitandremana f ON f.sp_id = sp.id
    GROUP BY sp.numero_officiel
    ORDER BY CAST(sp.numero_officiel AS UNSIGNED) ASC
");

$sps = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSP = count($sps);
$totalParties = 0;
$totalFitandremana = 0;

foreach ($sps as $sp) {
    $totalParties += (int) $sp['nombre_parties'];
    $totalFitandremana += (int) $sp['nombre_fitandremana'];
}
?>
<!DOCTYPE html>
<html lang="mg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Fitantanana FJKM</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        :root {
            --primary: #173b68;
            --primary-light: #eaf1fb;
            --background: #f4f7fb;
            --text: #172b4d;
            --muted: #738198;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid #e7edf5;
            padding: 17px 0;
        }

        .brand-mark {
            width: 43px;
            height: 43px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: var(--primary);
            color: white;
            font-size: 22px;
            margin-right: 12px;
        }

        .brand-title {
            font-weight: 750;
            font-size: 16px;
            margin: 0;
        }

        .brand-subtitle {
            color: var(--muted);
            font-size: 12px;
            margin: 2px 0 0;
        }

        .main-content {
            max-width: 1450px;
            margin: auto;
            padding: 34px 24px 50px;
        }

        .welcome-panel {
            background: linear-gradient(120deg, #173b68, #285a91);
            color: white;
            border-radius: 22px;
            padding: 32px;
            position: relative;
            overflow: hidden;
            margin-bottom: 28px;
        }

        .welcome-panel::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border: 35px solid rgba(255,255,255,.07);
            border-radius: 50%;
            right: -50px;
            top: -95px;
        }

        .welcome-panel h1 {
            font-size: clamp(24px, 3vw, 34px);
            font-weight: 750;
            margin-bottom: 10px;
        }

        .welcome-panel p {
            color: #d9e6f7;
            margin: 0;
        }

        .welcome-actions {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 24px;
        }

        .welcome-actions .btn {
            padding: 10px 16px;
            font-weight: 600;
            border-radius: 10px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e8edf5;
            border-radius: 17px;
            padding: 22px;
            height: 100%;
            box-shadow: 0 5px 18px rgba(25, 48, 80, .035);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .icon-blue { background: #eaf1ff; }
        .icon-green { background: #e5f7ee; }
        .icon-orange { background: #fff1df; }

        .stat-label {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 5px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -.7px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            margin: 35px 0 20px;
        }

        .section-heading h2 {
            font-size: 22px;
            font-weight: 750;
            margin: 0 0 5px;
        }

        .section-heading p {
            color: var(--muted);
            margin: 0;
            font-size: 14px;
        }

        .search-box {
            position: relative;
            min-width: 260px;
            max-width: 360px;
            width: 100%;
        }

        .search-box input {
            border: 1px solid #dfe6f0;
            background: #fff;
            padding: 12px 15px 12px 42px;
            border-radius: 12px;
            width: 100%;
            outline: none;
        }

        .search-box input:focus {
            border-color: #7399ce;
            box-shadow: 0 0 0 3px rgba(49, 104, 173, .1);
        }

        .search-symbol {
            position: absolute;
            left: 14px;
            top: 11px;
            color: var(--muted);
        }

        .sp-card {
            border: 1px solid #e7edf5;
            border-radius: 17px;
            background: #fff;
            height: 100%;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }

        .sp-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(25, 48, 80, .09);
        }

        .sp-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 20px 0;
        }

        .sp-icon {
            display: flex;
            width: 48px;
            height: 48px;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 23px;
        }

        .sp-badge {
            background: #edf2f8;
            color: #536781;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 650;
        }

        .sp-card-body {
            padding: 18px 20px 20px;
        }

        .sp-name {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .sp-description {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 20px;
        }

        .sp-count {
            display: flex;
            align-items: baseline;
            gap: 7px;
            margin-bottom: 17px;
        }

        .sp-count strong {
            font-size: 27px;
            font-weight: 800;
        }

        .sp-count span {
            font-size: 13px;
            color: var(--muted);
        }

        .sp-card .btn {
            width: 100%;
            border-radius: 10px;
            padding: 10px;
            font-weight: 650;
            background: var(--primary);
            border-color: var(--primary);
        }

        .sp-card .btn:hover {
            background: #24568e;
        }

        .empty-state {
            display: none;
            text-align: center;
            padding: 35px;
            color: var(--muted);
            background: white;
            border: 1px dashed #ccd6e5;
            border-radius: 16px;
        }

        .footer {
            text-align: center;
            padding: 24px;
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 576px) {
            .main-content { padding: 22px 14px 35px; }
            .welcome-panel { padding: 24px 20px; }
            .section-heading { align-items: stretch; }
            .search-box { max-width: none; }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="container-fluid px-3 px-lg-5 d-flex
                justify-content-between align-items-center gap-3">

        <div class="d-flex align-items-center">
            <div class="brand-mark">✦</div>
            <div>
                <p class="brand-title">FJKM · Fitantanana</p>
                <p class="brand-subtitle">Rafitra fitantanana antontan-taratasy</p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="d-none d-sm-inline text-secondary small">
                👤
                <?= htmlspecialchars(
                    $_SESSION['nom_complet'] ?? $_SESSION['username'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

            <a href="logout.php" class="btn btn-outline-danger btn-sm">
                Hivoaka ↗
            </a>
        </div>
    </div>
</header>

<main class="main-content">

    <section class="welcome-panel">
        <h1>Tongasoa eto amin'ny rafitra fitantanana ny antontan-taratasy FJKM.
            Safidio ny Synodam-Paritany tianao hojerena.</h1>

        <div class="welcome-actions">
            <a href="recherche.php" class="btn btn-light">
                🔎 Karohy antontan-taratasy
            </a>

            <?php if (
                isset($_SESSION['user_role']) &&
                $_SESSION['user_role'] === 'admin'
            ): ?>
                <a href="utilisateurs.php" class="btn btn-outline-light">
                    👥 Mpampiasa
                </a>
            <?php endif; ?>
        </div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Synodam-Paritany</span>
                    <span class="stat-icon icon-blue">🏛️</span>
                </div>
                <div class="stat-number">
                    <?= number_format($totalSP, 0, ',', ' ') ?>
                </div>
                <!-- <div class="text-secondary small">SP affichés</div> -->
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Fitandremana</span>
                    <span class="stat-icon icon-green">📁</span>
                </div>
                <div class="stat-number">
                    <?= number_format($totalFitandremana, 0, ',', ' ') ?>
                </div>
                <!-- <div class="text-secondary small">Total des entrées</div> -->
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Parties des SP</span>
                    <span class="stat-icon icon-orange">🗂️</span>
                </div>
                <div class="stat-number">
                    <?= number_format($totalParties, 0, ',', ' ') ?>
                </div>
                <!-- <div class="text-secondary small">Enregistrements des SP</div> -->
            </div>
        </div>
    </section>

    <section>
        <div class="section-heading">
            <div>
                <h2>Synodam-Paritany</h2>
                <p>Safidio ny SP hanohizana ny fitantanana.</p>
            </div>

            <div class="search-box">
                <span class="search-symbol">⌕</span>
                <input type="search" id="spSearch"
                       placeholder="Rechercher un SP..."
                       aria-label="Rechercher un SP">
            </div>
        </div>

        <div class="row g-3" id="spList">
            <?php foreach ($sps as $sp): ?>
                <?php
                    $numero = (string) $sp['numero_officiel'];
                    $parties = (int) $sp['nombre_parties'];
                    $nombreFit = (int) $sp['nombre_fitandremana'];
                ?>

                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 sp-item"
                     data-search="<?= htmlspecialchars(
                         'SP ' . $numero . ' ' . $numero,
                         ENT_QUOTES,
                         'UTF-8'
                     ) ?>">

                    <article class="sp-card">
                        <div class="sp-card-top">
                            <div class="sp-icon">🏛️</div>
                            <span class="sp-badge">
                                <?= $parties ?> <?= $parties > 1 ? 'parties' : 'partie' ?>
                            </span>
                        </div>

                        <div class="sp-card-body">
                            <div class="sp-name">
                                SP <?= htmlspecialchars(
                                    $numero,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>

                            <div class="sp-description">
                                Synodam-Paritany
                            </div>

                            <div class="sp-count">
                                <strong><?= number_format($nombreFit, 0, ',', ' ') ?></strong>
                                <span>Fitandremana</span>
                            </div>

                            <a href="sp.php?numero=<?= urlencode($numero) ?>"
                               class="btn btn-primary">
                                Hijery ny antsipiriany →
                            </a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="empty-state mt-3" id="emptyState">
            Tsy misy SP mifanaraka amin'ny fikarohana.
        </div>
    </section>

</main>

<footer class="footer">
    FJKM · Rafitra fitantanana antontan-taratasy
</footer>

<script>
    const searchInput = document.getElementById('spSearch');
    const spItems = document.querySelectorAll('.sp-item');
    const emptyState = document.getElementById('emptyState');

    searchInput.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        let visible = 0;

        spItems.forEach(function (item) {
            const text = item.textContent.toLowerCase();
            const data = (item.dataset.search || '').toLowerCase();
            const match = text.includes(query) || data.includes(query);

            item.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        emptyState.style.display = visible === 0 ? 'block' : 'none';
    });
</script>

</body>
</html>
```
