<?php

require_once __DIR__ . '/../config/auth.php';
require_admin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/historique.php';

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

            $nouvelId = (int)$pdo->lastInsertId();

            enregistrer_action(
                $pdo,
                'Création',
                'Utilisateur',
                $nouvelId,
                'Création du compte « ' . $username .
                ' » avec le rôle ' . $role
            );

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

        $info = $pdo->prepare("
            SELECT username, actif
            FROM users
            WHERE id = ?
        ");
        $info->execute([$id]);
        $cible = $info->fetch(PDO::FETCH_ASSOC);

        if (!$cible) {
            $erreur = "Utilisateur introuvable.";
        } else {
            $nouveauStatut = (int)$cible['actif'] === 1
                ? 'Désactivation'
                : 'Activation';

            $stmt = $pdo->prepare("
                UPDATE users
                SET actif = IF(actif = 1, 0, 1)
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            enregistrer_action(
                $pdo,
                $nouveauStatut,
                'Utilisateur',
                $id,
                $nouveauStatut . ' du compte « ' .
                $cible['username'] . ' »'
            );

            $message = "Statut du compte modifié.";
        }

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

        $info = $pdo->prepare("
            SELECT username
            FROM users
            WHERE id = ?
        ");
        $info->execute([$id]);
        $cible = $info->fetch(PDO::FETCH_ASSOC);

        if (!$cible) {
            $erreur = "Utilisateur introuvable.";
        } else {
            $stmt = $pdo->prepare("
                DELETE FROM users
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            enregistrer_action(
                $pdo,
                'Suppression',
                'Utilisateur',
                $id,
                'Suppression du compte « ' .
                $cible['username'] . ' »'
            );

            $message = "Utilisateur supprimé.";
        }

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

        $info = $pdo->prepare("
            SELECT username
            FROM users
            WHERE id = ?
        ");
        $info->execute([$id]);
        $cible = $info->fetch(PDO::FETCH_ASSOC);

        if (!$cible) {
            $erreur = "Utilisateur introuvable.";
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

            enregistrer_action(
                $pdo,
                'Réinitialisation du mot de passe',
                'Utilisateur',
                $id,
                'Réinitialisation du mot de passe du compte « ' .
                $cible['username'] . ' »'
            );

            $message =
                "Mot de passe modifié avec succès.";
        }
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


<style id="FJKM-USERS-DESIGN">
:root {
    --navy: #101b32;
    --navy-light: #293d5d;
    --gold: #c9a45c;
    --gold-light: #e6d1a0;
    --cream: #f6f4ee;
    --white: #fffefa;
    --text: #202b3d;
    --muted: #778091;
    --border: #e8e5dc;
}

* {
    box-sizing: border-box;
}

body.bg-light {
    min-height: 100vh;
    background:
        radial-gradient(circle at top right, rgba(201,164,92,.10), transparent 30%),
        var(--cream) !important;
    color: var(--text);
    font-family: 'DM Sans', Arial, sans-serif;
}

.container.py-5 {
    max-width: 1450px;
    padding-top: 35px !important;
    padding-bottom: 50px !important;
}

.container > a.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 17px;
    color: var(--white);
    background: var(--navy);
    border: 1px solid var(--navy);
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
}

.container > a.btn-secondary:hover {
    color: var(--navy);
    background: var(--gold-light);
    border-color: var(--gold);
}

.container > h1 {
    margin: 22px 0 30px !important;
    color: var(--navy);
    font-family: Georgia, serif;
    font-size: clamp(25px, 4vw, 34px);
    font-weight: 700;
}

.card {
    overflow: hidden;
    background: var(--white);
    border: 1px solid var(--border) !important;
    border-radius: 15px;
    box-shadow: 0 12px 35px rgba(16,27,50,.06) !important;
}

.card-header {
    padding: 19px 24px;
    color: var(--white);
    background: linear-gradient(120deg, var(--navy), var(--navy-light));
    border-bottom: 2px solid var(--gold);
}

.card-header h4 {
    font-family: Georgia, serif;
    font-size: 19px;
    font-weight: 600;
}

.card-body {
    padding: 25px;
}

.form-label {
    margin-bottom: 9px;
    color: var(--navy);
    font-size: 13px;
    font-weight: 700;
}

.form-control,
.form-select {
    min-height: 44px;
    padding: 10px 13px;
    color: var(--text);
    background-color: var(--white);
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 13px;
    box-shadow: none;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,164,92,.15);
}

.btn {
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    transition: .2s ease;
}

.btn-success {
    padding: 11px 20px;
    color: var(--navy);
    background: var(--gold);
    border-color: var(--gold);
}

.btn-success:hover {
    color: var(--navy);
    background: var(--gold-light);
    border-color: var(--gold-light);
    transform: translateY(-1px);
}

.btn-warning {
    color: var(--navy);
    background: var(--gold-light);
    border-color: var(--gold-light);
}

.btn-warning:hover {
    color: var(--white);
    background: var(--navy-light);
    border-color: var(--navy-light);
}

.btn-primary {
    color: var(--white);
    background: var(--navy);
    border-color: var(--navy);
}

.btn-primary:hover {
    color: var(--navy);
    background: var(--gold-light);
    border-color: var(--gold);
}

.btn-danger {
    color: #fff;
    background: #a83d43;
    border-color: #a83d43;
}

.btn-danger:hover {
    background: #842b32;
    border-color: #842b32;
}

.alert {
    padding: 14px 17px;
    border-radius: 9px;
    font-size: 13px;
}

.alert-success {
    color: #245c42;
    background: #edf7f0;
    border-color: #cde7d5;
}

.alert-danger {
    color: #842b32;
    background: #fff1f0;
    border-color: #f1d1ce;
}

.table-responsive {
    border-radius: 9px;
}

.table {
    margin-bottom: 0;
    color: var(--text);
    font-size: 13px;
    vertical-align: middle;
}

.table > thead > tr > th {
    padding: 15px 12px;
    color: var(--gold-light);
    background: var(--navy);
    border-color: rgba(255,255,255,.12);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .5px;
    white-space: nowrap;
}

.table > tbody > tr > td {
    padding: 15px 12px;
    background: var(--white);
    border-color: var(--border);
}

.table > tbody > tr:hover > td {
    background: #f8f5ed;
}

.badge {
    padding: 7px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.badge.bg-danger {
    color: var(--white) !important;
    background: var(--navy) !important;
}

.badge.bg-primary {
    color: var(--navy) !important;
    background: var(--gold-light) !important;
}

.badge.bg-success {
    color: #245c42 !important;
    background: #dcefe2 !important;
}

.badge.bg-secondary {
    color: #555d69 !important;
    background: #e8e9eb !important;
}

.table form {
    margin: 3px 3px 3px 0;
    vertical-align: middle;
}

.table form .form-control {
    display: inline-block !important;
    width: 190px !important;
    min-height: 34px;
    padding: 6px 9px;
    font-size: 12px;
    vertical-align: middle;
}

.table form .btn {
    min-height: 34px;
    padding: 6px 10px;
    vertical-align: middle;
}

@media (max-width: 768px) {
    .container.py-5 {
        padding: 20px 13px !important;
    }

    .card-body {
        padding: 18px;
    }

    .container > h1 {
        margin-bottom: 22px !important;
    }

    .table {
        font-size: 12px;
    }

    .table > tbody > tr > td {
        padding: 11px 9px;
    }

    .table form .form-control {
        width: 145px !important;
    }
}
</style>

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

                        <div class="input-group">
                            <input
                                type="password"
                                id="password-ajout"
                                name="password"
                                class="form-control"
                                minlength="8"
                                required
                            >
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                onclick="togglePasswordVisibility('password-ajout', this)"
                                title="Afficher le mot de passe"
                                aria-label="Afficher le mot de passe"
                            >👁️</button>
                        </div>

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

                                    <div class="input-group input-group-sm d-inline-flex" style="width: 240px;">
                                        <input
                                            type="password"
                                            id="nouveau-mdp-<?= (int) $user['id'] ?>"
                                            name="nouveau_mot_de_passe"
                                            placeholder="Nouveau mot de passe"
                                            minlength="8"
                                            class="form-control"
                                            required
                                        >
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePasswordVisibility('nouveau-mdp-<?= (int) $user['id'] ?>', this)"
                                            title="Afficher le mot de passe"
                                            aria-label="Afficher le mot de passe"
                                        >👁️</button>
                                    </div>

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


<script>
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);

    if (!input) {
        return;
    }

    const afficher = input.type === "password";
    input.type = afficher ? "text" : "password";

    button.textContent = afficher ? "🙈" : "👁️";
    button.title = afficher ? "Masquer le mot de passe" : "Afficher le mot de passe";
    button.setAttribute(
        "aria-label",
        afficher ? "Masquer le mot de passe" : "Afficher le mot de passe"
    );
}
</script>

</body>
</html>
