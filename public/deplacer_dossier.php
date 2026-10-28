<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { http_response_code(400); exit('Dossier invalide.'); }

$stmt = $pdo->prepare("
    SELECT d.id, d.titre, d.type_id, t.nom AS type_nom,
           f.nom AS fit_nom, f.id AS fit_id,
           sp.numero_officiel, sp.nom AS sp_nom
    FROM dossiers d
    JOIN types_dossiers t ON t.id=d.type_id
    JOIN fitandremana f ON f.id=t.fitandremana_id
    JOIN synodam_paritany sp ON sp.id=f.sp_id
    WHERE d.id=?
");
$stmt->execute([$id]);
$dossier = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$dossier) { http_response_code(404); exit('Dossier introuvable.'); }

$typeActuel = strtoupper(trim($dossier['type_nom']));
if (!in_array($typeActuel, ['TANY','TRANO','FIARA'], true)) {
    http_response_code(400);
    exit('Le type actuel du dossier n’est pas pris en charge.');
}

$message = '';
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403); exit('Jeton de sécurité invalide. Rechargez la page.');
    }
    $nouveauTypeId = filter_input(INPUT_POST, 'type_id', FILTER_VALIDATE_INT);
    if (!$nouveauTypeId || $nouveauTypeId < 1) {
        $erreur = 'Sélectionnez une destination valide.';
    } else {
        $stmt = $pdo->prepare("
            SELECT t.id, t.nom, f.nom AS fit_nom, sp.numero_officiel, sp.nom AS sp_nom
            FROM types_dossiers t
            JOIN fitandremana f ON f.id=t.fitandremana_id
            JOIN synodam_paritany sp ON sp.id=f.sp_id
            WHERE t.id=? AND UPPER(TRIM(t.nom))=?
        ");
        $stmt->execute([$nouveauTypeId, $typeActuel]);
        $destination = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$destination) {
            $erreur = 'Le déplacement est limité aux catégories de même type (TANY, TRANO ou FIARA), afin de conserver les informations spécifiques.';
        } elseif ($nouveauTypeId === (int)$dossier['type_id']) {
            $erreur = 'Ce dossier se trouve déjà dans cette destination.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE dossiers SET type_id=? WHERE id=?');
                $stmt->execute([$nouveauTypeId, $id]);
                header('Location: voir_dossier.php?id=' . $id . '&moved=1');
                exit;
            } catch (Throwable $ex) {
                error_log('Déplacement dossier: ' . $ex->getMessage());
                $erreur = 'Impossible de déplacer ce dossier. Réessayez.';
            }
        }
    }
}

$stmt = $pdo->prepare("
    SELECT t.id, t.nom AS type_nom, f.nom AS fit_nom,
           sp.numero_officiel, sp.nom AS sp_nom
    FROM types_dossiers t
    JOIN fitandremana f ON f.id=t.fitandremana_id
    JOIN synodam_paritany sp ON sp.id=f.sp_id
    WHERE UPPER(TRIM(t.nom))=?
    ORDER BY CAST(sp.numero_officiel AS UNSIGNED), f.nom
");
$stmt->execute([$typeActuel]);
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Déplacer un dossier</title>
<style>
:root{--green:#245b43;--green2:#397553;--cream:#f5f1e6;--paper:#fffdf7;--ink:#242a25;--line:#d8dfd4}
*{box-sizing:border-box}body{margin:0;background:var(--cream);color:var(--ink);font:18px/1.5 Arial,sans-serif}header{background:var(--green);color:white;padding:24px clamp(16px,4vw,52px)}header h1{margin:0;font-size:clamp(27px,3vw,36px)}main{max-width:850px;margin:26px auto;padding:0 16px}.card{background:var(--paper);border:1px solid var(--line);border-radius:16px;padding:clamp(18px,3vw,30px);box-shadow:0 6px 18px #153b2410}.muted{color:#66736a}.field label{display:block;font-weight:700;margin:20px 0 8px}.field select{width:100%;min-height:54px;padding:12px;border:1px solid #bdcabe;border-radius:10px;font:inherit;background:white}.btn{display:inline-block;border-radius:10px;padding:13px 18px;text-decoration:none;font-weight:700;font-size:17px;border:1px solid var(--green);cursor:pointer}.primary{background:var(--green);color:white}.secondary{background:white;color:var(--green)}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}.alert{padding:13px 16px;border-radius:10px;margin-bottom:16px}.danger{background:#f8e7e2;color:#7c2f20}.note{background:#e8efe5;border-radius:10px;padding:14px;margin-top:15px;font-size:16px}
</style></head><body>
<header><h1>Déplacer un dossier</h1><div>Changer son Fitandremana sans perdre les informations propres à son type.</div></header>
<main><div class="card">
<p class="muted"><strong>Dossier :</strong> <?=e($dossier['titre'])?><br><strong>Emplacement actuel :</strong> SP <?=e($dossier['numero_officiel'])?> — <?=e($dossier['sp_nom'])?> / <?=e($dossier['fit_nom'])?><br><strong>Catégorie :</strong> <?=e($typeActuel)?></p>
<?php if($erreur):?><div class="alert danger"><?=e($erreur)?></div><?php endif;?>
<form method="post" action="deplacer_dossier.php?id=<?=$id?>">
<input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
<div class="field"><label for="type_id">Nouvelle destination</label><select name="type_id" id="type_id" required><option value="">-- Choisir une Fitandremana --</option><?php foreach($destinations as $dest):?><option value="<?=(int)$dest['id']?>" <?= (int)$dest['id']===(int)$dossier['type_id']?'selected':'' ?>>SP <?=e($dest['numero_officiel'])?> — <?=e($dest['sp_nom'])?> / <?=e($dest['fit_nom'])?> / <?=e($dest['type_nom'])?></option><?php endforeach;?></select></div>
<div class="note">Le déplacement conserve le type <?=e($typeActuel)?>. Les catégories TANY, TRANO et FIARA ne sont pas interchangeables afin d’éviter la perte ou l’incohérence des données spécifiques.</div>
<div class="actions"><button class="btn primary" type="submit">Déplacer le dossier</button><a class="btn secondary" href="voir_dossier.php?id=<?=$id?>">Annuler</a></div>
</form></div></main></body></html>
