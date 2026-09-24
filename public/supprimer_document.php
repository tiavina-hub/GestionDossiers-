<?php

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("Document invalide.");
}

$stmt = $pdo->prepare("
    SELECT dossier_id, chemin
    FROM documents
    WHERE id = ?
");

$stmt->execute([$id]);
$document = $stmt->fetch();

if (!$document) {
    die("Document introuvable.");
}

$fichier = __DIR__ . '/../' . $document['chemin'];

if (is_file($fichier)) {
    unlink($fichier);
}

$stmt = $pdo->prepare("
    DELETE FROM documents
    WHERE id = ?
");

$stmt->execute([$id]);

header(
    "Location: dossier.php?id=" .
    $document['dossier_id'] .
    "&document_deleted=1"
);

exit;

