<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$categorieId = (int)($_GET['categorie_id'] ?? 0);

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
@media(max-width:600px){table{font-size:14px}th,td{padding:8px}}
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

    <?php if (!$categorie): ?>
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
