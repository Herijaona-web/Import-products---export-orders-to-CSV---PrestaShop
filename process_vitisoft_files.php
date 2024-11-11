<?php
// Inclure le fichier de configuration PrestaShop
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/vitisoftintegration.php';

// Instancier le module
$module = new VitisoftIntegration();

// Appeler la méthode pour traiter les fichiers CSV
echo $module->processVitisoftFiles();
?>
