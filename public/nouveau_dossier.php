<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/historique.php';

$type_id = (int)($_GET['type_id'] ?? $_POST['type_id'] ?? 0);
if ($type_id <= 0) { header('Location: index.php'); exit; }
$stmt = $pdo->prepare("SELECT t.id AS type_id,t.nom AS type_nom,f.id AS fitandremana_id,f.nom AS fitandremana_nom,sp.numero_officiel,sp.nom AS sp_nom FROM types_dossiers t JOIN fitandremana f ON f.id=t.fitandremana_id JOIN synodam_paritany sp ON sp.id=f.sp_id WHERE t.id=?");
$stmt->execute([$type_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$info) { http_response_code(404); exit('Type tsy hita.'); }
$type = strtoupper(trim($info['type_nom']));
if (!in_array($type, ['TANY','TRANO','FIARA'], true)) { http_response_code(400); exit('Karazana dossier tsy tohanana: '.htmlspecialchars($info['type_nom'])); }
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$erreur = '';
$valeurs = array_fill_keys(['laharana_fitandremana','tomponny_tany','fokontany','kaominina','distrika','faritra','karazan_tany','titre','description','mpanofa','toerana','habe','asa','daty_debut','daty_fin','hofany','caution','situation','avenants','fiara','marque_modele','immatriculation','kilometrage','date_rapport','notes'], '');
$lignes = [['numero'=>'1','piece'=>'','cout_remplacement'=>'0','cout_reparation'=>'0','remarque'=>'']];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valide($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Jeton de sécurité invalide. Rechargez la page.');
    }
    foreach ($valeurs as $k => $_) $valeurs[$k] = trim((string)($_POST[$k] ?? ''));
    if ($type === 'FIARA') {
        $raw = $_POST['lignes'] ?? [];
        $lignes = [];
        if (is_array($raw)) foreach ($raw as $l) {
            if (!is_array($l)) continue;
            $piece = trim((string)($l['piece'] ?? ''));
            if ($piece === '') continue;
            $remp = trim((string)($l['cout_remplacement'] ?? '0'));
            $repa = trim((string)($l['cout_reparation'] ?? '0'));
            if (!is_numeric($remp) || !is_numeric($repa) || (float)$remp < 0 || (float)$repa < 0) { $erreur = 'Les coûts doivent être des nombres positifs.'; break; }
            $lignes[] = ['numero'=>trim((string)($l['numero'] ?? '')), 'piece'=>$piece, 'cout_remplacement'=>$remp, 'cout_reparation'=>$repa, 'remarque'=>trim((string)($l['remarque'] ?? ''))];
        }
        if (!$lignes) $lignes = [['numero'=>'1','piece'=>'','cout_remplacement'=>'0','cout_reparation'=>'0','remarque'=>'']];
    }
    if ($valeurs['titre'] === '') $erreur = "Ampidiro ny anaran'ny dossier.";
    elseif ($type === 'TANY' && ($valeurs['tomponny_tany'] === '' || !in_array($valeurs['karazan_tany'], ['Titré','Cadastré','Karatany','Taratasin-totra'], true))) $erreur = "Ampidiro ny tompon'ny tany ary safidio ny karazan-tany.";
    elseif ($type === 'TRANO' && $valeurs['mpanofa'] === '') $erreur = "Ampidiro ny anaran'ny mpanofa.";
    elseif ($type === 'FIARA' && ($valeurs['fiara'] === '' || count(array_filter($lignes, fn($l)=>$l['piece']!==''))===0)) $erreur = "Ampidiro ny mombamomba ny fiara sy farafahakeliny singa iray.";
    elseif ($type === 'TRANO' && $valeurs['daty_debut'] !== '' && $valeurs['daty_fin'] !== '' && $valeurs['daty_fin'] < $valeurs['daty_debut']) $erreur = 'Ny daty fiafarana dia tsy tokony ho alohan’ny daty fanombohana.';
    elseif ($type === 'TRANO' && ((!is_numeric($valeurs['hofany']) && $valeurs['hofany']!=='') || (!is_numeric($valeurs['caution']) && $valeurs['caution']!=='' ) || (float)$valeurs['hofany']<0 || (float)$valeurs['caution']<0)) $erreur = 'Ny hofany sy caution dia tokony ho isa tsara.';
    elseif ($type === 'FIARA' && $valeurs['kilometrage'] !== '' && (!ctype_digit($valeurs['kilometrage']))) $erreur = 'Ny kilométrage dia tokony ho isa manontolo.';
    if ($erreur === '') {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO dossiers (type_id,laharana_fitandremana,tomponny_tany,fokontany,kaominina,distrika,faritra,karazan_tany,titre,description,statut,date_dossier) VALUES (?,?,?,?,?,?,?,?,?,?, 'Mavitrika',CURDATE())");
            $stmt->execute([$type_id,$valeurs['laharana_fitandremana'] ?: null,$type==='TANY' ? ($valeurs['tomponny_tany'] ?: null) : null,$type==='TANY' ? ($valeurs['fokontany'] ?: null) : null,$type==='TANY' ? ($valeurs['kaominina'] ?: null) : null,$type==='TANY' ? ($valeurs['distrika'] ?: null) : null,$type==='TANY' ? ($valeurs['faritra'] ?: null) : null,$type==='TANY' ? ($valeurs['karazan_tany'] ?: null) : null,$valeurs['titre'],$valeurs['description'] ?: null]);
            $id = (int)$pdo->lastInsertId();
            if ($type === 'TRANO') {
                $stmt = $pdo->prepare('INSERT INTO dossiers_trano (dossier_id,mpanofa,toerana,habe,asa,daty_debut,daty_fin,hofany,caution,situation,avenants) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$id,$valeurs['mpanofa'] ?: null,$valeurs['toerana'] ?: null,$valeurs['habe'] ?: null,$valeurs['asa'] ?: null,$valeurs['daty_debut'] ?: null,$valeurs['daty_fin'] ?: null,$valeurs['hofany'] !== '' ? $valeurs['hofany'] : null,$valeurs['caution'] !== '' ? $valeurs['caution'] : null,$valeurs['situation'] ?: null,$valeurs['avenants'] ?: null]);
            } elseif ($type === 'FIARA') {
                $stmt = $pdo->prepare('INSERT INTO dossiers_fiara (dossier_id,fiara,marque_modele,immatriculation,kilometrage,date_rapport,notes) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$id,$valeurs['fiara'],$valeurs['marque_modele'] ?: null,$valeurs['immatriculation'] ?: null,$valeurs['kilometrage'] !== '' ? $valeurs['kilometrage'] : null,$valeurs['date_rapport'] ?: null,$valeurs['notes'] ?: null]);
                $stmt = $pdo->prepare('INSERT INTO lignes_fiara (dossier_id,numero,piece,cout_remplacement,cout_reparation,remarque) VALUES (?,?,?,?,?,?)');
                foreach ($lignes as $l) if ($l['piece'] !== '') $stmt->execute([$id,$l['numero'] !== '' ? (int)$l['numero'] : null,$l['piece'],(float)$l['cout_remplacement'],(float)$l['cout_reparation'],$l['remarque'] ?: null]);
            }
                        enregistrer_action(
                $pdo,
                'Création',
                'Dossier SP',
                $id,
                'Création du dossier « ' . $valeurs['titre'] .
                ' » (' . $type . ') dans ' .
                $info['fitandremana_nom'] .
                ' — SP ' . $info['numero_officiel']
            );

            $pdo->commit();
            header('Location: voir_dossier.php?id='.$id); exit;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($ex->getMessage());
            $erreur = 'Tsy voatahiry ny dossier. Jereo ny angon-drakitra ary andramo indray.';
        }
    }
}
?>
<!DOCTYPE html><html lang="mg"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Hanampy dossier <?= e($type) ?></title>
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
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: var(--cream);
    color: var(--text);
    font-size: 16px;
    line-height: 1.6;
}

header {
    background: linear-gradient(120deg, var(--navy), var(--navy-light));
    color: white;
    padding: 30px 5%;
    border-bottom: 4px solid var(--gold);
}

header h1 {
    margin: 0 0 9px;
    font-family: Georgia, serif;
    font-size: clamp(24px, 3vw, 32px);
}

header p {
    margin: 0;
    color: var(--gold-light);
}

.container {
    max-width: 1050px;
    margin: 28px auto 45px;
    padding: 0 20px;
}

.breadcrumb {
    margin-bottom: 22px;
    font-size: 14px;
    color: var(--muted);
    line-height: 2;
}

a {
    color: var(--navy-light);
    text-decoration: none;
    font-weight: 600;
}

.breadcrumb a:hover {
    color: #987638;
    text-decoration: underline;
}

.panel {
    background: var(--white);
    border: 1px solid var(--border);
    border-top: 4px solid var(--gold);
    border-radius: 17px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(16, 27, 50, .06);
}

.panel h2 {
    margin: 0 0 8px;
    font-family: Georgia, serif;
    font-size: 26px;
    color: var(--navy);
}

.intro {
    color: var(--muted);
    margin: 0 0 25px;
    line-height: 1.7;
}

.section-title {
    font-family: Georgia, serif;
    font-size: 19px;
    color: var(--navy);
    margin: 30px 0 18px;
    padding-bottom: 11px;
    border-bottom: 1px solid var(--border);
    position: relative;
}

.section-title::after {
    content: "";
    display: block;
    width: 65px;
    height: 3px;
    background: var(--gold);
    position: absolute;
    bottom: -2px;
    left: 0;
}

.grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.group {
    margin-bottom: 18px;
    min-width: 0;
}

label {
    display: block;
    font-weight: 650;
    font-size: 14px;
    margin-bottom: 8px;
    color: var(--navy-light);
}

input,
select,
textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d9d6cc;
    border-radius: 9px;
    font: inherit;
    background: var(--white);
    color: var(--text);
    transition: border-color .2s, box-shadow .2s;
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201, 164, 92, .18);
}

input::placeholder,
textarea::placeholder {
    color: #a0a5ae;
}

textarea {
    min-height: 105px;
    resize: vertical;
}

.error {
    background: #fff0f0;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 14px 16px;
    border-radius: 10px;
    margin-bottom: 22px;
}

.required {
    color: #bd4242;
    font-weight: 800;
}

.actions {
    display: flex;
    gap: 13px;
    flex-wrap: wrap;
    margin-top: 28px;
    padding-top: 22px;
    border-top: 1px solid var(--border);
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 13px 21px;
    border: 2px solid var(--gold);
    border-radius: 9px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: .2s ease;
}

.btn-primary {
    background: var(--navy);
    color: white;
}

.btn-primary:hover {
    background: var(--gold);
    color: var(--navy);
}

.btn-secondary {
    background: #f1eee6;
    color: var(--navy);
    border-color: #ddd5c3;
}

.btn-secondary:hover {
    background: var(--gold-light);
    color: var(--navy);
}

.line {
    border: 1px solid var(--border);
    border-left: 4px solid var(--gold);
    background: #fbfaf6;
    padding: 19px;
    border-radius: 11px;
    margin: 14px 0;
}

.line-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
    color: var(--navy);
}

.remove {
    border: 1px solid #efc5c5;
    border-radius: 7px;
    padding: 8px 12px;
    background: #fff0f0;
    color: #991b1b;
    font-weight: 600;
    cursor: pointer;
}

.remove:hover {
    background: #fee2e2;
}

.add {
    border: 1px solid var(--gold);
    border-radius: 8px;
    padding: 11px 16px;
    background: #f3efe4;
    color: var(--navy);
    font-weight: 700;
    cursor: pointer;
    transition: .2s ease;
}

.add:hover {
    background: var(--gold-light);
}

button:focus-visible,
a:focus-visible {
    outline: 3px solid var(--gold);
    outline-offset: 3px;
}

@media (max-width: 650px) {
    header {
        padding: 23px 18px;
    }

    .container {
        padding: 0 12px;
        margin-top: 19px;
    }

    .panel {
        padding: 20px 17px;
    }

    .panel h2 {
        font-size: 23px;
    }

    .grid {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        text-align: center;
    }

    .line {
        padding: 14px;
    }

    .line-head {
        align-items: flex-start;
    }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        transition: none !important;
    }
}
</style></head><body>
<header><h1>Hanampy dossier <?= e($type) ?></h1><p><?= e($info['fitandremana_nom']) ?> — <?= e($info['type_nom']) ?></p></header><main class="container"><nav class="breadcrumb"><a href="index.php">SP</a> → <a href="sp.php?numero=<?= urlencode((string)$info['numero_officiel']) ?>">SP <?= e($info['numero_officiel']) ?></a> → <a href="fitandremana.php?sp_id=<?= (int)$info['fitandremana_id'] ?>">Fitandremana</a> → <a href="type.php?fitandremana_id=<?= (int)$info['fitandremana_id'] ?>"><?= e($info['fitandremana_nom']) ?></a> → <a href="dossier.php?type_id=<?= $type_id ?>">Dossiers</a> → Nouveau</nav><section class="panel"><h2>Fampidirana dossier vaovao</h2><p class="intro">Fenoy ny mombamomba ny dossier. Ny saha misy <span class="required">*</span> dia tsy maintsy fenoina.</p><?php if($erreur!==''):?><div class="error"><?=e($erreur)?></div><?php endif;?>
<form method="post" action="nouveau_dossier.php?type_id=<?=$type_id?>"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>"><input type="hidden" name="type_id" value="<?=$type_id?>"><div class="group"><label for="laharana_fitandremana">Laharana Fitandremana</label><input id="laharana_fitandremana" name="laharana_fitandremana" value="<?=e($valeurs['laharana_fitandremana'])?>" placeholder="Ohatra: 001/2026"></div>
<?php if($type==='TANY'):?><h3 class="section-title">Mombamomba ny tany</h3><div class="group"><label>Tompon'ny tany <span class="required">*</span></label><input name="tomponny_tany" required value="<?=e($valeurs['tomponny_tany'])?>"></div><div class="grid"><?php foreach(['fokontany'=>'Fokontany','kaominina'=>'Kaominina','distrika'=>'Distrika','faritra'=>'Faritra'] as $k=>$lab):?><div class="group"><label><?=e($lab)?></label><input name="<?=$k?>" value="<?=e($valeurs[$k])?>"></div><?php endforeach;?></div><h3 class="section-title">Antontan-taratasy</h3><div class="group"><label>Karazan-tany <span class="required">*</span></label><select name="karazan_tany" required><option value="">-- Safidio --</option><?php foreach(['Titré','Cadastré','Karatany','Taratasin-totra'] as $op):?><option <?= $valeurs['karazan_tany']===$op?'selected':''?>><?=e($op)?></option><?php endforeach;?></select></div>
<?php elseif($type==='TRANO'):?><h3 class="section-title">Mombamomba ny fanofana</h3><div class="grid"><div class="group"><label>Ny Mpanofa <span class="required">*</span></label><input name="mpanofa" required value="<?=e($valeurs['mpanofa'])?>"></div><div class="group"><label>Toerana</label><input name="toerana" value="<?=e($valeurs['toerana'])?>" placeholder="Ohatra: Imm Ifan, RC Imm Ifan"></div><div class="group"><label>Habe</label><input name="habe" value="<?=e($valeurs['habe'])?>" placeholder="Ohatra: 3me & 4éme étage, 38 m²"></div><div class="group"><label>Asa / fampiasana</label><input name="asa" value="<?=e($valeurs['asa'])?>" placeholder="Bureau, commercial..."></div><div class="group"><label>Daty fanombohana</label><input type="date" name="daty_debut" value="<?=e($valeurs['daty_debut'])?>"></div><div class="group"><label>Daty fiafarana</label><input type="date" name="daty_fin" value="<?=e($valeurs['daty_fin'])?>"></div><div class="group"><label>Hofany (Ar)</label><input type="number" min="0" step="0.01" name="hofany" value="<?=e($valeurs['hofany'])?>"></div><div class="group"><label>Caution (Ar)</label><input type="number" min="0" step="0.01" name="caution" value="<?=e($valeurs['caution'])?>"></div></div><div class="group"><label>Situation / fepetra momba ny fifanarahana</label><textarea name="situation" placeholder="Fampitsaharana mialoha, préavis, fepetra..."><?=e($valeurs['situation'])?></textarea></div><div class="group"><label>Avenants / fanavaozana</label><textarea name="avenants" placeholder="AVEN n°8, Aven 4, daty sy vola..."><?=e($valeurs['avenants'])?></textarea></div>
<?php else: ?><h3 class="section-title">Mombamomba ny fiara</h3><div class="grid"><div class="group"><label>Fiara / désignation <span class="required">*</span></label><input name="fiara" required value="<?=e($valeurs['fiara'])?>" placeholder="Ohatra: FORTUNER"></div><div class="group"><label>Marque / modèle</label><input name="marque_modele" value="<?=e($valeurs['marque_modele'])?>"></div><div class="group"><label>Immatriculation</label><input name="immatriculation" value="<?=e($valeurs['immatriculation'])?>" placeholder="3049 TBH"></div><div class="group"><label>Kilométrage</label><input type="number" min="0" step="1" name="kilometrage" value="<?=e($valeurs['kilometrage'])?>"></div><div class="group"><label>Date du rapport</label><input type="date" name="date_rapport" value="<?=e($valeurs['date_rapport'])?>"></div></div><h3 class="section-title">Pièces et réparations</h3><p class="intro">Renseignez le coût de remplacement et/ou de réparation de chaque pièce.</p><div id="lignes"><?php foreach($lignes as $i=>$l):?><div class="line"><div class="line-head"><strong>Pièce <span class="line-number"><?=$i+1?></span></strong><button class="remove" type="button" onclick="this.closest('.line').remove();renumber()">Supprimer</button></div><div class="grid"><div class="group"><label>Lah / numéro</label><input type="number" min="1" name="lignes[<?=$i?>][numero]" value="<?=e($l['numero'])?>"></div><div class="group"><label>FITAOVANA / pièce <span class="required">*</span></label><input name="lignes[<?=$i?>][piece]" value="<?=e($l['piece'])?>" placeholder="AIL A DROITE, CAPOT..."></div><div class="group"><label>SOLOINA — remplacement (Ar)</label><input type="number" min="0" step="0.01" name="lignes[<?=$i?>][cout_remplacement]" value="<?=e($l['cout_remplacement'])?>"></div><div class="group"><label>AMBOARINA — réparation (Ar)</label><input type="number" min="0" step="0.01" name="lignes[<?=$i?>][cout_reparation]" value="<?=e($l['cout_reparation'])?>"></div></div><div class="group"><label>FANAMARIHANA</label><textarea name="lignes[<?=$i?>][remarque]" placeholder="Cassé, réparable, complètement endommagé..."><?=e($l['remarque'])?></textarea></div></div><?php endforeach;?></div><button class="add" type="button" onclick="addLine()">+ Ajouter une pièce</button><div class="group" style="margin-top:18px"><label>Notes générales</label><textarea name="notes" placeholder="Carrosserie, peinture, défauts cachés possibles..."><?=e($valeurs['notes'])?></textarea></div>
<?php endif;?><h3 class="section-title">Informations générales</h3><div class="group"><label>Anaran'ny dossier / titre <span class="required">*</span></label><input name="titre" required value="<?=e($valeurs['titre'])?>" placeholder="<?= $type==='FIARA'?'TATITRA FIARA FORTUNER 3049 TBH':($type==='TRANO'?'Dossier location Imm Ifan':'Tany FJKM...')?>"></div><div class="group"><label>Fanazavana fanampiny</label><textarea name="description"><?=e($valeurs['description'])?></textarea></div><div class="actions"><button class="btn btn-primary" type="submit">💾 Tehirizo ny dossier</button><a class="btn btn-secondary" href="dossier.php?type_id=<?=$type_id?>">Hiverina</a></div></form></section></main>
<script>let lineIndex=<?=count($lignes)?>;function renumber(){document.querySelectorAll('#lignes .line').forEach((el,i)=>el.querySelector('.line-number').textContent=i+1)}function addLine(){const i=lineIndex++;const div=document.createElement('div');div.className='line';div.innerHTML=`<div class="line-head"><strong>Pièce <span class="line-number"></span></strong><button class="remove" type="button" onclick="this.closest('.line').remove();renumber()">Supprimer</button></div><div class="grid"><div class="group"><label>Lah / numéro</label><input type="number" min="1" name="lignes[${i}][numero]"></div><div class="group"><label>FITAOVANA / pièce</label><input name="lignes[${i}][piece]" placeholder="AIL A DROITE, CAPOT..."></div><div class="group"><label>SOLOINA — remplacement (Ar)</label><input type="number" min="0" step="0.01" name="lignes[${i}][cout_remplacement]" value="0"></div><div class="group"><label>AMBOARINA — réparation (Ar)</label><input type="number" min="0" step="0.01" name="lignes[${i}][cout_reparation]" value="0"></div></div><div class="group"><label>FANAMARIHANA</label><textarea name="lignes[${i}][remarque]" placeholder="Cassé, réparable..."></textarea></div>`;document.getElementById('lignes').appendChild(div);renumber()}renumber();</script></body></html>
