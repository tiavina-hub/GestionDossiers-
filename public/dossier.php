<?php

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("Dossier invalide.");
}

$stmt = $pdo->prepare("
    SELECT
        d.*,
        t.nom AS type_nom,
        r.id AS region_id,
        r.nom AS region_nom
    FROM dossiers d
    JOIN types_dossiers t ON d.type_id = t.id
    JOIN regions r ON t.region_id = r.id
    WHERE d.id = ?
");

$stmt->execute([$id]);

$dossier = $stmt->fetch();

if (!$dossier) {
    die("Dossier introuvable.");
}

$stmt = $pdo->prepare("
    SELECT *
    FROM documents
    WHERE dossier_id = ?
    ORDER BY date_ajout DESC
");

$stmt->execute([$id]);

$documents = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($dossier['titre']) ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <a
        href="type.php?id=<?= $dossier['type_id'] ?>"
        class="btn btn-secondary mb-4"
    >
        ← Retour
    </a>

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <p class="text-muted">
                📍 <?= htmlspecialchars($dossier['region_nom']) ?>
                →
                📂 <?= htmlspecialchars($dossier['type_nom']) ?>
            </p>

            <h1>
                📁 <?= htmlspecialchars($dossier['titre']) ?>
            </h1>

            <hr>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <strong>Référence :</strong><br>
                    <?= htmlspecialchars($dossier['reference'] ?? '') ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Propriétaire :</strong><br>
                    <?= htmlspecialchars($dossier['proprietaire'] ?? '') ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Localisation :</strong><br>
                    <?= htmlspecialchars($dossier['localisation'] ?? '') ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Superficie :</strong><br>
                    <?= htmlspecialchars($dossier['superficie'] ?? '') ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Statut :</strong><br>
                    <?= htmlspecialchars($dossier['statut']) ?>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>Date :</strong><br>
                    <?= htmlspecialchars($dossier['date_dossier'] ?? '') ?>
                </div>

                <div class="col-12">
                    <strong>Description :</strong>

                    <p class="mt-2">
                        <?= nl2br(htmlspecialchars($dossier['description'] ?? '')) ?>
                    </p>
                </div>

            </div>

        </div>

    </div>

    <div class="card shadow-sm">

        <div class="card-header">

            <h4 class="mb-0">
                📎 Documents
            </h4>

        </div>

        <div class="card-body">

            <?php if (empty($documents)): ?>

                <p class="text-muted">
                    Aucun document attaché à ce dossier.
                </p>

            <?php else: ?>

                <ul class="list-group">

                    <?php foreach ($documents as $document): ?>

                        <li class="list-group-item">

                            📄
                            <?= htmlspecialchars($document['nom_original']) ?>

                            <span class="text-muted">
                                (<?= round($document['taille'] / 1024, 1) ?> Ko)
                            </span>

                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>
</html>
