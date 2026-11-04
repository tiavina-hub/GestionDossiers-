<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$categorieId = filter_input(INPUT_GET, 'categorie_id', FILTER_VALIDATE_INT);

if (!$categorieId || $categorieId < 1) {
    http_response_code(400);
    exit('Catégorie invalide.');
}

/* Récupération de la catégorie */
$stmt = $pdo->prepare(
    "SELECT id, nom, description
     FROM foibe_categories
     WHERE id = ?"
);
$stmt->execute([$categorieId]);
$categorie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categorie) {
    http_response_code(404);
    exit('Catégorie Foibe introuvable.');
}

/* Récupération des types de la catégorie */
$stmt = $pdo->prepare(
    "SELECT id, nom, description
     FROM foibe_types_dossiers
     WHERE categorie_id = ?
     ORDER BY nom"
);
$stmt->execute([$categorieId]);
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalDossiers = 0;
$totalDocuments = 0;

$html = '
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 35px 38px; }
    body { font-family: DejaVu Sans, sans-serif; color: #26352d; font-size: 10px; }
    h1 { color: #285744; font-size: 22px; margin-bottom: 5px; }
    h2 { color: #285744; font-size: 16px; border-bottom: 1px solid #285744; padding-bottom: 7px; }
    h3 { color: #285744; font-size: 12px; margin-bottom: 5px; }
    p { line-height: 1.5; }
    .header { border-bottom: 3px solid #285744; padding-bottom: 15px; margin-bottom: 20px; }
    .muted { color: #68766d; }
    .summary { background: #edf3ee; padding: 10px; margin: 15px 0 20px; }
    .type { margin-top: 22px; }
    .dossier { border: 1px solid #dce5dd; padding: 12px; margin: 12px 0; }
    .field { margin: 4px 0; }
    .label { font-weight: bold; color: #285744; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #dce5dd; padding: 6px; text-align: left; }
    th { background: #edf3ee; }
    .empty { color: #68766d; font-style: italic; }
    .footer { margin-top: 25px; border-top: 1px solid #ddd; padding-top: 8px; font-size: 8px; color: #68766d; }
    .page-break { page-break-before: always; }
</style>
</head>
<body>

<div class="header">
    <h1>FJKM — Gestion des dossiers Foibe</h1>
    <h2>' . e($categorie['nom']) . '</h2>
    <p class="muted">' . nl2br(e($categorie['description'])) . '</p>
    <p class="muted">Rapport généré le ' . date('d/m/Y à H:i') . '</p>
</div>
';

foreach ($types as $type) {
    $stmt = $pdo->prepare(
        "SELECT id, titre, description, statut, date_dossier,
                date_creation, date_modification,
                laharana_fitandremana, tomponny_tany,
                fokontany, kaominina, distrika, faritra, karazan_tany
         FROM dossiers
         WHERE foibe_type_id = ?
         ORDER BY date_dossier DESC, id DESC"
    );
    $stmt->execute([(int)$type['id']]);
    $dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalDossiers += count($dossiers);

    $html .= '<div class="type">';
    $html .= '<h2>Type : ' . e($type['nom']) . '</h2>';
    if (!empty($type['description'])) {
        $html .= '<p>' . nl2br(e($type['description'])) . '</p>';
    }

    if (!$dossiers) {
        $html .= '<p class="empty">Aucun dossier dans ce type.</p>';
    }

    foreach ($dossiers as $dossier) {
        $html .= '<div class="dossier">';
        $html .= '<h3>' . e($dossier['titre']) . '</h3>';

        $html .= '<div class="field"><span class="label">Statut :</span> ' . e($dossier['statut']) . '</div>';
        $html .= '<div class="field"><span class="label">Date du dossier :</span> ' . e($dossier['date_dossier']) . '</div>';

        if (!empty($dossier['description'])) {
            $html .= '<div class="field"><span class="label">Description :</span><br>' . nl2br(e($dossier['description'])) . '</div>';
        }

        $html .= '<h3>Informations complémentaires</h3>';

        $champs = [
            'Laharana Fitandremana' => $dossier['laharana_fitandremana'],
            'Tomponny tany' => $dossier['tomponny_tany'],
            'Fokontany' => $dossier['fokontany'],
            'Kaominina' => $dossier['kaominina'],
            'Distrika' => $dossier['distrika'],
            'Faritra' => $dossier['faritra'],
            'Karazan-tany' => $dossier['karazan_tany'],
            'Créé le' => $dossier['date_creation'],
            'Modifié le' => $dossier['date_modification']
        ];

        foreach ($champs as $label => $valeur) {
            if ($valeur !== null && $valeur !== '') {
                $html .= '<div class="field"><span class="label">' . e($label) . ' :</span> ' . e($valeur) . '</div>';
            }
        }

        /* Documents associés */
        $docStmt = $pdo->prepare(
            "SELECT nom_original, type_fichier, taille, date_ajout
             FROM documents
             WHERE dossier_id = ?
             ORDER BY date_ajout DESC, id DESC"
        );
        $docStmt->execute([(int)$dossier['id']]);
        $documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalDocuments += count($documents);

        $html .= '<h3>Documents associés (' . count($documents) . ')</h3>';

        if ($documents) {
            $html .= '<table><thead><tr>
                <th>Nom du fichier</th>
                <th>Type</th>
                <th>Taille</th>
                <th>Date d’ajout</th>
            </tr></thead><tbody>';

            foreach ($documents as $doc) {
                $taille = (int)$doc['taille'];
                $tailleTexte = $taille >= 1048576
                    ? round($taille / 1048576, 2) . ' Mo'
                    : round($taille / 1024, 1) . ' Ko';

                $html .= '<tr>
                    <td>' . e($doc['nom_original']) . '</td>
                    <td>' . e($doc['type_fichier']) . '</td>
                    <td>' . e($tailleTexte) . '</td>
                    <td>' . e($doc['date_ajout']) . '</td>
                </tr>';
            }

            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="empty">Aucun document associé.</p>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';
}

$html .= '
<div class="summary">
    <strong>Récapitulatif de la catégorie</strong><br>
    Nombre de types : ' . count($types) . '<br>
    Nombre de dossiers : ' . $totalDossiers . '<br>
    Nombre de documents associés : ' . $totalDocuments . '
</div>
<div class="footer">
    FJKM — Gestion des dossiers Foibe | Document généré automatiquement
</div>
</body>
</html>
';

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$nomFichier = 'Foibe_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $categorie['nom']) . '.pdf';

$dompdf->stream($nomFichier, ['Attachment' => true]);
exit;
