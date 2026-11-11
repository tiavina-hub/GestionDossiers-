<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Méthode non autorisée.');
}

// Protection CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Jeton de sécurité invalide. Rechargez la page.');
}

$dossierId = filter_input(INPUT_POST, 'dossier_id', FILTER_VALIDATE_INT);

if (!$dossierId || $dossierId < 1) {
    http_response_code(400);
    exit('Dossier invalide.');
}

$stmt = $pdo->prepare('SELECT id FROM dossiers WHERE id = ?');
$stmt->execute([$dossierId]);

if (!$stmt->fetch()) {
    http_response_code(404);
    exit('Dossier introuvable.');
}

if (
    !isset($_FILES['documents']) ||
    !is_array($_FILES['documents']['name'])
) {
    header('Location: dossier.php?type_id=' . $dossierId . '&upload=none');
    exit;
}

$extensionsAutorisees = [
    'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx'
];

$mimeAutorises = [
    'pdf'  => ['application/pdf'],
    'doc'  => ['application/msword'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'xls'  => ['application/vnd.ms-excel', 'application/CDFV2'],
    'xlsx' => [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip'
    ]
];

$tailleMax = 10 * 1024 * 1024; // 10 Mo par fichier
$nombreMax = 10;               // 10 fichiers par envoi

$racineUploads = __DIR__ . '/../storage/uploads';
$dossierDir = $racineUploads . '/' . $dossierId;

if (!is_dir($dossierDir) && !mkdir($dossierDir, 0750, true)) {
    http_response_code(500);
    exit('Impossible de préparer le stockage.');
}

if (!is_writable($dossierDir)) {
    http_response_code(500);
    exit('Le répertoire de stockage n’est pas accessible en écriture.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$ajoutes = 0;
$erreurs = [];
$fichiersDeplaces = [];

$noms = $_FILES['documents']['name'];
$temporaires = $_FILES['documents']['tmp_name'];
$tailles = $_FILES['documents']['size'];
$codes = $_FILES['documents']['error'];

$nombre = count($noms);

if ($nombre > $nombreMax) {
    http_response_code(400);
    exit('Maximum 10 fichiers par envoi.');
}

try {
    $pdo->beginTransaction();

    for ($i = 0; $i < $nombre; $i++) {
        $erreurUpload = $codes[$i] ?? UPLOAD_ERR_NO_FILE;
        $nomOriginal = basename((string)($noms[$i] ?? ''));

        if ($erreurUpload !== UPLOAD_ERR_OK) {
            $erreurs[] = $nomOriginal . ' : erreur de téléversement.';
            continue;
        }

        $tmp = $temporaires[$i] ?? '';
        $taille = (int)($tailles[$i] ?? 0);

        if (
            $nomOriginal === '' ||
            !is_uploaded_file($tmp) ||
            $taille <= 0 ||
            $taille > $tailleMax
        ) {
            $erreurs[] = $nomOriginal . ' : fichier invalide ou trop volumineux.';
            continue;
        }

        $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));

        if (!in_array($extension, $extensionsAutorisees, true)) {
            $erreurs[] = $nomOriginal . ' : extension non autorisée.';
            continue;
        }

        $mime = $finfo->file($tmp);

        if (!in_array($mime, $mimeAutorises[$extension] ?? [], true)) {
            $erreurs[] = $nomOriginal . ' : type de fichier non autorisé.';
            continue;
        }

        $nomStockage = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $dossierDir . '/' . $nomStockage;

        if (!move_uploaded_file($tmp, $destination)) {
            $erreurs[] = $nomOriginal . ' : déplacement impossible.';
            continue;
        }

        $fichiersDeplaces[] = $destination;

        $chemin = 'storage/uploads/' . $dossierId . '/' . $nomStockage;

        $stmt = $pdo->prepare(
            'INSERT INTO documents
            (dossier_id, nom_original, nom_stockage, chemin, type_fichier, taille)
            VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $dossierId,
            $nomOriginal,
            $nomStockage,
            $chemin,
            $mime,
            $taille
        ]);

        $ajoutes++;
    }

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    foreach ($fichiersDeplaces as $fichier) {
        if (is_file($fichier)) {
            @unlink($fichier);
        }
    }

    error_log('Ajout document : ' . $e->getMessage());
    http_response_code(500);
    exit('Une erreur est survenue pendant l’ajout des documents.');
}

// Ne pas exposer les noms de fichiers ni les erreurs dans l’URL.
$_SESSION['upload_result'] = [
    'ajoutes' => $ajoutes,
    'erreurs' => $erreurs
];

header('Location: voir_dossier.php?id=' . $dossierId . '&upload=' . $ajoutes);
exit;
