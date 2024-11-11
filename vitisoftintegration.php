<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class VitisoftIntegration extends Module
{
    public function __construct()
    {
        $this->name = 'vitisoftintegration';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Votre nom';
        parent::__construct();

        $this->displayName = $this->l('Intégration Vitisoft');
        $this->description = $this->l('Module pour l\'importation des produits et l\'exportation des commandes avec Vitisoft.');
        $this->confirmUninstall = $this->l('Êtes-vous sûr de vouloir désinstaller ce module ?');
    }

    public function install()
    {
        // Installer le module et enregistrer le hook actionOrderStatusPostUpdate
        if (!parent::install() || !$this->registerHook('actionOrderStatusPostUpdate') || !$this->registerHook('displayBackOfficeHeader') ) {
            return false; // Si l'installation échoue ou le hook ne peut pas être enregistré
        }
    
        // Créer le dossier vitisoftintegration si nécessaire
        $moduleDir = _PS_MODULE_DIR_ . 'vitisoftintegration';
        if (!is_dir($moduleDir)) {
            if (!mkdir($moduleDir, 0777, true)) {
                return false; // Vérifier si le dossier principal a été créé
            }
        }
    
        // Créer les sous-dossiers nécessaires
        $subDirs = [
            $moduleDir . '/files',
            $moduleDir . '/files/processed',
            $moduleDir . '/orders',
            $moduleDir . '/orders/processed'
        ];
    
        foreach ($subDirs as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                return false; // Vérifier chaque sous-dossier
            }
        }
    
        // Ajouter la tâche cron
        // if (!$this->addCronJob()) {
        //     return false; // Vérifier si la tâche cron a été ajoutée
        // }
    
        // Ajouter les tabs
        if (!$this->addTab('AdminExportOrder', 'AdminParentOrders', 'Commande exportés en csv', $this->name)) {
            return false; // Si l'ajout du tab "Export Order" échoue
        }
    
        if (!$this->addTab('AdminVitisoftImport', 'AdminCatalog', 'Imports des produits csv', $this->name)) {
            return false; // If the tab creation fails
        }        
    
        return true; // Retourner true si l'installation a réussi
    }
      
    public function addTab($className, $parentTabClass, $tabName, $moduleName)
    {
        // Créer un nouvel objet Tab
        $tab = new Tab();
        $tab->class_name = $className;
        $tab->module = $moduleName;

        // Définir le parent du tab
        $tab->id_parent = Tab::getIdFromClassName($parentTabClass);
        
        // Vérifier si l'ID parent est valide
        // if (!$tab->id_parent) {
        //     return false; // Si l'ID parent est invalide, retourner false
        // }

        // Ajouter les noms du tab pour chaque langue
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }

        // Ajouter le tab dans le back-office et vérifier si cela réussit
        return $tab->add();


    }


    public function uninstall()
    {
        // Supprimer les sous-dossiers créés
        // rmdir(_PS_MODULE_DIR_ . 'vitisoftintegration/files/');
        // rmdir(_PS_MODULE_DIR_ . 'vitisoftintegration/orders/');

        // Supprimer la tâche cron
        // $this->removeCronJob();

        return parent::uninstall();
    }

    // Ajout d'une tâche CRON pour la gestion des fichiers CSV
    public function addCronJob()
    {
        // Ajoutez ici votre code pour planifier le cron job via PrestaShop ou le serveur
    }

    // Suppression de la tâche CRON
    public function removeCronJob()
    {
        // Ajoutez ici votre code pour supprimer la tâche cron
    }

    // Fonction qui sera appelée par le CRON pour traiter les fichiers CSV envoyés par Vitisoft
    public function processVitisoftFiles()
    {
        $directoryPath = _PS_MODULE_DIR_ . 'vitisoftintegration/files/';
        $processedDirectoryPath = _PS_MODULE_DIR_ . 'vitisoftintegration/files/processed/';
        $files = glob($directoryPath . '*.csv');

        foreach ($files as $file) {
            $this->importProductsFromCSV($file);
            rename($file, $processedDirectoryPath . basename($file));
        }

        return 'Traitement des fichiers Vitisoft terminé.';
    }

    // Fonction pour importer les produits depuis le fichier CSV
    public function importProductsFromCSV($csvFilePath)
    {
        if (!file_exists($csvFilePath)) {
            return 'Le fichier CSV n\'existe pas.';
        }

        if (($handle = fopen($csvFilePath, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ',');

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $productData = array_combine($headers, $data);
                $this->createOrUpdateProduct(
                    $productData['name'],
                    $productData['reference'],
                    (int)$productData['quantity'],
                    (float)$productData['price'],
                    $productData['image_url'] ?? null,
                    $productData['category'] ?? null,
                    $productData['tax_id'] ?? null,
                    $productData['description'] ?? null,
                    $productData['short_description'] ?? null
                );
            }

            fclose($handle);
            return 'Importation réussie.';
        } else {
            return 'Erreur lors de l\'ouverture du fichier CSV.';
        }
    }

    // Fonction pour créer ou mettre à jour les produits
    private function createOrUpdateProduct($name, $reference, $quantity, $price, $imageUrl, $category, $taxId, $description, $shortDescription)
    {
        // Rechercher un produit existant par référence
        $productId = Product::getIdByReference($reference);
        
        // Si le produit existe, charger l'objet du produit pour mise à jour
        if ($productId) {
            $product = new Product($productId);
        } else {
            // Sinon, créer un nouvel objet produit
            $product = new Product();
        }
        
        // Définir les propriétés communes
        $product->name = array_fill_keys(Language::getIDs(false), $name);
        $product->reference = $reference;
        $product->price = $price;
        $product->id_tax_rules_group = $taxId ?: 0;
        $product->active = 1;
        $product->description = array_fill_keys(Language::getIDs(false), $description);
        $product->description_short = array_fill_keys(Language::getIDs(false), $shortDescription);
    
        // Définir la catégorie par défaut
        if ($category) {
            $product->id_category_default = $category != 'HOME' ? (int) $category : 2;
        }
    
        // Ajouter ou mettre à jour le produit
        if ($productId ? $product->update() : $product->add()) {
            // Mettre à jour la quantité en stock
            StockAvailable::setQuantity($product->id, 0, $quantity);
    
            // Associer le produit à la catégorie
            if ($category) {
                $categoryObj = new Category($category);
                if (Validate::isLoadedObject($categoryObj)) {
                    $product->addToCategories([$categoryObj->id]);
                }
            }
    
            // Ajouter ou mettre à jour l'image si elle est valide
            if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $imageAdded = $this->downloadAndUploadImage($product->id, $imageUrl);
                if (!$imageAdded) {
                    return 'Erreur lors de l\'ajout de l\'image au produit.';
                }
            }
            return true;
        }
        return false;
    }

    private function downloadAndUploadImage($productId, $imageUrl)
    {
        $tempDir = _PS_TMP_IMG_DIR_;
        $tempImagePath = $tempDir . uniqid() . '.jpg';
    
        // Télécharger l'image à partir de l'URL
        if ($this->downloadImage($imageUrl, $tempImagePath)) {
            // Vérifier si le produit a déjà une image principale (cover)
            $existingCoverImage = Db::getInstance()->getRow('
                SELECT id_image FROM '._DB_PREFIX_.'image
                WHERE id_product = '.(int)$productId.' AND cover = 1
            ');
    
            // Si une image principale existe déjà, la supprimer
            if ($existingCoverImage) {
                $existingImage = new Image((int)$existingCoverImage['id_image']);
                $existingImage->delete();
            }
    
            // Créer un nouvel objet image
            $image = new Image();
            $image->id_product = $productId;
            $image->position = Image::getHighestPosition($productId) + 1;
            $image->cover = true;  // Assigner cette image comme couverture principale
    
            if ($image->add()) {
                $imagePath = _PS_PROD_IMG_DIR_ . $image->getImgPath() . '.jpg';
                $directoryPath = dirname($imagePath);
    
                // Créer le répertoire si nécessaire
                if (!file_exists($directoryPath)) {
                    mkdir($directoryPath, 0777, true);
                }
    
                // Déplacer l'image téléchargée vers le répertoire du produit
                if (rename($tempImagePath, $imagePath)) {
                    $imagesTypes = ImageType::getImagesTypes('products');
                    foreach ($imagesTypes as $imageType) {
                        // Redimensionner l'image pour les différentes tailles
                        ImageManager::resize(
                            $imagePath,
                            _PS_PROD_IMG_DIR_ . $image->getImgPath() . '-' . $imageType['name'] . '.jpg',
                            $imageType['width'],
                            $imageType['height']
                        );
                    }
                    return true;
                } else {
                    error_log("Erreur lors du déplacement de l'image temporaire vers le chemin du produit.");
                }
            } else {
                error_log("Erreur lors de l'ajout de l'image dans PrestaShop.");
            }
        }
        return false;
    }
    

    // Fonction pour télécharger l'image depuis une URL
    private function downloadImage($url, $path)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && $data) {
            return file_put_contents($path, $data) !== false;
        } else {
            var_dump("Échec du téléchargement de l'image, code HTTP: $httpCode");
        }

        return false;
    }


    public function exportOrderToCSV($orderId)
    {
        
        // Récupérer la commande
        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            return 'Commande introuvable.';
        }
    
        // Récupérer le client de la commande
        $customer = new Customer($order->id_customer);
    
        // Récupérer les produits associés à la commande
        $products = $order->getProducts();
    
        // Définir le chemin du fichier CSV à générer
        $filename = _PS_MODULE_DIR_ . 'vitisoftintegration/orders/order_' . $orderId . '.csv';
    
        // Ouvrir le fichier en mode écriture
        $file = fopen($filename, 'w');
    
        // Définir les en-têtes du fichier CSV
        $headers = [
            'order_id', 
            'customer_name', 
            'customer_email', 
            'product_reference', 
            'product_name', 
            'product_quantity', 
            'product_price'
        ];
        fputcsv($file, $headers);
    
        // Parcourir les produits et ajouter leurs informations dans le fichier CSV
        foreach ($products as $product) {
            $line = [
                'order_id' => $order->id,
                'customer_name' => $customer->firstname . ' ' . $customer->lastname,
                'customer_email' => $customer->email,
                'product_reference' => $product['product_reference'],
                'product_name' => $product['product_name'],
                'product_quantity' => $product['product_quantity'],
                'product_price' => $product['unit_price_tax_incl']
            ];
            fputcsv($file, $line);
        }
    
        // Fermer le fichier après l'écriture
        fclose($file);
    
        // Vérifier et créer le dossier de destination si nécessaire
        $processedDirectoryPath = _PS_MODULE_DIR_ . 'vitisoftintegration/orders/processed/';
        if (!is_dir($processedDirectoryPath)) {
            mkdir($processedDirectoryPath, 0777, true);
        }
    
        // Déplacer le fichier vers le dossier "processed" une fois qu'il a été traité
        $processedFilePath = $processedDirectoryPath . basename($filename);
        $fileMoved = rename($filename, $processedFilePath);
    
        // Vérifier si le fichier a bien été déplacé
        if ($fileMoved) {
            // Mettre à jour la configuration avec l'état "uploaded"
            Configuration::updateValue('viti_file_' . $orderId, 'uploaded');
            return 'Fichier CSV de la commande généré, déplacé et l\'état mis à jour avec succès.';
        } else {
            return 'Échec du déplacement du fichier CSV.';
        }


    }
        

    public function hookActionOrderStatusPostUpdate($params)
    {

        if ( isset($params['id_order']) && isset($params['newOrderStatus'])  && Configuration::get('PS_OS_PAYMENT') == $params['newOrderStatus']->id ) {

            $orderId = $params['id_order'];
            
            $this->exportOrderToCSV($orderId);

            file_put_contents(_PS_MODULE_DIR_ . 'vitisoftintegration/debug_hook_net.txt', 'Success 200 : ' . $orderId . PHP_EOL, FILE_APPEND);

        } else {
            var_dump('Order object not found');
            file_put_contents(_PS_MODULE_DIR_ . 'vitisoftintegration/debug_hook_net.txt', 'Error 404 : ' . PHP_EOL, FILE_APPEND);

        }

    }

    public function hookDisplayBackOfficeHeader()
    {
        // Lier le fichier CSS
        $this->context->controller->addCSS($this->_path . 'views/css/styles.css', 'all');
    }


   

}
