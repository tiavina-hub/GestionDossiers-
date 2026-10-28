GESTION DES DOSSIERS — LOT DE CORRECTIONS
=========================================

Contenu
-------
- Tous les fichiers PHP actifs de public/
- config/auth.php
- Recherche réécrite pour la structure actuelle :
  Synodam-Paritany -> Fitandremana -> types_dossiers -> dossiers
- Déplacement de dossier corrigé, avec conservation de la catégorie TANY/TRANO/FIARA
- Ancienne page region.php redirigée vers l'accueil, car elle utilisait
  la colonne obsolète types_dossiers.region_id
- Protection CSRF ajoutée aux formulaires de création et modification de dossier
- Lien « Déplacer le dossier » ajouté à la fiche détaillée

IMPORTANT
---------
1. config/database.php n'est pas inclus : garde ton fichier actuel et ses identifiants.
2. Le schéma SQL reçu est un dump --no-data. Ne le réimporte pas sur la base réelle :
   il contient des DROP TABLE et pourrait supprimer les tables existantes.
3. Le lot ne modifie pas les données MariaDB et ne lance aucune migration.
4. Les fichiers *.bak présents sur ton serveur ne sont pas inclus et ne seront pas
   écrasés par l'installation.

Installation conseillée (depuis le dossier du projet)
-----------------------------------------------------
Faire d'abord une sauvegarde :

  cd /var/www/html/gestion_dossiers
  sudo tar -czf ~/gestion_dossiers_backup_$(date +%F_%H%M).tar.gz public config/auth.php

Après avoir téléchargé le ZIP dans ~/Téléchargements :

  sudo unzip -o ~/Téléchargements/gestion_dossiers_corrige.zip -d /var/www/html/gestion_dossiers
  cd /var/www/html/gestion_dossiers
  find public config -maxdepth 1 -name '*.php' -print0 | xargs -0 -n1 php -l

Si les fichiers sont extraits avec un propriétaire incorrect :

  sudo chown -R www-data:www-data /var/www/html/gestion_dossiers/storage
  sudo find public config -type f -name '*.php' -exec chmod 644 {} \;

Remarque
--------
Le ZIP a été vérifié avec php -l (PHP CLI 8.4). Ce contrôle valide la syntaxe PHP,
mais ne remplace pas un test fonctionnel sur ton serveur connecté à MariaDB.
