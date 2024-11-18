<?php
class AdminVitisoftImportController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    // Function to initialize and display the content of the admin page
    public function initContent()
    {
        parent::initContent();
        
        $this->context->smarty->assign([
            'import_message' => $this->l('Upload your import file below.'),
        ]);
        
        $this->setTemplate('vitisoft_import.tpl');
    }

    // Function to handle form submissions and file uploads
    public function postProcess()
    {
        // Check if the specific form was submitted
        if (Tools::isSubmit('submit_vitisoft_import')) {
            $file = $_FILES['vitisoft_import_file'];
    
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $uploadDir = _PS_ROOT_DIR_ . '/vitisoft/products/';

                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
                    $this->errors[] = $this->l('Impossible de créer le répertoire de destination.');
                    return;
                }
    
                $uploadFile = $uploadDir . basename($file['name']);
                
                // Move the uploaded file to the target directory
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