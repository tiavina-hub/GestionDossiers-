<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

$type_id = (int)($_GET['type_id'] ?? 0);

if ($type_id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        t.id AS type_id,
        t.nom AS type_nom,
        f.id AS fitandremana_id,
        f.nom AS fitandremana_nom,
        sp.id AS sp_id,
        sp.numero_officiel,
        sp.nom AS sp_nom
    FROM types_dossiers t
    JOIN fitandremana f ON f.id = t.fitandremana_id
    JOIN synodam_paritany sp ON sp.id = f.sp_id
    WHERE t.id = ?
");
$stmt->execute([$type_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    http_response_code(404);
    exit("Karazana antontan-taratasy tsy hita.");
}

$stmt = $pdo->prepare("
    SELECT *
    FROM dossiers
    WHERE type_id = ?
    ORDER BY date_modification DESC, id DESC
");
$stmt->execute([$type_id]);
$dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($dossiers);
?>
<!DOCTYPE html>
<html lang="mg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dossiers TANY | Fitantanana</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:#f3f6fb;color:#172033}
a{text-decoration:none}
header{background:linear-gradient(120deg,#142b4a,#1769aa);color:white;padding:26px 5%}
header h1{margin:0 0 10px;font-size:25px}
header p{margin:0;color:#e0edff}
.container{max-width:1250px;margin:25px auto;padding:0 20px}
.breadcrumb{font-size:14px;color:#64748b;margin-bottom:22px;line-height:2}
.breadcrumb a{color:#1769aa;font-weight:600}
.hero{background:white;border-radius:16px;padding:24px;margin-bottom:22px;box-shadow:0 5px 20px #1720330b}
.hero h2{margin:0 0 12px;font-size:23px}
.hero p{margin:7px 0;color:#64748b}
.stats{display:flex;gap:15px;flex-wrap:wrap;margin-top:20px}
.stat{background:#edf5ff;border-radius:12px;padding:15px 20px;min-width:150px}
.stat small{display:block;color:#64748b;margin-bottom:6px}
.stat strong{font-size:23px;color:#1769aa}
.actions{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:22px 0}
.btn{display:inline-block;border:0;border-radius:9px;padding:12px 17px;font-weight:600;font-size:14px;cursor:pointer}
.btn-primary{background:#1769aa;color:white}
.btn-primary:hover{background:#12548a}
.btn-secondary{background:white;color:#1769aa;border:1px solid #d8e3f0}
.search{width:100%;max-width:350px;padding:12px 14px;border:1px solid #d8e0eb;border-radius:9px;font-size:14px}
.table-wrap{background:white;border-radius:14px;overflow:auto;box-shadow:0 5px 20px #1720330b}
table{width:100%;border-collapse:collapse;min-width:850px}
th,td{padding:15px;text-align:left;border-bottom:1px solid #edf0f5;font-size:14px}
th{background:#f7f9fc;color:#475569;font-size:12px;text-transform:uppercase;letter-spacing:.3px}
tbody tr:hover{background:#f8fbff}
.badge{display:inline-block;padding:5px 10px;border-radius:20px;background:#e5f0ff;color:#1769aa;font-weight:600}
.link{color:#1769aa;font-weight:600}
.empty{background:white;text-align:center;padding:45px 20px;border-radius:14px;color:#64748b}
.empty h3{color:#172033}
@media(max-width:600px){header{padding:22px 20px}header h1{font-size:21px}.container{padding:0 12px}.hero{padding:18px}.actions{align-items:stretch}.search{max-width:none}.btn{ text-align:center}}
</style>
</head>
<body>
<header>
    <h1>Fitantanana ny antontan-taratasy FJKM</h1>
    <p>SP <?= htmlspecialchars((string)$info['numero_officiel']) ?>
       — <?= htmlspecialchars($info['sp_nom'] ?? '') ?></p>
</header>

<main class="container">
    <nav class="breadcrumb">
        <a href="index.php">SP</a> →
        <a href="sp.php?numero=<?= urlencode((string)$info['numero_officiel']) ?>">
            SP <?= htmlspecialchars((string)$info['numero_officiel']) ?>
        </a> →
        <a href="fitandremana.php?sp_id=<?= (int)$info['sp_id'] ?>">Fitandremana</a> →
        <a href="type.php?fitandremana_id=<?= (int)$info['fitandremana_id'] ?>">
            <?= htmlspecialchars($info['fitandremana_nom']) ?>
        </a> → Dossiers
    </nav>

    <section class="hero">
        <h2><?= htmlspecialchars($info['fitandremana_nom']) ?></h2>
        <p><strong>Karazana :</strong> <?= htmlspecialchars($info['type_nom']) ?></p>
        <p>Lisitra sy fitantanana ireo dossiers mifandraika amin'ity karazana ity.</p>
        <div class="stats">
            <div class="stat">
                <small>Isan'ny dossiers</small>
                <strong><?= $total ?></strong>
            </div>
            <div class="stat">
                <small>Karazana dossier</small>
                <strong><?= htmlspecialchars($info['type_nom']) ?></strong>
            </div>
        </div>
    </section>

    <div class="actions">
        <a class="btn btn-primary" href="nouveau_dossier.php?type_id=<?= $type_id ?>">
            + Hanampy dossier TANY
        </a>
        <input class="search" id="search" type="search"
               placeholder="🔎 Karohy laharana, tompony, fokontany...">
    </div>

    <?php if (!$dossiers): ?>
        <section class="empty">
            <h3>Tsy mbola misy dossier TANY.</h3>
            <p>Tsindrio ny bokotra etsy ambony mba hanampiana dossier vaovao.</p>
        </section>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Laharana</th>
                        <th>Tompon'ny tany</th>
                        <th>Fokontany</th>
                        <th>Kaominina</th>
                        <th>Karazan-tany</th>
                        <th>Daty</th>
                        <th>Hetsika</th>
                    </tr>
                </thead>
                <tbody id="dossierRows">
                <?php foreach ($dossiers as $dossier): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($dossier['laharana_fitandremana'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($dossier['tomponny_tany'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($dossier['fokontany'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($dossier['kaominina'] ?? '')) ?></td>
                        <td><span class="badge"><?= htmlspecialchars((string)($dossier['karazan_tany'] ?? '')) ?></span></td>
                        <td><?= htmlspecialchars((string)($dossier['date_creation'] ?? '')) ?></td>
                        <td><a class="link" href="voir_dossier.php?id=<?= (int)$dossier['id'] ?>">Voir →</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p id="noResult" style="display:none;text-align:center;color:#64748b;padding:20px">
            Aucun dossier ne correspond à cette recherche.
        </p>
    <?php endif; ?>
</main>

<script>
const search = document.getElementById('search');
if (search) {
    search.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        let visible = 0;

        document.querySelectorAll('#dossierRows tr').forEach(function (row) {
            const match = row.textContent.toLowerCase().includes(query);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        const noResult = document.getElementById('noResult');
        if (noResult) noResult.style.display = visible === 0 ? 'block' : 'none';
    });
}
</script>
</body>
</html>

