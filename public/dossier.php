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
    <?php if (isset($_GET['upload'])): ?>

    <?php if ((int) $_GET['upload'] > 0): ?>

        <div class="alert alert-success">
            ✅
            <?= (int) $_GET['upload'] ?>
            document(s) ajouté(s) avec succès.
        </div>

    <?php endif; ?>

<?php endif; ?>

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

            <div class="mb-4">

    <a
        href="modifier_dossier.php?id=<?= $dossier['id'] ?>"
        class="btn btn-warning"
    >
        ✏️ Modifier
    </a>

    <a
        href="supprimer_dossier.php?id=<?= $dossier['id'] ?>"
        class="btn btn-danger"
        onclick="return confirm(
            'Voulez-vous vraiment supprimer ce dossier ?'
        );"
    >
        🗑️ Supprimer
    </a>

</div>
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

    <div class="card shadow-sm mt-4">

    <div class="card-header">
        <h4 class="mb-0">
            📎 Documents du dossier
        </h4>
    </div>

    <div class="card-body">
        <form
    action="ajouter_document.php"
    method="POST"
    enctype="multipart/form-data"
    class="mb-4"
>

    <input
        type="hidden"
        name="dossier_id"
        value="<?= $dossier['id'] ?>"
    >

    <label class="form-label">
        Ajouter des documents
    </label>

    <div class="input-group">

        <input
            type="file"
            name="documents[]"
            class="form-control"
            multiple
            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx"
        >

        <button
            type="submit"
            class="btn btn-success"
        >
            📎 Ajouter
        </button>

    </div>

    <small class="text-muted">
        PDF, Word, Excel et images.
    </small>

</form>

        <?php if (empty($documents)): ?>

            <div class="alert alert-info mb-0">
                Aucun document n'est associé à ce dossier.
            </div>

        <?php else: ?>

            <div class="list-group">

                <?php foreach ($documents as $document): ?>

                    <div class="list-group-item">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <?php
                                $extension = strtolower(
                                    pathinfo(
                                        $document['nom_original'],
                                        PATHINFO_EXTENSION
                                    )
                                );

                                if ($extension === 'pdf') {
                                    echo '📕';
                                } elseif (
                                    in_array(
                                        $extension,
                                        ['jpg', 'jpeg', 'png'],
                                        true
                                    )
                                ) {
                                    echo '🖼️';
                                } elseif (
                                    in_array(
                                        $extension,
                                        ['doc', 'docx'],
                                        true
                                    )
                                ) {
                                    echo '📘';
                                } elseif (
                                    in_array(
                                        $extension,
                                        ['xls', 'xlsx'],
                                        true
                                    )
                                ) {
                                    echo '📊';
                                } else {
                                    echo '📄';
                                }
                                ?>

                                <strong>
                                    <?= htmlspecialchars(
                                        $document['nom_original']
                                    ) ?>
                                </strong>

                                <small class="text-muted ms-2">

                                    <?= round(
                                        ((int) $document['taille']) / 1024,
                                        1
                                    ) ?>
                                    Ko

                                </small>

                            </div>

                            <div>

                                <a
                                    href="telecharger.php?id=<?= $document['id'] ?>"
                                    class="btn btn-sm btn-primary"
                                >
                                    ⬇️ Télécharger
                                </a>

                                <?php if ($extension === 'pdf'): ?>

                                    <a
                                        href="telecharger.php?id=<?= $document['id'] ?>"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        👁️ Ouvrir
                                    </a>

                                <?php endif; ?>

                                <a
                                    href="supprimer_document.php?id=<?= $document['id'] ?>"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm(
                                        'Supprimer ce document ?'
                                    );"
                                >
                                    🗑️
                                </a>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</div>
</div>

</body>
</html>
