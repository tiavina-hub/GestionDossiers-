<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

if (est_connecte()) {
    header('Location: index.php');
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $erreur = 'Veuillez remplir tous les champs.';

    } else {

        $stmt = $pdo->prepare("
            SELECT
                id,
                username,
                password_hash,
                nom_complet,
                role,
                actif
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->execute([$username]);

        $user = $stmt->fetch();

        if (
            $user &&
            (int) $user['actif'] === 1 &&
            password_verify($password, $user['password_hash'])
        ) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nom_complet'] = $user['nom_complet'];
            $_SESSION['user_role'] = $user['role'];

            $stmt = $pdo->prepare("
                UPDATE users
                SET derniere_connexion = NOW()
                WHERE id = ?
            ");

            $stmt->execute([$user['id']]);

            header('Location: index.php');
            exit;

        } else {

            $erreur = 'Identifiant ou mot de passe incorrect.';
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

    <title>Connexion</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container">

    <div
        class="row justify-content-center align-items-center"
        style="min-height: 100vh;"
    >

        <div class="col-md-5 col-lg-4">

            <div class="card shadow">

                <div class="card-body p-4">

                    <div class="text-center mb-4">

                        <h1>📁</h1>

                        <h3>
                            Gestion des dossiers
                        </h3>

                        <p class="text-muted">
                            Connexion
                        </p>

                    </div>

                    <?php if ($erreur): ?>

                        <div class="alert alert-danger">
                            <?= htmlspecialchars($erreur) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Identifiant
                            </label>

                            <input
                                type="text"
                                name="username"
                                class="form-control"
                                autocomplete="username"
                                required
                            >

                        </div>

                        <div class="mb-4">

                            <label class="form-label">
                                Mot de passe
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                autocomplete="current-password"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            🔐 Se connecter
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>
