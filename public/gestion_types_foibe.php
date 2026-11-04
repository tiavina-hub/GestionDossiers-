<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_admin();
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$message = '';
$erreur = '';

$categories = $pdo->query("
    SELECT id, nom
    FROM foibe_categories
    ORDER BY id
")->fetchAll(PDO::FETCH_ASSOC);

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$typeEdit = null;

if ($editId && $editId > 0) {
    $stmt = $pdo->prepare("
        SELECT id, categorie_id, nom, description
        FROM foibe_types_dossiers
        WHERE id = ?
    ");
    $stmt->execute([$editId]);
    $typeEdit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$typeEdit) {
        $erreur = "Le type demandé n'existe pas.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Jeton de sécurité invalide.');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'ajouter' || $action === 'modifier') {
            $categorieId = filter_var(
                $_POST['categorie_id'] ?? null,
                FILTER_VALIDATE_INT
            );
            $nom = trim((string)($_POST['nom'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));

            if (!$categorieId || $categorieId < 1) {
                throw new Exception("Choisis une catégorie valide.");
            }

            if ($nom === '') {
                throw new Exception("Le nom du type est obligatoire.");
            }

            $check = $pdo->prepare(
                "SELECT COUNT(*) FROM foibe_categories WHERE id = ?"
            );
            $check->execute([$categorieId]);

            if (!$check->fetchColumn()) {
                throw new Exception("Cette catégorie n'existe pas.");
            }

            if ($action === 'ajouter') {
                $stmt = $pdo->prepare("
                    INSERT INTO foibe_types_dossiers
                        (categorie_id, nom, description)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $categorieId,
                    $nom,
                    $description !== '' ? $description : null
                ]);

                header('Location: gestion_types_foibe.php?success=added');
                exit;
            }

            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new Exception("Identifiant de type invalide.");
            }

            $stmt = $pdo->prepare("
                UPDATE foibe_types_dossiers
                SET categorie_id = ?, nom = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $categorieId,
                $nom,
                $description !== '' ? $description : null,
                $id
            ]);

            header('Location: gestion_types_foibe.php?success=updated');
            exit;
        }

        if ($action === 'supprimer') {
            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new Exception("Identifiant invalide.");
            }

            $check = $pdo->prepare("
                SELECT COUNT(*)
                FROM dossiers
                WHERE foibe_type_id = ?
            ");
            $check->execute([$id]);

            if ((int)$check->fetchColumn() > 0) {
                throw new Exception(
                    "Impossible de supprimer ce type : des dossiers lui sont associés. Modifie d'abord leur type."
                );
            }

            $stmt = $pdo->prepare("
                DELETE FROM foibe_types_dossiers
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            header('Location: gestion_types_foibe.php?success=deleted');
            exit;
        }

        throw new Exception("Action non reconnue.");

    } catch (PDOException $ex) {
        if ($ex->getCode() === '23000') {
            $erreur = "Ce type existe déjà dans cette catégorie.";
        } else {
            $erreur = "Erreur lors de l'opération en base de données.";
        }
    } catch (Exception $ex) {
        $erreur = $ex->getMessage();
    }
}

if (isset($_GET['success'])) {
    $messages = [
        'added' => 'Type ajouté avec succès.',
        'updated' => 'Type modifié avec succès.',
        'deleted' => 'Type supprimé avec succès.'
    ];
    $message = $messages[$_GET['success']] ?? '';
}

$types = $pdo->query("
    SELECT
        t.id,
        t.categorie_id,
        t.nom,
        t.description,
        c.nom AS categorie_nom,
        (
            SELECT COUNT(*)
            FROM dossiers d
            WHERE d.foibe_type_id = t.id
        ) AS nombre_dossiers
    FROM foibe_types_dossiers t
    JOIN foibe_categories c ON c.id = t.categorie_id
    ORDER BY c.id, t.nom
")->fetchAll(PDO::FETCH_ASSOC);

$selectedCategory = $typeEdit['categorie_id'] ?? '';
$selectedName = $typeEdit['nom'] ?? '';
$selectedDescription = $typeEdit['description'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gestion des types Foibe</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f3f6f4;color:#25352e;font-family:Arial,sans-serif}
header{background:#245541;color:white;padding:25px 5%}
header h1{margin:0 0 8px;font-size:25px}
header p{margin:0;color:#e0eee6}
.container{max-width:1050px;margin:28px auto;padding:0 16px}
.panel{background:white;padding:25px;border-radius:12px;margin-bottom:22px;box-shadow:0 4px 18px #0000000b}
h2{font-size:20px;margin-top:0}
label{display:block;font-weight:600;margin-bottom:7px}
input,select,textarea{width:100%;padding:11px;border:1px solid #cbd5ce;border-radius:7px;font-size:14px;margin-bottom:16px;background:white}
textarea{resize:vertical}
button,.btn{display:inline-block;border:0;border-radius:7px;padding:11px 15px;font-weight:600;cursor:pointer;text-decoration:none;font-size:14px}
.primary{background:#245541;color:white}
.secondary{background:#e8f0eb;color:#245541}
.danger{background:#fee2e2;color:#991b1b}
.success{padding:13px;background:#dcfce7;color:#166534;border-radius:7px;margin-bottom:15px}
.error{padding:13px;background:#fee2e2;color:#991b1b;border-radius:7px;margin-bottom:15px}
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:12px 9px;border-bottom:1px solid #e5ebe7;font-size:14px;vertical-align:middle}
th{background:#f5f8f6}
.actions{display:flex;gap:7px;flex-wrap:wrap}
.actions form{margin:0}
.category{margin-top:25px;color:#245541}
.empty{padding:20px;background:#f6f8f6;border-radius:8px;color:#64756b}
@media(max-width:650px){.panel{padding:16px}.table-wrap{overflow-x:auto}table{min-width:600px}}
</style>
</head>
<body>

<header>
    <h1>Gestion des types de dossiers Foibe</h1>
    <p>Administration des catégories et des types de dossiers</p>
</header>

<main class="container">

    <p>
        <a class="btn secondary" href="foibe.php">← Retour à Foibe</a>
    </p>

    <?php if ($message): ?>
        <div class="success"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <div class="error"><?= e($erreur) ?></div>
    <?php endif; ?>

    <section class="panel">
        <h2><?= $typeEdit ? 'Modifier un type de dossier' : 'Ajouter un type de dossier' ?></h2>

        <form method="POST">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION['csrf_token']) ?>">

            <input type="hidden" name="action"
                   value="<?= $typeEdit ? 'modifier' : 'ajouter' ?>">

            <?php if ($typeEdit): ?>
                <input type="hidden" name="id"
                       value="<?= (int)$typeEdit['id'] ?>">
            <?php endif; ?>

            <label for="categorie_id">Catégorie *</label>
            <select name="categorie_id" id="categorie_id" required>
                <option value="">Choisir une catégorie</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"
                        <?= (string)$selectedCategory === (string)$cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="nom">Nom du type de dossier *</label>
            <input type="text" id="nom" name="nom" maxlength="150"
                   required value="<?= e($selectedName) ?>"
                   placeholder="Exemple : Dossier administratif">

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"
                      placeholder="Description facultative"><?= e($selectedDescription) ?></textarea>

            <button class="primary" type="submit">
                <?= $typeEdit ? 'Enregistrer les modifications' : '+ Ajouter le type' ?>
            </button>

            <?php if ($typeEdit): ?>
                <a class="btn secondary" href="gestion_types_foibe.php">Annuler</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <h2>Types de dossiers enregistrés</h2>

        <?php if (!$types): ?>
            <div class="empty">Aucun type de dossier enregistré.</div>
        <?php else: ?>

            <?php foreach ($categories as $cat): ?>
                <?php
                $typesCategorie = array_filter(
                    $types,
                    fn($t) => (int)$t['categorie_id'] === (int)$cat['id']
                );
                ?>

                <h3 class="category"><?= e($cat['nom']) ?></h3>

                <?php if (!$typesCategorie): ?>
                    <div class="empty">Aucun type dans cette catégorie.</div>
                <?php else: ?>
                    <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom du type</th>
                                <th>Description</th>
                                <th>Dossiers associés</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($typesCategorie as $type): ?>
                            <tr>
                                <td><strong><?= e($type['nom']) ?></strong></td>
                                <td><?= e($type['description'] ?: '—') ?></td>
                                <td><?= (int)$type['nombre_dossiers'] ?></td>
                                <td>
                                    <div class="actions">
                                        <a class="btn secondary"
                                           href="gestion_types_foibe.php?edit=<?= (int)$type['id'] ?>">
                                            Modifier
                                        </a>

                                        <form method="POST"
                                              onsubmit="return confirm('Supprimer ce type de dossier ?')">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= e($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action"
                                                   value="supprimer">
                                            <input type="hidden" name="id"
                                                   value="<?= (int)$type['id'] ?>">
                                            <button class="danger" type="submit">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>

</main>
</body>
</html>
