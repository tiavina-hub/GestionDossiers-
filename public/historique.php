
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

require_admin();

function h($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

$utilisateur = trim($_GET['utilisateur'] ?? '');
$action = trim($_GET['action'] ?? '');
$date_debut = trim($_GET['date_debut'] ?? '');
$date_fin = trim($_GET['date_fin'] ?? '');

$sql = "
    SELECT
        id,
        utilisateur_id,
        nom_utilisateur,
        action,
        type_element,
        element_id,
        description,
        adresse_ip,
        date_action
    FROM historique_actions
    WHERE 1 = 1
";

$params = [];

if ($utilisateur !== '') {
    $sql .= " AND nom_utilisateur LIKE ?";
    $params[] = "%$utilisateur%";
}

if ($action !== '') {
    $sql .= " AND action = ?";
    $params[] = $action;
}

if ($date_debut !== '') {
    $sql .= " AND date_action >= ?";
    $params[] = $date_debut . ' 00:00:00';
}

if ($date_fin !== '') {
    $sql .= " AND date_action <= ?";
    $params[] = $date_fin . ' 23:59:59';
}

$sql .= " ORDER BY date_action DESC, id DESC LIMIT 300";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$historique = $stmt->fetchAll();

$actionsDisponibles = $pdo->query("
    SELECT DISTINCT action
    FROM historique_actions
    ORDER BY action ASC
")->fetchAll(PDO::FETCH_COLUMN);

$total = $pdo->query("
    SELECT COUNT(*) FROM historique_actions
")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historique des actions | FJKM</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
            color: #263238;
            font-family: Arial, sans-serif;
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 18px 0;
        }

        .container-main {
            max-width: 1400px;
            margin: 35px auto;
            padding: 0 18px;
        }

        .panel {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            margin-bottom: 22px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: 700;
        }

        .table th {
            background: #f1f4f8;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .description {
            min-width: 220px;
            max-width: 350px;
        }

        .badge-action {
            background: #e8eef8;
            color: #23436b;
            padding: 7px 10px;
            border-radius: 6px;
        }

        .muted {
            color: #78838f;
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
        <div>
            <strong>FJKM · Fitantanana</strong>
            <div class="small text-secondary">Journal des actions</div>
        </div>

        <div class="d-flex gap-3 align-items-center">
            <span class="text-secondary">
                <?= h($_SESSION['nom_complet'] ?? $_SESSION['username'] ?? '') ?>
            </span>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                Retour à l'accueil
            </a>
        </div>
    </div>
</header>

<main class="container-main">

    <div class="mb-4">
        <h2>Historique des actions</h2>
        <p class="text-secondary">
            Consultez les opérations effectuées par les utilisateurs.
        </p>
    </div>

    <div class="panel">
        <div class="text-secondary">Nombre total d'actions enregistrées</div>
        <div class="stat-number"><?= number_format((int) $total, 0, ',', ' ') ?></div>
    </div>

    <div class="panel">
        <h5 class="mb-3">Filtrer l'historique</h5>

        <form method="GET" class="row g-3">

            <div class="col-md-3">
                <label class="form-label">Utilisateur</label>
                <input
                    type="text"
                    name="utilisateur"
                    class="form-control"
                    value="<?= h($utilisateur) ?>"
                    placeholder="Nom de l'utilisateur"
                >
            </div>

            <div class="col-md-2">
                <label class="form-label">Action</label>
                <select name="action" class="form-select">
                    <option value="">Toutes les actions</option>
                    <?php foreach ($actionsDisponibles as $a): ?>
                        <option value="<?= h($a) ?>"
                            <?= $action === $a ? 'selected' : '' ?>>
                            <?= h($a) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Date début</label>
                <input
                    type="date"
                    name="date_debut"
                    class="form-control"
                    value="<?= h($date_debut) ?>"
                >
            </div>

            <div class="col-md-2">
                <label class="form-label">Date fin</label>
                <input
                    type="date"
                    name="date_fin"
                    class="form-control"
                    value="<?= h($date_fin) ?>"
                >
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    Filtrer
                </button>

                <a href="historique.php" class="btn btn-outline-secondary">
                    Réinitialiser
                </a>
            </div>

        </form>
    </div>

    <div class="panel">
        <div class="d-flex justify-content-between mb-3">
            <h5>Journal des opérations</h5>
            <span class="text-secondary small">
                Maximum : 300 résultats
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date et heure</th>
                        <th>Utilisateur</th>
                        <th>Action</th>
                        <th>Élément</th>
                        <th>Identifiant</th>
                        <th>Description</th>
                        <th>Adresse IP</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (!$historique): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-secondary">
                            Aucune action enregistrée pour le moment.
                        </td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($historique as $ligne): ?>
                        <tr>
                            <td class="text-nowrap">
                                <?= h(date('d/m/Y H:i:s', strtotime($ligne['date_action']))) ?>
                            </td>

                            <td>
                                <strong><?= h($ligne['nom_utilisateur']) ?></strong>
                                <div class="small muted">
                                    ID : <?= h($ligne['utilisateur_id'] ?? 'Compte supprimé') ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge-action">
                                    <?= h($ligne['action']) ?>
                                </span>
                            </td>

                            <td><?= h($ligne['type_element']) ?></td>

                            <td><?= h($ligne['element_id'] ?? '—') ?></td>

                            <td class="description">
                                <?= h($ligne['description'] ?? '—') ?>
                            </td>

                            <td><?= h($ligne['adresse_ip'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

</body>
</html>
