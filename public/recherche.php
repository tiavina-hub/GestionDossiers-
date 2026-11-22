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
    --navy: #101b32;
    --navy-light: #293d5d;
    --gold: #c9a45c;
    --gold-light: #e6d1a0;
    --cream: #f6f4ee;
    --white: #fffefa;
    --text: #202b3d;
    --muted: #778091;
    --border: #e8e5dc;
    --shadow: 0 10px 30px rgba(16, 27, 50, .07);
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    background: var(--cream);
    color: var(--text);
    font: 15px/1.6 Arial, sans-serif;
}

header {
    position: relative;
    overflow: hidden;
    padding: 38px clamp(20px, 5vw, 75px);
    color: white;
    background: linear-gradient(120deg, var(--navy), #263b5b);
    border-bottom: 4px solid var(--gold);
}

header::after {
    content: "";
    position: absolute;
    width: 250px;
    height: 250px;
    right: 7%;
    top: -140px;
    border: 1px solid rgba(230, 209, 160, .25);
    border-radius: 50%;
    box-shadow: 0 0 0 25px rgba(230, 209, 160, .04),
                0 0 0 55px rgba(230, 209, 160, .03);
    pointer-events: none;
}

header h1 {
    position: relative;
    z-index: 1;
    margin: 0;
    font: 700 clamp(25px, 4vw, 34px)/1.25 Georgia, serif;
    letter-spacing: .2px;
}

header p {
    position: relative;
    z-index: 1;
    margin: 10px 0 0;
    color: #dce3ed;
    font-size: 15px;
}

main {
    width: min(1200px, 100%);
    margin: 30px auto;
    padding: 0 20px 45px;
}

.nav {
    display: flex;
    gap: 10px;
    margin-bottom: 22px;
    flex-wrap: wrap;
}

.nav a,
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 43px;
    padding: 10px 17px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: var(--white);
    color: var(--navy);
    text-decoration: none;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: .2s ease;
}

.nav a:hover,
.btn:hover {
    border-color: var(--gold);
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(16, 27, 50, .08);
}

.panel {
    margin-bottom: 24px;
    padding: clamp(20px, 3vw, 30px);
    border: 1px solid var(--border);
    border-radius: 15px;
    background: var(--white);
    box-shadow: var(--shadow);
}

form.panel {
    border-top: 4px solid var(--gold);
}

.filters {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 19px;
}

.field label {
    display: block;
    margin-bottom: 7px;
    color: var(--navy);
    font-size: 13px;
    font-weight: 700;
}

.field input,
.field select {
    width: 100%;
    min-height: 47px;
    padding: 11px 13px;
    border: 1px solid #d9dce2;
    border-radius: 9px;
    background: #fff;
    color: var(--text);
    font: inherit;
    outline: none;
    transition: .2s ease;
}

.field input:focus,
.field select:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201, 164, 92, .16);
}

.field.search {
    grid-column: 1 / -1;
}

.field.search input {
    min-height: 53px;
    padding-left: 16px;
    font-size: 15px;
}

.field input::placeholder {
    color: #a0a5ae;
}

.actions {
    display: flex;
    gap: 11px;
    flex-wrap: wrap;
    margin-top: 23px;
    padding-top: 19px;
    border-top: 1px solid var(--border);
}

.btn.primary {
    border-color: var(--navy);
    background: var(--navy);
    color: white;
}

.btn.primary:hover {
    border-color: var(--navy-light);
    background: var(--navy-light);
}

.count {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 17px;
    color: var(--navy);
    font-size: 16px;
    font-weight: 700;
}

.count::before {
    content: "";
    display: inline-block;
    width: 5px;
    height: 23px;
    border-radius: 5px;
    background: var(--gold);
}

.results {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 17px;
}

.result {
    position: relative;
    display: block;
    min-width: 0;
    padding: 22px;
    overflow: hidden;
    border: 1px solid var(--border);
    border-left: 4px solid var(--gold);
    border-radius: 13px;
    background: var(--white);
    color: inherit;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(16, 27, 50, .035);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}

.result:hover {
    transform: translateY(-3px);
    border-color: var(--gold-light);
    box-shadow: 0 12px 25px rgba(16, 27, 50, .09);
}

.result h2 {
    margin: 0 0 13px;
    color: var(--navy);
    font: 700 21px/1.35 Georgia, serif;
    overflow-wrap: anywhere;
}

.meta {
    margin: 7px 0;
    color: var(--muted);
    font-size: 13px;
    overflow-wrap: anywhere;
}

.pill {
    display: inline-flex;
    align-items: center;
    margin: 7px 5px 5px 0;
    padding: 5px 11px;
    border: 1px solid #e5d6b6;
    border-radius: 30px;
    background: #f8f1e2;
    color: #80602b;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .5px;
}

.pill.foibe {
    border-color: #d9d5e8;
    background: #f0eef7;
    color: #554b79;
}

.empty {
    padding: 45px 20px;
    border-top: 4px solid var(--gold);
    color: var(--muted);
    text-align: center;
}

@media (max-width: 850px) {
    .filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .results {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 520px) {
    header {
        padding: 28px 20px;
    }

    main {
        margin-top: 20px;
        padding-right: 14px;
        padding-left: 14px;
    }

    .filters {
        grid-template-columns: 1fr;
    }

    .field.search {
        grid-column: auto;
    }

    .actions .btn {
        width: 100%;
    }

    .result {
        padding: 18px;
    }
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
