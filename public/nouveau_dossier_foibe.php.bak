<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$categorieId = (int)($_GET['categorie_id'] ?? $_POST['categorie_id'] ?? 0);
$typeId = (int)($_POST['foibe_type_id'] ?? 0);
$erreur = '';

$stmt = $pdo->prepare(
    "SELECT id,nom FROM foibe_categories WHERE id = ?"
);
$stmt->execute([$categorieId]);
$categorie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categorie) {
    http_response_code(404);
    exit('Categorie Foibe tsy hita.');
}

$stmt = $pdo->prepare(
    "SELECT id,nom FROM foibe_types_dossiers
     WHERE categorie_id = ? ORDER BY nom"
);
$stmt->execute([$categorieId]);
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titre = trim((string)($_POST['titre'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$statut = trim((string)($_POST['statut'] ?? 'Mavitrika'));
$date = trim((string)($_POST['date_dossier'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Jeton de sécurité invalide. Rechargez la page.');
    }

    $validTypes = array_map(
        fn($t) => (int)$t['id'],
        $types
    );

    if (!in_array($typeId, $validTypes, true)) {
        $erreur = 'Safidio ny karazana dossier marina.';
    } elseif ($titre === '') {
        $erreur = 'Ampidiro ny titre du dossier.';
    } elseif (mb_strlen($titre) > 255) {
        $erreur = 'Lava loatra ny titre (255 caractères maximum).';
    } elseif ($statut === '' || mb_strlen($statut) > 100) {
        $erreur = 'Ampidiro ny statut (100 caractères maximum).';
    } elseif ($date !== '' &&
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $erreur = 'Tsy marina ny daty.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO dossiers
                    (type_id, foibe_type_id, titre, description, statut, date_dossier)
                 VALUES
                    (NULL, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $typeId,
                $titre,
                $description !== '' ? $description : null,
                $statut,
                $date !== '' ? $date : null
            ]);

            $id = (int)$pdo->lastInsertId();
            header('Location: voir_dossier.php?id=' . $id);
            exit;
        } catch (Throwable $ex) {
            error_log($ex->getMessage());
            $erreur = 'Tsy voatahiry ny dossier. Jereo ny logs.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="mg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dossier Foibe vaovao</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:#f5f6f1;color:#26352d}
header{background:#285744;color:white;padding:24px 5%}
main{max-width:800px;margin:28px auto;padding:0 16px}
.panel{background:white;padding:25px;border-radius:14px;border:1px solid #e0e5dd}
label{display:block;font-weight:bold;margin:16px 0 7px}
input,select,textarea{width:100%;padding:12px;border:1px solid #ccd6cc;border-radius:8px;font:inherit}
textarea{min-height:120px}
button,.btn{display:inline-block;margin-top:20px;padding:12px 17px;border:0;border-radius:8px;background:#285744;color:white;text-decoration:none;cursor:pointer;font-weight:bold}
.btn-light{background:#e7efe8;color:#285744}
.error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px}
</style>
</head>
<body>
<header>
    <h1>Dossier Foibe vaovao</h1>
    <p><?= e($categorie['nom']) ?></p>
</header>
<main>
    <p><a class="btn btn-light" href="foibe.php?categorie_id=<?= $categorieId ?>">← Hiverina</a></p>
    <section class="panel">
        <h2>Fampidirana dossier</h2>

        <?php if ($erreur !== ''): ?>
            <div class="error"><?= e($erreur) ?></div>
        <?php endif; ?>

        <?php if (!$types): ?>
            <p>Tsy mbola misy karazana dossier ao amin'ity sokajy ity.
            Mila mamorona karazana aloha.</p>
        <?php else: ?>
        <form method="post" action="nouveau_dossier_foibe.php?categorie_id=<?= $categorieId ?>">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="categorie_id" value="<?= $categorieId ?>">

            <label for="foibe_type_id">Karazana dossier *</label>
            <select id="foibe_type_id" name="foibe_type_id" required>
                <option value="">-- Safidio --</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"
                        <?= $typeId === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= e($t['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="titre">Titre / Anaran'ny dossier *</label>
            <input id="titre" name="titre" maxlength="255" required value="<?= e($titre) ?>">

            <label for="description">Description</label>
            <textarea id="description" name="description"><?= e($description) ?></textarea>

            <label for="statut">Statut</label>
            <select id="statut" name="statut">
                <?php foreach (['Mavitrika','Arsiva','Miandry','Mikatona'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $statut === $s ? 'selected' : '' ?>>
                        <?= e($s) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="date_dossier">Daty</label>
            <input type="date" id="date_dossier" name="date_dossier" value="<?= e($date) ?>">

            <button type="submit">💾 Tehirizo</button>
        </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
