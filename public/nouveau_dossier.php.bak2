<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

$type_id = (int)($_GET['type_id'] ?? $_POST['type_id'] ?? 0);

if ($type_id <= 0) {
    header('Location: index.php');
    exit;
}

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
    http_response_code(404);
    exit("Type tsy hita.");
}

$erreur = '';
$valeurs = [
    'laharana_fitandremana' => '',
    'tomponny_tany' => '',
    'fokontany' => '',
    'kaominina' => '',
    'distrika' => '',
    'faritra' => '',
    'karazan_tany' => '',
    'titre' => '',
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($valeurs as $champ => $valeur) {
        $valeurs[$champ] = trim((string)($_POST[$champ] ?? ''));
    }

    $karazan = $valeurs['karazan_tany'];
    $typesValides = [
        'Titré',
        'Cadastré',
        'Karatany',
        'Taratasin-totra'
    ];

    if ($valeurs['titre'] === '') {
        $erreur = "Ampidiro ny anaran'ny dossier.";
    } elseif ($valeurs['tomponny_tany'] === '') {
        $erreur = "Ampidiro ny anaran'ny tompon'ny tany.";
    } elseif (!in_array($karazan, $typesValides, true)) {
        $erreur = "Safidio ny karazan-tany.";
    } else {
        try {
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
                $valeurs['laharana_fitandremana'],
                $valeurs['tomponny_tany'],
                $valeurs['fokontany'],
                $valeurs['kaominina'],
                $valeurs['distrika'],
                $valeurs['faritra'],
                $karazan,
                $valeurs['titre'],
                $valeurs['description']
            ]);

            $id = $pdo->lastInsertId();
            header('Location: voir_dossier.php?id=' . $id);
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $erreur = "Tsy voatahiry ny dossier. Jereo ny angon-drakitra ary andramo indray.";
        }
    }
}

function e($valeur): string {
    return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="mg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hanampy dossier TANY</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:#f3f6fb;color:#172033}
header{background:linear-gradient(120deg,#142b4a,#1769aa);color:white;padding:25px 5%}
header h1{margin:0 0 9px;font-size:25px}
header p{margin:0;color:#e0edff}
.container{max-width:950px;margin:25px auto;padding:0 18px}
.breadcrumb{margin-bottom:20px;font-size:14px;color:#64748b;line-height:2}
a{color:#1769aa;text-decoration:none;font-weight:600}
.panel{background:white;border-radius:16px;padding:28px;box-shadow:0 5px 22px #1720330b}
.panel h2{margin:0 0 8px;font-size:22px}
.intro{color:#64748b;margin:0 0 25px;line-height:1.6}
.section-title{font-size:15px;color:#1769aa;margin:25px 0 16px;padding-bottom:10px;border-bottom:1px solid #e8eef5}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.group{margin-bottom:17px}
label{display:block;font-weight:600;font-size:14px;margin-bottom:8px}
input,select,textarea{width:100%;padding:12px 13px;border:1px solid #d6dfeb;border-radius:9px;font:inherit;background:white;color:#172033}
input:focus,select:focus,textarea:focus{outline:2px solid #bfdbfe;border-color:#1769aa}
textarea{min-height:120px;resize:vertical}
.hint{font-size:12px;color:#64748b;margin-top:6px}
.error{background:#fff0f0;border:1px solid #fecaca;color:#991b1b;padding:13px 15px;border-radius:9px;margin-bottom:20px}
.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:25px}
.btn{display:inline-block;padding:12px 18px;border:0;border-radius:9px;font-weight:600;font-size:14px;cursor:pointer}
.btn-primary{background:#1769aa;color:white}
.btn-primary:hover{background:#12548a}
.btn-secondary{background:#edf2f7;color:#334155}
.required{color:#dc2626}
@media(max-width:650px){header{padding:22px 18px}.container{padding:0 12px}.panel{padding:19px}.grid{grid-template-columns:1fr;gap:0}.actions{flex-direction:column}.btn{text-align:center}}
</style>
</head>
<body>
<header>
    <h1>Hanampy dossier TANY</h1>
    <p><?= e($info['fitandremana_nom']) ?> — <?= e($info['type_nom']) ?></p>
</header>

<main class="container">
    <nav class="breadcrumb">
        <a href="index.php">SP</a> →
        <a href="sp.php?numero=<?= urlencode((string)$info['numero_officiel']) ?>">SP <?= e($info['numero_officiel']) ?></a> →
        <a href="fitandremana.php?sp_id=<?= (int)$info['fitandremana_id'] ?>">Fitandremana</a> →
        <a href="type.php?fitandremana_id=<?= (int)$info['fitandremana_id'] ?>"><?= e($info['fitandremana_nom']) ?></a> →
        <a href="dossier.php?type_id=<?= $type_id ?>">Dossiers</a> →
        Nouveau
    </nav>

    <section class="panel">
        <h2>Fampidirana dossier vaovao</h2>
        <p class="intro">Fenoy ny mombamomba ny tany sy ny antontan-taratasy. Ny saha misy <span class="required">*</span> dia tsy maintsy fenoina.</p>

        <?php if ($erreur !== ''): ?>
            <div class="error"><?= e($erreur) ?></div>
        <?php endif; ?>

        <form method="post" action="nouveau_dossier.php?type_id=<?= $type_id ?>">
            <input type="hidden" name="type_id" value="<?= $type_id ?>">

            <h3 class="section-title">Mombamomba ny tany</h3>
            <div class="group">
                <label for="laharana_fitandremana">Laharana Fitandremana</label>
                <input id="laharana_fitandremana" name="laharana_fitandremana"
                       value="<?= e($valeurs['laharana_fitandremana']) ?>"
                       placeholder="Ohatra: 001/2026">
            </div>

            <div class="group">
                <label for="tomponny_tany">Tompon'ny tany <span class="required">*</span></label>
                <input id="tomponny_tany" name="tomponny_tany" required
                       value="<?= e($valeurs['tomponny_tany']) ?>"
                       placeholder="Anarana feno">
            </div>

            <div class="grid">
                <div class="group">
                    <label for="fokontany">Fokontany</label>
                    <input id="fokontany" name="fokontany" value="<?= e($valeurs['fokontany']) ?>">
                </div>
                <div class="group">
                    <label for="kaominina">Kaominina</label>
                    <input id="kaominina" name="kaominina" value="<?= e($valeurs['kaominina']) ?>">
                </div>
                <div class="group">
                    <label for="distrika">Distrika</label>
                    <input id="distrika" name="distrika" value="<?= e($valeurs['distrika']) ?>">
                </div>
                <div class="group">
                    <label for="faritra">Faritra</label>
                    <input id="faritra" name="faritra" value="<?= e($valeurs['faritra']) ?>">
                </div>
            </div>

            <h3 class="section-title">Antontan-taratasy</h3>
            <div class="group">
                <label for="karazan_tany">Karazan-tany <span class="required">*</span></label>
                <select id="karazan_tany" name="karazan_tany" required>
                    <option value="">-- Safidio --</option>
                    <?php foreach (['Titré','Cadastré','Karatany','Taratasin-totra'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $valeurs['karazan_tany'] === $option ? 'selected' : '' ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="group">
                <label for="titre">Anaran'ny dossier <span class="required">*</span></label>
                <input id="titre" name="titre" required
                       value="<?= e($valeurs['titre']) ?>"
                       placeholder="Ohatra: Tany FJKM...">
            </div>

            <div class="group">
                <label for="description">Fanazavana</label>
                <textarea id="description" name="description"
                          placeholder="Fanamarihana na antsipiriany fanampiny..."><?= e($valeurs['description']) ?></textarea>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">💾 Tehirizo ny dossier</button>
                <a class="btn btn-secondary" href="dossier.php?type_id=<?= $type_id ?>">Hiverina</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
