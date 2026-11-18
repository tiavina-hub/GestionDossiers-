
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/historique.php';
require_once __DIR__ . '/../config/auth.php';

require_login();

$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['csrf_fitandremana'])) {
    $_SESSION['csrf_fitandremana'] = bin2hex(random_bytes(32));
}

$sp_id = filter_input(INPUT_GET, 'sp_id', FILTER_VALIDATE_INT);

if (!$sp_id) {
    die("SP invalide.");
}

// Récupération du SP
$stmt = $pdo->prepare("
    SELECT id, numero_officiel, nom
    FROM synodam_paritany
    WHERE id = ?
");
$stmt->execute([$sp_id]);
$sp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sp) {
    die("SP introuvable.");
}

$message = '';
$erreur = '';

// Ajout d'un Fitandremana
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'ajouter_fitandremana'
) {
    if (!$isAdmin) {
        http_response_code(403);
        die("Accès refusé.");
    }

    if (!hash_equals(
        $_SESSION['csrf_fitandremana'],
        $_POST['csrf_token'] ?? ''
    )) {
        die("Jeton de sécurité invalide.");
    }

    $nom = trim($_POST['nom'] ?? '');

    if ($nom === '') {
        $erreur = "Le nom du Fitandremana est obligatoire.";
    } elseif (mb_strlen($nom) > 255) {
        $erreur = "Le nom ne doit pas dépasser 255 caractères.";
    } else {
        try {
            $pdo->beginTransaction();

            // Verrouiller le SP pendant l'opération
            $stmt = $pdo->prepare("
                SELECT id
                FROM synodam_paritany
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$sp_id]);

            if (!$stmt->fetch()) {
                throw new Exception("SP introuvable.");
            }

            // Insérer le nouveau Fitandremana
            $stmt = $pdo->prepare("
                INSERT INTO fitandremana (sp_id, numero, nom)
                VALUES (?, NULL, ?)
            ");
            $stmt->execute([$sp_id, $nom]);

            // Récupérer tous les Fitandremana par ordre alphabétique
            $stmt = $pdo->prepare("
                SELECT id
                FROM fitandremana
                WHERE sp_id = ?
                ORDER BY nom ASC, id ASC
                FOR UPDATE
            ");
            $stmt->execute([$sp_id]);

            $liste = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Réattribuer les numéros de 1 à N
            $update = $pdo->prepare("
                UPDATE fitandremana
                SET numero = ?
                WHERE id = ? AND sp_id = ?
            ");

            $numero = 1;

            foreach ($liste as $fit_id) {
                $update->execute([
                    $numero,
                    $fit_id,
                    $sp_id
                ]);

                $numero++;
            }

            $fitId = (int)$pdo->lastInsertId();

            enregistrer_action(
                $pdo,
                'Création',
                'Fitandremana',
                $fitId,
                'Création du Fitandremana « ' . $nom .
                ' » dans le SP ' . $sp['numero_officiel'] .
                ' — ' . $sp['nom']
            );

            $pdo->commit();

            header(
                "Location: fitandremana.php?sp_id="
                . $sp_id
                . "&ajoute=1"
            );
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $erreur = "Erreur lors de l'ajout du Fitandremana.";
        }
    }
}

// Messages
if (isset($_GET['ajoute'])) {
    $message = "Fitandremana ajouté avec succès.";
}

if (isset($_GET['modifie'])) {
    $message = "Fitandremana modifié avec succès.";
}

if (isset($_GET['supprime'])) {
    $message = "Fitandremana supprimé avec succès.";
}

if (($_GET['erreur'] ?? '') === 'suppression') {
    $erreur = "Impossible de supprimer ce Fitandremana : il contient au moins un dossier.";
}

// Récupérer la liste dans l'ordre alphabétique
$stmt = $pdo->prepare("
    SELECT id, numero, nom
    FROM fitandremana
    WHERE sp_id = ?
    ORDER BY nom ASC, id ASC
");
$stmt->execute([$sp_id]);

$fitandremana = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Gestion des Fitandremana</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f4f6f9;
            color: #263238;
        }

        .main-container {
            max-width: 1100px;
            margin: 35px auto;
        }

        .page-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,.04);
        }

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,.04);
        }

        .fit-card {
            background: white;
            border: 1px solid #e4e8ed;
            border-radius: 10px;
            padding: 20px;
            height: 100%;
            transition: .2s;
        }

        .fit-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,.07);
        }

        .fit-number {
            color: #0d6efd;
            font-weight: 700;
            font-size: 14px;
        }

        .fit-name {
            font-size: 18px;
            font-weight: 600;
            margin: 10px 0 18px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 650;
            margin-bottom: 20px;
        }

        .search-input {
            max-width: 350px;
        }
    
    
    /* DESIGN FJKM NAVY GOLD */

    body {
        background: #f6f4ee !important;
        color: #202b3d;
        font-family: "DM Sans", Arial, sans-serif;
        min-height: 100vh;
    }

    .main-container {
        max-width: 1320px;
        margin: 35px auto 70px;
        padding-left: 24px;
        padding-right: 24px;
    }

    .page-header {
        background: linear-gradient(115deg, #101b32, #192944 70%, #29344a);
        color: white;
        padding: 40px 45px;
        border-radius: 25px;
        margin-bottom: 28px;
        box-shadow: 0 22px 50px rgba(16,27,50,.16);
        border: 0;
    }

    .page-header h2 {
        color: white;
        font-family: "Playfair Display", Georgia, serif;
        font-size: clamp(28px, 4vw, 40px);
        font-weight: 700;
    }

    .page-header .text-muted {
        color: #e6d1a0 !important;
        font-size: 14px;
    }

    .page-header .btn-outline-secondary {
        color: #e6d1a0;
        border-color: #c9a45c;
        border-radius: 10px;
        font-weight: 700;
    }

    .page-header .btn-outline-secondary:hover {
        background: #c9a45c;
        color: #101b32;
    }

    .section-card {
        background: #fffefa;
        border: 1px solid #e8e5dc;
        border-radius: 18px;
        padding: 28px;
        margin-bottom: 25px;
        box-shadow: 0 12px 35px rgba(16,27,50,.05);
    }

    .section-title {
        color: #101b32;
        font-family: "Playfair Display", Georgia, serif;
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .fit-card {
        background: #fffefa;
        border: 1px solid #e8e5dc;
        border-radius: 17px;
        padding: 23px;
        min-height: 185px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform .2s, box-shadow .2s, border-color .2s;
    }

    .fit-card:hover {
        transform: translateY(-5px);
        border-color: #d5c49f;
        box-shadow: 0 18px 40px rgba(16,27,50,.09);
    }

    .fit-number {
        color: #a28a5d;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }

    .fit-name {
        color: #101b32;
        font-family: "Playfair Display", Georgia, serif;
        font-size: 21px;
        line-height: 1.4;
        font-weight: 700;
        margin: 17px 0 22px;
        overflow-wrap: anywhere;
    }

    .search-input {
        max-width: 350px;
        border: 1px solid #e8e5dc;
        border-radius: 10px;
        padding: 11px 14px;
    }

    .form-control {
        border-color: #e1ded5;
        border-radius: 10px;
        padding: 11px 13px;
    }

    .form-control:focus {
        border-color: #c9a45c;
        box-shadow: 0 0 0 .2rem rgba(201,164,92,.15);
    }

    .form-label {
        color: #192944;
        font-weight: 700;
    }

    .btn {
        border-radius: 9px;
        font-weight: 700;
        padding: 9px 14px;
    }

    .btn-primary {
        background: #101b32;
        border-color: #101b32;
    }

    .btn-primary:hover {
        background: #293d5d;
        border-color: #293d5d;
    }

    .btn-outline-secondary {
        color: #192944;
        border-color: #c9a45c;
    }

    .btn-outline-secondary:hover {
        background: #c9a45c;
        color: #101b32;
        border-color: #c9a45c;
    }

    .btn-outline-danger {
        border-color: #ead8d4;
    }

    .alert {
        border-radius: 12px;
    }

    @media(max-width: 600px) {
        .main-container {
            width: 100%;
            margin: 20px auto 40px;
            padding-left: 16px;
            padding-right: 16px;
        }

        .page-header {
            padding: 28px 23px;
            border-radius: 18px;
        }

        .section-card {
            padding: 20px;
        }

        .d-flex.justify-content-between {
            align-items: stretch !important;
        }

        .search-input {
            max-width: 100%;
            width: 100%;
        }
    }
    
</style>
</head>

<body>

<div class="container main-container">

    <div class="page-header">

        <a href="index.php" class="btn btn-outline-secondary btn-sm mb-3">
            Retour à l'accueil
        </a>

        <h2 class="mb-2">
            Gestion des Fitandremana
        </h2>

        <div class="text-muted">
            SP <?= e($sp['numero_officiel']) ?>
            - <?= e($sp['nom']) ?>
        </div>

    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <div class="alert alert-danger">
            <?= e($erreur) ?>
        </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>

        <div class="section-card">

            <h3 class="section-title">
                Ajouter un Fitandremana
            </h3>

            <form method="POST">

                <input
                    type="hidden"
                    name="action"
                    value="ajouter_fitandremana"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_fitandremana']) ?>"
                >

                <div class="mb-3">

                    <label class="form-label">
                        Nom du Fitandremana
                    </label>

                    <input
                        type="text"
                        name="nom"
                        class="form-control"
                        maxlength="255"
                        placeholder="Saisir le nom du Fitandremana"
                        required
                    >

                </div>

                <button type="submit" class="btn btn-primary">
                    Ajouter
                </button>

            </form>

            <small class="text-muted d-block mt-3">
                Le numéro est attribué automatiquement selon l'ordre alphabétique.
            </small>

        </div>

    <?php endif; ?>

    <div class="section-card">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

            <h3 class="section-title mb-0">
                Liste des Fitandremana
                <span class="badge bg-secondary">
                    <?= count($fitandremana) ?>
                </span>
            </h3>

            <input
                type="search"
                id="rechercheFit"
                class="form-control search-input"
                placeholder="Rechercher un Fitandremana..."
            >

        </div>

        <?php if (empty($fitandremana)): ?>

            <div class="alert alert-info mb-0">
                Aucun Fitandremana enregistré pour ce SP.
            </div>

        <?php else: ?>

            <div class="row g-3" id="listeFit">

                <?php foreach ($fitandremana as $fit): ?>

                    <div class="col-md-6 col-lg-4 fit-item">

                        <div class="fit-card">

                            <div class="fit-number">
                                FITANDREMANА N° <?= e($fit['numero']) ?>
                            </div>

                            <div class="fit-name">
                                <?= e($fit['nom']) ?>
                            </div>

                            <div class="d-flex flex-wrap gap-2">

                                <a
                                    href="type.php?fitandremana_id=<?= (int)$fit['id'] ?>"
                                    class="btn btn-primary btn-sm"
                                >
                                    Voir les types
                                </a>

                                <?php if ($isAdmin): ?>

                                    <a
                                        href="modifier_fitandremana.php?id=<?= (int)$fit['id'] ?>"
                                        class="btn btn-outline-secondary btn-sm"
                                    >
                                        Modifier
                                    </a>

                                    <form
                                        method="POST"
                                        action="supprimer_fitandremana.php"
                                        onsubmit="return confirm('Voulez-vous vraiment supprimer ce Fitandremana ?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$fit['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="sp_id"
                                            value="<?= (int)$sp_id ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['csrf_fitandremana']) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-outline-danger btn-sm"
                                        >
                                            Supprimer
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <div
                id="aucunResultat"
                class="alert alert-warning mt-3"
                style="display:none;"
            >
                Aucun résultat trouvé.
            </div>

        <?php endif; ?>

    </div>

</div>

<script>
document.getElementById('rechercheFit')?.addEventListener('input', function () {

    const recherche = this.value.toLowerCase().trim();
    const elements = document.querySelectorAll('.fit-item');

    let visibles = 0;

    elements.forEach(function (element) {

        const contenu = element.textContent.toLowerCase();
        const correspond = contenu.includes(recherche);

        element.style.display = correspond ? '' : 'none';

        if (correspond) {
            visibles++;
        }
    });

    const aucun = document.getElementById('aucunResultat');

    if (aucun) {
        aucun.style.display = visibles === 0 ? 'block' : 'none';
    }

});
</script>

</body>
</html>
