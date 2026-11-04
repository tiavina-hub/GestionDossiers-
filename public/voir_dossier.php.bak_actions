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
    SELECT d.*, t.nom AS type_nom,
           f.id AS fitandremana_id, f.nom AS fitandremana_nom,
           sp.id AS sp_id, sp.numero_officiel, sp.nom AS sp_nom
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
    exit('Dossier tsy hita.');
}

$typeCode = strtoupper(trim((string)($dossier['type_nom'] ?? 'TANY')));
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
    SELECT id, nom_original, type_fichier, taille, date_ajout
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
h3{font-size:16px;color:#1769aa;padding-bottom:12px;border-bottom:1px solid #e8eef5;margin:25px 0 15px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.field{padding:13px;background:#f8fafc;border-radius:9px;min-width:0}
.field small{display:block;color:#64748b;margin-bottom:8px}
.field strong{display:block;overflow-wrap:anywhere;white-space:pre-wrap;font-size:15px}
.badge{display:inline-block;padding:6px 11px;background:#e5f0ff;color:#1769aa;border-radius:20px;font-size:13px}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}
.btn{display:inline-block;padding:11px 15px;border-radius:9px;font-size:14px;font-weight:600;border:0;cursor:pointer}
.primary{background:#1769aa;color:white}
.secondary{background:#edf2f7;color:#334155}
.danger{background:#dc2626;color:white}
.success{padding:12px;border-radius:8px;background:#dcfce7;color:#166534;margin:12px 0}
.error{padding:12px;border-radius:8px;background:#fee2e2;color:#991b1b;margin:12px 0}
.upload-form{display:flex;flex-direction:column;gap:14px}
input[type=file]{padding:12px;border:1px solid #cbd5e1;border-radius:8px;width:100%;background:#f8fafc}
.hint{font-size:13px;color:#64748b;line-height:1.6}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:570px}
th,td{text-align:left;padding:13px 10px;border-bottom:1px solid #e8eef5;font-size:14px;vertical-align:middle}
th{background:#f8fafc;color:#475569}
td form{display:inline}
.inline-actions{display:flex;gap:8px;flex-wrap:wrap}
.empty{padding:22px;text-align:center;color:#64748b;background:#f8fafc;border-radius:9px}
@media(max-width:650px){
 header{padding:22px 18px}.container{padding:0 12px}
 .panel{padding:18px}.grid{grid-template-columns:1fr}
 .actions .btn{width:100%;text-align:center}
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

    <?php if ($uploadCount !== false && $uploadCount !== null && $uploadCount > 0): ?>
        <div class="success"><?= (int)$uploadCount ?> document(s) ajouté(s).</div>
    <?php endif; ?>

    <?php if ($documentDeleted): ?>
        <div class="success">Document supprimé.</div>
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
            <div class="field"><small>Laharana Fitandremana</small><strong><?= afficher($dossier['laharana_fitandremana']) ?></strong></div>
            <div class="field"><small>SP</small><strong><?= e($dossier['numero_officiel']) ?> — <?= e($dossier['sp_nom']) ?></strong></div>
            <div class="field"><small>Fitandremana</small><strong><?= e($dossier['fitandremana_nom']) ?></strong></div>
            <div class="field"><small>Type de dossier</small><strong><?= e($dossier['type_nom']) ?></strong></div>
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
        <?php else: ?>
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
            <a class="btn secondary" href="deplacer_dossier.php?id=<?= (int)$dossier['id'] ?>">↔ Déplacer le dossier</a>
            <a class="btn secondary" href="dossier.php?type_id=<?= (int)$dossier['type_id'] ?>">← Retour à la liste</a>
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
            <label for="documents"><strong>Choisir un ou plusieurs fichiers</strong></label>
            <input id="documents" type="file" name="documents[]" multiple required
                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx">
            <div>
                <button type="submit" class="btn primary">⬆️ Ajouter les documents</button>
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
                            <td><?= e($doc['nom_original']) ?></td>
                            <td><?= e($doc['type_fichier'] ?: 'Inconnu') ?></td>
                            <td><?= taille_fichier($doc['taille'] ?? 0) ?></td>
                            <td><?= afficher($doc['date_ajout']) ?></td>
                            <td>
                                <div class="inline-actions">
                                    <a class="btn primary" target="_blank" rel="noopener"
   href="voir_document.php?id=<?= (int)$doc['id'] ?>">👁️ Voir</a>

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