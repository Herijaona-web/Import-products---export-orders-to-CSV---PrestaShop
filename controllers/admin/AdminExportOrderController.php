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

        // Récupérer la configuration pour l'export
        $order_update = Configuration::get('viti_file_172');

        // Assignation des données à Smarty pour le template
        $this->context->smarty->assign([
            'message' => 'Export de commande',
            'order_update' => $order_update,
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
    // Chemin complet du fichier CSV
    $fullFilePath = _PS_MODULE_DIR_ . 'vitisoftintegration/files/orders/processed/' . $filePath;

    // Générer un token d'accès pour la page admin
    $token = Tools::getAdminTokenLite('AdminExportOrder');

    // Vérifiez si le fichier existe avant de tenter un téléchargement
    if (file_exists($fullFilePath)) {
        // Rediriger avec le token pour forcer l'accès
        $downloadUrl = $this->context->link->getBaseLink() . 'modules/vitisoftintegration/files/orders/processed/' . $filePath . '?token=' . $token;

        // Utiliser un en-tête HTTP pour la redirection ou appeler le téléchargement directement
        header('Location: ' . $downloadUrl);
        exit;
    } else {
        die('Fichier introuvable : ' . $fullFilePath);
    }
    }
    
    
}
