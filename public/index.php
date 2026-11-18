
<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("
    SELECT
        sp.id,
        sp.numero_officiel,
        COUNT(DISTINCT sp.id) AS nombre_parties,
        COUNT(DISTINCT f.id) AS nombre_fitandremana
    FROM synodam_paritany sp
    LEFT JOIN fitandremana f ON f.sp_id = sp.id
    GROUP BY sp.id, sp.numero_officiel
    ORDER BY CAST(sp.numero_officiel AS UNSIGNED) ASC
");

$sps = $stmt->fetchAll(PDO::FETCH_ASSOC);

$foibeCategories = $pdo->query(
    "SELECT id, nom, description FROM foibe_categories ORDER BY id"
)->fetchAll(PDO::FETCH_ASSOC);

$totalSP = count($sps);
$totalParties = 0;
$totalFitandremana = 0;

foreach ($sps as $sp) {
    $totalParties += (int) $sp['nombre_parties'];
    $totalFitandremana += (int) $sp['nombre_fitandremana'];
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="mg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#101b32">
<title>Dashboard | FJKM Fitantanana</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<style>
:root{
 --navy:#101b32;
 --navy2:#192944;
 --gold:#c9a45c;
 --gold2:#e6d1a0;
 --cream:#f6f4ee;
 --paper:#fffefa;
 --ink:#202b3d;
 --muted:#788092;
 --line:#e8e5dc;
 --green:#315e4c;
 --shadow:0 18px 50px rgba(16,27,50,.08);
}

*{box-sizing:border-box}

body{
 margin:0;
 background:var(--cream);
 color:var(--ink);
 font-family:"DM Sans",Arial,sans-serif;
 font-size:15px;
 min-height:100vh
}

a{text-decoration:none}

.topbar{
 height:88px;
 background:rgba(255,254,250,.94);
 border-bottom:1px solid var(--line);
 display:flex;
 align-items:center;
 position:sticky;
 top:0;
 z-index:20;
 backdrop-filter:blur(16px)
}

.topbar-inner{
 width:min(1500px,100%);
 padding:0 4.2%;
 margin:auto;
 display:flex;
 align-items:center;
 justify-content:space-between;
 gap:20px
}

.brand{
 display:flex;
 align-items:center;
 gap:14px;
 color:var(--navy);
 min-width:0
}

.brand-seal{
 width:52px;
 height:52px;
 display:grid;
 place-items:center;
 border-radius:16px;
 background:var(--navy);
 box-shadow:0 7px 18px rgba(16,27,50,.18);
 flex:none
}

.brand-copy{min-width:0}

.brand-title{
 font-family:"Playfair Display",Georgia,serif;
 font-weight:700;
 font-size:21px;
 letter-spacing:.2px;
 line-height:1.1;
 margin:0
}

.brand-subtitle{
 font-size:10px;
 letter-spacing:1.65px;
 text-transform:uppercase;
 color:#8a7853;
 margin:5px 0 0;
 font-weight:800
}

.account-area{
 display:flex;
 align-items:center;
 gap:12px
}

.account{
 display:flex;
 align-items:center;
 gap:11px;
 padding:7px 13px 7px 8px;
 background:#f7f5ef;
 border:1px solid var(--line);
 border-radius:14px
}

.avatar{
 width:37px;
 height:37px;
 display:grid;
 place-items:center;
 border-radius:11px;
 background:#e9e1cf;
 color:var(--navy);
 font-weight:800;
 font-size:14px
}

.account-name{
 font-size:13px;
 font-weight:800;
 color:var(--navy);
 line-height:1.2;
 max-width:180px;
 overflow:hidden;
 text-overflow:ellipsis;
 white-space:nowrap
}

.account-role{
 font-size:10px;
 color:#8b7650;
 text-transform:uppercase;
 letter-spacing:1.1px;
 font-weight:800;
 margin-top:3px
}

.logout{
 display:inline-flex;
 align-items:center;
 gap:8px;
 padding:11px 15px;
 border:1px solid #ead8d4;
 border-radius:12px;
 color:#8b3e3a;
 background:#fffaf8;
 font-weight:800;
 font-size:12px;
 transition:.2s
}

.logout:hover{
 background:#8b3e3a;
 color:white;
 border-color:#8b3e3a
}

main{
 width:min(1320px,calc(100% - 48px));
 margin:34px auto 70px
}

.hero{
 position:relative;
 overflow:hidden;
 min-height:255px;
 padding:42px 48px;
 border-radius:25px;
 background:linear-gradient(115deg,#101b32 0%,#192944 67%,#29344a 100%);
 color:white;
 box-shadow:0 22px 50px rgba(16,27,50,.16);
 display:flex;
 align-items:center;
 justify-content:space-between;
 gap:30px
}

.hero:before,.hero:after{
 content:"";
 position:absolute;
 border:1px solid rgba(201,164,92,.22);
 border-radius:50%;
 pointer-events:none
}

.hero:before{
 width:390px;
 height:390px;
 right:8%;
 top:-205px
}

.hero:after{
 width:270px;
 height:270px;
 right:13%;
 top:-145px
}

.hero-content{
 position:relative;
 z-index:2;
 max-width:720px
}

.eyebrow{
 display:flex;
 align-items:center;
 gap:10px;
 color:var(--gold2);
 font-size:10px;
 font-weight:800;
 letter-spacing:2.2px;
 text-transform:uppercase;
 margin-bottom:18px
}

.eyebrow:before{
 content:"";
 height:1px;
 width:30px;
 background:var(--gold)
}

.hero h1{
 font-family:"Playfair Display",Georgia,serif;
 font-size:clamp(30px,4vw,46px);
 font-weight:700;
 line-height:1.13;
 margin:0 0 13px;
 letter-spacing:-.5px
}

.hero p{
 max-width:650px;
 color:rgba(255,255,255,.72);
 font-size:14px;
 line-height:1.8;
 margin:0
}

.hero-verse{
 position:relative;
 z-index:2;
 flex:none;
 width:230px;
 border-left:1px solid rgba(201,164,92,.45);
 padding:10px 0 10px 25px;
 color:#e8dfcc
}

.verse-mark{
 font-family:Georgia,serif;
 font-size:38px;
 color:var(--gold);
 line-height:.8
}

.verse-text{
 font-family:"Playfair Display",Georgia,serif;
 font-size:16px;
 line-height:1.5;
 margin:10px 0
}

.verse-ref{
 font-size:10px;
 letter-spacing:1.5px;
 text-transform:uppercase;
 color:var(--gold2);
 font-weight:800
}

.actions{
 display:flex;
 flex-wrap:wrap;
 gap:10px;
 margin:22px 0 28px
}

.action{
 display:inline-flex;
 align-items:center;
 gap:10px;
 padding:12px 17px;
 background:var(--paper);
 border:1px solid var(--line);
 border-radius:12px;
 color:var(--navy);
 font-size:12px;
 font-weight:800;
 box-shadow:0 5px 16px rgba(16,27,50,.035);
 transition:.2s
}

.action:hover{
 transform:translateY(-2px);
 border-color:var(--gold);
 box-shadow:0 10px 22px rgba(16,27,50,.08);
 color:var(--navy)
}

.action.admin{
 background:var(--navy);
 color:white;
 border-color:var(--navy)
}

.action.admin:hover{background:#223452}

.stats{
 display:grid;
 grid-template-columns:repeat(3,minmax(0,1fr));
 gap:16px;
 margin:0 0 38px
}

.stat{
 position:relative;
 overflow:hidden;
 display:flex;
 align-items:center;
 gap:17px;
 padding:22px 24px;
 background:var(--paper);
 border:1px solid var(--line);
 border-radius:17px;
 box-shadow:0 7px 22px rgba(16,27,50,.035)
}

.stat:after{
 content:"";
 position:absolute;
 width:90px;
 height:90px;
 border:1px solid #eee7d8;
 border-radius:50%;
 right:-35px;
 bottom:-48px
}

.stat-symbol{
 width:48px;
 height:48px;
 display:grid;
 place-items:center;
 flex:none;
 border-radius:14px;
 background:#f2ecdf;
 color:#8b6b31;
 font-family:"Playfair Display",Georgia,serif;
 font-size:20px
}

.stat-label{
 font-size:11px;
 color:var(--muted);
 font-weight:700;
 letter-spacing:.2px
}

.stat-number{
 font-family:"Playfair Display",Georgia,serif;
 font-size:31px;
 line-height:1.2;
 color:var(--navy);
 font-weight:700;
 margin-top:4px
}

.content-section{margin-top:38px}

.section-head{
 display:flex;
 justify-content:space-between;
 align-items:end;
 gap:20px;
 margin-bottom:19px
}

.section-kicker{
 font-size:10px;
 text-transform:uppercase;
 letter-spacing:2px;
 color:#9a8252;
 font-weight:800;
 margin:0 0 7px
}

.section-title{
 font-family:"Playfair Display",Georgia,serif;
 font-size:27px;
 color:var(--navy);
 font-weight:700;
 margin:0
}

.search-box{width:min(340px,100%)}

.search-box input{
 width:100%;
 height:47px;
 padding:0 16px;
 border:1px solid #dedbd2;
 border-radius:12px;
 background:var(--paper);
 font:inherit;
 font-size:13px;
 color:var(--ink);
 outline:none;
 transition:.2s
}

.search-box input:focus{
 border-color:var(--gold);
 box-shadow:0 0 0 4px rgba(201,164,92,.13)
}

.sp-grid{
 display:grid;
 grid-template-columns:repeat(4,minmax(0,1fr));
 gap:15px
}

.sp-card{
 position:relative;
 min-height:158px;
 padding:21px 20px 17px;
 background:var(--paper);
 border:1px solid var(--line);
 border-radius:17px;
 color:var(--navy);
 display:flex;
 flex-direction:column;
 justify-content:space-between;
 overflow:hidden;
 transition:transform .22s,border-color .22s,box-shadow .22s
}

.sp-card:before{
 content:"";
 position:absolute;
 top:0;
 left:0;
 width:4px;
 height:100%;
 background:var(--gold);
 transform:scaleY(0);
 transform-origin:bottom;
 transition:transform .22s
}

.sp-card:hover{
 transform:translateY(-4px);
 border-color:#d5c49f;
 box-shadow:var(--shadow);
 color:var(--navy)
}

.sp-card:hover:before{transform:scaleY(1)}

.sp-card:focus-visible{
 outline:3px solid var(--gold);
 outline-offset:3px
}

.sp-card-top{
 display:flex;
 justify-content:space-between;
 align-items:center;
 gap:10px
}

.sp-overline{
 font-size:9px;
 text-transform:uppercase;
 letter-spacing:1.7px;
 color:#a28a5d;
 font-weight:800
}

.sp-index{
 font-family:"Playfair Display",Georgia,serif;
 font-size:13px;
 color:#c7b58e
}

.sp-name{
 font-family:"Playfair Display",Georgia,serif;
 font-size:29px;
 font-weight:700;
 line-height:1.2;
 margin:13px 0 15px
}

.sp-count{
 display:flex;
 align-items:center;
 justify-content:space-between;
 border-top:1px solid #eeeae1;
 padding-top:12px;
 font-size:11px;
 color:var(--muted)
}

.sp-count strong{
 font-size:13px;
 color:var(--navy)
}

.sp-description{
 font-size:12px;
 color:var(--muted);
 line-height:1.5;
 margin:0
}

.foibe-card{min-height:150px}

.foibe-card .sp-name{font-size:21px}

.card-arrow{
 color:#b08e4e;
 font-size:18px
}

.empty-state{
 display:none;
 padding:25px;
 background:var(--paper);
 border:1px dashed #d9d2c2;
 border-radius:14px;
 color:var(--muted);
 text-align:center;
 font-size:13px
}

.footer{
 width:min(1320px,calc(100% - 48px));
 margin:0 auto;
 padding:20px 0 30px;
 border-top:1px solid var(--line);
 display:flex;
 justify-content:space-between;
 gap:15px;
 color:#9296a0;
 font-size:10px;
 letter-spacing:.7px;
 text-transform:uppercase
}

@media(max-width:1050px){
 .sp-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
 .hero-verse{width:200px}
}

@media(max-width:760px){
 .topbar{height:auto;min-height:76px}
 .topbar-inner{padding:12px 18px}
 .brand-seal{width:45px;height:45px}
 .brand-title{font-size:18px}
 .brand-subtitle{font-size:8px}
 .account{padding:5px}
 .account-name{max-width:110px}
 .account-role{font-size:8px}
 .logout{padding:10px;font-size:0}
 .logout svg{margin:0}
 .hero{padding:32px 27px;min-height:0}
 .hero-verse{display:none}
 main{width:calc(100% - 32px);margin:22px auto 45px}
 .stats{grid-template-columns:1fr;gap:10px}
 .stat{padding:17px}
 .sp-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
 .section-head{align-items:stretch;flex-direction:column}
 .search-box{width:100%}
 .footer{width:calc(100% - 32px)}
}

@media(max-width:480px){
 .brand-subtitle{display:none}
 .account-area{gap:6px}
 .account{gap:6px}
 .avatar{width:32px;height:32px}
 .account-name{max-width:82px;font-size:11px}
 .logout{padding:9px}
 .hero h1{font-size:29px}
 .hero{padding:27px 22px}
 .sp-grid{grid-template-columns:1fr}
 .sp-card{min-height:135px}
 .actions{display:grid;grid-template-columns:1fr}
 .action{width:100%}
 .footer{flex-direction:column}
}

@media(prefers-reduced-motion:reduce){
 *,*:before,*:after{
  transition:none!important;
  scroll-behavior:auto!important
 }
}
</style>
</head>

<body>

<header class="topbar">
 <div class="topbar-inner">

  <a class="brand" href="index.php" aria-label="FJKM Fitantanana - Accueil">

   <span class="brand-seal" aria-hidden="true">
    <svg width="32" height="32" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
     <path d="M20 4V36M10 14H30" stroke="#E6D1A0" stroke-width="2.5" stroke-linecap="round"/>
     <path d="M5 29C9 34 14 36 20 36C26 36 31 34 35 29" stroke="#C9A45C" stroke-width="1.2"/>
     <path d="M8 7L20 2L32 7" stroke="#C9A45C" stroke-width="1.2" stroke-linecap="round"/>
    </svg>
   </span>

   <span class="brand-copy">
    <p class="brand-title">FJKM · Fitantanana</p>
    <p class="brand-subtitle">Rafitra fitantanana antontan-taratasy</p>
   </span>

  </a>

  <div class="account-area">

   <div class="account">
    <span class="avatar" aria-hidden="true">
     <?= e(mb_strtoupper(mb_substr((string)($_SESSION['nom_complet'] ?? $_SESSION['username'] ?? 'U'), 0, 1, 'UTF-8'), 'UTF-8')) ?>
    </span>

    <span>
     <span class="account-name d-block">
      <?= e($_SESSION['nom_complet'] ?? $_SESSION['username'] ?? '') ?>
     </span>

     <span class="account-role d-block">
      <?= (($_SESSION['user_role'] ?? '') === 'admin') ? 'Administrateur' : 'Mpampiasa' ?>
     </span>
    </span>
   </div>

   <a href="logout.php" class="logout" aria-label="Se déconnecter">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true">
     <path d="M10 17L15 12L10 7M15 12H3M12 3H19C20.1 3 21 3.9 21 5V19C21 20.1 20.1 21 19 21H12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    Déconnexion
   </a>

  </div>
 </div>
</header>

<main>

 <section class="hero">

  <div class="hero-content">

   <div class="eyebrow">FJKM · Sehatry ny fitantanana</div>

   <h1>Tongasoa eto amin'ny<br>Sampandraharaha Fitantanana Fananana FJKM</h1>
   <h2>DEPARTEMENTA FANANANA, FAMPAMOKARANA & FAMPANDROSOANA ary SOSIALY</h2>
   <p>
    Toerana iray handaminana sy hitantanana ny antontan-taratasy.
    Safidio ny Synodam-Paritany na ny sokajy Foibe tianao hidirana.
   </p>

  </div>

  <aside class="hero-verse">
   <div class="verse-mark">“</div>

   <div class="verse-text">
    Ankino amin'i Jehovah ny asanao, dia ho lavorary izay kasainao.
   </div>

   <div class="verse-ref">Ohabolana 16:3</div>
  </aside>

 </section>

 <nav class="actions" aria-label="Raccourcis">

  <a href="recherche.php" class="action">
   Karohy antontan-taratasy
  </a>

  <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>

   <a href="utilisateurs.php" class="action admin">
    Fitantanana ny mpampiasa
   </a>

   <a href="historique.php" class="action admin">
    Tantaran'ny hetsika
   </a>

  <?php endif; ?>

 </nav>


 <section class="content-section">

  <div class="section-head">

   <div>
    <div class="section-kicker">Faritra sy rafitra</div>
    <h2 class="section-title">Synodam-Paritany</h2>
   </div>

   <label class="search-box">
    <input
     type="search"
     id="spSearch"
     placeholder="Mitady Synodam-Paritany..."
     aria-label="Mitady Synodam-Paritany"
    >
   </label>

  </div>

  <div class="sp-grid" id="spList">

   <?php foreach ($sps as $index => $sp): ?>

    <a
     class="sp-card"
     href="sp.php?numero=<?= urlencode((string) $sp['numero_officiel']) ?>"
     aria-label="Ouvrir SP<?= e($sp['numero_officiel']) ?>"
    >

     <div class="sp-card-top">
      <span class="sp-overline">Synodam-Paritany</span>

      <span class="sp-index">
       <?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?>
      </span>
     </div>

     <div class="sp-name">
      SP<?= e($sp['numero_officiel']) ?>
     </div>

     <div class="sp-count">
      <span>Fitandremana</span>

      <strong>
       <?= number_format((int) $sp['nombre_fitandremana'], 0, ',', ' ') ?>
      </strong>
     </div>

    </a>

   <?php endforeach; ?>

  </div>

  <div class="empty-state mt-3" id="emptyState">
   Tsy misy SP mifanaraka amin'ny fikarohana.
  </div>

 </section>

 <section id="foibe-home-section" class="content-section">

  <div class="section-head">
   <div>
    <div class="section-kicker">Fitantanana foibe</div>
    <h2 class="section-title">Foibe</h2>
   </div>
  </div>

  <div class="sp-grid">

   <?php foreach ($foibeCategories as $index => $categorie): ?>

    <a
     class="sp-card foibe-card"
     href="foibe.php?categorie_id=<?= (int) $categorie['id'] ?>"
    >

     <div class="sp-card-top">
      <span class="sp-overline">Sokajy Foibe</span>
      <span class="card-arrow">↗</span>
     </div>

     <div class="sp-name">
      <?= e($categorie['nom']) ?>
     </div>

     <p class="sp-description">
      <?= e($categorie['description'] ?? '') ?>
     </p>

    </a>

   <?php endforeach; ?>

  </div>

 </section>

</main>

<footer class="footer">
 <span>FJKM · Fitantanana antontan-taratasy</span>
 <span>Ohabolana 16:3</span>
</footer>

<script>
const searchInput = document.getElementById('spSearch');
const spItems = document.querySelectorAll('#spList .sp-card');
const emptyState = document.getElementById('emptyState');

if (searchInput) {

 searchInput.addEventListener('input', function () {

  const query = this.value.trim().toLocaleLowerCase();
  let visible = 0;

  spItems.forEach(function (item) {

   const match = item.textContent.toLocaleLowerCase().includes(query);

   item.style.display = match ? '' : 'none';

   if (match) visible++;

  });

  if (emptyState) {
   emptyState.style.display = visible === 0 ? 'block' : 'none';
  }

 });

}
</script>

</body>
</html>
