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

        $order_update = Configuration::get('viti_file_172');

        // Afficher un message ou exécuter une fonction d'export ici
        $this->context->smarty->assign([
            'message' => 'Export de commande',
            'order_update' => $order_update,
            'orders' => $orders
        ]);

        $this->setTemplate('export_order.tpl');
    }

    public function getAllOrdersDetails()
    {
        
        // Récupérer toutes les commandes
        $orders = Order::getOrdersWithInformations();

        // Tableau pour stocker les résultats
        $orderDetails = [];

        // Parcourir chaque commande
        foreach ($orders as $order) {

            // Vérifier si la commande est acceptée
            $orderUpdate = Configuration::get('viti_file_' . $order['id_order']);

            if ($orderUpdate === 'uploaded') {
                // Créer un tableau de détails pour chaque commande
                $orderDetail = [
                    'id_order'        => $order['id_order'],
                    'reference'       => $order['reference'],
                    'nouveau_client'  => $order['id_customer'] == 0 ? 'Oui' : 'Non', // Nouveau client si l'id_customer est 0
                    // 'livraison'       => $order['shipping'], // Commenté car non utilisé
                    'client_name'     => $order['firstname'] . ' ' . $order['lastname'], // Nom complet du client
                    'total'           => $order['total_paid_tax_incl'],
                    'payment'         => $order['payment'],
                    // 'order_state'     => $this->getOrderStateName($order['current_state']), // Commenté car non utilisé
                    'order_date'      => $order['date_add']
                ];

                // Ajouter les détails au tableau final
                $orderDetails[] = $orderDetail;
            }
        }

        return $orderDetails;
    }

}

