<?php

require_once __DIR__ . '/../config/database.php';

$message = null;
$erreur = null;

/*
 * AJOUTER UNE REGION
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {

    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($nom === '') {

        $erreur = "Le nom de la région est obligatoire.";

    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO regions (nom, description)
                VALUES (?, ?)
            ");

            $stmt->execute([
                $nom,
                $description !== '' ? $description : null
            ]);

            $message = "Région ajoutée avec succès.";

        } catch (PDOException $e) {

            if ($e->errorInfo[1] == 1062) {
                $erreur = "Cette région existe déjà.";
            } else {
                $erreur = "Erreur lors de l'ajout.";
            }
        }
    }
}

/*
 * RENOMMER UNE REGION
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {

    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $nom = trim($_POST['nom'] ?? '');

    if (!$id || $nom === '') {

        $erreur = "Informations invalides.";

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE regions
                SET nom = ?
                WHERE id = ?
            ");

            $stmt->execute([$nom, $id]);

            $message = "Région modifiée avec succès.";

        } catch (PDOException $e) {

            if ($e->errorInfo[1] == 1062) {
                $erreur = "Cette région existe déjà.";
            } else {
                $erreur = "Erreur lors de la modification.";
            }
        }
    }
}

/*
 * SUPPRIMER UNE REGION
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer'])) {

    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

    if (!$id) {

        $erreur = "Région invalide.";

    } else {

        try {

            $stmt = $pdo->prepare("
                DELETE FROM regions
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            $message = "Région supprimée avec succès.";

        } catch (PDOException $e) {

            $erreur = "Impossible de supprimer cette région.";
        }
    }
}

/*
 * LISTE DES REGIONS
 */
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Gestion des régions</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <a
        href="index.php"
        class="btn btn-secondary mb-4"
    >
        ← Accueil
    </a>

    <h1 class="mb-4">
        📍 Gestion des régions
    </h1>

    <?php if ($message): ?>

        <div class="alert alert-success">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>

    <?php if ($erreur): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($erreur) ?>
        </div>

    <?php endif; ?>


    <!-- AJOUT -->

    <div class="card shadow-sm mb-4">

        <div class="card-header">
            <h4 class="mb-0">
                ➕ Ajouter une région
            </h4>
        </div>

        <div class="card-body">

            <form method="POST">

                <div class="row">

                    <div class="col-md-5 mb-3">

                        <label class="form-label">
                            Nom de la région
                        </label>

                        <input
                            type="text"
                            name="nom"
                            class="form-control"
                            required
                            placeholder="Ex : Analamanga"
                        >

                    </div>

                    <div class="col-md-5 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <input
                            type="text"
                            name="description"
                            class="form-control"
                            placeholder="Description facultative"
                        >

                    </div>

                    <div class="col-md-2 d-flex align-items-end mb-3">

                        <button
                            type="submit"
                            name="ajouter"
                            class="btn btn-success w-100"
                        >
                            Ajouter
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- LISTE -->

    <div class="card shadow-sm">

        <div class="card-header">
            <h4 class="mb-0">
                📋 Régions existantes
            </h4>
        </div>

        <div class="card-body">

            <?php if (empty($regions)): ?>

                <div class="alert alert-info">
                    Aucune région enregistrée.
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-bordered align-middle">

                        <thead class="table-dark">

                            <tr>
                                <th>Nom</th>
                                <th>Description</th>
                                <th style="width: 280px;">
                                    Actions
                                </th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($regions as $region): ?>

                                <tr>

                                    <td>
                                        📍
                                        <?= htmlspecialchars($region['nom']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $region['description'] ?? ''
                                        ) ?>
                                    </td>

                                    <td>

                                        <!-- MODIFIER -->

                                        <form
                                            method="POST"
                                            class="d-inline-flex gap-2 mb-2"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= $region['id'] ?>"
                                            >

                                            <input
                                                type="text"
                                                name="nom"
                                                value="<?= htmlspecialchars($region['nom']) ?>"
                                                class="form-control"
                                            >

                                            <button
                                                type="submit"
                                                name="modifier"
                                                class="btn btn-warning"
                                            >
                                                ✏️
                                            </button>

                                        </form>

                                        <!-- SUPPRIMER -->

                                        <form
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm(
                                                'Voulez-vous vraiment supprimer cette région ?'
                                            );"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= $region['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="supprimer"
                                                class="btn btn-danger"
                                            >
                                                🗑️ Supprimer
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>
</html>

