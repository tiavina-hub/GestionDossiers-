<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

// Autoriser uniquement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Méthode non autorisée.');
}

// Vérifier le token CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Jeton de sécurité invalide. Rechargez la page.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Document invalide.');
}

try {
    $stmt = $pdo->prepare(
        'SELECT dossier_id, chemin
         FROM documents
         WHERE id = ?'
    );
    $stmt->execute([$id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        http_response_code(404);
        exit('Document introuvable.');
    }

    // Supprimer d'abord la référence en base.
    $stmt = $pdo->prepare('DELETE FROM documents WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Suppression impossible.');
    }

    // La suppression SQL est validée avant celle du fichier.
    $racine = realpath(__DIR__ . '/..');
    $chemin = (string)$document['chemin'];

    if ($racine !== false && $chemin !== '' && !str_contains($chemin, "\0")) {
        $fichier = realpath($racine . '/' . $chemin);

        if (
            $fichier !== false &&
            is_file($fichier) &&
            str_starts_with(
                $fichier,
                $racine . DIRECTORY_SEPARATOR
            )
        ) {
            if (!@unlink($fichier)) {
                error_log('Impossible de supprimer le fichier physique : ' . $fichier);
            }
        }
    }

    header(
        'Location: voir_dossier.php?id=' .
        (int)$document['dossier_id'] .
        '&document_deleted=1'
    );
    exit;

} catch (Throwable $e) {
    error_log('Suppression document : ' . $e->getMessage());
    http_response_code(500);
    exit('Impossible de supprimer le document.');
}