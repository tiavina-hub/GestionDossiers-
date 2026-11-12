<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_admin();
require_once __DIR__ . '/../config/database.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM fitandremana WHERE id = ?");
$stmt->execute([$id]);
$fit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fit) {
    http_response_code(404);
    exit('Fitandremana introuvable.');
}

if (empty($_SESSION['csrf_fitandremana'])) {
    $_SESSION['csrf_fitandremana'] = bin2hex(random_bytes(32));
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_fitandremana'], $token)) {
        $erreur = 'Session expirée ou formulaire invalide. Recharge la page.';
    } else {
        $numero = trim($_POST['numero'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $statut = trim($_POST['statut_source'] ?? '');
        $observation = trim($_POST['observation'] ?? '');

        if ($nom === '') {
            $erreur = 'Le nom du Fitandremana est obligatoire.';
        } elseif (mb_strlen($numero) > 30 || mb_strlen($nom) > 255 || mb_strlen($statut) > 100) {
            $erreur = 'Un des champs dépasse la longueur autorisée.';
        } else {
            $stmt = $pdo->prepare("
                UPDATE fitandremana
                SET numero = ?, nom = ?, statut_source = ?, observation = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $numero !== '' ? $numero : null,
                $nom,
                $statut !== '' ? $statut : null,
                $observation !== '' ? $observation : null,
                $id
            ]);

            unset($_SESSION['csrf_fitandremana']);
            header('Location: fitandremana.php?sp_id=' . (int)$fit['sp_id'] . '&modifie=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifier Fitandremana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width:650px">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Modifier le Fitandremana</h4>
        </div>
        <div class="card-body">
            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_fitandremana']) ?>">

                <div class="mb-3">
                    <label class="form-label">Numéro</label>
                    <input type="text" name="numero" class="form-control"
                           maxlength="30" value="<?= htmlspecialchars($fit['numero'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Nom *</label>
                    <input type="text" name="nom" class="form-control"
                           maxlength="255" required value="<?= htmlspecialchars($fit['nom']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Statut de la source</label>
                    <input type="text" name="statut_source" class="form-control"
                           maxlength="100" value="<?= htmlspecialchars($fit['statut_source'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Observation</label>
                    <textarea name="observation" class="form-control" rows="4"><?= htmlspecialchars($fit['observation'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="fitandremana.php?sp_id=<?= (int)$fit['sp_id'] ?>"
                       class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
