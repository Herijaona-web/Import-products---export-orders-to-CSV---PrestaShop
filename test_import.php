<?php
// Inclure le fichier de configuration PrestaShop
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/vitisoftintegration.php';

// Instancier le module
$module = new VitisoftIntegration();

// Définir les chemins des répertoires
$directoryPath = _PS_MODULE_DIR_ . 'vitisoftintegration/files/';
$processedDirectoryPath = _PS_MODULE_DIR_ . 'vitisoftintegration/files/processed/';

// Récupérer tous les fichiers CSV dans le répertoire
$files = glob($directoryPath . '*.csv');

// Traiter chaque fichier CSV
foreach ($files as $file) {
    // Appeler la méthode d'importation pour chaque fichier
    $module->importProductsFromCSV($file);

    // Déplacer le fichier traité dans le répertoire "processed"
    rename($file, $processedDirectoryPath . basename($file));
}

echo 'Traitement des fichiers Vitisoft terminé.';
?>
