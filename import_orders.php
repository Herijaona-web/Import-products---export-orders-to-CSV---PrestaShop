<?php

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/vitisoftintegration.php';

$module = Module::getInstanceByName('vitisoftintegration');

// echo 'User: ' . get_current_user();

// if (!$module) {
//     die('Le module n\'a pas pu être chargé.');
// }

// try {
//     $orderId = $module->createOrderForNewClient();
//     if ($orderId) {
//         echo 'Commande créée avec succès. ID de la commande : ' . $orderId;
//     } else {
//         echo 'Erreur lors de la création de la commande.';
//     }
// } catch (Exception $e) {
//     echo 'Erreur : ' . $e->getMessage();
// }


// Remplacez 'ORDER_ID' par l'ID d'une commande réelle
$order = new Order(172);
$newStatus = new OrderState(Configuration::get('PS_OS_PAYMENT'));

$params = [
    'order' => $order,
    'newOrderStatus' => $newStatus,
];

$module->hookActionOrderStatusPostUpdate($params);