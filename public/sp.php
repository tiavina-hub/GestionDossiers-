<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

require_once __DIR__ . '/../config/database.php';

$numero = $_GET['numero'] ?? '';

if ($numero === '') {
    header('Location: index.php');
    exit;
}

/*
 * Récupérer toutes les parties correspondant au numéro officiel.
 * Exemple SP 20 :
 *   IARIVO AVARADRANO 20
 *   MAEVATSARA 20 (1)
 *   MAEVATSARA 20 (2)
 */
$stmt = $pdo->prepare("
    SELECT
        sp.id,
        sp.numero,
        sp.numero_officiel,
        sp.nom,
        COUNT(f.id) AS nombre_fitandremana
    FROM synodam_paritany sp
    LEFT JOIN fitandremana f
        ON f.sp_id = sp.id
    WHERE sp.numero_officiel = ?
    GROUP BY sp.id
    ORDER BY sp.id ASC
");

$stmt->execute([$numero]);
$parties = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$parties) {
    header('Location: index.php');
    exit;
}

/*
 * S'il n'y a qu'une seule partie,
 * on va directement vers ses Fitandremana.
 */
if (count($parties) === 1) {
    header('Location: fitandremana.php?sp_id=' . $parties[0]['id']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="mg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SP <?= htmlspecialchars($numero) ?> - FJKM</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body class="bg-light">

<div class="container pt-3">

    <div class="d-flex justify-content-between align-items-center">

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


<div class="container py-5">

    <div class="mb-4">

        <a
            href="index.php"
            class="btn btn-outline-secondary"
        >
            ← Hiverina
        </a>

    </div>


    <div class="text-center mb-5">

        <h1 class="fw-bold">
            SP <?= htmlspecialchars($numero) ?>
        </h1>

        <p class="text-muted">
            Misafidiana ny Synodam-Paritany
        </p>

    </div>


    <div class="row g-4">

        <?php foreach ($parties as $partie): ?>

            <div class="col-md-6 col-lg-4">

                <div class="card shadow-sm h-100">

                    <div class="card-body">

                        <h4 class="card-title">
                            <?= htmlspecialchars($partie['nom']) ?>
                        </h4>

                        <p class="text-muted">
                            <?= (int)$partie['nombre_fitandremana'] ?>
                            Fitandremana
                        </p>

                        <a
                            href="fitandremana.php?sp_id=<?= (int)$partie['id'] ?>"
                            class="btn btn-primary"
                        >
                            Hijery Fitandremana →
                        </a>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>

</body>
</html>
