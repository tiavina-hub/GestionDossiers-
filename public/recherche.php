<?php

require_once __DIR__ . '/../config/database.php';

$recherche = trim($_GET['q'] ?? '');
$region_id = filter_input(INPUT_GET, 'region_id', FILTER_VALIDATE_INT);
$type_id = filter_input(INPUT_GET, 'type_id', FILTER_VALIDATE_INT);

$sql = "
    SELECT
        d.id,
        d.reference,
        d.titre,
        d.proprietaire,
        d.localisation,
        d.superficie,
        d.statut,
        t.nom AS type_nom,
        r.nom AS region_nom
    FROM dossiers d
    JOIN types_dossiers t ON d.type_id = t.id
    JOIN regions r ON t.region_id = r.id
    WHERE 1=1
";

$params = [];

if ($recherche !== '') {

    $sql .= "
        AND (
            d.reference LIKE ?
            OR d.titre LIKE ?
            OR d.proprietaire LIKE ?
            OR d.localisation LIKE ?
            OR d.superficie LIKE ?
        )
    ";

    $motif = '%' . $recherche . '%';

    $params[] = $motif;
    $params[] = $motif;
    $params[] = $motif;
    $params[] = $motif;
    $params[] = $motif;
}

if ($region_id) {

    $sql .= " AND r.id = ?";
    $params[] = $region_id;
}

if ($type_id) {

    $sql .= " AND t.id = ?";
    $params[] = $type_id;
}

$sql .= " ORDER BY d.titre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$resultats = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT id, nom
    FROM regions
    ORDER BY nom
");

$regions = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Recherche</title>

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

    <form
        method="GET"
        class="card shadow-sm p-4 mb-4"
    >

        <div class="row g-3">

            <div class="col-md-8">

                <label class="form-label">
                    Recherche
                </label>

                <input
                    type="text"
                    name="q"
                    class="form-control"
                    value="<?= htmlspecialchars($recherche) ?>"
                    placeholder="Propriétaire, lieu, superficie, référence..."
                >

            </div>

            <div class="col-md-4">

                <label class="form-label">
                    Région
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
                            <?= $region_id == $region['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($region['nom']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="col-12">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    🔍 Rechercher
                </button>

            </div>

        </div>

    </form>

    <h4 class="mb-3">
        <?= count($resultats) ?> résultat(s)
    </h4>

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
                        <th></th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($resultats as $dossier): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($dossier['region_nom']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($dossier['type_nom']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($dossier['reference'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($dossier['titre']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($dossier['proprietaire'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($dossier['localisation'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($dossier['superficie'] ?? '') ?>
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

</body>
</html>
