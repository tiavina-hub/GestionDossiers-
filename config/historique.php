
<?php

function enregistrer_action(
    PDO $pdo,
    string $action,
    string $type_element,
    ?int $element_id = null,
    ?string $description = null
): bool {

    if (empty($_SESSION['user_id'])) {
        return false;
    }

    $utilisateur_id = (int) $_SESSION['user_id'];

    $nom_utilisateur = $_SESSION['nom_complet']
        ?? $_SESSION['username']
        ?? 'Utilisateur inconnu';

    $adresse_ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO historique_actions (
                utilisateur_id,
                nom_utilisateur,
                action,
                type_element,
                element_id,
                description,
                adresse_ip,
                user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $utilisateur_id,
            $nom_utilisateur,
            $action,
            $type_element,
            $element_id,
            $description,
            $adresse_ip,
            $user_agent
        ]);

    } catch (PDOException $e) {
        error_log('Erreur historique : ' . $e->getMessage());
        return false;
    }
}
