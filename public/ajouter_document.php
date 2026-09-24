<?php

require_once __DIR__ . '/../config/database.php';

$dossier_id = filter_input(
    INPUT_POST,
    'dossier_id',
    FILTER_VALIDATE_INT
);

if (!$dossier_id) {
    die("Dossier invalide.");
}

/*
 * Vérifier que le dossier existe
 */
$stmt = $pdo->prepare("
    SELECT id, titre
    FROM dossiers
    WHERE id = ?
");

$stmt->execute([$dossier_id]);
$dossier = $stmt->fetch();

if (!$dossier) {
    die("Dossier introuvable.");
}

if (
    !isset($_FILES['documents']) ||
    !is_array($_FILES['documents']['name'])
) {
    header("Location: dossier.php?id=$dossier_id&upload=none");
    exit;
}

$extensionsAutorisees = [
    'pdf',
    'doc',
    'docx',
    'jpg',
    'jpeg',
    'png',
    'xls',
    'xlsx'
];

$mimeAutorises = [
    'application/pdf',

    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

    'image/jpeg',
    'image/png',

    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

$dossierDir =
    __DIR__ .
    '/../storage/uploads/' .
    $dossier_id;

if (!is_dir($dossierDir)) {
    mkdir($dossierDir, 0750, true);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);

$ajoutes = 0;
$erreurs = [];

$nombre = count($_FILES['documents']['name']);

for ($i = 0; $i < $nombre; $i++) {

    $erreurUpload = $_FILES['documents']['error'][$i];

    if ($erreurUpload !== UPLOAD_ERR_OK) {

        $erreurs[] =
            $_FILES['documents']['name'][$i] .
            " : erreur d'upload (" .
            $erreurUpload .
            ")";

        continue;
    }

    $nomOriginal =
        basename(
            $_FILES['documents']['name'][$i]
        );

    $tmp =
        $_FILES['documents']['tmp_name'][$i];

    $taille =
        (int) $_FILES['documents']['size'][$i];

    $extension =
        strtolower(
            pathinfo(
                $nomOriginal,
                PATHINFO_EXTENSION
            )
        );

    $mime = $finfo->file($tmp);

    if (
        !in_array(
            $extension,
            $extensionsAutorisees,
            true
        )
    ) {
        $erreurs[] =
            "$nomOriginal : extension non autorisée";

        continue;
    }

    if (
        !in_array(
            $mime,
            $mimeAutorises,
            true
        )
    ) {
        $erreurs[] =
            "$nomOriginal : type de fichier non autorisé ($mime)";

        continue;
    }

    $nomStockage =
        bin2hex(random_bytes(16)) .
        '.' .
        $extension;

    $destination =
        $dossierDir .
        '/' .
        $nomStockage;

    if (
        !move_uploaded_file(
            $tmp,
            $destination
        )
    ) {
        $erreurs[] =
            "$nomOriginal : impossible de déplacer le fichier";

        continue;
    }

    $chemin =
        'storage/uploads/' .
        $dossier_id .
        '/' .
        $nomStockage;

    $stmt = $pdo->prepare("
        INSERT INTO documents
        (
            dossier_id,
            nom_original,
            nom_stockage,
            chemin,
            type_fichier,
            taille
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $dossier_id,
        $nomOriginal,
        $nomStockage,
        $chemin,
        $mime,
        $taille
    ]);

    $ajoutes++;
}

header(
    "Location: dossier.php?id=" .
    $dossier_id .
    "&upload=" .
    $ajoutes
);

exit;
