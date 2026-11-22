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
/* DESIGN FJKM NAVY GOLD TYPE */

:root {
    --navy: #101b32;
    --navy-light: #293d5d;
    --gold: #c9a45c;
    --gold-light: #e6d1a0;
    --cream: #f6f4ee;
    --white: #fffefa;
    --text: #202b3d;
    --muted: #778091;
    --border: #e8e5dc;
}

* { box-sizing: border-box; }

body {
    margin: 0;
    background: var(--cream);
    color: var(--text);
    font-family: "DM Sans", "Segoe UI", Arial, sans-serif;
    min-height: 100vh;
}

.topbar {
    background: var(--navy);
    border-bottom: 3px solid var(--gold);
    padding: 16px 0;
    box-shadow: 0 8px 25px rgba(16,27,50,.12);
}

.topbar .fw-bold {
    color: white;
    letter-spacing: .3px;
}

.topbar .text-secondary {
    color: var(--gold-light) !important;
}

.brand-mark {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 13px;
    background: var(--gold);
    color: var(--navy);
    font-size: 22px;
    font-weight: 800;
}

.topbar .btn-outline-danger {
    color: var(--gold-light);
    border-color: var(--gold);
    border-radius: 9px;
    font-weight: 700;
}

.topbar .btn-outline-danger:hover {
    background: var(--gold);
    color: var(--navy);
    border-color: var(--gold);
}

.main {
    max-width: 1300px;
    margin: auto;
    padding: 32px 24px 55px;
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
    color: #98763d;
    text-decoration: none;
    font-weight: 700;
}

.breadcrumb a:hover {
    color: var(--navy);
    text-decoration: underline;
}

.hero {
    background: linear-gradient(115deg, #101b32, #192944 70%, #29344a);
    color: white;
    border: 1px solid rgba(201,164,92,.35);
    border-radius: 22px;
    padding: 35px;
    margin-bottom: 30px;
    box-shadow: 0 18px 42px rgba(16,27,50,.14);
}

.hero h1 {
    color: white;
    font-family: "Playfair Display", Georgia, serif;
    font-size: clamp(26px, 3vw, 36px);
    font-weight: 700;
    overflow-wrap: anywhere;
    margin-bottom: 12px;
}

.hero p {
    color: var(--gold-light);
    margin-bottom: 0;
}

.hero .mt-3 {
    color: white;
    font-size: 17px;
}

.stats {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 22px;
}

.stat {
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(230,209,160,.4);
    border-radius: 11px;
    padding: 11px 16px;
    color: var(--gold-light);
}

.stat strong {
    color: white;
}

.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin: 30px 0 22px;
}

.toolbar h2 {
    color: var(--navy);
    font-family: "Playfair Display", Georgia, serif;
    font-size: 25px;
    font-weight: 700;
    margin-bottom: 6px;
}

.toolbar p {
    color: var(--muted);
    margin: 0;
    font-size: 14px;
}

.search {
    width: 100%;
    max-width: 350px;
    border: 1px solid var(--border);
    border-radius: 11px;
    background: var(--white);
    color: var(--text);
    padding: 12px 15px;
    outline: none;
}

.search:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,164,92,.15);
}

.type-card {
    height: 100%;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 17px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 8px 25px rgba(16,27,50,.035);
    transition: transform .2s, box-shadow .2s, border-color .2s;
}

.type-card:hover {
    transform: translateY(-5px);
    border-color: var(--gold);
    box-shadow: 0 17px 38px rgba(16,27,50,.09);
    color: inherit;
}

.type-icon {
    width: 49px;
    height: 49px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f2ead8;
    color: var(--navy);
    border: 1px solid #e8d9b8;
    border-radius: 13px;
    font-size: 23px;
    margin-bottom: 19px;
}

.type-card h3 {
    color: var(--navy);
    font-family: "Playfair Display", Georgia, serif;
    font-size: 21px;
    font-weight: 700;
    overflow-wrap: anywhere;
    margin-bottom: 10px;
}

.description {
    font-size: 14px;
    color: var(--muted);
    line-height: 1.7;
    overflow-wrap: anywhere;
    margin-bottom: 22px;
}

.type-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    border-top: 1px solid var(--border);
    padding-top: 16px;
    margin-top: auto;
}

.count {
    font-size: 13px;
    color: var(--muted);
}

.count strong {
    color: #a1844d;
    font-size: 20px;
}

.open-link {
    color: #98763d;
    font-size: 13px;
    font-weight: 800;
}

.empty {
    background: var(--white);
    border: 1px dashed #d7c69f;
    border-radius: 17px;
    padding: 38px 22px;
    text-align: center;
    color: var(--muted);
}

.empty h3 {
    color: var(--navy);
    font-weight: 700;
}

@media (max-width: 576px) {
    .main { padding: 22px 14px 35px; }
    .hero { padding: 25px 21px; border-radius: 17px; }
    .type-card { padding: 20px; }
    .toolbar { align-items: stretch; }
    .search { max-width: 100%; }
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
