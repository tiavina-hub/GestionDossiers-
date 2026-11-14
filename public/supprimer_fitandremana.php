
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/historique.php';
require_once __DIR__ . '/../config/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

if (!isset($_SESSION['csrf_fitandremana'])
    || !hash_equals(
        $_SESSION['csrf_fitandremana'],
        $_POST['csrf_token'] ?? ''
    )) {
    die("Jeton de sécurité invalide.");
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$sp_id = filter_input(INPUT_POST, 'sp_id', FILTER_VALIDATE_INT);

if (!$id || !$sp_id) {
    die("Informations invalides.");
}

try {
    $pdo->beginTransaction();

    // Verrouillage du SP
    $stmt = $pdo->prepare("
        SELECT id
        FROM synodam_paritany
        WHERE id = ?
        FOR UPDATE
    ");
    $stmt->execute([$sp_id]);

    if (!$stmt->fetch()) {
        throw new Exception("SP introuvable.");
    }

    // Vérifier que le Fitandremana appartient bien au SP
    $stmt = $pdo->prepare("
        SELECT id, nom
        FROM fitandremana
        WHERE id = ? AND sp_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$id, $sp_id]);

    $fitSupprime = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fitSupprime) {
        throw new Exception("Fitandremana introuvable.");
    }

    // Vérifier si un dossier existe dans l'un de ses types
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM dossiers d
        INNER JOIN types_dossiers t ON t.id = d.type_id
        WHERE t.fitandremana_id = ?
    ");
    $stmt->execute([$id]);

    $nombreDossiers = (int)$stmt->fetchColumn();

    if ($nombreDossiers > 0) {
        $pdo->rollBack();

        header(
            "Location: fitandremana.php?sp_id="
            . $sp_id
            . "&erreur=suppression"
        );
        exit;
    }

    // Supprimer les types vides associés au Fitandremana
    $stmt = $pdo->prepare("
        DELETE FROM types_dossiers
        WHERE fitandremana_id = ?
    ");
    $stmt->execute([$id]);

    // Supprimer le Fitandremana
    $stmt = $pdo->prepare("
        DELETE FROM fitandremana
        WHERE id = ? AND sp_id = ?
    ");
    $stmt->execute([$id, $sp_id]);

    // Récupérer les Fitandremana restants dans l'ordre
    $stmt = $pdo->prepare("
        SELECT id
FROM fitandremana
WHERE sp_id = ?
ORDER BY nom ASC, id ASC
FOR UPDATE
    ");
    $stmt->execute([$sp_id]);

    $restants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Réorganiser les numéros selon l'ordre alphabétique
$update = $pdo->prepare("
    UPDATE fitandremana
    SET numero = ?
    WHERE id = ? AND sp_id = ?
");

$nouveauNumero = 1;

foreach ($restants as $fit) {
    $update->execute([
        $nouveauNumero,
        $fit['id'],
        $sp_id
    ]);

    $nouveauNumero++;
}

    enregistrer_action(
        $pdo,
        'Suppression',
        'Fitandremana',
        (int)$fitSupprime['id'],
        'Suppression du Fitandremana « ' .
        $fitSupprime['nom'] . ' » du SP ' . $sp_id
    );

    $pdo->commit();

    header(
        "Location: fitandremana.php?sp_id="
        . $sp_id
        . "&supprime=1"
    );
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header(
        "Location: fitandremana.php?sp_id="
        . $sp_id
        . "&erreur=suppression"
    );
    exit;
}
