<?php

require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("
    SELECT id, nom, description
    FROM regions
    ORDER BY nom ASC
");

$regions = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion des dossiers</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="text-center mb-5">
        <h1>📁 Gestion des dossiers</h1>
        <p class="text-muted">
            Sélectionnez une région pour consulter ses dossiers.
        </p>
    </div>
<div class="text-center mb-4">
    <a
        href="recherche.php"
        class="btn btn-primary btn-lg"
    >
        🔎 Rechercher un dossier
    </a>
    <a
    href="regions.php"
    class="btn btn-outline-secondary btn-lg"
>
    ⚙️ Gérer les régions
</a>
</div>

    <div class="row g-4">

        <?php foreach ($regions as $region): ?>

            <div class="col-md-4">

                <div class="card shadow-sm h-100">

                    <div class="card-body">

                        <h4 class="card-title">
                            📍 <?= htmlspecialchars($region['nom']) ?>
                        </h4>

                        <?php if (!empty($region['description'])): ?>
                            <p class="text-muted">
                                <?= htmlspecialchars($region['description']) ?>
                            </p>
                        <?php endif; ?>
                         <a
    href="region.php?id=<?= $region['id'] ?>"
    class="btn btn-primary"
>
    Ouvrir la région
</a> 

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>

</body>
</html>
