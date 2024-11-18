<?php
class AdminVitisoftImportController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        // Définir les permissions si nécessaire
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();
        
        // Assign variables to the template
        $this->context->smarty->assign([
            'import_message' => $this->l('Upload your import file below.'),
            // Add more variables as needed
        ]);
        
        // Render the template
        $this->setTemplate('vitisoft_import.tpl');
    }

    public function postProcess()
    {
        // Gérer l'upload de fichier ou la soumission de formulaire
        if (Tools::isSubmit('submit_vitisoft_import')) {
            $file = $_FILES['vitisoft_import_file'];
    
            // Vérifier si un fichier a été téléchargé et qu'il n'y a pas d'erreur
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                // Chemin du répertoire de destination
                $uploadDir = _PS_ROOT_DIR_ . '/vitisoft/products/';
                
                // Vérifier si le répertoire existe, sinon le créer
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
                    $this->errors[] = $this->l('Impossible de créer le répertoire de destination.');
                    return;
                }
    
                // Définir le chemin complet du fichier à sauvegarder
                $uploadFile = $uploadDir . basename($file['name']);
                
                // Déplacer le fichier téléchargé vers le répertoire cible
                if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                    $this->confirmations[] = $this->l('Fichier téléchargé avec succès !');
                } else {
                    $this->errors[] = $this->l('Une erreur est survenue lors du téléchargement du fichier.');
                }
            } else {
                $this->errors[] = $this->l('Aucun fichier valide n\'a été téléchargé.');
            }
        }
    }
    
}


// class AdminVitisoftImportController extends ModuleAdminController
// {
//     public function __construct()
//     {
//         parent::__construct();
//         // Définir les permissions si nécessaire
//         $this->bootstrap = true;
//     }

//     public function initContent()
//     {
//         parent::initContent();

//         // Afficher un message ou exécuter une fonction d'export ici
//         $this->context->smarty->assign([
//             'message' => 'Export de commande'
//         ]);

//         $testOrderId = 172; 

//         // Tester l'export111

//         $array = array(11, 22, 172);
//         foreach ($array as $key => $value) {
//             // $result = $this->module->exportOrderToCSV($value);
//         }

//         $this->setTemplate('vitisoft_import.tpl');
//     }
// }

