<?php

class AdminExportOrderController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();
        $orders = $this->getAllOrdersDetails();

        $this->context->smarty->assign([
            'message' => 'Export de commande',
            'orders' => $orders
        ]);

        $this->setTemplate('export_order.tpl');
    }

    public function getAllOrdersDetails()
    {
        $orders = Order::getOrdersWithInformations();
        $orderDetails = [];

        foreach ($orders as $order) {

            $orderUpdate = Configuration::get('viti_file_' . $order['id_order']);

            if ($orderUpdate === 'uploaded') {
                $token = Tools::getAdminTokenLite('AdminExportOrder');
                $link = $this->context->link->getAdminLink('AdminExportOrder') . '&file=order_' . $order['id_order'] . '.csv';
                
                $orderDetail = [
                    'id_order'        => $order['id_order'],
                    'reference'       => $order['reference'],
                    'nouveau_client'  => $order['id_customer'] == 0 ? 'Oui' : 'Non', // Nouveau client si l'id_customer est 0
                    'client_name'     => $order['firstname'] . ' ' . $order['lastname'], // Nom complet du client
                    'total'           => $order['total_paid_tax_incl'],
                    'payment'         => $order['payment'],
                    'order_date'      => $order['date_add'],
                    'link' => $link
                ];

                $orderDetails[] = $orderDetail;
            }
        }

        return $orderDetails;
    }

    public function display()
    {
        if (Tools::getValue('file')) {
            $this->downloadCSV(Tools::getValue('file'));
        } else {
            parent::display();
        }
    }

    public function downloadCSV($filePath)
    {
        $fullFilePath = _PS_ROOT_DIR_ . '/vitisoft/orders/processed/' . $filePath;

        if (file_exists($fullFilePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename="' . basename($fullFilePath) . '"');
            header('Content-Length: ' . filesize($fullFilePath));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
    
            readfile($fullFilePath);
            exit;
        } else {
            die('Fichier introuvable : ' . $fullFilePath);
        }
    }    
    
    
}
