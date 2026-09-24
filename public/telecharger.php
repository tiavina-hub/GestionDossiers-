<?php

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("Document invalide.");
}

$stmt = $pdo->prepare("
    SELECT nom_original, chemin, type_fichier, taille
    FROM documents
    WHERE id = ?
");

$stmt->execute([$id]);
$document = $stmt->fetch();

if (!$document) {
    die("Document introuvable.");
}

$fichier = __DIR__ . '/../' . $document['chemin'];

if (!is_file($fichier)) {
    die("Fichier introuvable.");
}

header(
    'Content-Type: ' .
    ($document['type_fichier'] ?: 'application/octet-stream')
);

header(
    'Content-Disposition: attachment; filename="' .
    basename($document['nom_original']) .
    '"'
);

header(
    'Content-Length: ' .
    filesize($fichier)
);

readfile($fichier);
exit;
