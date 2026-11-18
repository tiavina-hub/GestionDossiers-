<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$categorieId = (int)($_GET['categorie_id'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));
$searchResults = [];

if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare("
        SELECT DISTINCT d.id, d.titre, d.description, d.statut,
               d.date_dossier, ft.nom AS type_nom,
               fc.nom AS categorie_nom
        FROM dossiers d
        LEFT JOIN foibe_types_dossiers ft ON ft.id = d.foibe_type_id
        LEFT JOIN foibe_categories fc ON fc.id = ft.categorie_id
        LEFT JOIN documents doc ON doc.dossier_id = d.id
        WHERE d.foibe_type_id IS NOT NULL
          AND (
              d.titre LIKE ? OR d.description LIKE ?
              OR d.laharana_fitandremana LIKE ?
              OR d.tomponny_tany LIKE ? OR d.fokontany LIKE ?
              OR d.kaominina LIKE ? OR d.distrika LIKE ?
              OR d.faritra LIKE ? OR d.karazan_tany LIKE ?
              OR d.statut LIKE ? OR ft.nom LIKE ?
              OR ft.description LIKE ? OR fc.nom LIKE ?
              OR fc.description LIKE ? OR doc.nom_original LIKE ?
              OR doc.intitule LIKE ? OR doc.description LIKE ?
              OR doc.categorie_document LIKE ?
          )
        ORDER BY COALESCE(d.date_dossier, DATE(d.date_creation)) DESC,
                 d.id DESC
        LIMIT 300
    ");
    $stmt->execute(array_fill(0, 18, $like));
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$categories = $pdo->query(
    "SELECT id, nom, description FROM foibe_categories ORDER BY id"
)->fetchAll(PDO::FETCH_ASSOC);

if ($categorieId > 0) {
    $stmt = $pdo->prepare(
        "SELECT id, nom, description
         FROM foibe_categories WHERE id = ?"
    );
    $stmt->execute([$categorieId]);
    $categorie = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$categorie) {
        http_response_code(404);
        exit('Categorie Foibe tsy hita.');
    }

    $stmt = $pdo->prepare(
        "SELECT t.id, t.nom, t.description,
                COUNT(d.id) AS total
         FROM foibe_types_dossiers t
         LEFT JOIN dossiers d ON d.foibe_type_id = t.id
         WHERE t.categorie_id = ?
         GROUP BY t.id, t.nom, t.description
         ORDER BY t.nom"
    );
    $stmt->execute([$categorieId]);
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $categorie = null;
    $types = [];
}

$nbDossiers = (int)$pdo->query(
    "SELECT COUNT(*) FROM dossiers WHERE foibe_type_id IS NOT NULL"
)->fetchColumn();

$nbTypes = (int)$pdo->query(
    "SELECT COUNT(*) FROM foibe_types_dossiers"
)->fetchColumn();
?>
<!DOCTYPE html>
<html lang="mg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Foibe | Fitantanana FJKM</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:#f5f6f1;color:#26352d}
header{background:#285744;color:white;padding:25px 5%}
main{max-width:1150px;margin:28px auto;padding:0 18px}
a{text-decoration:none;color:#285744}
h1{margin:0 0 8px}
.muted{color:#68766d}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:17px}
.card,.panel{background:white;border:1px solid #e0e5dd;border-radius:14px;padding:21px}
.card{display:block;transition:.15s}
.card:hover{transform:translateY(-2px);box-shadow:0 6px 20px #0000000b}
.card h2{font-size:19px;margin:0 0 10px}
.count{font-size:14px;color:#68766d;margin-top:12px}
.btn{display:inline-block;padding:11px 15px;border-radius:8px;background:#285744;color:white;font-weight:bold;border:0;cursor:pointer}
.btn-light{background:#e7efe8;color:#285744}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin:18px 0}
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:12px;border-bottom:1px solid #e5e9e2}
th{background:#f3f6f0}
.empty{padding:22px;background:white;border-radius:10px}
.search-box{background:white;border:1px solid #e0e5dd;border-radius:14px;padding:18px;margin:18px 0 25px}
.search-row{display:flex;gap:10px;margin-top:10px;flex-wrap:wrap}
.search-row input{flex:1;min-width:220px;padding:12px;border:1px solid #ccd6cc;border-radius:8px;font-size:15px}
@media(max-width:600px){table{font-size:14px}th,td{padding:8px}}

/* DESIGN FJKM NAVY GOLD FOIBE */

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

body {
    background: var(--cream);
    color: var(--text);
    font-family: "DM Sans", Arial, sans-serif;
    min-height: 100vh;
}

header {
    background: linear-gradient(115deg, #101b32, #192944 70%, #29344a);
    color: white;
    padding: 42px max(5%, calc((100% - 1200px) / 2));
    box-shadow: 0 12px 35px rgba(16,27,50,.12);
}

header h1 {
    color: white;
    font-family: "Playfair Display", Georgia, serif;
    font-size: clamp(27px, 4vw, 39px);
    font-weight: 700;
    line-height: 1.3;
}

header p {
    color: var(--gold-light);
    margin: 12px 0 0;
    font-size: 15px;
}

main {
    max-width: 1250px;
    margin: 35px auto 70px;
    padding: 0 24px;
}

h2, h3 {
    color: var(--navy);
    font-family: "Playfair Display", Georgia, serif;
    font-weight: 700;
}

h2 {
    font-size: 25px;
    margin: 25px 0 12px;
}

h3 {
    font-size: 21px;
}

a {
    color: var(--navy-light);
    text-decoration: none;
}

a:hover {
    color: #98763d;
}

.muted {
    color: var(--muted);
    line-height: 1.6;
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 22px;
}

.card, .panel, .search-box, .empty {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(16,27,50,.045);
}

.card {
    display: block;
    padding: 25px;
    min-height: 170px;
    transition: transform .2s, box-shadow .2s, border-color .2s;
}

.card:hover {
    transform: translateY(-5px);
    border-color: var(--gold);
    box-shadow: 0 18px 38px rgba(16,27,50,.09);
}

.card h2 {
    font-size: 22px;
    margin: 0 0 13px;
}

.card p {
    color: var(--muted);
    line-height: 1.6;
}

.count {
    display: inline-block;
    color: #a28a5d;
    font-size: 13px;
    font-weight: 800;
    margin-top: 12px;
}

.panel {
    padding: 25px;
    margin: 20px 0;
}

.actions {
    display: flex;
    gap: 11px;
    flex-wrap: wrap;
    margin: 0 0 25px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 11px 17px;
    border-radius: 9px;
    background: var(--navy);
    color: white;
    font-weight: 700;
    border: 1px solid var(--navy);
    cursor: pointer;
    transition: .2s;
}

.btn:hover {
    background: var(--navy-light);
    border-color: var(--navy-light);
    color: white;
}

.btn-light {
    background: #fffefa;
    color: var(--navy);
    border-color: var(--gold);
}

.btn-light:hover {
    background: var(--gold);
    color: var(--navy);
    border-color: var(--gold);
}

.search-box {
    padding: 25px;
    margin: 20px 0 30px;
}

.search-box label {
    color: var(--navy);
    font-size: 15px;
}

.search-row {
    display: flex;
    gap: 11px;
    margin-top: 13px;
    flex-wrap: wrap;
}

.search-row input {
    flex: 1;
    min-width: 220px;
    padding: 13px 15px;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 15px;
    background: white;
    color: var(--text);
    outline: none;
}

.search-row input:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,164,92,.14);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 550px;
}

th, td {
    text-align: left;
    padding: 14px 12px;
    border-bottom: 1px solid var(--border);
}

th {
    background: #f2efe7;
    color: var(--navy);
    font-weight: 800;
    white-space: nowrap;
}

td {
    color: var(--text);
}

tbody tr:hover {
    background: #faf8f2;
}

td a {
    color: #98763d;
    font-weight: 800;
}

.empty {
    padding: 25px;
    color: var(--muted);
}

@media(max-width: 650px) {
    header {
        padding: 30px 22px;
    }

    main {
        margin: 22px auto 45px;
        padding: 0 15px;
    }

    .actions {
        gap: 8px;
    }

    .btn {
        padding: 10px 12px;
        font-size: 13px;
    }

    .panel, .search-box {
        padding: 18px;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    table {
        font-size: 13px;
    }
}

</style>
</head>
<body>
<header>
    <h1>Foibe — Fitantanana antontan-taratasy</h1>
    <p>Fitantanana misaraka amin'ny dossiers SP.</p>
</header>
<main>
    <div class="actions">
        <a class="btn btn-light" href="index.php">← Fandraisana</a>
        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <a class="btn" href="gestion_types_foibe.php">⚙️ Gestion des types</a>
        <?php endif; ?>
        <?php if ($categorie): ?>
            <a class="btn btn-light" href="foibe.php">Ireo sokajy rehetra</a>
            <a class="btn btn-light" href="export_foibe_pdf.php?categorie_id=<?= (int)$categorie['id'] ?>">📄 Exporter en PDF</a>
            <a class="btn" href="nouveau_dossier_foibe.php?categorie_id=<?= (int)$categorie['id'] ?>">+ Dossier vaovao</a>
        <?php endif; ?>
    </div>

    <form method="get" action="foibe.php" class="search-box">
        <label for="q"><strong>🔎 Karohy dossier Foibe</strong></label>
        <div class="search-row">
            <input type="search" id="q" name="q"
                   value="<?= e($q) ?>"
                   placeholder="Titre, tompon-tany, fokontany, sokajy, karazana, document...">
            <button class="btn" type="submit">Karohy</button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-light" href="foibe.php">Averina</a>
            <?php endif; ?>
        </div>
        <p class="muted">Afaka mitady amin'ny teny na laharana rehetra mifandray amin'ny dossier ianao.</p>
    </form>

    <?php if ($q !== ''): ?>
        <h2>Valin'ny fikarohana: "<?= e($q) ?>"</h2>
        <p class="muted"><?= count($searchResults) ?> dossier hita</p>

        <?php if ($searchResults): ?>
            <div class="panel" style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Sokajy</th>
                            <th>Karazana</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($searchResults as $d): ?>
                        <tr>
                            <td><?= e($d['titre']) ?></td>
                            <td><?= e($d['categorie_nom']) ?></td>
                            <td><?= e($d['type_nom']) ?></td>
                            <td><?= e($d['statut']) ?></td>
                            <td><?= e($d['date_dossier']) ?></td>
                            <td>
                                <a href="voir_dossier.php?id=<?= (int)$d['id'] ?>">Hijery</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">
                Tsy nahitana dossier mifanaraka amin'io fikarohana io.
            </div>
        <?php endif; ?>

    <?php elseif (!$categorie): ?>
        <h2>Ireo sokajy Foibe</h2>
        <p class="muted">
            <?= count($categories) ?> sokajy ·
            <?= $nbTypes ?> karazana ·
            <?= $nbDossiers ?> dossier
        </p>
        <section class="grid">
        <?php foreach ($categories as $c): ?>
            <a class="card" href="foibe.php?categorie_id=<?= (int)$c['id'] ?>">
                <h2><?= e($c['nom']) ?></h2>
                <p><?= e($c['description']) ?></p>
                <span class="count">Hijery ny karazana sy dossiers →</span>
            </a>
        <?php endforeach; ?>
        </section>
    <?php else: ?>
        <h2><?= e($categorie['nom']) ?></h2>
        <p class="muted"><?= e($categorie['description']) ?></p>

        <?php if (!$types): ?>
            <div class="empty">
                Tsy mbola misy karazana dossier ao amin'ity sokajy ity.
                <p>Ilaina aloha ny mamorona karazana dossier.</p>
            </div>
        <?php else: ?>
            <?php foreach ($types as $t): ?>
                <section class="panel" style="margin:15px 0">
                    <h3><?= e($t['nom']) ?></h3>
                    <p class="muted"><?= e($t['description']) ?></p>
                    <p>Dossiers: <?= (int)$t['total'] ?></p>
                    <?php
                    $stmt = $pdo->prepare(
                        "SELECT id,titre,statut,date_dossier
                         FROM dossiers
                         WHERE foibe_type_id = ?
                         ORDER BY date_modification DESC,id DESC"
                    );
                    $stmt->execute([(int)$t['id']]);
                    $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if ($dossiers): ?>
                        <div style="overflow-x:auto">
                        <table>
                            <thead><tr>
                                <th>Titre</th><th>Statut</th><th>Date</th><th>Action</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($dossiers as $d): ?>
                                <tr>
                                    <td><?= e($d['titre']) ?></td>
                                    <td><?= e($d['statut']) ?></td>
                                    <td><?= e($d['date_dossier']) ?></td>
                                    <td><a href="voir_dossier.php?id=<?= (int)$d['id'] ?>">Hijery</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php else: ?>
                        <p class="muted">Tsy mbola misy dossier.</p>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>
