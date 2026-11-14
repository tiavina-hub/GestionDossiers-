
from pathlib import Path
from datetime import datetime
import subprocess
import sys

ROOT = Path(__file__).resolve().parent
PUBLIC = ROOT / "public"
STAMP = datetime.now().strftime("%Y%m%d_%H%M%S")

changes = {}

def patch(filename, replacements):
    path = PUBLIC / filename
    original = path.read_text(encoding="utf-8")
    updated = original

    for old, new in replacements:
        count = updated.count(old)
        if count != 1:
            raise RuntimeError(
                f"{filename}: bloc introuvable ou non unique "
                f"(occurrences={count})"
            )
        updated = updated.replace(old, new, 1)

    changes[path] = (original, updated)


# --------------------------------------------------
# 1. GESTION DES UTILISATEURS
# --------------------------------------------------

patch("utilisateurs.php", [
(
"require_once __DIR__ . '/../config/database.php';",
"require_once __DIR__ . '/../config/database.php';\n"
"require_once __DIR__ . '/../config/historique.php';"
),
(
"""            $stmt->execute([
                $username,
                $hash,
                $nom_complet !== '' ? $nom_complet : null,
                $role
            ]);

            $message = "Utilisateur ajouté avec succès.";""",
"""            $stmt->execute([
                $username,
                $hash,
                $nom_complet !== '' ? $nom_complet : null,
                $role
            ]);

            $nouvelId = (int)$pdo->lastInsertId();

            enregistrer_action(
                $pdo,
                'Création',
                'Utilisateur',
                $nouvelId,
                'Création du compte « ' . $username .
                ' » avec le rôle ' . $role
            );

            $message = "Utilisateur ajouté avec succès.";"""
),
(
"""        $stmt = $pdo->prepare("
            UPDATE users
            SET actif = IF(actif = 1, 0, 1)
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $message = "Statut du compte modifié.";""",
"""        $info = $pdo->prepare("
            SELECT username, actif
            FROM users
            WHERE id = ?
        ");
        $info->execute([$id]);
        $cible = $info->fetch(PDO::FETCH_ASSOC);

        if (!$cible) {
            $erreur = "Utilisateur introuvable.";
        } else {
            $nouveauStatut = (int)$cible['actif'] === 1
                ? 'Désactivation'
                : 'Activation';

            $stmt = $pdo->prepare("
                UPDATE users
                SET actif = IF(actif = 1, 0, 1)
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            enregistrer_action(
                $pdo,
                $nouveauStatut,
                'Utilisateur',
                $id,
                $nouveauStatut . ' du compte « ' .
                $cible['username'] . ' »'
            );

            $message = "Statut du compte modifié.";
        }"""
),
(
"""        $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $message = "Utilisateur supprimé.";""",
"""        $info = $pdo->prepare("
            SELECT username
            FROM users
            WHERE id = ?
        ");
        $info->execute([$id]);
        $cible = $info->fetch(PDO::FETCH_ASSOC);

        if (!$cible) {
            $erreur = "Utilisateur introuvable.";
        } else {
            $stmt = $pdo->prepare("
                DELETE FROM users
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            enregistrer_action(
                $pdo,
                'Suppression',
                'Utilisateur',
                $id,
                'Suppression du compte « ' .
                $cible['username'] . ' »'
            );

            $message = "Utilisateur supprimé.";
        }"""
),
(
"""        $hash = password_hash(
            $nouveau_mot_de_passe,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            UPDATE users
            SET password_hash = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $hash,
            $id
        ]);

        $message =
            "Mot de passe modifié avec succès.";""",
"""        $info = $pdo->prepare("
            SELECT username
            FROM users
            WHERE id = ?
        ");
        $info->execute([$id]);
        $cible = $info->fetch(PDO::FETCH_ASSOC);

        if (!$cible) {
            $erreur = "Utilisateur introuvable.";
        } else {
            $hash = password_hash(
                $nouveau_mot_de_passe,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                UPDATE users
                SET password_hash = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $hash,
                $id
            ]);

            enregistrer_action(
                $pdo,
                'Réinitialisation du mot de passe',
                'Utilisateur',
                $id,
                'Réinitialisation du mot de passe du compte « ' .
                $cible['username'] . ' »'
            );

            $message =
                "Mot de passe modifié avec succès.";
        }"""
)
])


# --------------------------------------------------
# 2. AJOUT D'UN FITANDREМANA
# --------------------------------------------------

patch("fitandremana.php", [
(
"require_once __DIR__ . '/../config/database.php';",
"require_once __DIR__ . '/../config/database.php';\n"
"require_once __DIR__ . '/../config/historique.php';"
),
(
"""            $pdo->commit();

            header(
                "Location: fitandremana.php?sp_id=""",
"""            $fitId = (int)$pdo->lastInsertId();

            enregistrer_action(
                $pdo,
                'Création',
                'Fitandremana',
                $fitId,
                'Création du Fitandremana « ' . $nom .
                ' » dans le SP ' . $sp['numero_officiel'] .
                ' — ' . $sp['nom']
            );

            $pdo->commit();

            header(
                "Location: fitandremana.php?sp_id="""
)
])


# --------------------------------------------------
# 3. MODIFICATION D'UN FITANDREМANA
# --------------------------------------------------

patch("modifier_fitandremana.php", [
(
"require_once __DIR__ . '/../config/database.php';",
"require_once __DIR__ . '/../config/database.php';\n"
"require_once __DIR__ . '/../config/historique.php';"
),
(
"""$pdo->commit();
            header(
                "Location: fitandremana.php?sp_id=""",
"""enregistrer_action(
    $pdo,
    'Modification',
    'Fitandremana',
    (int)$fit['id'],
    'Modification du Fitandremana « ' . $fit['nom'] .
    ' » en « ' . $nom . ' »'
);

$pdo->commit();
            header(
                "Location: fitandremana.php?sp_id="""
)
])


# --------------------------------------------------
# 4. SUPPRESSION D'UN FITANDREМANA
# --------------------------------------------------

patch("supprimer_fitandremana.php", [
(
"require_once __DIR__ . '/../config/database.php';",
"require_once __DIR__ . '/../config/database.php';\n"
"require_once __DIR__ . '/../config/historique.php';"
),
(
"""    $stmt = $pdo->prepare("
        SELECT id
        FROM fitandremana
        WHERE id = ? AND sp_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$id, $sp_id]);

    if (!$stmt->fetch()) {
        throw new Exception("Fitandremana introuvable.");
    }""",
"""    $stmt = $pdo->prepare("
        SELECT id, nom
        FROM fitandremana
        WHERE id = ? AND sp_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$id, $sp_id]);

    $fitSupprime = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fitSupprime) {
        throw new Exception("Fitandremana introuvable.");
    }"""
),
(
"""    $pdo->commit();

    header(
        "Location: fitandremana.php?sp_id=""",
"""    enregistrer_action(
        $pdo,
        'Suppression',
        'Fitandremana',
        (int)$fitSupprime['id'],
        'Suppression du Fitandremana « ' .
        $fitSupprime['nom'] . ' » du SP ' . $sp_id
    );

    $pdo->commit();

    header(
        "Location: fitandremana.php?sp_id="""
)
])


# --------------------------------------------------
# 5. TYPES FOIBE
# --------------------------------------------------

patch("gestion_types_foibe.php", [
(
"require_once __DIR__ . '/../config/database.php';",
"require_once __DIR__ . '/../config/database.php';\n"
"require_once __DIR__ . '/../config/historique.php';"
),
(
"""                $stmt->execute([
                    $categorieId,
                    $nom,
                    $description !== '' ? $description : null
                ]);

                header('Location: gestion_types_foibe.php?success=added');""",
"""                $stmt->execute([
                    $categorieId,
                    $nom,
                    $description !== '' ? $description : null
                ]);

                $typeId = (int)$pdo->lastInsertId();

                $catStmt = $pdo->prepare(
                    "SELECT nom FROM foibe_categories WHERE id = ?"
                );
                $catStmt->execute([$categorieId]);
                $nomCategorie = $catStmt->fetchColumn();

                enregistrer_action(
                    $pdo,
                    'Création',
                    'Type Foibe',
                    $typeId,
                    'Création du type « ' . $nom .
                    ' » dans la catégorie « ' . $nomCategorie . ' »'
                );

                header('Location: gestion_types_foibe.php?success=added');"""
),
(
"""            $stmt = $pdo->prepare("
                UPDATE foibe_types_dossiers
                SET categorie_id = ?, nom = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $categorieId,
                $nom,
                $description !== '' ? $description : null,
                $id
            ]);

            header('Location: gestion_types_foibe.php?success=updated');""",
"""            $stmt = $pdo->prepare("
                UPDATE foibe_types_dossiers
                SET categorie_id = ?, nom = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $categorieId,
                $nom,
                $description !== '' ? $description : null,
                $id
            ]);

            $catStmt = $pdo->prepare(
                "SELECT nom FROM foibe_categories WHERE id = ?"
            );
            $catStmt->execute([$categorieId]);
            $nomCategorie = $catStmt->fetchColumn();

            enregistrer_action(
                $pdo,
                'Modification',
                'Type Foibe',
                $id,
                'Modification du type Foibe : « ' . $nom .
                ' » dans la catégorie « ' . $nomCategorie . ' »'
            );

            header('Location: gestion_types_foibe.php?success=updated');"""
),
(
"""            $stmt = $pdo->prepare("
                DELETE FROM foibe_types_dossiers
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            header('Location: gestion_types_foibe.php?success=deleted');""",
"""            $info = $pdo->prepare("
                SELECT t.nom, c.nom AS categorie_nom
                FROM foibe_types_dossiers t
                JOIN foibe_categories c ON c.id = t.categorie_id
                WHERE t.id = ?
            ");
            $info->execute([$id]);
            $typeSupprime = $info->fetch(PDO::FETCH_ASSOC);

            if (!$typeSupprime) {
                throw new Exception("Type Foibe introuvable.");
            }

            $stmt = $pdo->prepare("
                DELETE FROM foibe_types_dossiers
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            enregistrer_action(
                $pdo,
                'Suppression',
                'Type Foibe',
                $id,
                'Suppression du type « ' .
                $typeSupprime['nom'] .
                ' » de la catégorie « ' .
                $typeSupprime['categorie_nom'] . ' »'
            );

            header('Location: gestion_types_foibe.php?success=deleted');"""
)
])


# --------------------------------------------------
# VALIDATION DE TOUS LES BLOCS AVANT ÉCRITURE
# --------------------------------------------------

print("Vérification des modifications...")

for path, (original, updated) in changes.items():
    if original == updated:
        raise RuntimeError(f"Aucune modification pour {path.name}")

print("Tous les blocs correspondent aux fichiers.")

# Sauvegardes
backups = {}

for path, (original, updated) in changes.items():
    backup = path.with_name(path.name + ".bak_audit_" + STAMP)
    if backup.exists():
        raise RuntimeError(f"Sauvegarde déjà existante : {backup}")
    backups[path] = backup

# Écriture des sauvegardes et fichiers modifiés
written = []

try:
    for path, (original, updated) in changes.items():
        backup = backups[path]
        backup.write_text(original, encoding="utf-8")
        path.write_text(updated, encoding="utf-8")
        written.append(path)

    print("\nVérification de la syntaxe PHP...")

    for path in changes:
        result = subprocess.run(
            ["php", "-l", str(path)],
            capture_output=True,
            text=True
        )

        print(result.stdout.strip() or result.stderr.strip())

        if result.returncode != 0:
            raise RuntimeError(
                f"Erreur de syntaxe dans {path.name}"
            )

except Exception as error:
    print("\nERREUR :", error)
    print("Restauration des fichiers originaux...")

    for path in written:
        original = changes[path][0]
        path.write_text(original, encoding="utf-8")

    print("Les fichiers modifiés ont été restaurés.")
    sys.exit(1)

print("\nSUCCÈS : historique intégré dans les cinq fichiers.")

print("\nSauvegardes créées :")
for backup in backups.values():
    print(backup.name)

print("\nTu peux maintenant tester les opérations dans l'application.")
