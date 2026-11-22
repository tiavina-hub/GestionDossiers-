
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/historique.php';
require_once __DIR__ . '/../config/auth.php';

require_admin();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['csrf_fitandremana'])) {
    $_SESSION['csrf_fitandremana'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id) {
    die("Fitandremana invalide.");
}

// Récupération du Fitandremana
$stmt = $pdo->prepare("
    SELECT id, sp_id, numero, nom
    FROM fitandremana
    WHERE id = ?
");
$stmt->execute([$id]);
$fit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fit) {
    die("Fitandremana introuvable.");
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals(
        $_SESSION['csrf_fitandremana'],
        $_POST['csrf_token'] ?? ''
    )) {
        die("Jeton de sécurité invalide.");
    }

    $nom = trim($_POST['nom'] ?? '');

    if ($nom === '') {
        $erreur = "Le nom est obligatoire.";
    } elseif (mb_strlen($nom) > 255) {
        $erreur = "Le nom ne doit pas dépasser 255 caractères.";
    } else {
        try {
            $pdo->beginTransaction();

// Verrouiller le SP concerné
$stmt = $pdo->prepare("
    SELECT id
    FROM synodam_paritany
    WHERE id = ?
    FOR UPDATE
");
$stmt->execute([$fit['sp_id']]);

// Modifier le nom
$stmt = $pdo->prepare("
    UPDATE fitandremana
    SET nom = ?
    WHERE id = ?
");
$stmt->execute([$nom, $id]);

// Reclasser tous les Fitandremana du SP
$stmt = $pdo->prepare("
    SELECT id
    FROM fitandremana
    WHERE sp_id = ?
    ORDER BY nom ASC, id ASC
    FOR UPDATE
");
$stmt->execute([$fit['sp_id']]);

$liste = $stmt->fetchAll(PDO::FETCH_COLUMN);

$update = $pdo->prepare("
    UPDATE fitandremana
    SET numero = ?
    WHERE id = ?
");

$numero = 1;

foreach ($liste as $fit_id) {
    $update->execute([$numero, $fit_id]);
    $numero++;
}

enregistrer_action(
    $pdo,
    'Modification',
    'Fitandremana',
    (int)$fit['id'],
    'Modification du Fitandremana « ' . $fit['nom'] .
    ' » en « ' . $nom . ' »'
);

$pdo->commit();
            header(
                "Location: fitandremana.php?sp_id="
                . (int)$fit['sp_id']
                . "&modifie=1"
            );
            exit;

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $erreur = "Erreur lors de la modification.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Modifier un Fitandremana</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
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

body {
    min-height: 100vh;
    margin: 0;
    padding: 40px 18px;
    background:
        radial-gradient(circle at top right, rgba(201,164,92,.12), transparent 32%),
        var(--cream);
    color: var(--text);
    font-family: 'DM Sans', Arial, sans-serif;
}

.container-form {
    max-width: 680px;
    margin: 35px auto;
    padding: 0;
    overflow: hidden;
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 18px;
    box-shadow: 0 18px 50px rgba(16,27,50,.09);
}

.form-header {
    position: relative;
    overflow: hidden;
    padding: 32px 36px;
    color: var(--white);
    background: linear-gradient(125deg, var(--navy), var(--navy-light));
}

.form-header::after {
    content: "";
    position: absolute;
    width: 190px;
    height: 190px;
    top: -100px;
    right: -35px;
    border: 1px solid rgba(230,209,160,.25);
    border-radius: 50%;
    box-shadow: 0 0 0 24px rgba(230,209,160,.04),
                0 0 0 48px rgba(230,209,160,.03);
}

.form-kicker {
    margin-bottom: 10px;
    color: var(--gold-light);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.form-header h2 {
    position: relative;
    z-index: 1;
    margin: 0;
    font-family: Georgia, serif;
    font-size: clamp(23px, 4vw, 30px);
    font-weight: 600;
}

.form-subtitle {
    position: relative;
    z-index: 1;
    margin: 10px 0 0;
    color: rgba(255,255,255,.72);
    font-size: 13px;
}

.form-body {
    padding: 34px 36px 36px;
}

.form-label {
    display: block;
    margin-bottom: 10px;
    color: var(--navy);
    font-size: 13px;
    font-weight: 700;
}

.form-control {
    min-height: 49px;
    padding: 12px 15px;
    color: var(--text);
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 9px;
    font-size: 14px;
    box-shadow: none;
    transition: border-color .2s, box-shadow .2s;
}

.form-control:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,164,92,.15);
}

.form-control:disabled {
    color: var(--navy);
    background: var(--cream);
    opacity: 1;
    font-weight: 700;
}

.field-help {
    display: block;
    margin-top: 9px;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.6;
}

.form-group {
    margin-bottom: 25px;
}

.alert-danger {
    margin-bottom: 25px;
    padding: 14px 16px;
    color: #842b32;
    background: #fff1f0;
    border: 1px solid #f1d1ce;
    border-radius: 9px;
    font-size: 13px;
}

.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--border);
}

.btn {
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 11px 22px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: .2s ease;
}

.btn-primary {
    color: var(--navy);
    background: var(--gold);
    border: 1px solid var(--gold);
}

.btn-primary:hover {
    color: var(--navy);
    background: var(--gold-light);
    border-color: var(--gold-light);
    transform: translateY(-1px);
}

.btn-outline-secondary {
    color: var(--navy);
    background: transparent;
    border: 1px solid var(--border);
}

.btn-outline-secondary:hover {
    color: var(--white);
    background: var(--navy);
    border-color: var(--navy);
}

@media (max-width: 576px) {
    body {
        padding: 20px 12px;
    }

    .container-form {
        margin: 12px auto;
        border-radius: 14px;
    }

    .form-header {
        padding: 27px 23px;
    }

    .form-body {
        padding: 26px 22px;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
    }
}
</style>
</head>

<body>

<div class="container container-form">

    <div class="form-header">
        <div class="form-kicker">FJKM · Fitantanana</div>
        <h2>Fanovana ny mombamomba ny Fitandremana</h2>
        <p class="form-subtitle">Modification</p>
    </div>

    <div class="form-body">

        <?php if ($erreur): ?>
            <div class="alert alert-danger">
                <?= e($erreur) ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">Numéro</label>
            <input type="text"
                   class="form-control"
                   value="N° <?= e($fit['numero']) ?>"
                   disabled>
            <small class="field-help">
                Le numéro est automatique et ne peut pas être modifié.
            </small>
        </div>

        <form method="POST">

            <input type="hidden"
                   name="id"
                   value="<?= (int)$fit['id'] ?>">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= e($_SESSION['csrf_fitandremana']) ?>">

            <div class="form-group">
                <label class="form-label">
                    Nom du Fitandremana
                </label>

                <input type="text"
                       name="nom"
                       class="form-control"
                       maxlength="255"
                       value="<?= e($fit['nom']) ?>"
                       required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Enregistrer les modifications
                </button>

                <a href="fitandremana.php?sp_id=<?= (int)$fit['sp_id'] ?>"
                   class="btn btn-outline-secondary">
                    Annuler
                </a>
            </div>

        </form>
    </div>
</div>

</body>
</html>
