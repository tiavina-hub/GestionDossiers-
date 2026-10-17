<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

require_once __DIR__ . '/../config/database.php';

$type_id = (int)($_GET['type_id'] ?? $_POST['type_id'] ?? 0);

if ($type_id <= 0) {
    header('Location: index.php');
    exit;
}

/* Informations du type */
$stmt = $pdo->prepare("
    SELECT 
        t.id AS type_id,
        t.nom AS type_nom,
        f.id AS fitandremana_id,
        f.nom AS fitandremana_nom,
        sp.numero_officiel,
        sp.nom AS sp_nom
    FROM types_dossiers t
    JOIN fitandremana f ON f.id = t.fitandremana_id
    JOIN synodam_paritany sp ON sp.id = f.sp_id
    WHERE t.id = ?
");
$stmt->execute([$type_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    die("Type tsy hita.");
}

$erreur = '';

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

    if ($titre === '') {
        $erreur = "Ampidiro ny anaran'ny dossier.";
    } elseif (!in_array($karazan, [
        'Titré',
        'Cadastré',
        'Karatany',
        'Taratasin-totra'
    ], true)) {
        $erreur = "Safidio ny karazan-tany.";
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO dossiers (
                type_id,
                laharana_fitandremana,
                tomponny_tany,
                fokontany,
                kaominina,
                distrika,
                faritra,
                karazan_tany,
                titre,
                description,
                statut,
                date_dossier
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Mavitrika', CURDATE())
        ");

        $stmt->execute([
            $type_id,
            $laharana,
            $tomponny,
            $fokontany,
            $kaominina,
            $distrika,
            $faritra,
            $karazan,
            $titre,
            $description
        ]);

        $id = $pdo->lastInsertId();

        header("Location: voir_dossier.php?id=" . $id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="mg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Hanampy dossier TANY</title>

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f8;
}

header {
    background: #1f2937;
    color: white;
    padding: 20px 30px;
}

.container {
    max-width: 900px;
    margin: 30px auto;
    padding: 0 20px;
}

.form {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
}

input, select, textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 7px;
    font-size: 15px;
}

textarea {
    min-height: 120px;
    resize: vertical;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

button {
    background: #2563eb;
    color: white;
    border: none;
    padding: 12px 22px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
}

.erreur {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 20px;
}

@media(max-width:700px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<header>
    <h1>Hanampy dossier TANY</h1>
    <div>
        <?= htmlspecialchars($info['fitandremana_nom']) ?>
    </div>
</header>

<div class="container">

    <div class="form">

        <?php if ($erreur): ?>
            <div class="erreur">
                <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <form method="post">

            <input type="hidden"
                   name="type_id"
                   value="<?= $type_id ?>">

            <div class="form-group">
                <label>Laharana Fitandremana</label>
                <input type="text"
                       name="laharana_fitandremana"
                       placeholder="Ohatra: 001/2026">
            </div>

            <div class="form-group">
                <label>Tompon'ny tany *</label>
                <input type="text"
                       name="tomponny_tany"
                       required>
            </div>

            <div class="grid">

                <div class="form-group">
                    <label>Fokontany</label>
                    <input type="text" name="fokontany">
                </div>

                <div class="form-group">
                    <label>Kaominina</label>
                    <input type="text" name="kaominina">
                </div>

                <div class="form-group">
                    <label>Distrika</label>
                    <input type="text" name="distrika">
                </div>

                <div class="form-group">
                    <label>Faritra</label>
                    <input type="text" name="faritra">
                </div>

            </div>

            <div class="form-group">
                <label>Karazan-tany *</label>

                <select name="karazan_tany" required>

                    <option value="">-- Safidio --</option>

                    <option value="Titré">Titré</option>

                    <option value="Cadastré">Cadastré</option>

                    <option value="Karatany">Karatany</option>

                    <option value="Taratasin-totra">
                        Taratasin-totra
                    </option>

                </select>
            </div>

            <div class="form-group">
                <label>Anaran'ny dossier *</label>

                <input type="text"
                       name="titre"
                       placeholder="Ohatra: Tany FJKM..."
                       required>
            </div>

            <div class="form-group">
                <label>Fanazavana</label>

                <textarea name="description"></textarea>
            </div>

            <button type="submit">
                💾 Tehirizo ny dossier
            </button>

        </form>

    </div>

</div>

</body>
</html>