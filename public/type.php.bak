<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

require_once __DIR__ . '/../config/database.php';

$fitandremana_id = (int)($_GET['fitandremana_id'] ?? 0);

if ($fitandremana_id <= 0) {
    header('Location: index.php');
    exit;
}

/* Récupérer le Fitandremana */
$stmt = $pdo->prepare("
    SELECT 
        f.id,
        f.nom,
        sp.id AS sp_id,
        sp.numero_officiel,
        sp.numero,
        sp.nom AS sp_nom
    FROM fitandremana f
    JOIN synodam_paritany sp ON sp.id = f.sp_id
    WHERE f.id = ?
");
$stmt->execute([$fitandremana_id]);
$fitandremana = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fitandremana) {
    die("Fitandremana tsy hita.");
}

/* Récupérer les types */
$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.nom,
        t.description,
        COUNT(d.id) AS nombre_dossiers
    FROM types_dossiers t
    LEFT JOIN dossiers d ON d.type_id = t.id
    WHERE t.fitandremana_id = ?
    GROUP BY t.id
    ORDER BY t.nom
");
$stmt->execute([$fitandremana_id]);
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="mg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karazana antontan-taratasy</title>

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
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .breadcrumb {
            margin-bottom: 25px;
            color: #555;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
        }

        .info {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .info h2 {
            margin-top: 0;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            text-decoration: none;
            color: #222;
            transition: .2s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,.15);
        }

        .card h3 {
            margin-top: 0;
            color: #1d4ed8;
        }

        .count {
            margin-top: 15px;
            color: #666;
        }

        .empty {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
        }
    </style>
</head>

<body>

<header>
    <h1>Fitantanana antontan-taratasy FJKM</h1>
    <div>
        SP <?= htmlspecialchars($fitandremana['numero_officiel']) ?>
        — <?= htmlspecialchars($fitandremana['sp_nom']) ?>
    </div>
</header>

<div class="container">

    <div class="breadcrumb">
        <a href="index.php">SP</a>
        →
        <a href="sp.php?numero=<?= urlencode($fitandremana['numero_officiel']) ?>">
            SP <?= htmlspecialchars($fitandremana['numero_officiel']) ?>
        </a>
        →
        <a href="fitandremana.php?sp_id=<?= $fitandremana['sp_id'] ?>">
            Fitandremana
        </a>
        →
        Karazana
    </div>

    <div class="info">
        <h2>
            <?= htmlspecialchars($fitandremana['nom']) ?>
        </h2>

        <p>
            Safidio ny karazana antontan-taratasy tianao tantanana.
        </p>
    </div>

    <?php if (empty($types)): ?>

        <div class="empty">
            <h3>Tsy mbola misy karazana antontan-taratasy.</h3>
            <p>
                Afaka mamorona karazana toy ny <strong>TANY</strong>,
                <strong>TRANO</strong>, sns.
            </p>
        </div>

    <?php else: ?>

        <div class="grid">

            <?php foreach ($types as $type): ?>

                <a class="card"
                   href="dossier.php?type_id=<?= $type['id'] ?>">

                    <h3>
                        <?= htmlspecialchars($type['nom']) ?>
                    </h3>

                    <?php if (!empty($type['description'])): ?>
                        <p>
                            <?= htmlspecialchars($type['description']) ?>
                        </p>
                    <?php endif; ?>

                    <div class="count">
                        <?= (int)$type['nombre_dossiers'] ?> dossier(s)
                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>