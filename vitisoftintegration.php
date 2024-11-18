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
        if (!parent::install() || !$this->registerHook('actionOrderStatusPostUpdate') || !$this->registerHook('displayBackOfficeHeader')) {
            return false; // Si l'installation échoue ou le hook ne peut pas être enregistré
        }
    
        // Définir le chemin vers le répertoire principal de PrestaShop
        $prestashopDir = _PS_ROOT_DIR_ . '/vitisoft';
    
        // Créer le dossier vitisoft si nécessaire
        if (!is_dir($prestashopDir)) {
            if (!mkdir($prestashopDir, 0777, true)) {
                return false; // Vérifier si le dossier principal a été créé
            }
        }
    
        // Créer les sous-dossiers nécessaires
        $subDirs = [
            $prestashopDir . '/orders/processed',
            $prestashopDir . '/products/processed'
        ];
    
        foreach ($subDirs as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                return false; // Vérifier chaque sous-dossier
            }
        }
    
        // Définir les bonnes permissions pour permettre l'accès aux fichiers
        foreach ($subDirs as $dir) {
            // Assurez-vous que les fichiers sont accessibles en lecture/écriture
            chmod($dir, 0777); // Permissions de lecture, écriture, exécution pour tous
        }
    
        // Ajouter les tabs
        if (!$this->addTab('AdminExportOrder', 'AdminParentOrders', 'Exportées en csv (Vitisoft)', $this->name)) {
            return false; // Si l'ajout du tab "Export Order" échoue
        }
    
        if (!$this->addTab('AdminVitisoftImport', 'AdminCatalog', 'Imports des produits csv', $this->name)) {
            return false; // Si l'ajout du tab "Imports des produits csv" échoue
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
        // Chemin du dossier de fichiers à traiter
        $directoryPath = _PS_ROOT_DIR_ . '/vitisoft/products/';
        $processedDirectoryPath = _PS_ROOT_DIR_ . '/vitisoft/products/processed/';
    
        // Vérifier si les répertoires existent
        if (!is_dir($directoryPath)) {
            return 'Le répertoire des fichiers à traiter n\'existe pas.';
        }
        if (!is_dir($processedDirectoryPath) && !mkdir($processedDirectoryPath, 0777, true)) {
            return 'Impossible de créer le répertoire pour les fichiers traités.';
        }
    
        // Récupérer les fichiers CSV dans le répertoire
        $files = glob($directoryPath . '*.csv');
        if (empty($files)) {
            return 'Aucun fichier CSV trouvé à traiter.';
        }
    
        // Traiter chaque fichier
        foreach ($files as $file) {
            try {
                $this->importProductsFromCSV($file); // Appeler une méthode pour importer les produits
                rename($file, $processedDirectoryPath . basename($file)); // Déplacer le fichier traité
            } catch (Exception $e) {
                // Gestion des erreurs spécifiques au traitement
                return 'Erreur lors du traitement du fichier : ' . basename($file) . '. ' . $e->getMessage();
            }
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
            return json_encode(['status' => 'error', 'message' => 'Commande introuvable.']);
        }
    
        // Récupérer le client et les informations de facturation et livraison
        $customer = new Customer($order->id_customer);
        $billingAddress = new Address($order->id_address_invoice);
        $deliveryAddress = new Address($order->id_address_delivery);

        // var_dump($billingAddress);
    
        // Récupérer les produits associés à la commande
        $products = $order->getProducts();
    
        // Définir le chemin du fichier CSV à générer
        $filename = _PS_ROOT_DIR_ . '/vitisoft/orders/order_' . $orderId . '.csv';
    
        // Vérifier si le fichier peut être créé
        if (!is_writable(_PS_ROOT_DIR_ . '/vitisoft/orders/')) {
            return json_encode(['status' => 'error', 'message' => 'Le dossier de destination n\'est pas accessible en écriture.']);
        }
    
        // Ouvrir le fichier en mode écriture
        $file = fopen($filename, 'w');

        // Ajouter le BOM UTF-8 pour gérer les caractères spéciaux
        fputs($file, "\xEF\xBB\xBF");
    
        // Définir les en-têtes du fichier CSV
        $headers = [
            'numéro_commande', 'mail_client', 'référence_commande_client', 'date_heure_commande', 
            'civilité_facturation', 'nom_facturation', 'prenom_facturation', 'societe_facturation', 
            'adresse1_facturation', 'adresse2_facturation', 'code_postal_facturation', 'ville_facturation', 
            'pays_facturation', 'téléphone_facturation', 'mobile_facturation', 'nom_livraison', 
            'prénom_livraison', 'adresse1_livraison', 'adresse2_livraison', 'code_postal_livraison', 
            'ville_livraison', 'pays_livraison', 'téléphone_livraison', 'mobile_livraison', 'mode_de_règlement', 
            'commentaire_livraison', 'montant_livraison', 'Transporteur', 'Total_HT', 'numéro_ligne', 
            'numéro_produit_vitisoft', 'désignation', 'quantité', 'taux_tva', 'prix_unitaire', 
            'prix_unitaire_sans_remise', 'montant_remise_unitaire', 'total_ht_ligne'
        ];
        fputcsv($file, $headers);

        $idCarrier = $order->id_carrier;

        // Obtenir le nom du transporteur
        $carrier = new Carrier($idCarrier);
    
        // Parcourir les produits et ajouter leurs informations dans le fichier CSV
        foreach ($products as $index => $product) {
            $line = [
                'numéro_commande' => $order->id,
                'mail_client' => $customer->email,
                'référence_commande_client' => $order->reference,
                'date_heure_commande' => $order->date_add,
                'civilité_facturation' => $customer->id_gender,
                'prenom_facturation' => $billingAddress->firstname,
                'societe_facturation' => $billingAddress->company,
                'adresse1_facturation' => $billingAddress->address1,
                'adresse2_facturation' => $billingAddress->address2,
                'code_postal_facturation' => $billingAddress->postcode,
                'ville_facturation' => $billingAddress->city,
                'pays_facturation' => Country::getNameById(Configuration::get('PS_LANG_DEFAULT'), $billingAddress->id_country),
                'téléphone_facturation' => $billingAddress->phone,
                'mobile_facturation' => $billingAddress->phone_mobile,
                'nom_livraison' => $deliveryAddress->lastname,
                'prénom_livraison' => $deliveryAddress->firstname,
                'adresse1_livraison' => $deliveryAddress->address1,
                'adresse2_livraison' => $deliveryAddress->address2,
                'code_postal_livraison' => $deliveryAddress->postcode,
                'ville_livraison' => $deliveryAddress->city,
                'pays_livraison' => Country::getNameById(Configuration::get('PS_LANG_DEFAULT'), $deliveryAddress->id_country),
                'téléphone_livraison' => $deliveryAddress->phone,
                'mobile_livraison' => $deliveryAddress->phone_mobile,
                'mode_de_règlement' => $order->payment,
                'commentaire_livraison' => $order->gift_message,
                'montant_livraison' => number_format($order->total_shipping_tax_incl, 2), // Arrondi à 2 décimales
                'Transporteur' => $carrier->name,
                'Total_HT' => number_format($order->total_paid_tax_excl, 2), // Arrondi à 2 décimales
                'numéro_ligne' => $index + 1,
                'numéro_produit_vitisoft' => $product['product_reference'],
                'désignation' => $product['product_name'],
                'quantité' => $product['product_quantity'],
                'taux_tva' => number_format($product['tax_rate'], 2), // Arrondi à 2 décimales
                'prix_unitaire' => number_format($product['unit_price_tax_incl'], 2), // Arrondi à 2 décimales
                'prix_unitaire_sans_remise' => number_format($product['unit_price_tax_excl'], 2), // Arrondi à 2 décimales
                'montant_remise_unitaire' => number_format($product['reduction_amount_tax_excl'], 2), // Arrondi à 2 décimales
                'total_ht_ligne' => number_format($product['total_price_tax_excl'], 2) // Arrondi à 2 décimales
            ];            
            fputcsv($file, $line);
        }
    
        // Fermer le fichier après l'écriture
        fclose($file);
    
        // Créer le dossier "processed" si nécessaire et déplacer le fichier
        $processedDirectoryPath = _PS_ROOT_DIR_ . '/vitisoft/orders/processed/';
        if (!is_dir($processedDirectoryPath)) {
            if (!mkdir($processedDirectoryPath, 0777, true)) {
                return json_encode(['status' => 'error', 'message' => 'Échec de la création du dossier de destination.']);
            }
        }
    
        $processedFilePath = $processedDirectoryPath . basename($filename);
        if (rename($filename, $processedFilePath)) {
            Configuration::updateValue('viti_file_' . $orderId, 'uploaded');
            return json_encode(['status' => 'success', 'message' => 'Fichier CSV généré, déplacé et l\'état mis à jour avec succès.', 'file' => $processedFilePath]);
        } else {
            return json_encode(['status' => 'error', 'message' => 'Échec du déplacement du fichier CSV.']);
        }
    }
    
        

    public function hookActionOrderStatusPostUpdate($params)
    {

        if ( isset($params['id_order']) && isset($params['newOrderStatus'])  && Configuration::get('PS_OS_PAYMENT') == $params['newOrderStatus']->id ) {

            $orderId = $params['id_order'];
            
            $this->exportOrderToCSV($orderId);

            file_put_contents(_PS_ROOT_DIR_ . '/vitisoft/debug_hook_net.txt', 'Success 200 : ' . $orderId . PHP_EOL, FILE_APPEND);

        } else {
            var_dump('Order object not found');
            file_put_contents(_PS_ROOT_DIR_ . '/vitisoft/debug_hook_net.txt', 'Error 404 : ' . PHP_EOL, FILE_APPEND);

        }

    }

    public function hookDisplayBackOfficeHeader()
    {
        // Lier le fichier CSS
        $this->context->controller->addCSS($this->_path . 'views/css/styles.css', 'all');
    }


   

}
