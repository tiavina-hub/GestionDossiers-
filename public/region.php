<?php

require_once __DIR__ . '/../config/database.php';

$region_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$region_id) {
    die("Région invalide.");
}

$stmt = $pdo->prepare("
    SELECT id, nom, description
    FROM regions
    WHERE id = ?
");

$stmt->execute([$region_id]);
$region = $stmt->fetch();

if (!$region) {
    die("Région introuvable.");
}

$stmt = $pdo->prepare("
    SELECT id, nom, description
    FROM types_dossiers
    WHERE region_id = ?
    ORDER BY nom ASC
");

$stmt->execute([$region_id]);
$types = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($region['nom']) ?></title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="mb-4">
        <a href="index.php" class="btn btn-secondary">
            ← Retour
        </a>
    </div>

    <div class="mb-5">
        <h1>📍 <?= htmlspecialchars($region['nom']) ?></h1>

        <?php if (!empty($region['description'])): ?>
            <p class="text-muted">
                <?= htmlspecialchars($region['description']) ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>📂 Types de dossiers</h3>

        <button class="btn btn-success">
            + Ajouter un type
        </button>
    </div>

    <div class="row g-4">

        <?php if (empty($types)): ?>

            <div class="col-12">
                <div class="alert alert-info">
                    Aucun type de dossier n'est encore enregistré
                    dans cette région.
                </div>
            </div>

        <?php else: ?>

            <?php foreach ($types as $type): ?>

                <div class="col-md-4">

                    <div class="card shadow-sm h-100">

                        <div class="card-body">

                            <h4>
                                📂 <?= htmlspecialchars($type['nom']) ?>
                            </h4>

                            <?php if (!empty($type['description'])): ?>
                                <p class="text-muted">
                                    <?= htmlspecialchars($type['description']) ?>
                                </p>
                            <?php endif; ?>

<a
    href="type.php?id=<?= $type['id'] ?>"
    class="btn btn-primary"
>
    Ouvrir
</a>

                        </div>

                    </div>

                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>

