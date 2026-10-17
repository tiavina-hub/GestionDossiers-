```php
<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

$fitandremana_id = (int) ($_GET['fitandremana_id'] ?? 0);

if ($fitandremana_id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT f.id, f.nom, sp.id AS sp_id,
           sp.numero_officiel, sp.numero, sp.nom AS sp_nom
    FROM fitandremana f
    JOIN synodam_paritany sp ON sp.id = f.sp_id
    WHERE f.id = ?
");
$stmt->execute([$fitandremana_id]);
$fitandremana = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fitandremana) {
    http_response_code(404);
    exit('Fitandremana tsy hita.');
}

$stmt = $pdo->prepare("
    SELECT t.id, t.nom, t.description,
           COUNT(d.id) AS nombre_dossiers
    FROM types_dossiers t
    LEFT JOIN dossiers d ON d.type_id = t.id
    WHERE t.fitandremana_id = ?
    GROUP BY t.id, t.nom, t.description
    ORDER BY t.nom
");
$stmt->execute([$fitandremana_id]);
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalDossiers = array_sum(array_column($types, 'nombre_dossiers'));
?>
<!DOCTYPE html>
<html lang="mg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Karazana dossiers | FJKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #173b68;
            --background: #f4f7fb;
            --muted: #738198;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--background);
            color: #172b4d;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        .topbar {
            background: white;
            border-bottom: 1px solid #e7edf5;
            padding: 16px 0;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: var(--primary);
            color: white;
            font-size: 21px;
        }

        .main {
            max-width: 1300px;
            margin: auto;
            padding: 30px 24px 50px;
        }

        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            align-items: center;
            margin-bottom: 24px;
            font-size: 14px;
            color: var(--muted);
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        .breadcrumb a:hover { text-decoration: underline; }

        .hero {
            background: linear-gradient(120deg, #173b68, #285a91);
            color: white;
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 25px;
        }

        .hero h1 {
            font-size: clamp(23px, 3vw, 31px);
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .hero p {
            color: #d9e6f7;
            margin-bottom: 0;
        }

        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .stat {
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 11px;
            padding: 10px 14px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin: 30px 0 20px;
        }

        .toolbar h2 {
            font-size: 21px;
            font-weight: 750;
            margin-bottom: 4px;
        }

        .toolbar p {
            color: var(--muted);
            margin: 0;
            font-size: 14px;
        }

        .search {
            width: 100%;
            max-width: 350px;
            border: 1px solid #dfe6f0;
            border-radius: 11px;
            background: white;
            padding: 12px 14px;
        }

        .type-card {
            height: 100%;
            background: white;
            border: 1px solid #e5ebf4;
            border-radius: 16px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            transition: .2s;
        }

        .type-card:hover {
            transform: translateY(-4px);
            border-color: #cbd9eb;
            box-shadow: 0 12px 26px rgba(25,48,80,.08);
            color: inherit;
        }

        .type-icon {
            width: 47px;
            height: 47px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eaf1fb;
            color: var(--primary);
            border-radius: 13px;
            font-size: 23px;
            margin-bottom: 18px;
        }

        .type-card h3 {
            font-size: 19px;
            font-weight: 750;
            overflow-wrap: anywhere;
            margin-bottom: 9px;
        }

        .description {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6;
            overflow-wrap: anywhere;
            margin-bottom: 20px;
        }

        .type-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border-top: 1px solid #edf1f6;
            padding-top: 15px;
            margin-top: auto;
        }

        .count {
            font-size: 13px;
            color: var(--muted);
        }

        .count strong {
            color: var(--primary);
            font-size: 18px;
        }

        .open-link {
            color: var(--primary);
            font-size: 13px;
            font-weight: 700;
        }

        .empty {
            background: white;
            border: 1px dashed #ccd6e5;
            border-radius: 16px;
            padding: 35px 20px;
            text-align: center;
        }

        @media (max-width: 576px) {
            .main { padding: 22px 14px 35px; }
            .hero { padding: 23px 20px; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="container-fluid px-3 px-lg-5 d-flex justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <div class="brand-mark">✦</div>
            <div>
                <div class="fw-bold">FJKM · Fitantanana</div>
                <div class="text-secondary small">Fitantanana antontan-taratasy</div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="d-none d-sm-inline text-secondary small">
                👤 <?= htmlspecialchars(
                    $_SESSION['nom_complet'] ?? $_SESSION['username'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Hivoaka ↗</a>
        </div>
    </div>
</header>

<main class="main">
    <nav class="breadcrumb" aria-label="Navigation">
        <a href="index.php">Dashboard</a>
        <span>›</span>
        <a href="sp.php?numero=<?= urlencode($fitandremana['numero_officiel']) ?>">
            SP <?= htmlspecialchars($fitandremana['numero_officiel'], ENT_QUOTES, 'UTF-8') ?>
        </a>
        <span>›</span>
        <a href="fitandremana.php?sp_id=<?= (int) $fitandremana['sp_id'] ?>">
            Fitandremana
        </a>
        <span>›</span>
        <span>Karazana dossiers</span>
    </nav>

    <section class="hero">
        <h1>Karazana dossiers</h1>
        <p>
            SP <?= htmlspecialchars($fitandremana['numero_officiel'], ENT_QUOTES, 'UTF-8') ?>
            — <?= htmlspecialchars($fitandremana['sp_nom'], ENT_QUOTES, 'UTF-8') ?>
        </p>
        <div class="mt-3 fw-semibold">
            📁 <?= htmlspecialchars($fitandremana['nom'], ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="stats">
            <div class="stat">🗂️ <strong><?= count($types) ?></strong> karazana</div>
            <div class="stat">📄 <strong><?= (int) $totalDossiers ?></strong> dossiers</div>
        </div>
    </section>

    <section>
        <div class="toolbar">
            <div>
                <h2>Lisitry ny karazana</h2>
                <p>Safidio ny karazana hijerena ireo dossiers mifandraika aminy.</p>
            </div>
            <?php if ($types): ?>
                <input id="typeSearch" class="search" type="search"
                       placeholder="Karohy ny karazana..."
                       aria-label="Karohy ny karazana">
            <?php endif; ?>
        </div>

        <?php if (!$types): ?>
            <div class="empty">
                <div style="font-size:38px">🗂️</div>
                <h3 class="h5 mt-3">Tsy mbola misy karazana dossiers.</h3>
                <p class="text-secondary mb-0">
                    Tsy misy karazana voasoratra ho an'ity Fitandremana ity.
                </p>
            </div>
        <?php else: ?>
            <div class="row g-3" id="typeList">
                <?php foreach ($types as $type): ?>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3 type-item">
                        <a class="type-card"
                           href="dossier.php?type_id=<?= (int) $type['id'] ?>">
                            <div class="type-icon">🗂️</div>
                            <h3><?= htmlspecialchars($type['nom'], ENT_QUOTES, 'UTF-8') ?></h3>

                            <div class="description">
                                <?= !empty($type['description'])
                                    ? nl2br(htmlspecialchars($type['description'], ENT_QUOTES, 'UTF-8'))
                                    : 'Tantano ireo antontan-taratasy amin’ity sokajy ity.' ?>
                            </div>

                            <div class="type-footer">
                                <span class="count">
                                    <strong><?= (int) $type['nombre_dossiers'] ?></strong>
                                    dossier(s)
                                </span>
                                <span class="open-link">Hijery →</span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="emptySearch" class="empty mt-3" style="display:none">
                Tsy misy karazana mifanaraka amin'ny fikarohana.
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
const typeSearch = document.getElementById('typeSearch');

if (typeSearch) {
    typeSearch.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        const items = document.querySelectorAll('.type-item');
        let visible = 0;

        items.forEach(function (item) {
            const match = item.textContent.toLowerCase().includes(query);
            item.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        const empty = document.getElementById('emptySearch');
        if (empty) {
            empty.style.display = visible === 0 ? 'block' : 'none';
        }
    });
}
</script>

</body>
</html>
```
