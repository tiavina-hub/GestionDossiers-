<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Méthode non autorisée.');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$token = $_POST['csrf_token'] ?? '';

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Dossier invalide.');
}

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Jeton de sécurité invalide. Rechargez la page.');
}

try {
    $stmt = $pdo->prepare(
        'SELECT chemin FROM documents WHERE dossier_id = ?'
    );
    $stmt->execute([$id]);
    $documents = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('DELETE FROM dossiers WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Dossier introuvable.');
    }

    // Les lignes de documents sont supprimées par ON DELETE CASCADE.
    $pdo->commit();

    // Nettoyage des fichiers physiques après validation SQL.
    $racine = realpath(__DIR__ . '/..');
    $uploads = realpath(__DIR__ . '/../storage/uploads');

    if ($racine !== false) {
        foreach ($documents as $chemin) {
            if (
                !is_string($chemin) ||
                $chemin === '' ||
                str_contains($chemin, "\0")
            ) {
                continue;
            }

            $fichier = realpath($racine . '/' . $chemin);

            if (
                $fichier !== false &&
                is_file($fichier) &&
                str_starts_with(
                    $fichier,
                    $racine . DIRECTORY_SEPARATOR
                )
            ) {
                @unlink($fichier);
            }
        }
    }

    if ($uploads !== false) {
        $repertoire = realpath($uploads . '/' . $id);

        if (
            $repertoire !== false &&
            is_dir($repertoire) &&
            str_starts_with(
                $repertoire,
                $uploads . DIRECTORY_SEPARATOR
            )
        ) {
            foreach (glob($repertoire . '/*') ?: [] as $fichier) {
                if (is_file($fichier) || is_link($fichier)) {
                    @unlink($fichier);
                }
            }

            @rmdir($repertoire);
        }
    }

    header('Location: index.php?deleted=1');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Suppression dossier : ' . $e->getMessage());

    http_response_code(500);
    exit('Impossible de supprimer le dossier.');
}