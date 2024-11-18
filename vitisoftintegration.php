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
        // Install the module and register the hooks actionOrderStatusPostUpdate and displayBackOfficeHeader
        if (!parent::install() || !$this->registerHook('actionOrderStatusPostUpdate') || !$this->registerHook('displayBackOfficeHeader')) {
            return false;
        }
    
        // Define the path to the main PrestaShop directory
        $prestashopDir = _PS_ROOT_DIR_ . '/vitisoft';
    
        // Create the vitisoft folder if it does not exist
        if (!is_dir($prestashopDir)) {
            if (!mkdir($prestashopDir, 0777, true)) {
                return false; // Return false if the directory creation fails
            }
        }
    
        // Create the necessary subdirectories
        $subDirs = [
            $prestashopDir . '/orders/processed',
            $prestashopDir . '/products/processed'
        ];
    
        foreach ($subDirs as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                return false; // Return false if any subdirectory creation fails
            }
        }
    
        // Set the correct permissions to allow access to the files
        foreach ($subDirs as $dir) {
            // Ensure that files are readable/writable
            chmod($dir, 0777); // Permissions for read, write, execute for all
        }
    
        // Add the admin tabs
        if (!$this->addTab('AdminExportOrder', 'AdminParentOrders', 'Exported in CSV (Vitisoft)', $this->name)) {
            return false;
        }
    
        if (!$this->addTab('AdminVitisoftImport', 'AdminCatalog', 'Product CSV Imports', $this->name)) {
            return false;
        }
    
        return true; // Return true if the installation succeeds
    }       
    
    public function addTab($className, $parentTabClass, $tabName, $moduleName)
    {
        $tab = new Tab();
        $tab->class_name = $className;
        $tab->module = $moduleName;
    
        $tab->id_parent = Tab::getIdFromClassName($parentTabClass);
    
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }
    
        return $tab->add();
    }    

    public function uninstall()
    {
        return parent::uninstall();
    }

    // Cron function
    public function processVitisoftFiles()
    {
        // Folder directory
        $directoryPath = _PS_ROOT_DIR_ . '/vitisoft/products/';
        $processedDirectoryPath = _PS_ROOT_DIR_ . '/vitisoft/products/processed/';
    
        // Check folder exist
        if (!is_dir($directoryPath)) {
            return 'Le répertoire des fichiers à traiter n\'existe pas.';
        }
        if (!is_dir($processedDirectoryPath) && !mkdir($processedDirectoryPath, 0777, true)) {
            return 'Impossible de créer le répertoire pour les fichiers traités.';
        }
    
        // Get csv file
        $files = glob($directoryPath . '*.csv');
        if (empty($files)) {
            return 'Aucun fichier CSV trouvé à traiter.';
        }
    
        // Process each file
        foreach ($files as $file) {
            try {
                $this->importProductsFromCSV($file);
                rename($file, $processedDirectoryPath . basename($file)); // Move file
            } catch (Exception $e) {
                return 'Erreur lors du traitement du fichier : ' . basename($file) . '. ' . $e->getMessage();
            }
        }
    
        return 'Traitement des fichiers Vitisoft terminé.';
    }    

    // Function to import products from the CSV file
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

    // Update or create product
    private function createOrUpdateProduct($name, $reference, $quantity, $price, $imageUrl, $category, $taxId, $description, $shortDescription)
    {
        // Check product by reference
        $productId = Product::getIdByReference($reference);
        
        // If the product exists, load the product object for updating
        if ($productId) {
            $product = new Product($productId);
        } else {
            // Otherwise, create a new product object
            $product = new Product();
        }
        
        // Set the common properties
        $product->name = array_fill_keys(Language::getIDs(false), $name);
        $product->reference = $reference;
        $product->price = $price;
        $product->id_tax_rules_group = $taxId ?: 0;
        $product->active = 1;
        $product->description = array_fill_keys(Language::getIDs(false), $description);
        $product->description_short = array_fill_keys(Language::getIDs(false), $shortDescription);
    
        // Set the default category
        if ($category) {
            $product->id_category_default = $category != 'HOME' ? (int) $category : 2;
        }
    
        // add or update product
        if ($productId ? $product->update() : $product->add()) {
            StockAvailable::setQuantity($product->id, 0, $quantity);
    
            // Assign the product to the category
            if ($category) {
                $categoryObj = new Category($category);
                if (Validate::isLoadedObject($categoryObj)) {
                    $product->addToCategories([$categoryObj->id]);
                }
            }
    
            // Add or update the image if it is valid
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
    
        // Download product by url
        if ($this->downloadImage($imageUrl, $tempImagePath)) {
            // Check if the product already has a main image (cover)
            $existingCoverImage = Db::getInstance()->getRow('
                SELECT id_image FROM '._DB_PREFIX_.'image
                WHERE id_product = '.(int)$productId.' AND cover = 1
            ');
    
            // If a main image exists, delete it
            if ($existingCoverImage) {
                $existingImage = new Image((int)$existingCoverImage['id_image']);
                $existingImage->delete();
            }
    
            // Create a new object
            $image = new Image();
            $image->id_product = $productId;
            $image->position = Image::getHighestPosition($productId) + 1;
            $image->cover = true;  // cover principal
    
            if ($image->add()) {
                $imagePath = _PS_PROD_IMG_DIR_ . $image->getImgPath() . '.jpg';
                $directoryPath = dirname($imagePath);
    
                // Create the directory if necessary
                if (!file_exists($directoryPath)) {
                    mkdir($directoryPath, 0777, true);
                }
    
                // Move the uploaded image to the product directory
                if (rename($tempImagePath, $imagePath)) {
                    $imagesTypes = ImageType::getImagesTypes('products');
                    foreach ($imagesTypes as $imageType) {
                        // Resize the image for different sizes
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

    // Download image by URL
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
        // Retrieve order
        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            return json_encode(['status' => 'error', 'message' => 'Commande introuvable.']);
        }

        // Client
        $customer = new Customer($order->id_customer);
        $billingAddress = new Address($order->id_address_invoice);
        $deliveryAddress = new Address($order->id_address_delivery);

        // Products
        $products = $order->getProducts();

        // Set the path for the CSV file to be generated
        $filename = _PS_ROOT_DIR_ . '/vitisoft/orders/order_' . $orderId . '.csv';

        // Check if the file can be created
        if (!is_writable(_PS_ROOT_DIR_ . '/vitisoft/orders/')) {
            return json_encode(['status' => 'error', 'message' => 'Le dossier de destination n\'est pas accessible en écriture.']);
        }

        // Open the file in write mode
        $file = fopen($filename, 'w');

        // Add the UTF-8 BOM to handle special characters
        fputs($file, "\xEF\xBB\xBF");

        // Define the header csv
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

        // Get the carrier name
        $carrier = new Carrier($idCarrier);

        // All informations csv
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
                'montant_livraison' => number_format($order->total_shipping_tax_incl, 2),
                'Transporteur' => $carrier->name,
                'Total_HT' => number_format($order->total_paid_tax_excl, 2),
                'numéro_ligne' => $index + 1,
                'numéro_produit_vitisoft' => $product['product_reference'],
                'désignation' => $product['product_name'],
                'quantité' => $product['product_quantity'],
                'taux_tva' => number_format($product['tax_rate'], 2),
                'prix_unitaire' => number_format($product['unit_price_tax_incl'], 2),
                'prix_unitaire_sans_remise' => number_format($product['unit_price_tax_excl'], 2),
                'montant_remise_unitaire' => number_format($product['reduction_amount_tax_excl'], 2),
                'total_ht_ligne' => number_format($product['total_price_tax_excl'], 2)
            ];            
            fputcsv($file, $line);
        }

        // Close the file after writing
        fclose($file);

        return json_encode(['status' => 'success', 'message' => 'Fichier CSV généré avec succès.', 'file' => $filename]);
    }

    public function hookActionOrderStatusPostUpdate($params)
    {

        if ( isset($params['id_order']) && isset($params['newOrderStatus'])  && Configuration::get('PS_OS_PAYMENT') == $params['newOrderStatus']->id ) {

            $orderId = $params['id_order'];
            
            $this->exportOrderToCSV($orderId);

            file_put_contents(_PS_ROOT_DIR_ . '/vitisoft/debug_hook_net.txt', 'Success 200 : ' . $orderId . PHP_EOL, FILE_APPEND);

        } else {
            file_put_contents(_PS_ROOT_DIR_ . '/vitisoft/debug_hook_net.txt', 'Error 404 : ' . PHP_EOL, FILE_APPEND);
        }

    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/styles.css', 'all');
    }


}
