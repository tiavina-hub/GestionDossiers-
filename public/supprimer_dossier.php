<?php

require_once __DIR__ . '/../config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("Dossier invalide.");
}

$stmt = $pdo->prepare("
    SELECT chemin
    FROM documents
    WHERE dossier_id = ?
");

$stmt->execute([$id]);
$documents = $stmt->fetchAll();

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        DELETE FROM dossiers
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Dossier introuvable.");
    }

    $pdo->commit();

    /*
     * Supprimer les fichiers physiques
     */
    foreach ($documents as $document) {

        $fichier = __DIR__ . '/../' . $document['chemin'];

        if (is_file($fichier)) {
            unlink($fichier);
        }
    }

    /*
     * Supprimer le dossier physique
     */
    $dossierDir = __DIR__ . '/../storage/uploads/' . $id;

    if (is_dir($dossierDir)) {

        $fichiers = glob($dossierDir . '/*');

        foreach ($fichiers as $fichier) {

            if (is_file($fichier)) {
                unlink($fichier);
            }
        }

        @rmdir($dossierDir);
    }

    header("Location: index.php?deleted=1");
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("Impossible de supprimer le dossier.");
}
