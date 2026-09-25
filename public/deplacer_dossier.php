<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

require_once __DIR__ . '/../config/database.php';

$dossier_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$dossier_id) {
    die("Dossier invalide.");
}

/*
 * Récupérer le dossier
 */
$stmt = $pdo->prepare("
    SELECT
        d.id,
        d.titre,
        d.type_id,
        t.nom AS type_nom,
        r.id AS region_id,
        r.nom AS region_nom
    FROM dossiers d
    JOIN types_dossiers t ON d.type_id = t.id
    JOIN regions r ON t.region_id = r.id
    WHERE d.id = ?
");

$stmt->execute([$dossier_id]);
$dossier = $stmt->fetch();

if (!$dossier) {
    die("Dossier introuvable.");
}

$message = null;
$erreur = null;

/*
 * Récupérer toutes les régions
 */
$stmt = $pdo->query("
    SELECT id, nom
    FROM regions
    ORDER BY nom ASC
");

$regions = $stmt->fetchAll();

/*
 * Traitement
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nouveau_type_id = filter_var(
        $_POST['type_id'] ?? '',
        FILTER_VALIDATE_INT
    );

    if (!$nouveau_type_id) {

        $erreur = "Veuillez sélectionner un type.";

    } else {

        try {

            /*
             * Vérifier que le type existe
             */
            $stmt = $pdo->prepare("
                SELECT id
                FROM types_dossiers
                WHERE id = ?
            ");

            $stmt->execute([$nouveau_type_id]);

            if (!$stmt->fetch()) {

                $erreur = "Type invalide.";

            } else {

                $stmt = $pdo->prepare("
                    UPDATE dossiers
                    SET type_id = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $nouveau_type_id,
                    $dossier_id
                ]);

                header(
                    "Location: dossier.php?id=" .
                    $dossier_id .
                    "&moved=1"
                );

                exit;
            }

        } catch (PDOException $e) {

            $erreur = "Erreur lors du déplacement.";
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

    <title>Déplacer le dossier</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <a
        href="dossier.php?id=<?= $dossier_id ?>"
        class="btn btn-secondary mb-4"
    >
        ← Retour
    </a>

    <h1>
        📂 Déplacer le dossier
    </h1>

    <p class="text-muted">
        <?= htmlspecialchars($dossier['titre']) ?>
    </p>

    <?php if ($erreur): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($erreur) ?>
        </div>

    <?php endif; ?>

    <div class="card shadow-sm">

        <div class="card-body">

            <form method="POST">

                <div class="mb-4">

                    <label class="form-label">
                        Nouvelle région
                    </label>

                    <select
                        id="regionSelect"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Sélectionner une région
                        </option>

                        <?php foreach ($regions as $region): ?>

                            <option
                                value="<?= $region['id'] ?>"
                                <?= $region['id'] == $dossier['region_id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars($region['nom']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="mb-4">

                    <label class="form-label">
                        Nouveau type
                    </label>

                    <select
                        id="typeSelect"
                        name="type_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Sélectionner d'abord une région
                        </option>

                    </select>

                </div>

                <button
                    type="submit"
                    class="btn btn-success"
                >
                    🔄 Déplacer le dossier
                </button>

            </form>

        </div>

    </div>

</div>


<script>

const regionSelect = document.getElementById('regionSelect');
const typeSelect = document.getElementById('typeSelect');

const types = <?= json_encode(
    array_map(
        function ($type) {
            return [
                'id' => $type['id'],
                'region_id' => $type['region_id'],
                'nom' => $type['nom']
            ];
        },
        $pdo->query("
            SELECT id, region_id, nom
            FROM types_dossiers
            ORDER BY nom ASC
        ")->fetchAll()
    ),
    JSON_UNESCAPED_UNICODE
) ?>;

function chargerTypes() {

    const regionId = regionSelect.value;

    typeSelect.innerHTML = '';

    const premier = document.createElement('option');

    premier.value = '';
    premier.textContent = 'Sélectionner un type';

    typeSelect.appendChild(premier);

    types
        .filter(type => type.region_id == regionId)
        .forEach(type => {

            const option = document.createElement('option');

            option.value = type.id;
            option.textContent = type.nom;

            <?php if ($dossier['region_id']): ?>

            if (
                regionId ==
                <?= (int) $dossier['region_id'] ?>
                &&
                type.id ==
                <?= (int) $dossier['type_id'] ?>
            ) {
                option.selected = true;
            }

            <?php endif; ?>

            typeSelect.appendChild(option);
        });
}

regionSelect.addEventListener(
    'change',
    chargerTypes
);

chargerTypes();

</script>

</body>
</html>
