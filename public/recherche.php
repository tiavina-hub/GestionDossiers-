<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function extrait($v, int $limite = 180): string {
    $v = (string)($v ?? '');
    if (mb_strlen($v, 'UTF-8') <= $limite) return $v;
    return mb_substr($v, 0, $limite, 'UTF-8') . '…';
}

$q = trim((string)($_GET['q'] ?? ''));
$spId = (int)($_GET['sp_id'] ?? 0);
$fitId = (int)($_GET['fitandremana_id'] ?? 0);
$typeId = (int)($_GET['type_id'] ?? 0);
$statut = trim((string)($_GET['statut'] ?? ''));

$spList = $pdo->query("
    SELECT id, numero_officiel, nom
    FROM synodam_paritany
    ORDER BY CAST(numero_officiel AS UNSIGNED), nom
")->fetchAll(PDO::FETCH_ASSOC);

$fitList = $pdo->query("
    SELECT f.id, f.nom, f.sp_id, sp.numero_officiel
    FROM fitandremana f
    JOIN synodam_paritany sp ON sp.id = f.sp_id
    ORDER BY CAST(sp.numero_officiel AS UNSIGNED), f.nom
")->fetchAll(PDO::FETCH_ASSOC);

$typeList = $pdo->query("
    SELECT t.id, t.nom, t.fitandremana_id,
           f.nom AS fit_nom, sp.numero_officiel
    FROM types_dossiers t
    JOIN fitandremana f ON f.id = t.fitandremana_id
    JOIN synodam_paritany sp ON sp.id = f.sp_id
    ORDER BY CAST(sp.numero_officiel AS UNSIGNED), f.nom, t.nom
")->fetchAll(PDO::FETCH_ASSOC);

/*
 * Recherche générale :
 * - informations des dossiers SP et Foibe
 * - catégories et types Foibe
 * - informations SP / Fitandremana
 * - informations TANY, TRANO et FIARA
 * - noms et fiches descriptives des documents
 */

$conditions = [];
$params = [];

if ($q !== '') {
    $champs = [
        'd.titre',
        'd.description',
        'd.laharana_fitandremana',
        'd.tomponny_tany',
        'd.fokontany',
        'd.kaominina',
        'd.distrika',
        'd.faritra',
        'd.karazan_tany',
        'd.statut',
        'd.date_dossier',
        't.nom',
        'f.nom',
        'sp.nom',
        'sp.numero_officiel',
        'ft.nom',
        'fc.nom',
        'tr.mpanofa',
        'tr.toerana',
        'car.fiara',
        'car.marque_modele',
        'car.immatriculation',
        'doc.nom_original',
        'doc.intitule',
        'doc.description',
        'doc.categorie_document',
        'lf.piece'
    ];

    $conditions[] = '(' . implode(' OR ', array_map(
        fn($champ) => "$champ LIKE ?",
        $champs
    )) . ')';

    foreach ($champs as $champ) {
        $params[] = '%' . $q . '%';
    }
}

if ($spId > 0) {
    $conditions[] = 'sp.id = ?';
    $params[] = $spId;
}

if ($fitId > 0) {
    $conditions[] = 'f.id = ?';
    $params[] = $fitId;
}

if ($typeId > 0) {
    $conditions[] = 't.id = ?';
    $params[] = $typeId;
}

if ($statut !== '' && in_array($statut, ['Mavitrika', 'En cours', 'Archivé'], true)) {
    $conditions[] = 'd.statut = ?';
    $params[] = $statut;
}

$sql = "
SELECT DISTINCT
    d.id,
    d.titre,
    d.description,
    d.statut,
    d.date_dossier,
    d.date_creation,
    d.type_id,
    d.foibe_type_id,

    t.nom AS type_nom,
    f.id AS fitandremana_id,
    f.nom AS fitandremana_nom,
    sp.id AS sp_id,
    sp.numero_officiel,
    sp.nom AS sp_nom,

    ft.nom AS foibe_type_nom,
    fc.id AS foibe_categorie_id,
    fc.nom AS foibe_categorie_nom

FROM dossiers d

LEFT JOIN types_dossiers t ON t.id = d.type_id
LEFT JOIN fitandremana f ON f.id = t.fitandremana_id
LEFT JOIN synodam_paritany sp ON sp.id = f.sp_id

LEFT JOIN foibe_types_dossiers ft ON ft.id = d.foibe_type_id
LEFT JOIN foibe_categories fc ON fc.id = ft.categorie_id

LEFT JOIN dossiers_trano tr ON tr.dossier_id = d.id
LEFT JOIN dossiers_fiara car ON car.dossier_id = d.id
LEFT JOIN lignes_fiara lf ON lf.dossier_id = d.id
LEFT JOIN documents doc ON doc.dossier_id = d.id
";

if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= "
ORDER BY COALESCE(d.date_dossier, DATE(d.date_creation)) DESC, d.id DESC
LIMIT 500
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statuts = ['Mavitrika', 'En cours', 'Archivé'];
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recherche générale des dossiers</title>
<style>
:root {
    --green:#245b43;
    --green2:#397553;
    --cream:#f5f1e6;
    --paper:#fffdf7;
    --ink:#242a25;
    --muted:#68736b;
    --line:#d8dfd4;
}
* {box-sizing:border-box}
body {
    margin:0;
    background:var(--cream);
    color:var(--ink);
    font:16px/1.5 Arial,sans-serif;
}
header {
    background:var(--green);
    color:white;
    padding:24px clamp(16px,4vw,56px);
}
header h1 {margin:0;font-size:30px}
header p {margin:6px 0 0;color:#e5eee7}
main {max-width:1200px;margin:25px auto;padding:0 16px}
.nav {display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap}
.nav a,.btn {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:9px;
    padding:11px 16px;
    text-decoration:none;
    font-weight:bold;
    border:1px solid var(--green);
    color:var(--green);
    background:white;
    cursor:pointer;
}
.panel {
    background:var(--paper);
    border:1px solid var(--line);
    border-radius:15px;
    padding:20px;
    margin-bottom:20px;
}
.filters {
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:14px;
}
.field label {display:block;font-weight:bold;margin-bottom:6px}
.field input,.field select {
    width:100%;
    min-height:46px;
    border:1px solid #bfcabe;
    border-radius:8px;
    background:white;
    padding:10px;
    font:inherit;
}
.field.search {grid-column:1/-1}
.actions {display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
.btn.primary {background:var(--green);color:white}
.count {font-weight:bold;color:var(--green);margin:4px 0 14px}
.results {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:14px;
}
.result {
    display:block;
    background:var(--paper);
    border:1px solid var(--line);
    border-left:6px solid var(--green2);
    border-radius:12px;
    padding:18px;
    text-decoration:none;
    color:inherit;
}
.result:hover {box-shadow:0 7px 18px #18352218}
.result h2 {margin:0 0 9px;font-size:21px;color:var(--green)}
.meta {color:var(--muted);font-size:14px;margin:5px 0}
.pill {
    display:inline-block;
    background:#e4eee4;
    color:var(--green);
    border-radius:99px;
    padding:4px 10px;
    font-size:13px;
    font-weight:bold;
    margin-top:8px;
}
.pill.foibe {background:#e9e5f5;color:#55418a}
.empty {padding:30px;text-align:center;color:var(--muted)}
@media(max-width:850px) {
    .filters {grid-template-columns:repeat(2,minmax(0,1fr))}
    .results {grid-template-columns:1fr}
}
@media(max-width:480px) {
    .filters {grid-template-columns:1fr}
}
</style>
</head>
<body>

<header>
    <h1>🔎 Recherche générale des dossiers</h1>
    <p>Rechercher dans les dossiers SP, Foibe et leurs documents associés.</p>
</header>

<main>
<nav class="nav">
    <a href="index.php">← Accueil</a>
</nav>

<form class="panel" method="get" action="recherche.php">
    <div class="filters">

        <div class="field search">
            <label for="q">Mot-clé à rechercher</label>
            <input
                id="q"
                name="q"
                value="<?=e($q)?>"
                placeholder="Ex. : 24487, propriétaire, titre, terrain, contrat, nom de fichier..."
                autofocus
            >
        </div>

        <div class="field">
            <label for="sp_id">Synodam-Paritany (SP)</label>
            <select id="sp_id" name="sp_id">
                <option value="0">Tous les SP</option>
                <?php foreach($spList as $sp): ?>
                    <option value="<?=(int)$sp['id']?>" <?=$spId===(int)$sp['id']?'selected':''?>>
                        SP <?=e($sp['numero_officiel'])?> — <?=e($sp['nom'])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="fitandremana_id">Fitandremana</label>
            <select id="fitandremana_id" name="fitandremana_id">
                <option value="0">Toutes les Fitandremana</option>
                <?php foreach($fitList as $fit): ?>
                    <option value="<?=(int)$fit['id']?>" <?=$fitId===(int)$fit['id']?'selected':''?>>
                        SP <?=e($fit['numero_officiel'])?> — <?=e($fit['nom'])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="type_id">Type de dossier SP</label>
            <select id="type_id" name="type_id">
                <option value="0">Tous les types SP</option>
                <?php foreach($typeList as $type): ?>
                    <option value="<?=(int)$type['id']?>" <?=$typeId===(int)$type['id']?'selected':''?>>
                        SP <?=e($type['numero_officiel'])?> — <?=e($type['fit_nom'])?> / <?=e($type['nom'])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="statut">Statut</label>
            <select id="statut" name="statut">
                <option value="">Tous les statuts</option>
                <?php foreach($statuts as $s): ?>
                    <option value="<?=e($s)?>" <?=$statut===$s?'selected':''?>>
                        <?=e($s)?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

    </div>

    <div class="actions">
        <button class="btn primary" type="submit">🔎 Rechercher</button>
        <a class="btn" href="recherche.php">Effacer les filtres</a>
    </div>
</form>

<p class="count">
    <?=count($results)?> résultat(s)
    <?=count($results)===500?' — affichage limité aux 500 premiers':''?>
</p>

<?php if(!$results): ?>
    <section class="panel empty">
        Aucun dossier ne correspond à ces critères.
    </section>
<?php else: ?>
    <section class="results">
        <?php foreach($results as $d): ?>
            <?php $isFoibe = !empty($d['foibe_type_id']); ?>

            <a class="result" href="voir_dossier.php?id=<?=(int)$d['id']?>">
                <h2><?=e($d['titre'])?></h2>

                <?php if($isFoibe): ?>
                    <div class="meta">
                        Catégorie Foibe : <?=e($d['foibe_categorie_nom'])?>
                    </div>
                    <div class="meta">
                        Type : <?=e($d['foibe_type_nom'])?>
                    </div>
                    <span class="pill foibe">FOIBE</span>
                <?php else: ?>
                    <div class="meta">
                        SP <?=e($d['numero_officiel'])?> — <?=e($d['sp_nom'])?>
                    </div>
                    <div class="meta">
                        Fitandremana : <?=e($d['fitandremana_nom'])?>
                    </div>
                    <div class="meta">
                        Type : <?=e($d['type_nom'])?>
                    </div>
                    <span class="pill">SP</span>
                <?php endif; ?>

                <div class="meta">
                    Date : <?=e($d['date_dossier'] ?: 'Non renseignée')?>
                    · Statut : <?=e($d['statut'] ?: 'Non renseigné')?>
                </div>

                <?php if(!empty($d['description'])): ?>
                    <div class="meta"><?=e(extrait($d['description']))?></div>
                <?php endif; ?>

                <span class="pill">Ouvrir le dossier →</span>
            </a>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

</main>
</body>
</html>
