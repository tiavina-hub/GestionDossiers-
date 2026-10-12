<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

require_once __DIR__ . '/../config/database.php';

$type_id = (int)($_GET['type_id'] ?? 0);

if ($type_id <= 0) {
    header('Location: index.php');
    exit;
}

/* Type + Fitandremana + SP */
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
    die("Karazana antontan-taratasy tsy hita.");
}

/* Liste des dossiers */
$stmt = $pdo->prepare("
    SELECT *
    FROM dossiers
    WHERE type_id = ?
    ORDER BY date_modification DESC, id DESC
");
$stmt->execute([$type_id]);
$dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="mg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dossiers TANY</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    color: #222;
}

header {
    background: #1f2937;
    color: white;
    padding: 20px 30px;
}

header h1 {
    margin: 0 0 8px;
}

.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px;
}

.breadcrumb {
    margin-bottom: 25px;
}

.breadcrumb a {
    color: #2563eb;
    text-decoration: none;
}

.info {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.actions {
    margin-bottom: 20px;
}

.btn {
    display: inline-block;
    padding: 11px 18px;
    border-radius: 8px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 15px;
}

.btn-primary {
    background: #2563eb;
    color: white;
}

.btn-primary:hover {
    background: #1d4ed8;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

th, td {
    padding: 14px;
    border-bottom: 1px solid #eee;
    text-align: left;
}

th {
    background: #f1f5f9;
}

.badge {
    padding: 5px 10px;
    border-radius: 20px;
    background: #e5e7eb;
}

.empty {
    background: white;
    padding: 35px;
    border-radius: 12px;
    text-align: center;
}
</style>
</head>

<body>

<header>
    <h1>Fitantanana ny antontan-taratasy FJKM</h1>

    <div>
        SP <?= htmlspecialchars($info['numero_officiel']) ?>
        — <?= htmlspecialchars($info['sp_nom']) ?>
    </div>
</header>

<div class="container">

    <div class="breadcrumb">
        <a href="index.php">SP</a>
        →
        <a href="sp.php?numero=<?= urlencode($info['numero_officiel']) ?>">
            SP <?= htmlspecialchars($info['numero_officiel']) ?>
        </a>
        →
        <a href="fitandremana.php?sp_id=<?= $info['sp_id'] ?>">
            Fitandremana
        </a>
        →
        <a href="type.php?fitandremana_id=<?= $info['fitandremana_id'] ?>">
            <?= htmlspecialchars($info['fitandremana_nom']) ?>
        </a>
        →
        TANY
    </div>

    <div class="info">
        <h2><?= htmlspecialchars($info['fitandremana_nom']) ?></h2>

        <p>
            <strong>Karazana :</strong>
            <?= htmlspecialchars($info['type_nom']) ?>
        </p>

        <p>
            <strong>Isan'ny dossier :</strong>
            <?= count($dossiers) ?>
        </p>
    </div>

    <div class="actions">
        <a class="btn btn-primary"
           href="nouveau_dossier.php?type_id=<?= $type_id ?>">
            + Hanampy dossier TANY
        </a>
    </div>

    <?php if (empty($dossiers)): ?>

        <div class="empty">
            <h3>Tsy mbola misy dossier TANY.</h3>
            <p>
                Tsindrio ny bokotra etsy ambony mba hanampiana dossier vaovao.
            </p>
        </div>

    <?php else: ?>

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

            <tbody>

            <?php foreach ($dossiers as $dossier): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($dossier['laharana_fitandremana'] ?? '') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($dossier['tomponny_tany'] ?? '') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($dossier['fokontany'] ?? '') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($dossier['kaominina'] ?? '') ?>
                    </td>

                    <td>
                        <span class="badge">
                            <?= htmlspecialchars($dossier['karazan_tany'] ?? '') ?>
                        </span>
                    </td>

                    <td>
                        <?= htmlspecialchars($dossier['date_creation'] ?? '') ?>
                    </td>

                    <td>
                        <a href="voir_dossier.php?id=<?= $dossier['id'] ?>">
                            Voir
                        </a>
                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    <?php endif; ?>

</div>

</body>
</html>