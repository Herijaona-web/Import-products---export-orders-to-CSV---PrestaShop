<?php

class AdminExportOrderController extends ModuleAdminController
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
    
        $orders = $this->getAllOrdersDetails();

        // Assignation des données à Smarty pour le template
        $this->context->smarty->assign([
            'message' => 'Export de commande',
            'orders' => $orders
        ]);

        // Définir le template à utiliser pour l'affichage
        $this->setTemplate('export_order.tpl');
    }

    public function getAllOrdersDetails()
    {
        // Récupérer toutes les commandes avec les informations nécessaires
        $orders = Order::getOrdersWithInformations();

        // Tableau pour stocker les détails des commandes
        $orderDetails = [];

        // Parcourir chaque commande
        foreach ($orders as $order) {

            // Vérifier l'état d'export de la commande (si le fichier est téléchargé)
            $orderUpdate = Configuration::get('viti_file_' . $order['id_order']);

            if ($orderUpdate === 'uploaded') {
                $token = Tools::getAdminTokenLite('AdminExportOrder');
                $link = $this->context->link->getAdminLink('AdminExportOrder') . '&file=order_' . $order['id_order'] . '.csv';
                
                // Créer un tableau de détails pour chaque commande
                $orderDetail = [
                    'id_order'        => $order['id_order'],
                    'reference'       => $order['reference'],
                    'nouveau_client'  => $order['id_customer'] == 0 ? 'Oui' : 'Non', // Nouveau client si l'id_customer est 0
                    'client_name'     => $order['firstname'] . ' ' . $order['lastname'], // Nom complet du client
                    'total'           => $order['total_paid_tax_incl'],
                    'payment'         => $order['payment'],
                    'order_date'      => $order['date_add'],
                    // Génération du lien pour télécharger le fichier CSV
                    'link' => $link
                ];

                // Ajouter les détails au tableau final
                $orderDetails[] = $orderDetail;
            }
        }

        return $orderDetails;
    }

    public function display()
    {
        // Gestion du téléchargement de fichier CSV
        if (Tools::getValue('file')) {
            $this->downloadCSV(Tools::getValue('file'));
        } else {
            parent::display();
        }
    }

    public function downloadCSV($filePath)
    {
        // Chemin complet du fichier CSV dans le répertoire principal de PrestaShop
        $fullFilePath = _PS_ROOT_DIR_ . '/vitisoft/orders/processed/' . $filePath;
    
        // Vérifiez si le fichier existe avant de tenter un téléchargement
        if (file_exists($fullFilePath)) {
            // Définir les en-têtes pour le téléchargement du fichier
            header('Content-Description: File Transfer');
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename="' . basename($fullFilePath) . '"');
            header('Content-Length: ' . filesize($fullFilePath));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
    
            // Lire le fichier et l'envoyer au navigateur
            readfile($fullFilePath);
            exit;
        } else {
            die('Fichier introuvable : ' . $fullFilePath);
        }
    }    
    
    
}
