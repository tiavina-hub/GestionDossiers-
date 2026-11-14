<?php

require_once __DIR__ . '/../config/auth.php';
require_admin();

require_once __DIR__ . '/../config/database.php';

$message = null;
$erreur = null;

/*
 * AJOUTER UN UTILISATEUR
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {

    $username = trim($_POST['username'] ?? '');
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    $role = $_POST['role'] ?? 'utilisateur';
    $password = $_POST['password'] ?? '';

    if (
        $username === '' ||
        $password === ''
    ) {

        $erreur = "L'identifiant et le mot de passe sont obligatoires.";

    } elseif (
        !in_array($role, ['admin', 'utilisateur'], true)
    ) {

        $erreur = "Rôle invalide.";

    } else {

        try {

            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO users
                (
                    username,
                    password_hash,
                    nom_complet,
                    role
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $username,
                $hash,
                $nom_complet !== '' ? $nom_complet : null,
                $role
            ]);

            $message = "Utilisateur ajouté avec succès.";

        } catch (PDOException $e) {

            if (($e->errorInfo[1] ?? null) == 1062) {
                $erreur = "Cet identifiant existe déjà.";
            } else {
                $erreur = "Erreur lors de l'ajout.";
            }
        }
    }
}


/*
 * ACTIVER / DÉSACTIVER
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['toggle'])
) {

    $id = filter_var(
        $_POST['id'] ?? '',
        FILTER_VALIDATE_INT
    );

    if ($id && $id !== (int) $_SESSION['user_id']) {

        $stmt = $pdo->prepare("
            UPDATE users
            SET actif = IF(actif = 1, 0, 1)
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $message = "Statut du compte modifié.";

    } else {

        $erreur =
            "Vous ne pouvez pas désactiver votre propre compte.";
    }
}


/*
 * SUPPRIMER UN UTILISATEUR
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['supprimer'])
) {

    $id = filter_var(
        $_POST['id'] ?? '',
        FILTER_VALIDATE_INT
    );

    if (!$id) {

        $erreur = "Utilisateur invalide.";

    } elseif ($id === (int) $_SESSION['user_id']) {

        $erreur = "Vous ne pouvez pas supprimer votre propre compte.";

    } else {

        $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $message = "Utilisateur supprimé.";

    }
}


/*
 * RÉINITIALISER LE MOT DE PASSE
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['reset_password'])
) {

    $id = filter_var(
        $_POST['id'] ?? '',
        FILTER_VALIDATE_INT
    );

    $nouveau_mot_de_passe =
        $_POST['nouveau_mot_de_passe'] ?? '';

    if (
        !$id ||
        $nouveau_mot_de_passe === ''
    ) {

        $erreur =
            "Utilisateur ou mot de passe invalide.";

    } elseif (strlen($nouveau_mot_de_passe) < 8) {

        $erreur =
            "Le mot de passe doit contenir au moins 8 caractères.";

    } else {

        $hash = password_hash(
            $nouveau_mot_de_passe,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            UPDATE users
            SET password_hash = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $hash,
            $id
        ]);

        $message =
            "Mot de passe modifié avec succès.";
    }
}


/*
 * LISTE
 */
$stmt = $pdo->query("
    SELECT
        id,
        username,
        nom_complet,
        role,
        actif,
        derniere_connexion,
        date_creation
    FROM users
    ORDER BY username ASC
");

$users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Gestion des utilisateurs</title>

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
        👥 Gestion des utilisateurs
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


    <!-- AJOUT UTILISATEUR -->

    <div class="card shadow-sm mb-4">

        <div class="card-header">

            <h4 class="mb-0">
                ➕ Ajouter un utilisateur
            </h4>

        </div>

        <div class="card-body">

            <form method="POST">

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Identifiant
                        </label>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Nom complet
                        </label>

                        <input
                            type="text"
                            name="nom_complet"
                            class="form-control"
                        >

                    </div>


                    <div class="col-md-2 mb-3">

                        <label class="form-label">
                            Rôle
                        </label>

                        <select
                            name="role"
                            class="form-select"
                        >

                            <option value="utilisateur">
                                Utilisateur
                            </option>

                            <option value="admin">
                                Administrateur
                            </option>

                        </select>

                    </div>


                    <div class="col-md-2 mb-3">

                        <label class="form-label">
                            Mot de passe
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            minlength="8"
                            required
                        >

                    </div>

                </div>


                <button
                    type="submit"
                    name="ajouter"
                    class="btn btn-success"
                >
                    Ajouter
                </button>

            </form>

        </div>

    </div>


    <!-- LISTE -->

    <div class="card shadow-sm">

        <div class="card-header">

            <h4 class="mb-0">
                📋 Comptes
            </h4>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>Identifiant</th>
                            <th>Nom</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Dernière connexion</th>
                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($users as $user): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $user['username']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $user['nom_complet'] ?? ''
                                ) ?>
                            </td>

                            <td>

                                <?php if (
                                    $user['role'] === 'admin'
                                ): ?>

                                    <span class="badge bg-danger">
                                        Administrateur
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-primary">
                                        Utilisateur
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if (
                                    (int) $user['actif'] === 1
                                ): ?>

                                    <span class="badge bg-success">
                                        Actif
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary">
                                        Désactivé
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $user['derniere_connexion'] ?? 'Jamais'
                                ) ?>
                            </td>

                            <td>

                                <form
                                    method="POST"
                                    class="d-inline"
                                >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $user['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="toggle"
                                        class="btn btn-sm btn-warning"
                                    >
                                        🔄
                                        <?= (int) $user['actif'] === 1
                                            ? 'Désactiver'
                                            : 'Activer' ?>
                                    </button>

                                </form>


                                <form
                                    method="POST"
                                    class="d-inline"
                                >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $user['id'] ?>"
                                    >

                                    <input
                                        type="password"
                                        name="nouveau_mot_de_passe"
                                        placeholder="Nouveau mot de passe"
                                        minlength="8"
                                        class="form-control d-inline-block"
                                        style="width: 200px;"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        name="reset_password"
                                        class="btn btn-sm btn-primary"
                                    >
                                        🔑
                                    </button>

                                </form>


                                <form
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm(
                                        'Supprimer cet utilisateur ?'
                                    );"
                                >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $user['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="supprimer"
                                        class="btn btn-sm btn-danger"
                                    >
                                        🗑️
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>
