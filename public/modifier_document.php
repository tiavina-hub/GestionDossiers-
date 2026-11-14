<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/historique.php';

require_login();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id || !hash_equals(
        (string)($_SESSION['csrf_token'] ?? ''),
        (string)($_POST['csrf_token'] ?? '')
    )) {
        http_response_code(403);
        exit('Requête invalide ou jeton CSRF incorrect.');
    }

    $intitule = trim((string)($_POST['intitule'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $dateDocument = trim((string)($_POST['date_document'] ?? ''));
    $categorie = trim((string)($_POST['categorie_document'] ?? ''));

    $categoriesAutorisees = [
        'Document administratif',
        'Certificat',
        'Acte',
        'Contrat',
        'Justificatif',
        'Rapport',
        'Photo',
        'Autre'
    ];

    if ($intitule === '' || mb_strlen($intitule) > 255) {
        exit('Veuillez saisir un intitulé valide.');
    }

    if (!in_array($categorie, $categoriesAutorisees, true)) {
        exit('Catégorie de document invalide.');
    }

    if ($dateDocument !== '') {
        $dateValidee = DateTime::createFromFormat('!Y-m-d', $dateDocument);

        if (!$dateValidee || $dateValidee->format('Y-m-d') !== $dateDocument) {
            exit('Date du document invalide.');
        }
    } else {
        $dateDocument = null;
    }

    $description = ($description === '') ? null : $description;

    $stmt = $pdo->prepare("
        SELECT dossier_id
        FROM documents
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        http_response_code(404);
        exit('Document introuvable.');
    }

    $stmt = $pdo->prepare("
        UPDATE documents
        SET intitule = ?,
            description = ?,
            date_document = ?,
            categorie_document = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $intitule,
        $description,
        $dateDocument,
        $categorie,
        $id
    ]);

    enregistrer_action(
        $pdo,
        'Modification',
        'Document',
        (int)$id,
        'Modification du document « ' . $intitule .
        ' » dans le dossier #' . (int)$document['dossier_id']
    );

    header('Location: voir_dossier.php?id=' . (int)$document['dossier_id']);
    exit;
}

if (!$id) {
    http_response_code(400);
    exit('Identifiant de document invalide.');
}

$stmt = $pdo->prepare("
    SELECT id, dossier_id, nom_original, intitule,
           description, date_document, categorie_document
    FROM documents
    WHERE id = ?
");
$stmt->execute([$id]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    http_response_code(404);
    exit('Document introuvable.');
}

$categories = [
    'Document administratif',
    'Certificat',
    'Acte',
    'Contrat',
    'Justificatif',
    'Rapport',
    'Photo',
    'Autre'
];

$dateAffichee = $document['date_document'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifier les informations du document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px 15px;
            color: #222;
        }
        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,.08);
        }
        h2 { margin-top: 0; }
        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
        }
        input, textarea, select {
            box-sizing: border-box;
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font: inherit;
        }
        textarea { min-height: 110px; resize: vertical; }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 22px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 11px 17px;
            border: 0;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-weight: bold;
        }
        .primary { background: #1769aa; color: white; }
        .secondary { background: #e5e7eb; color: #222; }
        .filename { color: #555; overflow-wrap: anywhere; }
    </style>
</head>
<body>

<div class="container">
    <h2>Modifier les informations du document</h2>

    <p class="filename">
        Fichier : <strong><?= h($document['nom_original']) ?></strong>
    </p>

    <form method="POST">
        <input type="hidden" name="id" value="<?= (int)$document['id'] ?>">
        <input type="hidden" name="csrf_token"
               value="<?= h($_SESSION['csrf_token'] ?? '') ?>">

        <label for="intitule">Intitulé du document *</label>
        <input
            type="text"
            id="intitule"
            name="intitule"
            maxlength="255"
            value="<?= h($document['intitule']) ?>"
            required
        >

        <label for="description">Description</label>
        <textarea id="description" name="description"><?= h($document['description']) ?></textarea>

        <label for="date_document">Date du document</label>
        <input
            type="date"
            id="date_document"
            name="date_document"
            value="<?= h($dateAffichee) ?>"
        >

        <label for="categorie_document">Catégorie *</label>
        <select id="categorie_document" name="categorie_document" required>
            <option value="">-- Choisir une catégorie --</option>
            <?php foreach ($categories as $categorie): ?>
                <option value="<?= h($categorie) ?>"
                    <?= ($document['categorie_document'] === $categorie) ? 'selected' : '' ?>>
                    <?= h($categorie) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="actions">
            <button type="submit" class="btn primary">
                Enregistrer les modifications
            </button>

            <a class="btn secondary"
               href="voir_dossier.php?id=<?= (int)$document['dossier_id'] ?>">
                Annuler
            </a>
        </div>
    </form>
</div>

</body>
</html>
