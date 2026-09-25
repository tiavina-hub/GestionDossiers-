<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

require_once __DIR__ . '/../config/database.php';

$q = trim($_GET['q'] ?? '');

$region_id = filter_input(
    INPUT_GET,
    'region_id',
    FILTER_VALIDATE_INT
);

$type_id = filter_input(
    INPUT_GET,
    'type_id',
    FILTER_VALIDATE_INT
);


/*
 * Récupérer les régions
 */
$stmt = $pdo->query("
    SELECT id, nom
    FROM regions
    ORDER BY nom ASC
");

$regions = $stmt->fetchAll();


/*
 * Récupérer les types
 */
$stmt = $pdo->query("
    SELECT
        t.id,
        t.nom,
        t.region_id,
        r.nom AS region_nom
    FROM types_dossiers t
    JOIN regions r ON t.region_id = r.id
    ORDER BY r.nom ASC, t.nom ASC
");

$types = $stmt->fetchAll();


/*
 * RECHERCHE
 *
 * Le mot-clé peut correspondre à :
 * - référence
 * - nom du dossier
 * - propriétaire
 * - localisation
 * - superficie
 * - description
 * - statut
 * - région
 * - type
 * - nom d'un document
 */

$sql = "
    SELECT DISTINCT
        d.id,
        d.reference,
        d.titre,
        d.proprietaire,
        d.localisation,
        d.superficie,
        d.description,
        d.statut,
        d.date_dossier,

        t.id AS type_id,
        t.nom AS type_nom,

        r.id AS region_id,
        r.nom AS region_nom

    FROM dossiers d

    JOIN types_dossiers t
        ON d.type_id = t.id

    JOIN regions r
        ON t.region_id = r.id

    LEFT JOIN documents doc
        ON doc.dossier_id = d.id

    WHERE 1 = 1
";

$params = [];


/*
 * MOT-CLÉ GÉNÉRAL
 */
if ($q !== '') {

    $sql .= "
        AND (
            d.reference LIKE ?
            OR d.titre LIKE ?
            OR d.proprietaire LIKE ?
            OR d.localisation LIKE ?
            OR d.superficie LIKE ?
            OR d.description LIKE ?
            OR d.statut LIKE ?
            OR t.nom LIKE ?
            OR r.nom LIKE ?
            OR doc.nom_original LIKE ?
        )
    ";

    $motif = '%' . $q . '%';

    for ($i = 0; $i < 10; $i++) {
        $params[] = $motif;
    }
}


/*
 * FILTRE RÉGION
 */
if ($region_id) {

    $sql .= " AND r.id = ?";
    $params[] = $region_id;
}


/*
 * FILTRE TYPE
 */
if ($type_id) {

    $sql .= " AND t.id = ?";
    $params[] = $type_id;
}


$sql .= "
    ORDER BY d.titre ASC
";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$resultats = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Recherche de dossiers</title>

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
        🔎 Recherche de dossiers
    </h1>


    <div class="card shadow-sm mb-4">

        <div class="card-header">

            <h4 class="mb-0">
                Rechercher et filtrer
            </h4>

        </div>


        <div class="card-body">

            <form method="GET">

                <div class="row">

                    <!-- MOT-CLÉ -->

                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Mot-clé
                        </label>

                        <input
                            type="text"
                            name="q"
                            class="form-control form-control-lg"
                            value="<?= htmlspecialchars($q) ?>"
                            placeholder="Propriétaire, lieu, superficie, nom, référence, document..."
                        >

                        <div class="form-text">
                            Un seul mot-clé peut suffire.
                        </div>

                    </div>


                    <!-- RÉGION -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            📍 Région
                        </label>

                        <select
                            name="region_id"
                            class="form-select"
                        >

                            <option value="">
                                Toutes les régions
                            </option>

                            <?php foreach ($regions as $region): ?>

                                <option
                                    value="<?= $region['id'] ?>"
                                    <?= $region_id == $region['id']
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $region['nom']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- TYPE -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            📂 Type
                        </label>

                        <select
                            name="type_id"
                            class="form-select"
                        >

                            <option value="">
                                Tous les types
                            </option>

                            <?php foreach ($types as $type): ?>

                                <option
                                    value="<?= $type['id'] ?>"
                                    <?= $type_id == $type['id']
                                        ? 'selected'
                                        : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $type['region_nom']
                                        . ' → '
                                        . $type['nom']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        🔎 Rechercher
                    </button>

                    <a
                        href="recherche.php"
                        class="btn btn-outline-secondary"
                    >
                        Réinitialiser
                    </a>

                </div>

            </form>

        </div>

    </div>


    <!-- RÉSULTATS -->

    <div class="d-flex justify-content-between mb-3">

        <h4>
            Résultats
        </h4>

        <span class="badge bg-primary fs-6">
            <?= count($resultats) ?>
        </span>

    </div>


    <?php if (empty($resultats)): ?>

        <div class="alert alert-info">
            Aucun dossier trouvé.
        </div>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table table-bordered table-hover bg-white">

                <thead class="table-dark">

                    <tr>

                        <th>Région</th>
                        <th>Type</th>
                        <th>Référence</th>
                        <th>Dossier</th>
                        <th>Propriétaire</th>
                        <th>Localisation</th>
                        <th>Superficie</th>
                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($resultats as $dossier): ?>

                    <tr>

                        <td>
                            📍
                            <?= htmlspecialchars(
                                $dossier['region_nom']
                            ) ?>
                        </td>

                        <td>
                            📂
                            <?= htmlspecialchars(
                                $dossier['type_nom']
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $dossier['reference'] ?? ''
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $dossier['titre']
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $dossier['proprietaire'] ?? ''
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $dossier['localisation'] ?? ''
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $dossier['superficie'] ?? ''
                            ) ?>
                        </td>

                        <td>

                            <a
                                href="dossier.php?id=<?= $dossier['id'] ?>"
                                class="btn btn-sm btn-primary"
                            >
                                Ouvrir
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>
<script>

const regionSelect = document.querySelector(
    'select[name="region_id"]'
);

const typeSelect = document.querySelector(
    'select[name="type_id"]'
);

const typesData = <?= json_encode(
    $types,
    JSON_UNESCAPED_UNICODE
) ?>;

function filtrerTypes() {

    const regionId = regionSelect.value;

    const typeActuel = typeSelect.value;

    typeSelect.innerHTML = '';

    const optionTous = document.createElement('option');

    optionTous.value = '';
    optionTous.textContent = 'Tous les types';

    typeSelect.appendChild(optionTous);

    typesData.forEach(type => {

        if (
            regionId === '' ||
            String(type.region_id) === String(regionId)
        ) {

            const option = document.createElement('option');

            option.value = type.id;

            option.textContent = type.nom;

            if (
                String(type.id) === String(typeActuel)
            ) {
                option.selected = true;
            }

            typeSelect.appendChild(option);
        }

    });
}

regionSelect.addEventListener(
    'change',
    filtrerTypes
);

filtrerTypes();

</script>
</body>
</html>