<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        d.*,
        t.nom AS type_nom,
        f.id AS fitandremana_id,
        f.nom AS fitandremana_nom,
        sp.id AS sp_id,
        sp.numero_officiel,
        sp.nom AS sp_nom
    FROM dossiers d
    JOIN types_dossiers t ON t.id = d.type_id
    JOIN fitandremana f ON f.id = t.fitandremana_id
    JOIN synodam_paritany sp ON sp.id = f.sp_id
    WHERE d.id = ?
");
$stmt->execute([$id]);
$dossier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dossier) {
    http_response_code(404);
    exit("Dossier tsy hita.");
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function afficher($value): string {
    return trim((string)($value ?? '')) !== '' ? e($value) : '—';
}
?>
<!DOCTYPE html>
<html lang="mg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($dossier['titre']) ?> | Dossier TANY</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial,sans-serif;background:#f3f6fb;color:#172033}
header{background:linear-gradient(120deg,#142b4a,#1769aa);color:white;padding:25px 5%}
header h1{margin:0 0 8px;font-size:25px}
header p{margin:0;color:#e0edff}
.container{max-width:1050px;margin:25px auto;padding:0 18px}
.breadcrumb{font-size:14px;color:#64748b;margin-bottom:22px;line-height:2}
a{color:#1769aa;text-decoration:none;font-weight:600}
.panel{background:white;border-radius:15px;padding:25px;margin-bottom:20px;box-shadow:0 5px 22px #1720330b}
h2{margin:0 0 20px;font-size:21px}
h3{font-size:15px;color:#1769aa;padding-bottom:12px;border-bottom:1px solid #e8eef5;margin:25px 0 15px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.field{padding:13px;background:#f8fafc;border-radius:9px;min-width:0}
.field small{display:block;color:#64748b;margin-bottom:8px}
.field strong{display:block;overflow-wrap:anywhere;white-space:pre-wrap;font-size:15px}
.badge{display:inline-block;padding:6px 11px;background:#e5f0ff;color:#1769aa;border-radius:20px;font-size:13px}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}
.btn{display:inline-block;padding:12px 17px;border-radius:9px;font-size:14px;font-weight:600}
.primary{background:#1769aa;color:white}
.secondary{background:#edf2f7;color:#334155}
@media(max-width:650px){header{padding:22px 18px}.container{padding:0 12px}.panel{padding:18px}.grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<header>
    <h1>Fitantanana ny antontan-taratasy FJKM</h1>
    <p>Détails du dossier foncier</p>
</header>

<main class="container">
    <nav class="breadcrumb">
        <a href="index.php">SP</a> →
        <a href="sp.php?numero=<?= urlencode((string)$dossier['numero_officiel']) ?>">
            SP <?= e($dossier['numero_officiel']) ?>
        </a> →
        <a href="fitandremana.php?sp_id=<?= (int)$dossier['sp_id'] ?>">Fitandremana</a> →
        <a href="type.php?fitandremana_id=<?= (int)$dossier['fitandremana_id'] ?>">
            <?= e($dossier['fitandremana_nom']) ?>
        </a> →
        <a href="dossier.php?type_id=<?= (int)$dossier['type_id'] ?>">Dossiers</a> →
        Consultation
    </nav>

    <section class="panel">
        <h2><?= e($dossier['titre']) ?></h2>

        <p>
            <span class="badge"><?= afficher($dossier['statut']) ?></span>
            &nbsp;
            <span class="badge"><?= afficher($dossier['karazan_tany']) ?></span>
        </p>

        <h3>Fampahalalana ankapobeny</h3>
        <div class="grid">
            <div class="field">
                <small>Anaran'ny dossier</small>
                <strong><?= afficher($dossier['titre']) ?></strong>
            </div>
            <div class="field">
                <small>Laharana Fitandremana</small>
                <strong><?= afficher($dossier['laharana_fitandremana']) ?></strong>
            </div>
            <div class="field">
                <small>Tompon'ny tany</small>
                <strong><?= afficher($dossier['tomponny_tany']) ?></strong>
            </div>
            <div class="field">
                <small>Karazan-tany</small>
                <strong><?= afficher($dossier['karazan_tany']) ?></strong>
            </div>
            <div class="field">
                <small>SP</small>
                <strong><?= e($dossier['numero_officiel']) ?> — <?= e($dossier['sp_nom']) ?></strong>
            </div>
            <div class="field">
                <small>Fitandremana</small>
                <strong><?= e($dossier['fitandremana_nom']) ?></strong>
            </div>
            <div class="field">
                <small>Type de dossier</small>
                <strong><?= e($dossier['type_nom']) ?></strong>
            </div>
            <div class="field">
                <small>Date du dossier</small>
                <strong><?= afficher($dossier['date_dossier']) ?></strong>
            </div>
        </div>

        <h3>Toerana misy ny tany</h3>
        <div class="grid">
            <div class="field">
                <small>Fokontany</small>
                <strong><?= afficher($dossier['fokontany']) ?></strong>
            </div>
            <div class="field">
                <small>Kaominina</small>
                <strong><?= afficher($dossier['kaominina']) ?></strong>
            </div>
            <div class="field">
                <small>Distrika</small>
                <strong><?= afficher($dossier['distrika']) ?></strong>
            </div>
            <div class="field">
                <small>Faritra</small>
                <strong><?= afficher($dossier['faritra']) ?></strong>
            </div>
        </div>

        <h3>Fanazavana fanampiny</h3>
        <div class="field">
            <strong><?= afficher($dossier['description']) ?></strong>
        </div>

        <h3>Historique</h3>
        <div class="grid">
            <div class="field">
                <small>Date de création</small>
                <strong><?= afficher($dossier['date_creation']) ?></strong>
            </div>
            <div class="field">
                <small>Dernière modification</small>
                <strong><?= afficher($dossier['date_modification']) ?></strong>
            </div>
        </div>

        <div class="actions">
            <a class="btn primary" href="modifier_dossier.php?id=<?= (int)$dossier['id'] ?>">
                ✏️ Modifier le dossier
            </a>
<form method="POST" action="supprimer_dossier.php"
      onsubmit="return confirm('Tena hofafana tanteraka ve ity dossier ity sy ny documents rehetra mifandray aminy ?');"
      style="display:inline">
    <input type="hidden" name="id" value="<?= (int)$dossier['id'] ?>">
    <input type="hidden" name="csrf_token"
           value="<?= e($_SESSION['csrf_token']) ?>">
    <button type="submit"
            class="btn"
            style="border:0;background:#dc2626;color:white;cursor:pointer">
        🗑️ Supprimer le dossier
    </button>
</form>
            <a class="btn secondary" href="dossier.php?type_id=<?= (int)$dossier['type_id'] ?>">
                ← Retour à la liste
            </a>
        </div>
    </section>
</main>
</body>
</html>
