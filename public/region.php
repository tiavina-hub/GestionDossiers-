<?php

require_once __DIR__ . '/../config/database.php';

$region_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$region_id) {
    die("Région invalide.");
}

/*
 * Récupérer la région
 */
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

$message = null;
$erreur = null;

/*
 * AJOUTER UN TYPE
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {

    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($nom === '') {

        $erreur = "Le nom du type est obligatoire.";

    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO types_dossiers
                (region_id, nom, description)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $region_id,
                $nom,
                $description !== '' ? $description : null
            ]);

            $message = "Type ajouté avec succès.";

        } catch (PDOException $e) {

            if ($e->errorInfo[1] == 1062) {
                $erreur = "Ce type existe déjà dans cette région.";
            } else {
                $erreur = "Erreur lors de l'ajout du type.";
            }
        }
    }
}

/*
 * MODIFIER UN TYPE
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {

    $type_id = filter_var(
        $_POST['type_id'] ?? '',
        FILTER_VALIDATE_INT
    );

    $nom = trim($_POST['nom'] ?? '');

    if (!$type_id || $nom === '') {

        $erreur = "Informations invalides.";

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE types_dossiers
                SET nom = ?
                WHERE id = ?
                  AND region_id = ?
            ");

            $stmt->execute([
                $nom,
                $type_id,
                $region_id
            ]);

            $message = "Type modifié avec succès.";

        } catch (PDOException $e) {

            if ($e->errorInfo[1] == 1062) {
                $erreur = "Ce type existe déjà dans cette région.";
            } else {
                $erreur = "Erreur lors de la modification.";
            }
        }
    }
}

/*
 * SUPPRIMER UN TYPE
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer'])) {

    $type_id = filter_var(
        $_POST['type_id'] ?? '',
        FILTER_VALIDATE_INT
    );

    if (!$type_id) {

        $erreur = "Type invalide.";

    } else {

        try {

            $stmt = $pdo->prepare("
                DELETE FROM types_dossiers
                WHERE id = ?
                  AND region_id = ?
            ");

            $stmt->execute([
                $type_id,
                $region_id
            ]);

            $message = "Type supprimé avec succès.";

        } catch (PDOException $e) {

            $erreur = "Impossible de supprimer ce type. Il contient peut-être encore des dossiers.";
        }
    }
}

/*
 * Récupérer les types
 */
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($region['nom']) ?>
    </title>

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

    <h1>
        📍 <?= htmlspecialchars($region['nom']) ?>
    </h1>
<div class="card shadow-sm mb-4">
    <div class="card-body">

        <form method="GET" action="recherche.php">

            <input
                type="hidden"
                name="region_id"
                value="<?= $region['id'] ?>"
            >

            <label class="form-label">
                🔎 Rechercher dans <?= htmlspecialchars($region['nom']) ?>
            </label>

            <div class="input-group">

                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Propriétaire, lieu, superficie, référence..."
                >

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Rechercher
                </button>

            </div>

        </form>

    </div>
</div>
    <?php if (!empty($region['description'])): ?>

        <p class="text-muted">
            <?= htmlspecialchars($region['description']) ?>
        </p>

    <?php endif; ?>


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


    <!-- AJOUT TYPE -->

    <div class="card shadow-sm mb-4">

        <div class="card-header">

            <h4 class="mb-0">
                ➕ Ajouter un type de dossier
            </h4>

        </div>

        <div class="card-body">

            <form method="POST">

                <div class="row">

                    <div class="col-md-5 mb-3">

                        <label class="form-label">
                            Nom du type
                        </label>

                        <input
                            type="text"
                            name="nom"
                            class="form-control"
                            required
                            placeholder="Ex : Immobilier"
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


    <!-- TYPES -->

    <div class="card shadow-sm">

        <div class="card-header">

            <h4 class="mb-0">
                📂 Types de dossiers
            </h4>

        </div>

        <div class="card-body">

            <?php if (empty($types)): ?>

                <div class="alert alert-info">
                    Aucun type n'est encore enregistré dans cette région.
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-bordered align-middle">

                        <thead class="table-dark">

                            <tr>
                                <th>Type</th>
                                <th>Description</th>
                                <th style="width: 330px;">
                                    Actions
                                </th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($types as $type): ?>

                            <tr>

                                <td>

                                    <a
                                        href="type.php?id=<?= $type['id'] ?>"
                                        class="text-decoration-none"
                                    >
                                        📂
                                        <?= htmlspecialchars($type['nom']) ?>
                                    </a>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $type['description'] ?? ''
                                    ) ?>

                                </td>

                                <td>

                                    <div class="d-flex gap-2">

                                        <!-- RENOMMER -->

                                        <form
                                            method="POST"
                                            class="d-flex gap-2"
                                        >

                                            <input
                                                type="hidden"
                                                name="type_id"
                                                value="<?= $type['id'] ?>"
                                            >

                                            <input
                                                type="text"
                                                name="nom"
                                                value="<?= htmlspecialchars($type['nom']) ?>"
                                                class="form-control"
                                                required
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
                                            onsubmit="return confirm(
                                                'Voulez-vous vraiment supprimer ce type ?'
                                            );"
                                        >

                                            <input
                                                type="hidden"
                                                name="type_id"
                                                value="<?= $type['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="supprimer"
                                                class="btn btn-danger"
                                            >
                                                🗑️
                                            </button>

                                        </form>

                                    </div>

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