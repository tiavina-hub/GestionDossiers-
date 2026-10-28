<?php
require_once __DIR__ . '/../config/auth.php';
require_login();
// Ancienne page fondée sur regions.region_id, colonne qui n'existe plus
// dans la structure actuelle. Les types se gèrent depuis Fitandremana.
header('Location: index.php');
exit;
