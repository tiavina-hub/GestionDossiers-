<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
require_admin();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

$id = (int)($_POST['id'] ?? 0);
$token = $_POST['csrf_token'] ?? '';

if ($id <= 0 || empty($_SESSION['csrf_fitandremana']) ||
    !hash_equals($_SESSION['csrf_fitandremana'], $token)) {
    http_response_code(400);
    exit('Requête invalide ou session expirée.');
}

$stmt = $pdo->prepare("SELECT sp_id FROM fitandremana WHERE id = ?");
$stmt->execute([$id]);
$fit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fit) {
    exit('Fitandremana introuvable.');
}

try {
    $pdo->beginTransaction();

    // Vérifier si au moins un type possède un dossier
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM dossiers d
        INNER JOIN types_dossiers t ON t.id = d.type_id
        WHERE t.fitandremana_id = ?
    ");
    $stmt->execute([$id]);
    $totalDossiers = (int)$stmt->fetchColumn();

    if ($totalDossiers > 0) {
        $pdo->rollBack();
        $_SESSION['fitandremana_message'] =
            "Suppression impossible : au moins un type de ce Fitandremana contient déjà des dossiers.";
    } else {
        // Aucun dossier : supprimer les types vides, puis le Fitandremana
        $stmt = $pdo->prepare("DELETE FROM types_dossiers WHERE fitandremana_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM fitandremana WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        $_SESSION['fitandremana_message'] =
            "Fitandremana et types de dossiers vides supprimés avec succès.";
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Suppression Fitandremana : ' . $e->getMessage());
    $_SESSION['fitandremana_message'] =
        "Une erreur est survenue. Le Fitandremana n'a pas été supprimé.";
}

unset($_SESSION['csrf_fitandremana']);

header('Location: fitandremana.php?sp_id=' . (int)$fit['sp_id']);
exit;
