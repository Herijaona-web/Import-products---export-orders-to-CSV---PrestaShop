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
        // Handle file upload or form submission
        if (Tools::isSubmit('submit_vitisoft_import')) {
            $file = $_FILES['vitisoft_import_file'];
            if ($file && $file['error'] == 0) {
                // Process the file, e.g., move it to a specific folder or import data
                $uploadDir = _PS_MODULE_DIR_ . 'vitisoftintegration/files/products/';
                $uploadFile = $uploadDir . basename($file['name']);
                
                if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                    $this->confirmations[] = $this->l('Fichier téléchargé avec succès !');
                } else {
                    $this->errors[] = $this->l('Une erreur est survenue lors du téléchargement du fichier.');
                }
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

