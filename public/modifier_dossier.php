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

$message = null;
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

            $stmt = $pdo->prepare("
                UPDATE dossiers
                SET
                    reference = ?,
                    titre = ?,
                    proprietaire = ?,
                    localisation = ?,
                    superficie = ?,
                    description = ?,
                    statut = ?,
                    date_dossier = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $reference !== '' ? $reference : null,
                $titre,
                $proprietaire !== '' ? $proprietaire : null,
                $localisation !== '' ? $localisation : null,
                $superficie !== '' ? $superficie : null,
                $description !== '' ? $description : null,
                $statut,
                $date_dossier !== '' ? $date_dossier : null,
                $id
            ]);

            header("Location: dossier.php?id=" . $id . "&modified=1");
            exit;

        } catch (PDOException $e) {

            $erreur = "Erreur lors de la modification.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Modifier le dossier</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <a
        href="dossier.php?id=<?= $id ?>"
        class="btn btn-secondary mb-4"
    >
        ← Retour
    </a>

    <p class="text-muted">
        📍 <?= htmlspecialchars($dossier['region_nom']) ?>
        →
        📂 <?= htmlspecialchars($dossier['type_nom']) ?>
    </p>

    <h1 class="mb-4">✏️ Modifier le dossier</h1>

    <?php if ($erreur): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($erreur) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="card shadow-sm">

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
                            value="<?= htmlspecialchars($dossier['reference'] ?? '') ?>"
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

                            <option value="Actif"
                                <?= $dossier['statut'] === 'Actif' ? 'selected' : '' ?>>
                                Actif
                            </option>

                            <option value="En cours"
                                <?= $dossier['statut'] === 'En cours' ? 'selected' : '' ?>>
                                En cours
                            </option>

                            <option value="Archivé"
                                <?= $dossier['statut'] === 'Archivé' ? 'selected' : '' ?>>
                                Archivé
                            </option>

                        </select>

                    </div>

                    <div class="col-12 mb-3">

                        <label class="form-label">
                            Nom du dossier *
                        </label>

                        <input
                            type="text"
                            name="titre"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($dossier['titre']) ?>"
                        >

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Propriétaire
                        </label>

                        <input
                            type="text"
                            name="proprietaire"
                            class="form-control"
                            value="<?= htmlspecialchars($dossier['proprietaire'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($dossier['superficie'] ?? '') ?>"
                        >

                    </div>

                    <div class="col-12 mb-3">

                        <label class="form-label">
                            Localisation
                        </label>

                        <input
                            type="text"
                            name="localisation"
                            class="form-control"
                            value="<?= htmlspecialchars($dossier['localisation'] ?? '') ?>"
                        >

                    </div>

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Date
                        </label>

                        <input
                            type="date"
                            name="date_dossier"
                            class="form-control"
                            value="<?= htmlspecialchars($dossier['date_dossier'] ?? '') ?>"
                        >

                    </div>

                    <div class="col-12 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                        ><?= htmlspecialchars($dossier['description'] ?? '') ?></textarea>

                    </div>

                </div>

                <button
                    type="submit"
                    class="btn btn-success"
                >
                    💾 Enregistrer les modifications
                </button>

            </div>

        </div>

    </form>

</div>

</body>
</html>
