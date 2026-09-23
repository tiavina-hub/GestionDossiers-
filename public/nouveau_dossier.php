<?php

require_once __DIR__ . '/../config/database.php';

$type_id = filter_input(INPUT_GET, 'type_id', FILTER_VALIDATE_INT);

if (!$type_id) {
    die("Type invalide.");
}

$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.nom AS type_nom,
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

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reference = trim($_POST['reference'] ?? '');
    $titre = trim($_POST['titre'] ?? '');
    $proprietaire = trim($_POST['proprietaire'] ?? '');
    $localisation = trim($_POST['localisation'] ?? '');
    $superficie = trim($_POST['superficie'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $statut = trim($_POST['statut'] ?? 'Actif');
    $date_dossier = $_POST['date_dossier'] ?? '';

    if ($titre === '') {

        $erreur = "Le nom du dossier est obligatoire.";

    } else {

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO dossiers
                (
                    type_id,
                    reference,
                    titre,
                    proprietaire,
                    localisation,
                    superficie,
                    description,
                    statut,
                    date_dossier
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $type_id,
                $reference !== '' ? $reference : null,
                $titre,
                $proprietaire !== '' ? $proprietaire : null,
                $localisation !== '' ? $localisation : null,
                $superficie !== '' ? $superficie : null,
                $description !== '' ? $description : null,
                $statut !== '' ? $statut : 'Actif',
                $date_dossier !== '' ? $date_dossier : null
            ]);

            $dossier_id = (int) $pdo->lastInsertId();

            /*
             * Gestion des fichiers
             */
            if (
                isset($_FILES['documents']) &&
                is_array($_FILES['documents']['name'])
            ) {

                $dossierDir =
                    __DIR__ .
                    '/../storage/uploads/' .
                    $dossier_id;

                if (!is_dir($dossierDir)) {
                    mkdir($dossierDir, 0750, true);
                }

                $extensionsAutorisees = [
                    'pdf',
                    'doc',
                    'docx',
                    'jpg',
                    'jpeg',
                    'png',
                    'xls',
                    'xlsx'
                ];

                $mimeAutorises = [
                    'application/pdf',

                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

                    'image/jpeg',
                    'image/png',

                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ];

                $finfo = new finfo(FILEINFO_MIME_TYPE);

                $nombreFichiers = count($_FILES['documents']['name']);

                for ($i = 0; $i < $nombreFichiers; $i++) {

                    if ($_FILES['documents']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $nomOriginal =
                        basename($_FILES['documents']['name'][$i]);

                    $taille =
                        (int) $_FILES['documents']['size'][$i];

                    $tmp =
                        $_FILES['documents']['tmp_name'][$i];

                    $extension =
                        strtolower(
                            pathinfo(
                                $nomOriginal,
                                PATHINFO_EXTENSION
                            )
                        );

                    $mime =
                        $finfo->file($tmp);

                    if (
                        !in_array($extension, $extensionsAutorisees, true) ||
                        !in_array($mime, $mimeAutorises, true)
                    ) {
                        continue;
                    }

                    $nomStockage =
                        bin2hex(random_bytes(16)) .
                        '.' .
                        $extension;

                    $destination =
                        $dossierDir .
                        '/' .
                        $nomStockage;

                    if (
                        move_uploaded_file(
                            $tmp,
                            $destination
                        )
                    ) {

                        $chemin =
                            'storage/uploads/' .
                            $dossier_id .
                            '/' .
                            $nomStockage;

                        $stmtDoc = $pdo->prepare("
                            INSERT INTO documents
                            (
                                dossier_id,
                                nom_original,
                                nom_stockage,
                                chemin,
                                type_fichier,
                                taille
                            )
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");

                        $stmtDoc->execute([
                            $dossier_id,
                            $nomOriginal,
                            $nomStockage,
                            $chemin,
                            $mime,
                            $taille
                        ]);
                    }
                }
            }

            $pdo->commit();

            header(
                'Location: type.php?id=' .
                $type_id .
                '&success=1'
            );

            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erreur = "Erreur lors de l'enregistrement du dossier.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Nouveau dossier</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <a
        href="type.php?id=<?= $type['id'] ?>"
        class="btn btn-secondary mb-4"
    >
        ← Retour
    </a>

    <div class="mb-4">

        <p class="text-muted">
            📍 <?= htmlspecialchars($type['region_nom']) ?>
            →
            📂 <?= htmlspecialchars($type['type_nom']) ?>
        </p>

        <h1>➕ Nouveau dossier</h1>

    </div>

    <?php if ($erreur): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($erreur) ?>
        </div>

    <?php endif; ?>

    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <div class="card shadow-sm mb-4">

            <div class="card-header">
                <h4 class="mb-0">
                    Informations du dossier
                </h4>
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Référence
                        </label>

                        <input
                            type="text"
                            name="reference"
                            class="form-control"
                            placeholder="Ex : PROP-2026-001"
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Statut
                        </label>

                        <select
                            name="statut"
                            class="form-select"
                        >
                            <option value="Actif">Actif</option>
                            <option value="En cours">En cours</option>
                            <option value="Archivé">Archivé</option>
                        </select>

                    </div>

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Nom du dossier *
                        </label>

                        <input
                            type="text"
                            name="titre"
                            class="form-control"
                            required
                            placeholder="Ex : Terrain LOVASOA-C"
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Nom du propriétaire
                        </label>

                        <input
                            type="text"
                            name="proprietaire"
                            class="form-control"
                            placeholder="Ex : Jean Rakoto"
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Superficie
                        </label>

                        <input
                            type="text"
                            name="superficie"
                            class="form-control"
                            placeholder="Ex : 02ha 20a"
                        >

                    </div>

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Localisation
                        </label>

                        <input
                            type="text"
                            name="localisation"
                            class="form-control"
                            placeholder="Ex : Mahazoarivo, Antananarivo"
                        >

                    </div>

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Date du dossier
                        </label>

                        <input
                            type="date"
                            name="date_dossier"
                            class="form-control"
                        >

                    </div>

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                            placeholder="Informations complémentaires..."
                        ></textarea>

                    </div>

                </div>

            </div>

        </div>

        <div class="card shadow-sm mb-4">

            <div class="card-header">
                <h4 class="mb-0">
                    📎 Documents
                </h4>
            </div>

            <div class="card-body">

                <p class="text-muted">
                    Vous pouvez ajouter plusieurs fichiers.
                </p>

                <input
                    type="file"
                    name="documents[]"
                    class="form-control"
                    multiple
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx"
                >

                <small class="text-muted">
                    PDF, Word, Excel et images.
                </small>

            </div>

        </div>

        <div class="d-flex gap-2">

            <a
                href="type.php?id=<?= $type['id'] ?>"
                class="btn btn-secondary"
            >
                Annuler
            </a>

            <button
                type="submit"
                class="btn btn-success"
            >
                💾 Enregistrer le dossier
            </button>

        </div>

    </form>

</div>

</body>
</html>