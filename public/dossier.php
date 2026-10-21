<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

$type_id = (int)($_GET['type_id'] ?? 0);

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
        sp.id AS sp_id,
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
    exit("Karazana antontan-taratasy tsy hita.");
}

$stmt = $pdo->prepare("
    SELECT *
    FROM dossiers
    WHERE type_id = ?
    ORDER BY date_modification DESC, id DESC
");
$stmt->execute([$type_id]);
$dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($dossiers);

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="mg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dossiers | Fitantanana FJKM</title>
<style>
:root {
    --green: #285744;
    --dark-green: #1d4032;
    --soft-green: #e7efe8;
    --cream: #f7f6f0;
    --text: #282d29;
    --muted: #626b64;
    --border: #dedfd7;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: Arial, "Segoe UI", sans-serif;
    background: var(--cream);
    color: var(--text);
    font-size: 18px;
    line-height: 1.5;
}
a { text-decoration: none; }
header {
    background: var(--green);
    color: white;
    padding: 25px max(20px, calc((100% - 1200px) / 2));
}
header h1 { margin: 0 0 7px; font-size: clamp(24px, 3vw, 32px); }
header p { margin: 0; color: #edf4ed; font-size: 17px; }
.container { max-width: 1250px; margin: 24px auto 45px; padding: 0 20px; }
.breadcrumb { font-size: 15px; color: var(--muted); margin-bottom: 22px; line-height: 2; }
.breadcrumb a { color: var(--dark-green); font-weight: 700; text-decoration: underline; }
.hero {
    background: white; border: 1px solid var(--border); border-radius: 17px;
    padding: 25px; margin-bottom: 24px;
}
.hero h2 { margin: 0 0 10px; font-size: 27px; color: var(--dark-green); }
.hero p { margin: 7px 0; }
.muted { color: var(--muted); }
.stats { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 20px; }
.stat { background: var(--soft-green); border-radius: 12px; padding: 14px 19px; min-width: 155px; }
.stat small { display: block; color: var(--muted); margin-bottom: 5px; font-size: 15px; }
.stat strong { color: var(--dark-green); font-size: 23px; }
.actions { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin: 22px 0; }
.btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-height: 52px; padding: 12px 18px; border-radius: 10px;
    font-size: 17px; font-weight: 700; cursor: pointer; border: 2px solid transparent;
}
.btn-primary { background: var(--green); color: white; }
.btn-primary:hover { background: var(--dark-green); }
.search {
    width: min(100%, 420px); min-height: 52px; padding: 12px 15px;
    border: 2px solid var(--border); border-radius: 10px; font-size: 17px; background: white;
}
.search:focus { outline: 3px solid #d7e5d8; border-color: var(--green); }
.dossier-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 17px; }
.dossier-card {
    display: flex; flex-direction: column; gap: 13px; min-width: 0;
    background: white; border: 2px solid var(--border); border-radius: 15px;
    padding: 21px; color: var(--text); transition: .15s ease;
}
.dossier-card:hover { border-color: var(--green); transform: translateY(-2px); box-shadow: 0 8px 20px #1d403214; }
.dossier-card:focus-visible { outline: 3px solid #789b83; outline-offset: 3px; }
.card-number { color: var(--dark-green); font-size: 23px; font-weight: 800; overflow-wrap: anywhere; }
.card-owner { font-size: 19px; font-weight: 700; overflow-wrap: anywhere; }
.card-details { color: var(--muted); font-size: 16px; display: grid; gap: 5px; }
.card-footer { border-top: 1px solid #ecece6; padding-top: 12px; margin-top: auto; display: flex; justify-content: space-between; gap: 8px; align-items: center; }
.badge { display: inline-block; padding: 5px 9px; border-radius: 8px; background: var(--soft-green); color: var(--dark-green); font-size: 14px; font-weight: 700; }
.open-label { color: var(--dark-green); font-size: 15px; font-weight: 800; }
.empty { background: white; border: 1px solid var(--border); text-align: center; padding: 38px 20px; border-radius: 15px; }
.empty h3 { color: var(--dark-green); margin-top: 0; }
.no-result { display: none; text-align: center; color: var(--muted); padding: 20px; }
footer { text-align: center; padding: 22px; color: var(--muted); font-size: 14px; }
@media (max-width: 900px) { .dossier-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 600px) {
    header { padding: 22px 17px; }
    .container { padding: 0 13px; margin-top: 18px; }
    .hero { padding: 19px; }
    .dossier-grid { grid-template-columns: 1fr; }
    .actions { align-items: stretch; }
    .search { width: 100%; max-width: none; }
    .btn { width: 100%; text-align: center; }
}
@media (prefers-reduced-motion: reduce) { *, *::before, *::after { transition: none !important; } }
</style>
</head>
<body>
<header>
    <h1>Fitantanana ny antontan-taratasy FJKM</h1>
    <p>SP <?= e($info['numero_officiel']) ?> — <?= e($info['sp_nom'] ?? '') ?></p>
</header>

<main class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.php">SP</a> →
        <a href="sp.php?numero=<?= urlencode((string)$info['numero_officiel']) ?>">SP <?= e($info['numero_officiel']) ?></a> →
        <a href="fitandremana.php?sp_id=<?= (int)$info['sp_id'] ?>">Fitandremana</a> →
        <a href="type.php?fitandremana_id=<?= (int)$info['fitandremana_id'] ?>"><?= e($info['fitandremana_nom']) ?></a> →
        Dossiers
    </nav>

    <section class="hero">
        <h2><?= e($info['fitandremana_nom']) ?></h2>
        <p><strong>Karazana :</strong> <?= e($info['type_nom']) ?></p>
        <p class="muted">Lisitra sy fitantanana ireo dossiers mifandraika amin'ity karazana ity.</p>
        <div class="stats">
            <div class="stat">
                <small>Isan'ny dossiers</small>
                <strong><?= number_format($total, 0, ',', ' ') ?></strong>
            </div>
            <div class="stat">
                <small>Karazana dossier</small>
                <strong><?= e($info['type_nom']) ?></strong>
            </div>
        </div>
    </section>

    <div class="actions">
        <a class="btn btn-primary" href="nouveau_dossier.php?type_id=<?= $type_id ?>">
            + Hanampy dossier <?= e($info['type_nom']) ?>
        </a>
        <input class="search" id="search" type="search" placeholder="Karohy laharana, tompony, fokontany..." aria-label="Karohy dossier">
    </div>

    <?php if (!$dossiers): ?>
        <section class="empty">
            <h3>Tsy mbola misy dossier.</h3>
            <p>Tsindrio ny bokotra etsy ambony mba hanampiana dossier vaovao.</p>
        </section>
    <?php else: ?>
        <div class="dossier-grid" id="dossierGrid">
            <?php foreach ($dossiers as $dossier): ?>
                <a class="dossier-card"
                   href="voir_dossier.php?id=<?= (int)$dossier['id'] ?>"
                   data-search="<?= e(implode(' ', [
                       $dossier['laharana_fitandremana'] ?? '',
                       $dossier['tomponny_tany'] ?? '',
                       $dossier['fokontany'] ?? '',
                       $dossier['kaominina'] ?? '',
                       $dossier['distrika'] ?? '',
                       $dossier['faritra'] ?? '',
                       $dossier['karazan_tany'] ?? '',
                       $dossier['titre'] ?? '',
                       $dossier['description'] ?? '',
                       $dossier['statut'] ?? ''
                   ])) ?>">

                    <div class="card-number">
                        <?= e($dossier['laharana_fitandremana'] ?? ('Dossier #' . $dossier['id'])) ?>
                    </div>

                    <div class="card-owner">
                        <?= e($dossier['tomponny_tany'] ?? $dossier['titre'] ?? 'Dossier') ?>
                    </div>

                    <div class="card-details">
                        <?php if (!empty($dossier['fokontany'])): ?>
                            <div>Fokontany : <?= e($dossier['fokontany']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($dossier['kaominina'])): ?>
                            <div>Kaominina : <?= e($dossier['kaominina']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($dossier['karazan_tany'])): ?>
                            <div>Karazan-tany : <?= e($dossier['karazan_tany']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($dossier['date_creation'])): ?>
                            <div>Daty : <?= e($dossier['date_creation']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer">
                        <?php if (!empty($dossier['statut'])): ?>
                            <span class="badge"><?= e($dossier['statut']) ?></span>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>
                        <span class="open-label">Sokafy →</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <p id="noResult" class="no-result">Tsy misy dossier mifanaraka amin'ny fikarohana.</p>
    <?php endif; ?>
</main>

<footer>FJKM · Rafitra fitantanana antontan-taratasy</footer>

<script>
const search = document.getElementById('search');
if (search) {
    search.addEventListener('input', function () {
        const query = this.value.toLocaleLowerCase().trim();
        const cards = document.querySelectorAll('.dossier-card');
        let visible = 0;

        cards.forEach(function (card) {
            const searchable = (card.textContent + ' ' + (card.dataset.search || '')).toLocaleLowerCase();
            const match = searchable.includes(query);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        const noResult = document.getElementById('noResult');
        if (noResult) noResult.style.display = visible === 0 ? 'block' : 'none';
    });
}
</script>
</body>
</html>
