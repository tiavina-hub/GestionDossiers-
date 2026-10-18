
<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Document invalide.');
}

$stmt = $pdo->prepare(
    "SELECT nom_original, chemin, type_fichier
     FROM documents WHERE id = ?"
);
$stmt->execute([$id]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    http_response_code(404);
    exit('Document introuvable.');
}

$uploads = realpath(__DIR__ . '/../storage/uploads');
$fichier = realpath(__DIR__ . '/../' . $document['chemin']);

if (
    !$uploads ||
    !$fichier ||
    !is_file($fichier) ||
    !str_starts_with($fichier, $uploads . DIRECTORY_SEPARATOR)
) {
    http_response_code(404);
    exit('Fichier introuvable.');
}

$extension = strtolower(pathinfo($document['nom_original'], PATHINFO_EXTENSION));

$typesAutorises = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];

if (!isset($typesAutorises[$extension])) {
    header('Location: telecharger.php?id=' . $id);
    exit;
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $typesAutorises[$extension]);
header('Content-Length: ' . filesize($fichier));
header('Content-Disposition: inline; filename="document.' . $extension . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

readfile($fichier);
exit;
