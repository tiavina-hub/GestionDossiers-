<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

require_once __DIR__ . '/../config/database.php';

$sp_id = isset($_GET['sp_id']) ? (int)$_GET['sp_id'] : 0;

if ($sp_id <= 0) {
    header('Location: index.php');
    exit;
}

/* Informations du SP */
$stmt = $pdo->prepare("
    SELECT id, numero, numero_officiel, nom
    FROM synodam_paritany
    WHERE id = ?
");

$stmt->execute([$sp_id]);
$sp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sp) {
    header('Location: index.php');
    exit;
}

/* Fitandremana de ce SP */
$stmt = $pdo->prepare("
    SELECT
        id,
        numero,
        nom,
        statut_source,
        observation
    FROM fitandremana
    WHERE sp_id = ?
    ORDER BY id ASC
");

$stmt->execute([$sp_id]);
$fitandremana = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="mg">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Fitandremana - <?= htmlspecialchars($sp['nom']) ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container pt-3">

    <div class="d-flex justify-content-between">

        <span class="text-muted">
            👤
            <?= htmlspecialchars(
                $_SESSION['nom_complet'] ?? $_SESSION['username'] ?? ''
            ) ?>
        </span>

        <a
            href="logout.php"
            class="btn btn-outline-danger btn-sm"
        >
            Hivoaka
        </a>

    </div>

</div>


<div class="container py-4">

    <div class="mb-4">

        <a
            href="sp.php?numero=<?= urlencode($sp['numero_officiel']) ?>"
            class="btn btn-outline-secondary"
        >
            ← Hiverina
        </a>

    </div>


    <div class="mb-5">

        <h1 class="fw-bold">
            SP <?= htmlspecialchars($sp['numero_officiel']) ?>
        </h1>

        <h4 class="text-primary">
            <?= htmlspecialchars($sp['nom']) ?>
        </h4>

        <p class="text-muted">
            Safidio ny Fitandremana
        </p>

    </div>


    <?php if (!$fitandremana): ?>

        <div class="alert alert-warning">
            Tsy mbola misy Fitandremana voasoratra ho an'ity SP ity.
        </div>

    <?php else: ?>

        <div class="row g-3">

            <?php foreach ($fitandremana as $fit): ?>

                <div class="col-md-6 col-lg-4">

                    <div class="card shadow-sm h-100">

                        <div class="card-body">

                            <div class="mb-2">

                                <span class="badge bg-secondary">
                                    <?= htmlspecialchars($fit['numero']) ?>
                                </span>

                            </div>

                            <h5 class="card-title">
                                <?= nl2br(
                                    htmlspecialchars($fit['nom'])
                                ) ?>
                            </h5>

                            <?php if (!empty($fit['statut_source'])): ?>

                                <p class="small text-muted">
                                    <?= htmlspecialchars(
                                        $fit['statut_source']
                                    ) ?>
                                </p>

                            <?php endif; ?>

                            <a
                                href="type.php?fitandremana_id=<?= (int)$fit['id'] ?>"
                                class="btn btn-primary"
                            >
                                Hijery karazana dossiers →
                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>
