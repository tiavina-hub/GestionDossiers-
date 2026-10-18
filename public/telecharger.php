
<?php
require_once __DIR__ . '/../config/auth.php';
require_login();

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Document invalide.');
}

$stmt = $pdo->prepare("
    SELECT nom_original, chemin, type_fichier, taille
    FROM documents
    WHERE id = ?
");
$stmt->execute([$id]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    http_response_code(404);
    exit('Document introuvable.');
}

$racine = realpath(__DIR__ . '/../');
$fichier = realpath(__DIR__ . '/../' . $document['chemin']);
$uploads = realpath(__DIR__ . '/../storage/uploads');

if (
    !$racine ||
    !$fichier ||
    !$uploads ||
    !is_file($fichier) ||
    !str_starts_with($fichier, $uploads . DIRECTORY_SEPARATOR) ||
    !str_starts_with($fichier, $racine . DIRECTORY_SEPARATOR)
) {
    http_response_code(404);
    exit('Fichier introuvable ou chemin invalide.');
}

$nom = basename($document['nom_original']);

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' .
    ($document['type_fichier'] ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($fichier));
header(
    "Content-Disposition: attachment; filename*=UTF-8''" .
    rawurlencode($nom)
);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

readfile($fichier);
exit;
