<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT d.*,
           COALESCE(ft.nom, t.nom) AS type_nom,
           fc.nom AS foibe_categorie_nom,
           ft.categorie_id AS foibe_categorie_id,
           f.id AS fitandremana_id,
           f.nom AS fitandremana_nom,
           sp.id AS sp_id,
           sp.numero_officiel,
           sp.nom AS sp_nom
    FROM dossiers d
    LEFT JOIN types_dossiers t ON t.id = d.type_id
    LEFT JOIN fitandremana f ON f.id = t.fitandremana_id
    LEFT JOIN synodam_paritany sp ON sp.id = f.sp_id
    LEFT JOIN foibe_types_dossiers ft ON ft.id = d.foibe_type_id
    LEFT JOIN foibe_categories fc ON fc.id = ft.categorie_id
    WHERE d.id = ?
");
$stmt->execute([$id]);
$dossier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dossier) {
    http_response_code(404);
    exit('Dossier tsy hita.');
}

$isFoibe = !empty($dossier['foibe_type_id']);
$typeCode = $isFoibe ? 'FOIBE' : strtoupper(trim((string)($dossier['type_nom'] ?? 'TANY')));
$details = [];
if ($typeCode === 'TRANO') {
    $detailStmt = $pdo->prepare('SELECT * FROM dossiers_trano WHERE dossier_id = ?');
    $detailStmt->execute([$id]);
    $details = $detailStmt->fetch(PDO::FETCH_ASSOC) ?: [];
} elseif ($typeCode === 'FIARA') {
    $detailStmt = $pdo->prepare('SELECT * FROM dossiers_fiara WHERE dossier_id = ?');
    $detailStmt->execute([$id]);
    $details = $detailStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $ligneStmt = $pdo->prepare('SELECT * FROM lignes_fiara WHERE dossier_id = ? ORDER BY numero ASC, id ASC');
    $ligneStmt->execute([$id]);
    $lignesFiara = $ligneStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalSol = 0;
    $totalRep = 0;
    foreach ($lignesFiara as $ligne) { $totalSol += (float)$ligne['cout_remplacement']; $totalRep += (float)$ligne['cout_reparation']; }
}

$stmt = $pdo->prepare("
    SELECT id, nom_original, intitule, description, date_document,
           categorie_document, type_fichier, taille, date_ajout
    FROM documents
    WHERE dossier_id = ?
    ORDER BY date_ajout DESC, id DESC
");
$stmt->execute([$id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function afficher($value): string {
    return trim((string)($value ?? '')) !== '' ? e($value) : '—';
}

function taille_fichier($bytes): string {
    $bytes = (int)$bytes;
    if ($bytes < 1024) return $bytes . ' o';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' Ko';
    return round($bytes / 1048576, 2) . ' Mo';
}

$uploadResult = $_SESSION['upload_result'] ?? null;
unset($_SESSION['upload_result']);

$uploadCount = filter_input(INPUT_GET, 'upload', FILTER_VALIDATE_INT);
$documentDeleted = isset($_GET['document_deleted']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($dossier['titre']) ?> | Dossier <?= e($dossier['type_nom']) ?></title>
<style>

* {
    box-sizing: border-box;
}

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
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: var(--cream);
    color: var(--text);
    line-height: 1.6;
}

header {
    background: linear-gradient(120deg, var(--navy), var(--navy-light));
    color: white;
    padding: 30px 5%;
    border-bottom: 4px solid var(--gold);
}

header h1 {
    margin: 0 0 8px;
    font-family: Georgia, serif;
    font-size: 27px;
    letter-spacing: .3px;
}

header p {
    margin: 0;
    color: var(--gold-light);
}

.container {
    max-width: 1150px;
    margin: 30px auto;
    padding: 0 20px;
}

.breadcrumb {
    font-size: 14px;
    color: var(--muted);
    margin-bottom: 24px;
    line-height: 2;
}

a {
    color: var(--navy-light);
    text-decoration: none;
    font-weight: 600;
    transition: color .2s;
}

a:hover {
    color: #a17b35;
}

.panel {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 15px;
    padding: 28px;
    margin-bottom: 24px;
    box-shadow: 0 5px 22px rgba(16, 27, 50, .06);
}

h2 {
    margin: 0 0 20px;
    color: var(--navy);
    font-family: Georgia, serif;
    font-size: 23px;
}

h3 {
    font-family: Georgia, serif;
    font-size: 18px;
    color: var(--navy);
    padding-bottom: 11px;
    border-bottom: 2px solid var(--gold-light);
    margin: 28px 0 17px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.field {
    padding: 16px;
    background: #faf9f5;
    border: 1px solid var(--border);
    border-left: 3px solid var(--gold);
    border-radius: 9px;
    min-width: 0;
}

.field small {
    display: block;
    color: var(--muted);
    margin-bottom: 7px;
    font-size: 13px;
}

.field strong {
    display: block;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
    font-size: 15px;
    color: var(--text);
}

.badge {
    display: inline-block;
    padding: 6px 12px;
    background: #f1eadb;
    color: #73551f;
    border: 1px solid var(--gold-light);
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    margin-right: 5px;
}

.actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
}

.btn {
    display: inline-block;
    padding: 11px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    text-align: center;
    transition: .2s;
}

.primary {
    background: var(--navy);
    color: white;
}

.primary:hover {
    background: var(--navy-light);
    color: var(--gold-light);
}

.secondary {
    background: #f2f0e9;
    color: var(--navy);
    border-color: var(--border);
}

.secondary:hover {
    background: #e9e3d5;
    color: var(--navy);
}

.danger {
    background: #a83232;
    color: white;
}

.danger:hover {
    background: #842525;
    color: white;
}

.success {
    padding: 13px 16px;
    border-radius: 9px;
    background: #e8f3e8;
    color: #285b32;
    border-left: 4px solid #579564;
    margin: 12px 0 18px;
}

.error {
    padding: 13px 16px;
    border-radius: 9px;
    background: #fbeaea;
    color: #912f2f;
    border-left: 4px solid #c75a5a;
    margin: 12px 0 18px;
}

.upload-form {
    display: flex;
    flex-direction: column;
    gap: 13px;
}

.upload-form label {
    color: var(--navy);
    font-size: 14px;
}

input[type=file],
input[type=text],
input[type=date],
select,
textarea {
    padding: 12px 13px;
    border: 1px solid #d9d5c9;
    border-radius: 8px;
    width: 100%;
    background: #fffefa;
    color: var(--text);
    font: inherit;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}

input:focus,
select:focus,
textarea:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201, 164, 92, .15);
}

textarea {
    resize: vertical;
}

.hint {
    font-size: 13px;
    color: var(--muted);
    line-height: 1.7;
    background: #faf9f5;
    border-radius: 8px;
    padding: 12px 15px;
}

.table-wrap {
    overflow-x: auto;
    border: 1px solid var(--border);
    border-radius: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 570px;
    background: var(--white);
}

th,
td {
    text-align: left;
    padding: 13px 12px;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
    vertical-align: middle;
}

th {
    background: var(--navy);
    color: white;
    font-weight: 600;
}

td {
    color: var(--text);
}

tbody tr:nth-child(even) {
    background: #faf9f5;
}

tbody tr:hover {
    background: #f3efe5;
}

td form {
    display: inline;
}

.inline-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.empty {
    padding: 25px;
    text-align: center;
    color: var(--muted);
    background: #faf9f5;
    border: 1px dashed #d9d1bf;
    border-radius: 9px;
}

@media (max-width: 650px) {
    header {
        padding: 24px 18px;
    }

    header h1 {
        font-size: 22px;
    }

    .container {
        padding: 0 12px;
        margin: 20px auto;
    }

    .panel {
        padding: 18px;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    .actions .btn,
    .actions form,
    .actions form .btn {
        width: 100%;
        text-align: center;
    }

    .inline-actions {
        flex-direction: column;
    }

    .inline-actions .btn,
    .inline-actions form,
    .inline-actions form .btn {
        width: 100%;
        text-align: center;
    }
}

</style>
</head>
<body>
<header>
    <h1>Fitantanana ny antontan-taratasy FJKM</h1>
    <p>Consultation et gestion du dossier <?= e($dossier['type_nom']) ?></p>
</header>

<main class="container">
    <nav class="breadcrumb">
        <?php if ($isFoibe): ?>
            <a href="index.php">Accueil</a> →
            <a href="foibe.php?categorie_id=<?= (int)$dossier['foibe_categorie_id'] ?>">
                <?= e($dossier['foibe_categorie_nom']) ?>
            </a> →
            <?= e($dossier['type_nom']) ?> →
            Consultation
        <?php else: ?>
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
        <?php endif; ?>
    </nav>

    <?php if ($uploadCount !== false && $uploadCount !== null && $uploadCount > 0): ?>
        <div class="success"><?= (int)$uploadCount ?> document(s) ajouté(s).</div>
    <?php endif; ?>

    <?php if ($documentDeleted): ?>
        <div class="success">Document supprimé.</div>
    <?php endif; ?>
    <?php if (isset($_GET['modified'])): ?>
        <div class="success">Dossier modifié avec succès.</div>
    <?php endif; ?>

    <?php if (is_array($uploadResult)): ?>
        <?php if (!empty($uploadResult['ajoutes'])): ?>
            <div class="success">
                <?= (int)$uploadResult['ajoutes'] ?> document(s) ajouté(s) avec succès.
            </div>
        <?php endif; ?>
        <?php if (!empty($uploadResult['erreurs'])): ?>
            <div class="error">
                <strong>Certains fichiers n'ont pas été ajoutés :</strong>
                <ul>
                <?php foreach ($uploadResult['erreurs'] as $erreur): ?>
                    <li><?= e($erreur) ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <section class="panel">
        <h2><?= e($dossier['titre']) ?></h2>
        <p>
            <span class="badge"><?= afficher($dossier['statut']) ?></span>
            <span class="badge"><?= e($dossier['type_nom']) ?></span>
        </p>

        <h3>Fampahalalana ankapobeny</h3>
        <div class="grid">
            <div class="field"><small>Anaran'ny dossier</small><strong><?= afficher($dossier['titre']) ?></strong></div>
            <?php if ($isFoibe): ?>
                <div class="field"><small>Foibe / Catégorie</small><strong><?= afficher($dossier['foibe_categorie_nom']) ?></strong></div>
                <div class="field"><small>Type de dossier</small><strong><?= afficher($dossier['type_nom']) ?></strong></div>
            <?php else: ?>
                <div class="field"><small>Laharana Fitandremana</small><strong><?= afficher($dossier['laharana_fitandremana']) ?></strong></div>
                <div class="field"><small>SP</small><strong><?= e($dossier['numero_officiel']) ?> — <?= e($dossier['sp_nom']) ?></strong></div>
                <div class="field"><small>Fitandremana</small><strong><?= e($dossier['fitandremana_nom']) ?></strong></div>
                <div class="field"><small>Type de dossier</small><strong><?= e($dossier['type_nom']) ?></strong></div>
            <?php endif; ?>
            <div class="field"><small>Date du dossier</small><strong><?= afficher($dossier['date_dossier']) ?></strong></div>
        </div>

        <?php if ($typeCode === 'TRANO'): ?>
            <h3>Fampahalalana momba ny trano / bail</h3>
            <div class="grid">
                <div class="field"><small>Ny Mpanofa</small><strong><?= afficher($details['mpanofa'] ?? null) ?></strong></div>
                <div class="field"><small>Toerana</small><strong><?= afficher($details['toerana'] ?? null) ?></strong></div>
                <div class="field"><small>Habe</small><strong><?= afficher($details['habe'] ?? null) ?></strong></div>
                <div class="field"><small>Asa / fampiasana</small><strong><?= afficher($details['asa'] ?? null) ?></strong></div>
                <div class="field"><small>Daty fanombohana</small><strong><?= afficher($details['daty_debut'] ?? null) ?></strong></div>
                <div class="field"><small>Daty fiafarana</small><strong><?= afficher($details['daty_fin'] ?? null) ?></strong></div>
                <div class="field"><small>Hofany (MGA)</small><strong><?= isset($details['hofany']) && $details['hofany'] !== null ? number_format((float)$details['hofany'], 2, ',', ' ') . ' Ar' : '—' ?></strong></div>
                <div class="field"><small>Caution (MGA)</small><strong><?= isset($details['caution']) && $details['caution'] !== null ? number_format((float)$details['caution'], 2, ',', ' ') . ' Ar' : '—' ?></strong></div>
                <div class="field"><small>Situation / fepetra</small><strong><?= afficher($details['situation'] ?? null) ?></strong></div>
                <div class="field"><small>Avenants / fanavaozana</small><strong><?= afficher($details['avenants'] ?? null) ?></strong></div>
            </div>
        <?php elseif ($typeCode === 'FIARA'): ?>
            <h3>Fampahalalana momba ny fiara</h3>
            <div class="grid">
                <div class="field"><small>Fiara / désignation</small><strong><?= afficher($details['fiara'] ?? null) ?></strong></div>
                <div class="field"><small>Marque / modèle</small><strong><?= afficher($details['marque_modele'] ?? null) ?></strong></div>
                <div class="field"><small>Immatriculation</small><strong><?= afficher($details['immatriculation'] ?? null) ?></strong></div>
                <div class="field"><small>Kilométrage</small><strong><?= isset($details['kilometrage']) && $details['kilometrage'] !== null ? number_format((int)$details['kilometrage'], 0, ',', ' ') . ' km' : '—' ?></strong></div>
                <div class="field"><small>Date du rapport</small><strong><?= afficher($details['date_rapport'] ?? null) ?></strong></div>
                <div class="field"><small>Notes</small><strong><?= afficher($details['notes'] ?? null) ?></strong></div>
            </div>
            <h3>Pièces et réparations</h3>
            <?php if (empty($lignesFiara)): ?>
                <div class="empty">Aucune pièce enregistrée pour ce rapport.</div>
            <?php else: ?>
                <div class="table-wrap"><table><thead><tr><th>Lah.</th><th>FITAOVANA</th><th>SOLOINA (MGA)</th><th>AMBOARINA (MGA)</th><th>FANAMARIHANA</th></tr></thead><tbody>
                <?php foreach ($lignesFiara as $ligne): ?>
                    <tr><td><?= afficher($ligne['numero'] ?? null) ?></td><td><?= afficher($ligne['piece']) ?></td><td><?= number_format((float)$ligne['cout_remplacement'], 2, ',', ' ') ?></td><td><?= number_format((float)$ligne['cout_reparation'], 2, ',', ' ') ?></td><td><?= afficher($ligne['remarque'] ?? null) ?></td></tr>
                <?php endforeach; ?>
                <tr><th colspan="2">TOTAL</th><th><?= number_format($totalSol, 2, ',', ' ') ?> Ar</th><th><?= number_format($totalRep, 2, ',', ' ') ?> Ar</th><td></td></tr>
                </tbody></table></div>
            <?php endif; ?>
        <?php elseif (!$isFoibe): ?>
            <h3>Tompon'ny tany sy toerana</h3>
            <div class="grid">
                <div class="field"><small>Tompon'ny tany</small><strong><?= afficher($dossier['tomponny_tany']) ?></strong></div>
                <div class="field"><small>Karazan-tany</small><strong><?= afficher($dossier['karazan_tany']) ?></strong></div>
                <div class="field"><small>Fokontany</small><strong><?= afficher($dossier['fokontany']) ?></strong></div>
                <div class="field"><small>Kaominina</small><strong><?= afficher($dossier['kaominina']) ?></strong></div>
                <div class="field"><small>Distrika</small><strong><?= afficher($dossier['distrika']) ?></strong></div>
                <div class="field"><small>Faritra</small><strong><?= afficher($dossier['faritra']) ?></strong></div>
            </div>
            <h3>Fanazavana fanampiny</h3>
            <div class="field"><strong><?= afficher($dossier['description']) ?></strong></div>
        <?php endif; ?>

        <h3>Historique</h3>
        <div class="grid">
            <div class="field"><small>Date de création</small><strong><?= afficher($dossier['date_creation']) ?></strong></div>
            <div class="field"><small>Dernière modification</small><strong><?= afficher($dossier['date_modification']) ?></strong></div>
        </div>

        <div class="actions">
            <a class="btn primary" href="modifier_dossier.php?id=<?= (int)$dossier['id'] ?>">✏️ Modifier le dossier</a>

            <?php if (!$isFoibe): ?>
                <a class="btn secondary" href="deplacer_dossier.php?id=<?= (int)$dossier['id'] ?>">↔ Déplacer le dossier</a>
                <a class="btn secondary" href="dossier.php?type_id=<?= (int)$dossier['type_id'] ?>">← Retour à la liste</a>
            <?php else: ?>
                <a class="btn secondary" href="foibe.php?categorie_id=<?= (int)($dossier['foibe_categorie_id'] ?? 0) ?>">← Retour à Foibe</a>
            <?php endif; ?>

            <form method="POST" action="supprimer_dossier.php"
                  onsubmit="return confirm('Tena hofafana tanteraka ve ity dossier ity sy ny documents rehetra mifandray aminy ?');">
                <input type="hidden" name="id" value="<?= (int)$dossier['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <button type="submit" class="btn danger">🗑️ Supprimer le dossier</button>
            </form>
        </div>
    </section>

    <section class="panel">
        <h2>📎 Documents associés</h2>
        <p class="hint">Formats autorisés : PDF, DOC, DOCX, JPG, JPEG, PNG, XLS et XLSX. Maximum annoncé : 10 Mo par fichier et 10 fichiers par envoi.</p>

        <form class="upload-form" method="POST" action="ajouter_document.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="dossier_id" value="<?= (int)$dossier['id'] ?>">
            <input type="hidden" name="MAX_FILE_SIZE" value="10485760">

            <label for="documents"><strong>Choisir un fichier</strong></label>
            <input id="documents" type="file" name="documents[]" required
                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx">

            <label for="intitule"><strong>Intitulé du document *</strong></label>
            <input id="intitule" type="text" name="intitule" maxlength="255"
                   placeholder="Ex. : Certificat de propriété" required>

            <label for="description"><strong>Description / informations</strong></label>
            <textarea id="description" name="description" rows="3"
                      placeholder="Expliquez brièvement le contenu du document..."></textarea>

            <label for="date_document"><strong>Date du document</strong></label>
            <input id="date_document" type="date" name="date_document">

            <label for="categorie_document"><strong>Catégorie du document *</strong></label>
            <select id="categorie_document" name="categorie_document" required>
                <option value="">Choisir une catégorie</option>
                <option value="Document administratif">Document administratif</option>
                <option value="Certificat">Certificat</option>
                <option value="Acte">Acte</option>
                <option value="Contrat">Contrat</option>
                <option value="Justificatif">Justificatif</option>
                <option value="Rapport">Rapport</option>
                <option value="Photo">Photo</option>
                <option value="Autre">Autre</option>
            </select>

            <div>
                <button type="submit" class="btn primary">⬆️ Ajouter le document</button>
            </div>
        </form>

        <h3>Documents enregistrés (<?= count($documents) ?>)</h3>

        <?php if (!$documents): ?>
            <div class="empty">Aucun document n'est encore associé à ce dossier.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nom du fichier</th>
                            <th>Type</th>
                            <th>Taille</th>
                            <th>Date d'ajout</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td>
                                <strong><?= e($doc['intitule'] ?: $doc['nom_original']) ?></strong>
                                <br><small><?= e($doc['nom_original']) ?></small>
                                <?php if (!empty($doc['description'])): ?>
                                    <br><?= nl2br(e($doc['description'])) ?>
                                <?php endif; ?>
                                <br><small>Catégorie : <?= afficher($doc['categorie_document']) ?></small>
                                <br><small>Date du document : <?= afficher($doc['date_document']) ?></small>
                            </td>
                            <td><?= e($doc['type_fichier'] ?: 'Inconnu') ?></td>
                            <td><?= taille_fichier($doc['taille'] ?? 0) ?></td>
                            <td><?= afficher($doc['date_ajout']) ?></td>
                            <td>
                                <div class="inline-actions">
                                    <a class="btn primary" target="_blank" rel="noopener"
   href="voir_document.php?id=<?= (int)$doc['id'] ?>">👁️ Voir</a>
    <a class="btn secondary"
       href="modifier_document.php?id=<?= (int)$doc['id'] ?>">
        ✏️ Modifier
    </a>

<a class="btn secondary"
   href="telecharger.php?id=<?= (int)$doc['id'] ?>">⬇ Télécharger</a>
                                    <form method="POST" action="supprimer_document.php"
                                          onsubmit="return confirm('Supprimer ce document ?');">
                                        <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                        <button type="submit" class="btn danger">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>