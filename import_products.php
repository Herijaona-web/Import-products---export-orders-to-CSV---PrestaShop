<?php

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/vitisoftintegration.php';

$module = Module::getInstanceByName('vitisoftintegration');
$csvDirectory = _PS_MODULE_DIR_ . 'vitisoftintegration/products/';
$logFilePath = _PS_MODULE_DIR_ . 'vitisoftintegration/products/processed_files.log';
$cronLogFilePath = _PS_MODULE_DIR_ . 'vitisoftintegration/products/cron_import.log';
$csvFiles = glob($csvDirectory . '*.csv');
$processedFiles = file_exists($logFilePath) ? file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

foreach ($csvFiles as $csvFilePath) {

    $fileName = basename($csvFilePath);

    if (in_array($fileName, $processedFiles)) {
        var_dump('fichier a dejà été traité');
        file_put_contents($cronLogFilePath, "Le fichier {$fileName} a déjà été traité.\n", FILE_APPEND);
        continue;
    }

    $result = $module->importProductsFromCSV($csvFilePath);

    if ($result) {
        echo 'Importation terminée avec succès. !';
        file_put_contents($logFilePath, $fileName . PHP_EOL, FILE_APPEND);
        // file_put_contents($cronLogFilePath, "Importation du fichier {$fileName} terminée avec succès.\n", FILE_APPEND);
    } else {
        echo 'Erreur d\'importation!';
        // file_put_contents($cronLogFilePath, "Erreur lors de l'importation du fichier {$fileName}.\n", FILE_APPEND);
    }
}
