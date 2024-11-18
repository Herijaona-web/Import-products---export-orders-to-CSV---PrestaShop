<?php

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/vitisoftintegration.php';

$module = Module::getInstanceByName('vitisoftintegration');

$order = new Order(172);
$newStatus = new OrderState(Configuration::get('PS_OS_PAYMENT'));

$params = [
    'order' => $order,
    'newOrderStatus' => $newStatus,
];

$module->hookActionOrderStatusPostUpdate($params);