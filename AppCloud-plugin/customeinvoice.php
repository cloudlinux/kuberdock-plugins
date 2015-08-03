<?php

define("CLIENTAREA",true);

require 'init.php';
require 'modules/servers/KuberDock/init.php';

$ca = new WHMCS_ClientArea();
$templatePath = '../../modules/servers/KuberDock/view/smarty';

$ca->setPageTitle('Add custom invoice');

$ca->addToBreadCrumb('index.php',$whmcs->get_lang('globalsystemname'));
$ca->addToBreadCrumb('customeinvoice.php','Custom invoice');

$ca->initPage();
$ca->requireLogin();

if($_POST) {
    $payment = $_POST['payment'];
    $gateway = $_POST['gateway'];

    try {
        $invoiceId = CL_Invoice::model()->createInvoice($ca->getUserID(), $payment, $gateway, false, CL_Invoice::CUSTOM_INVOICE_DESCRIPTION);
        header('Location: /clientarea.php');
    } catch(Exception $e) {
        $ca->assign('error', $e->getMessage());
    }
}

$paymentList = array(5, 10, 25, 50, 100);

$currency = CL_Currency::model()->getDefaultCurrency();
$gateways = $currency->getPaymentGateways();

$ca->assign('gateways', $gateways);
$ca->assign('paymentList', $paymentList);
$ca->assign('currency', $currency);

$ca->setTemplate($templatePath .'/clientarea_custominvoice');
$ca->output();
