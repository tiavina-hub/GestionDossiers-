<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit("Dossier invalide.");
}

$stmt = $pdo->prepare("
    SELECT d.*, t.nom AS type_nom,
           f.id AS fitandremana_id, f.nom AS fitandremana_nom,
           sp.id AS sp_id, sp.numero_officiel, sp.nom AS sp_nom
    FROM dossiers d
    JOIN types_dossiers t ON t.id = d.type_id
    JOIN fitandremana f ON f.id = t.fitandremana_id
    JOIN synodam_paritany sp ON sp.id = f.sp_id
    WHERE d.id = ?
");
$stmt->execute([$id]);
$dossier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dossier) {
    http_response_code(404);
    exit("Dossier introuvable.");
}

$erreur = '';
$typesValides = ['Titré', 'Cadastré', 'Karatany', 'Taratasin-totra'];
$statutsValides = ['Mavitrika', 'En cours', 'Archivé'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $laharana = trim($_POST['laharana_fitandremana'] ?? '');
    $tomponny = trim($_POST['tomponny_tany'] ?? '');
    $fokontany = trim($_POST['fokontany'] ?? '');
    $kaominina = trim($_POST['kaominina'] ?? '');
    $distrika = trim($_POST['distrika'] ?? '');
    $faritra = trim($_POST['faritra'] ?? '');
    $karazan = $_POST['karazan_tany'] ?? '';
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $statut = $_POST['statut'] ?? '';
    $dateDossier = trim($_POST['date_dossier'] ?? '');

    if ($titre === '') {
        $erreur = "Le nom du dossier est obligatoire.";
    } elseif ($tomponny === '') {
        $erreur = "Le nom du propriétaire est obligatoire.";
    } elseif (!in_array($karazan, $typesValides, true)) {
        $erreur = "Le type de terrain sélectionné est invalide.";
    } elseif (!in_array($statut, $statutsValides, true)) {
        $erreur = "Le statut sélectionné est invalide.";
    } elseif (
        $dateDossier !== '' &&
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDossier)
    ) {
        $erreur = "La date est invalide.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE dossiers
                SET laharana_fitandremana = ?,
                    tomponny_tany = ?,
                    fokontany = ?,
                    kaominina = ?,
                    distrika = ?,
                    faritra = ?,
                    karazan_tany = ?,
                    titre = ?,
                    description = ?,
                    statut = ?,
                    date_dossier = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $laharana !== '' ? $laharana : null,
                $tomponny,
                $fokontany !== '' ? $fokontany : null,
                $kaominina !== '' ? $kaominina : null,
                $distrika !== '' ? $distrika : null,
                $faritra !== '' ? $faritra : null,
                $karazan,
                $titre,
                $description !== '' ? $description : null,
                $statut,
                $dateDossier !== '' ? $dateDossier : null,
                $id
            ]);

            header('Location: voir_dossier.php?id=' . $id . '&modified=1');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $erreur = "Erreur lors de la modification du dossier.";
        }
    }

    // Conserver les valeurs saisies en cas d'erreur.
    $dossier['laharana_fitandremana'] = $laharana;
    $dossier['tomponny_tany'] = $tomponny;
    $dossier['fokontany'] = $fokontany;
    $dossier['kaominina'] = $kaominina;
    $dossier['distrika'] = $distrika;
    $dossier['faritra'] = $faritra;
    $dossier['karazan_tany'] = $karazan;
    $dossier['titre'] = $titre;
    $dossier['description'] = $description;
    $dossier['statut'] = $statut;
    $dossier['date_dossier'] = $dateDossier;
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Modifier le dossier</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">

    <a href="voir_dossier.php?id=<?= (int)$id ?>" class="btn btn-secondary mb-4">
        ← Retour au dossier
    </a>

    <p class="text-muted">
        SP <?= e($dossier['numero_officiel']) ?> —
        <?= e($dossier['sp_nom']) ?> /
        <?= e($dossier['fitandremana_nom']) ?> /
        <?= e($dossier['type_nom']) ?>
    </p>

    <h1 class="mb-4">Modifier le dossier</h1>

    <?php if ($erreur !== ''): ?>
        <div class="alert alert-danger"><?= e($erreur) ?></div>
    <?php endif; ?>

    <form method="post" action="modifier_dossier.php?id=<?= (int)$id ?>">
        <div class="card shadow-sm">
            <div class="card-body">

                <h5 class="mb-3">Informations générales</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Laharana Fitandremana</label>
                        <input class="form-control" name="laharana_fitandremana"
                               value="<?= e($dossier['laharana_fitandremana']) ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Statut</label>
                        <select class="form-select" name="statut" required>
                            <?php foreach ($statutsValides as $s): ?>
                                <option value="<?= e($s) ?>"
                                    <?= $dossier['statut'] === $s ? 'selected' : '' ?>>
                                    <?= e($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Nom du dossier *</label>
                        <input class="form-control" name="titre" required maxlength="255"
                               value="<?= e($dossier['titre']) ?>">
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Tompon'ny tany *</label>
                        <input class="form-control" name="tomponny_tany" required maxlength="255"
                               value="<?= e($dossier['tomponny_tany']) ?>">
                    </div>
                </div>

                <h5 class="mb-3 mt-3">Localisation du terrain</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fokontany</label>
                        <input class="form-control" name="fokontany"
                               value="<?= e($dossier['fokontany']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kaominina</label>
                        <input class="form-control" name="kaominina"
                               value="<?= e($dossier['kaominina']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Distrika</label>
                        <input class="form-control" name="distrika"
                               value="<?= e($dossier['distrika']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Faritra</label>
                        <input class="form-control" name="faritra"
                               value="<?= e($dossier['faritra']) ?>">
                    </div>
                </div>

                <h5 class="mb-3 mt-3">Informations foncières</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Karazan-tany *</label>
                        <select class="form-select" name="karazan_tany" required>
                            <?php foreach ($typesValides as $type): ?>
                                <option value="<?= e($type) ?>"
                                    <?= $dossier['karazan_tany'] === $type ? 'selected' : '' ?>>
                                    <?= e($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date du dossier</label>
                        <input type="date" class="form-control" name="date_dossier"
                               value="<?= e($dossier['date_dossier']) ?>">
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"><?= e($dossier['description']) ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">
                    Enregistrer les modifications
                </button>
                <a href="voir_dossier.php?id=<?= (int)$id ?>" class="btn btn-outline-secondary">
                    Annuler
                </a>
            </div>
        </div>
    </form>
</div>
</body>
</html>