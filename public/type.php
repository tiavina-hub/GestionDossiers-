<?php

require_once __DIR__ . '/../config/database.php';

$type_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$type_id) {
    die("Type invalide.");
}

$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.nom AS type_nom,
        t.description AS type_description,
        r.id AS region_id,
        r.nom AS region_nom
    FROM types_dossiers t
    JOIN regions r ON t.region_id = r.id
    WHERE t.id = ?
");

$stmt->execute([$type_id]);
$type = $stmt->fetch();

if (!$type) {
    die("Type introuvable.");
}

$stmt = $pdo->prepare("
    SELECT
        id,
        reference,
        titre,
        description,
        proprietaire,
        localisation,
        superficie,
        statut,
        date_dossier
    FROM dossiers
    WHERE type_id = ?
    ORDER BY titre ASC
");

$stmt->execute([$type_id]);
$dossiers = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($type['type_nom']) ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <a
        href="region.php?id=<?= $type['region_id'] ?>"
        class="btn btn-secondary mb-4"
    >
        ← Retour
    </a>

    <p class="text-muted mb-1">
        📍 <?= htmlspecialchars($type['region_nom']) ?>
    </p>

    <h1>
        📂 <?= htmlspecialchars($type['type_nom']) ?>
    </h1>

    <?php if (!empty($type['type_description'])): ?>

        <p class="text-muted">
            <?= htmlspecialchars($type['type_description']) ?>
        </p>

    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h3>📁 Dossiers</h3>

        <a
            href="nouveau_dossier.php?type_id=<?= $type['id'] ?>"
            class="btn btn-success"
        >
            + Nouveau dossier
        </a>

    </div>

    <?php if (empty($dossiers)): ?>

        <div class="alert alert-info">
            Aucun dossier n'est encore enregistré.
        </div>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table table-bordered table-hover bg-white">

                <thead class="table-dark">

                    <tr>
                        <th>Référence</th>
                        <th>Dossier</th>
                        <th>Propriétaire</th>
                        <th>Localisation</th>
                        <th>Superficie</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($dossiers as $dossier): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($dossier['reference'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($dossier['titre']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($dossier['proprietaire'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($dossier['localisation'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($dossier['superficie'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($dossier['statut']) ?>
                            </td>

                            <td>

                                <a
                                    href="dossier.php?id=<?= $dossier['id'] ?>"
                                    class="btn btn-sm btn-primary"
                                >
                                    Ouvrir
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

</body>
</html>