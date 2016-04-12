<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Base;
use base\CL_Tools;
use base\models\CL_Currency;
use base\models\CL_Invoice;
use base\models\CL_User;
use base\models\CL_BillableItems;
use components\KuberDock_Units;
use exceptions\CException;

include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';

/**
 * Run After the cron has completed and the cron email has sent, but before the database backups.
 *  The hook can be used to run automatic actions daily along with the WHMCS standard cron actions.
 */
function KuberDock_DailyCronJob() {
    echo "Starting '".KUBERDOCK_MODULE_NAME."' hook\n";

    $model = KuberDock_Hosting::model();
    $services = $model->getByUserStatus(CL_User::STATUS_ACTIVE);

    foreach($services as $service) {
        $service = $model->loadByParams($service);

        try {
            $service->calculate();
        } catch(Exception $e) {
            echo 'ERROR: serviceId-'. $service->id . ' '. $e->getMessage() . "\n";
            CException::log($e);
        }
    }

    echo " - Done\n";
}
add_hook('DailyCronJob', 1, 'KuberDock_DailyCronJob');

/**
 * Run: after edit product config options in "Setup" => "Product/Services" => Product/Services
 *
 * @param $params array('pid' => productId)
 */
function KuberDock_ProductEdit($params)
{
    $options = CL_Base::model()->getPost('packageconfigoption');
    $product = KuberDock_Product::model()->loadById($params['pid']);

    if($product->isKuberProduct()) {
        if(empty($options)) {
            return;
        }

        $i = 1;
        foreach($product->getConfig() as $row) {
            $value = isset($options[$i]) ? $options[$i] : '';
            $product->setConfigOptionByIndex($i, $value);
            $i++;
        }

        try {
            if(!$product->getKubes()) {
                $product->hidden = 1;
            }

            if($product->isFixedPrice()) {
                $product->setConfigOption('firstDeposit', 0);
            }

            $product->save();
            
            KuberDock_Addon_Kube::model()->updateByAttributes(array(
                'server_id' => $product->getServer()->id,
            ), array('product_id' => $product->id));

            $addonProduct = KuberDock_Addon_Product::model();
            $addonProduct->updateKubePricing($params['pid']);

            if($product->isTrial()) {
                $server = $product->getServer();
                $standardKube = KuberDock_Addon_Kube::model()->getStandardKube($product->id, $server->id);
                if(!$standardKube) {
                    KuberDock_Addon_Kube::model()->createStandardKube($product->id, $server->id);
                    $product->hidden = 0;
                    $product->save();
                }
            }
        } catch(Exception $e) {
            CException::log($e);
        }
    }
}
add_hook('ProductEdit', 1, 'KuberDock_ProductEdit');

/**
 * Run: Immediately before the product is removed from the database
 *
 * @param int $params
 */
function KuberDock_ProductDelete($params)
{
    try {
        $addonProduct = KuberDock_Addon_Product::model();
        $addonProduct->deleteKubePricing($params['pid']);
    } catch(Exception $e) {
        CException::log($e);
    }
}
add_hook('ProductDelete', 1, 'KuberDock_ProductDelete');

/**
 * Run immediately prior to a service being removed from the database
 *
 * @param array $params
 */
function KuberDock_ServiceDelete($params)
{
    try {
        $service = KuberDock_Hosting::model()->loadById($params['serviceid']);
        $product = KuberDock_Product::model()->loadById($service->packageid);
        if(!$product->isKuberProduct()) return;

        $service->getAdminApi()->deleteUser($service->username);

        if($product->getConfigOption('enableTrial')) {
            KuberDock_Addon_Trial::model()->deleteByAttributes(array(
                'user_id' => $service->userid,
            ));
        }
    } catch(Exception $e) {
        CException::log($e);
    }
}
add_hook('ServiceDelete', 1, 'KuberDock_ServiceDelete');

/**
 * Run: As the final checkout button is pressed, this hook is run and an error can be returned to stop the process.
 *
 * @param $params
 * @return array
 * @throws Exception
 */
function KuberDock_ShoppingCartValidateCheckout($params)
{
    global $CONFIG;

    $errors = array();
    $userId = $params['userid'];


    if(isset($_SESSION['cart']) && $userId) {
        foreach($_SESSION['cart']['products'] as $product) {
            $product = KuberDock_Product::model()->loadById($product['pid']);
            // TOS enabled but not accepted
            if(!$product->isKuberProduct()
                || ((bool) $CONFIG['EnableTOSAccept'] && !isset($_POST['accepttos']))) {
                continue;
            }

            try {
                $server = $product->getServer();
                $userProducts = KuberDock_Product::model()->getByUser($userId, $server->id);

                $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadBySessionId();
                if ($predefinedApp) {
                    $service = \KuberDock_Hosting::model()->loadByParams(current($userProducts));
                    $predefinedApp->user_id = $userId;
                    $predefinedApp->save();

                    if ($product->isFixedPrice()) {
                        if (!$service) {
                            if ($product->isSetupPayment()) {
                                continue;
                            }
                            $result = \base\models\CL_Order::model()->createOrder($userId, $product->id);
                            \base\models\CL_Order::model()->acceptOrder($result['orderid']);
                            $service = \KuberDock_Hosting::model()->loadById($result['productids']);
                        }
                        $item = $product->addBillableApp($userId, $predefinedApp);
                        if (!($pod = $predefinedApp->isPodExists($service->id))) {
                            $pod = $predefinedApp->create($service->id, 'unpaid');
                        }
                        $predefinedApp->pod_id = $pod['id'];
                        $predefinedApp->save();
                        $item->pod_id = $pod['id'];
                        $item->save();
                        $product->removeFromCart();

                        if ($item->isPayed()) {
                            $product->startPodAndRedirect($item->service_id, $item->pod_id, true);
                        } else {
                            $product->jsRedirect('viewinvoice.php?id=' . $item->invoice_id);
                        }
                    } else {
                        if (!$service) {
                            $result = \base\models\CL_Order::model()->createOrder($userId, $product->id);
                            \base\models\CL_Order::model()->acceptOrder($result['orderid']);
                            $service = \KuberDock_Hosting::model()->loadById($result['productids']);
                            $product->removeFromCart();
                            $product->createPodAndRedirect($service->id, true);
                        } else {
                            $product->removeFromCart();
                            $product->createPodAndRedirect($service->id, true);
                        }
                    }
                }
            } catch(Exception $e) {
                CException::log($e);
                CException::displayError($e, true);
            }

            $errorMessage = 'You can not buy more than 1 KuberDock product.';
            if($userProducts && !in_array($errorMessage, $userProducts)) {
                $errors[] = $errorMessage;
            }

            if($product->getConfigOption('enableTrial') && KuberDock_Addon_Trial::model()->loadById($userId)) {
                $errors[] = 'You are already have trial KuberDock product.';
            }
        }
    }

    return $errors;
}
add_hook('ShoppingCartValidateCheckout', 1, 'KuberDock_ShoppingCartValidateCheckout');

function KuberDock_PreShoppingCartCheckout($params)
{

}
//add_hook('PreShoppingCartCheckout', 1, 'KuberDock_PreShoppingCartCheckout');

/**
 * Run: This hook runs on every client area page load and can accept a return of information
 * to be added to the <head> of your output.
 *
 * @return mixed
 */
function KuberDock_ClientAreaHeadOutput()
{
    $assets = new KuberDock_Assets();
    $assets->registerStyleFiles(array(
        'styles',
    ));

    return $assets->renderScriptFiles(false) . $assets->renderStyleFiles(false);
}
add_hook('ClientAreaHeadOutput', 1, 'KuberDock_ClientAreaHeadOutput');

/**
 * Run: This hook runs on every admin area page load and can accept a return of information
 * to be added to the <head> of your output.
 *
 * @return mixed
 */
function KuberDock_AdminAreaHeadOutput()
{
    $assets = new KuberDock_Assets();
    $assets->registerStyleFiles(array(
        'styles',
    ));
    $assets->registerScriptFiles(array(
        'admin',
        'url.min',
    ));

    return $assets->renderScriptFiles(false) . $assets->renderStyleFiles(false);
}
add_hook('AdminAreaHeadOutput', 0, 'KuberDock_AdminAreaHeadOutput');

/**
 * Run: after an upgrade invoice has been paid, but before the ChangePackage command is sent to the server.
 *
 * @param int $upgradeId
 */
function KuberDock_AfterConfigOptionsUpgrade($upgradeId)
{
    // future
}
//add_hook('AfterConfigOptionsUpgrade', 1, 'KuberDock_AfterConfigOptionsUpgrade');

/**
 * Run: As the cart complete page is displayed to the client
 *
 * @param array $params
 */
function KuberDock_ShoppingCartCheckoutCompletePage($params)
{

}
//add_hook('ShoppingCartCheckoutCompletePage', 1, 'KuberDock_ShoppingCartCheckoutCompletePage');

/**
 * @param $params
 */
function KuberDock_AfterShoppingCartCheckout($params)
{
    $order = \base\models\CL_Order::model()->loadById($params['OrderID']);
    $predefinedApp = KuberDock_Addon_PredefinedApp::model()->loadBySessionId();
    if ($predefinedApp) {
        $predefinedApp->user_id = $order->userid;
        $predefinedApp->save();
    }

    /*$client = \base\models\CL_Client::model()->loadById($order->userid);
    $client->filterValues();

    $service_id = reset($params['ServiceIDs']);
    $service = \KuberDock_Hosting::model()->loadById($service_id);

    $service->username = $client->email;

    $product = \KuberDock_Product::model()->loadById($service->packageid);
    if ($product->servertype != 'KuberDock') {
        return;
    }
    $product->setClient($client);

    $product->createUser($service);*/
}
add_hook('AfterShoppingCartCheckout', 1, 'KuberDock_AfterShoppingCartCheckout');

/**
 * Run: As the cart page is being displayed, this hook is run separately for each product added to the cart.
 *
 * @param $params
 * @return array
 */
function KuberDock_OrderProductPricingOverride($params)
{
    $product = KuberDock_Product::model()->loadById($params['pid']);
    if(!$product) return;

    $setup = $product->getConfigOption('firstDeposit') ? $product->getConfigOption('firstDeposit') : 0;
    $recurring = 0;

    $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadBySessionId();
    // Price for yaml PA
    if ($predefinedApp && !$predefinedApp->getPod() && $product->isFixedPrice()) {
        $recurring = $predefinedApp->getTotalPrice(true);
    }

    $productPrice = array(
        'setup' => $setup,
        'recurring' => $recurring,
    );

    return $productPrice;
}
add_hook('OrderProductPricingOverride', 1, 'KuberDock_OrderProductPricingOverride');

/**
 * Rewrite KuberDock product billing cycle & price
 *
 * Run: This hook runs on the load of every client area page and can accept a return of values to be included
 * as additional smarty fields.
 *
 * @param array $params
 * @return mixed
 */
function KuberDock_ClientAreaPage($params)
{
    global $smarty;

    $values = $smarty->get_template_vars();
    $currency = CL_Currency::model()->getDefaultCurrency();

    // Client area
    if(!isset($values['products']) && in_array($values['filename'], array('clientarea'))) {
        $products = CL_Tools::getKeyAsField(KuberDock_Product::model()->loadByAttributes(array(
            'servertype' => KUBERDOCK_MODULE_NAME,
        )), 'id');
        $services = CL_Tools::getKeyAsField(KuberDock_Hosting::model()->getByUserStatus());

        // Service list
        if(isset($values['services'])) {
            foreach($values['services'] as $k=>$service) {
                if($service['module'] != KUBERDOCK_MODULE_NAME) {
                    continue;
                }

                $productId = $services[$service['id']]['product_id'];
                $product = KuberDock_Product::model()->loadByParams($products[$productId]);
                $serviceModel = KuberDock_Hosting::model()->loadByParams($service);

                $values['services'][$k]['billingcycle'] = $product->getReadablePaymentType();
                $values['services'][$k]['nextduedate'] = CL_Tools::getFormattedDate(new DateTime());

                $enableTrial = $product->getConfigOption('enableTrial');
                $trialTime = $product->getConfigOption('trialTime');
                $regDate = DateTime::createFromFormat(CL_Tools::getDateFormat(), $serviceModel->regdate);

                if($enableTrial && $serviceModel->isTrialExpired($regDate, $trialTime)) {
                    $values['services'][$k]['statustext'] = 'Expired';
                }
            }
        } elseif(isset($products[$values['pid']])) {
            $product = KuberDock_Product::model()->loadByParams($products[$values['pid']]);

            $enableTrial = $product->getConfigOption('enableTrial');
            $trialTime = $product->getConfigOption('trialTime');
            $regDate = DateTime::createFromFormat(CL_Tools::getDateFormat(), $values['regdate']);

            if($enableTrial && KuberDock_Hosting::model()->isTrialExpired($regDate, $trialTime)) {
                $values['status'] = 'Expired';
            }

            $values['firstpaymentamount'] = $currency->getFullPrice($product->getConfigOption('firstDeposit'));
            $values['billingcycle'] = $product->getReadablePaymentType();
            $values['nextduedate'] = CL_Tools::getFormattedDate(new DateTime());
        }
    }

    // Client cart area
    if(in_array($values['filename'], array('cart'))) {
        $products = CL_Tools::getKeyAsField(KuberDock_Product::model()->loadByAttributes(array(
            'servertype' => KUBERDOCK_MODULE_NAME,
        )), 'id');

        if(isset($values['products'])) {
            foreach($values['products'] as $k => &$product) {
                if(!isset($products[$product['pid']])) {
                    continue;
                }

                $p = KuberDock_Product::model()->loadById($product['pid']);
                if(!$p->isKuberProduct()) continue;
                $product['features'] = $p->getDescription();

                if($depositPrice = $p->getConfigOption('firstDeposit')) {
                    $product['pricing']['minprice']['price'] = ' First Deposit '.$currency->getFullPrice($depositPrice);

                    if(isset($product['pricingtext'])) {
                        $product['pricingtext'] .= ' + First Deposit '.$currency->getFullPrice($depositPrice);
                    }
                }

                if(($predefinedApp = KuberDock_Addon_PredefinedApp::model()->loadBySessionId()) && isset($product['pricingtext'])) {
                    $price =  $currency->getFullPrice($predefinedApp->getTotalPrice(true));
                    $product['pricingtext'] = $price . '/' . $p->getReadablePaymentType();
                    $product['pricing']['totaltodayexcltax'] = $price;
                    $product['pricing']['totalTodayExcludingTaxSetup'] = $price;
                    $product['billingcyclefriendly'] = $p->getReadablePaymentType();
                    $product['productinfo']['groupname'] = 'Package ' . $predefinedApp->getAppPackageName();
                    $product['productinfo']['name'] = ucfirst($predefinedApp->getName());
                }
            }
        }

        if(isset($values['productinfo']) && isset($products[$values['productinfo']['pid']])) {
            $product = KuberDock_Product::model()->loadById($values['productinfo']['pid']);
            $predefinedApp = KuberDock_Addon_PredefinedApp::model()->loadBySessionId();
            $kubes = KuberDock_Addon_Kube::model()->loadByAttributes(array(
                'product_id' => $values['productinfo']['pid'],
            ));

            if ($predefinedApp) {
                $values['productinfo']['name'] = ucfirst($predefinedApp->getName());
                $values['productinfo']['description'] = 'Package ' . $predefinedApp->getAppPackageName();
            }

            $customFields = array();

            if ((float) $product->getConfigOption('priceIP')) {
                $customFields[] = array(
                    'input' => '',
                    'name' => 'Public IP',
                    'description' => $currency->getFullPrice($product->getConfigOption('priceIP'))
                        . ' / ' . $product->getReadablePaymentType(),
                );
            }

            if ((float) $product->getConfigOption('pricePersistentStorage')) {
                $customFields[] = array(
                    'input' => '',
                    'name' => 'Persistent Storage',
                    'description' => $currency->getFullPrice($product->getConfigOption('pricePersistentStorage'))
                        . ' / 1 ' . KuberDock_Units::getHDDUnits(),
                );
            }

            if ((float) $product->getConfigOption('priceOverTraffic')) {
                $customFields[] = array(
                    'input' => '',
                    'name' => 'Additional Traffic',
                    'description' => $currency->getFullPrice($product->getConfigOption('priceOverTraffic'))
                        . ' / 1 ' . KuberDock_Units::getTrafficUnits(),
                );
            }
            $values['customfields'] = array_merge($values['customfields'], $customFields);

            $desc = 'Per Kube - %s, CPU - %s, Memory - %s %s, HDD - %s %s, Traffic - %s %s';
            $resources = false;

            foreach($kubes as $kube) {
                if ($predefinedApp) {
                    $kubeType = $predefinedApp->getKubeType();
                    if ($kubeType != $kube['kuber_kube_id']) continue;
                    $resources = true;
                }
                $values['customfields'][] = array(
                    'input' => '',
                    'name' => $resources ? 'Additional Resources' : 'Kube ' . $kube['kube_name'],
                    'description' => vsprintf($desc, array(
                        $currency->getFullPrice($kube['kube_price']) .' / '. $product->getReadablePaymentType(),
                        (float) $kube['cpu_limit'],
                        $kube['memory_limit'],
                        KuberDock_Units::getMemoryUnits(),
                        $kube['hdd_limit'],
                        KuberDock_Units::getHDDUnits(),
                        $kube['traffic_limit'],
                        KuberDock_Units::getTrafficUnits(),
                    )),
                );
            }
        }
    }

    // Upgrade area
    if(in_array($values['filename'], array('upgrade'))) {
        if(is_array($values['upgradepackages'])) {
            $service = KuberDock_Hosting::model()->loadById($values['id']);
            $product = KuberDock_Product::model()->loadById($service->packageid);
            if($product->isKuberProduct() && $product->getConfigOption('enableTrial')) {
                $service->amount = 0;
                $service->save();
            }

            foreach($values['upgradepackages'] as &$row) {
                $product = KuberDock_Product::model()->loadById($row['pid']);

                if(($firstDeposit = $product->getConfigOption('firstDeposit')) && $product->isKuberProduct()) {
                    $values['LANG']['orderfree'] = $currency->getFullPrice($firstDeposit).' First Deposit';
                    $row['pricing']['onetime'] = $currency->getFullPrice($firstDeposit).' First Deposit';
                    $row['pricing']['minprice']['price'] = $currency->getFullPrice($firstDeposit);
                }
            }
        } elseif(is_array($values['upgrades'])) {
            $upgrade = current($values['upgrades']);
            $model = new KuberDock_Product();
            $oldProduct = $model->loadById($upgrade['oldproductid']);
            $model = new KuberDock_Product();
            $newProduct = $model->loadById($upgrade['newproductid']);

            if(($firstDeposit = $newProduct->getConfigOption('firstDeposit')) && $oldProduct->isKuberProduct()) {
                $values['subtotal'] = $values['total'] = $currency->getFullPrice($firstDeposit);
                if(isset($values['upgrades'][0])) {
                    $values['upgrades'][0]['price'] = $currency->getFullPrice($firstDeposit);
                }
            }
        }
    }

    $smarty->assign($values);

    return $values;
}
add_hook('ClientAreaPage', 1, 'KuberDock_ClientAreaPage');

/**
 * @return string
 *
 * Run: As the main client area page is being displayed - clientarea.php without any action.
 */
function KuberDock_ClientAreaHomepage()
{
}
//add_hook('ClientAreaHomepage', 1, 'KuberDock_ClientAreaHomepage');

/**
 * @param $params
 */
function KuberDock_InvoiceCreated($params)
{
    $invoiceItems = \base\models\CL_BillableItems::model()->getByInvoice($params['invoiceid']);

    foreach($invoiceItems as $invoiceItem) {
        $data = KuberDock_Addon_Items::model()->loadByAttributes(array(
            'billable_item_id' => $invoiceItem['relid'],
        ), '', array(
            'order' => 'ID DESC',
        ));

        if($data) {
            $invoice = CL_Invoice::model()->loadById($params['invoiceid']);
            $item = KuberDock_Addon_Items::model()->loadByParams(current($data));
            $model = new KuberDock_Addon_Items();
            $model->setAttributes($item->getAttributes());
            unset($model->id);
            $model->invoice_id = $params['invoiceid'];
            $model->status = $invoice->status;
            $model->save();
        }
    }
}
add_hook('InvoiceCreated', 1, 'KuberDock_InvoiceCreated');

/**
 * Run: This hook runs as an invoice status is changing from Unpaid to Paid after all automation associated
 * with the invoice has run.
 *
 * @param $params
 */
function KuberDock_InvoicePaid($params)
{
    $invoiceId = $params['invoiceid'];
    $model = CL_Invoice::model();
    $invoice = $model->loadById($invoiceId);

    try {
        if($invoice->isCustomInvoice()) {
            $model->addCredit($invoice->userid, $invoice->subtotal, 'Adding funds via custom invoice '.$invoice->id);
        }

        if($invoice->isSetupInvoice()) {
            $model->addCredit($invoice->userid, $invoice->subtotal, 'Adding funds for setup fee '.$invoice->id);
        }

        // Start pod
        if($item = KuberDock_Addon_Items::model()->loadByInvoice($invoiceId)) {
            $item->status = CL_Invoice::STATUS_PAID;
            $item->save();

            $product = KuberDock_Product::model();
            $product->startPodAndRedirect($item->service_id, $item->pod_id, true);
        }

        // Add additional kubes
        if($invoice->isUpdateKubesInvoice()) {
            if($data = KuberDock_Hosting::model()->getByUser($invoice->userid)) {
                $invoiceItem = \base\models\CL_InvoiceItems::model()->loadByAttributes(array(
                    'type' => CL_BillableItems::TYPE,
                    'invoiceid' => $invoice->id,
                ), 'relid > 0');

                $invoiceItem = current($invoiceItem);
                $billableItem = CL_BillableItems::model()->loadById($invoiceItem['relid']);
                $billableItem->amount += $invoice->subtotal;
                $billableItem->save();

                $service = KuberDock_Hosting::model()->loadByParams(current($data));
                $params = json_decode($invoice->invoiceitems['notes'], true);
                $service->getAdminApi()->redeployPod($params['id'], $params);
                // Update app
                $data = KuberDock_Addon_Items::model()->loadByAttributes(array(
                    'billable_item_id' => $billableItem->id
                ));
                if($addonItem = current($data)) {
                    $pod = $service->getApi()->getPod($params['id']);
                    $app = KuberDock_Addon_PredefinedApp::model()->loadById($addonItem['app_id']);
                    $app->data = json_encode($pod);
                    $app->save();
                }
            }
        }
    } catch(Exception $e) {
        CException::log($e);
    }
}
add_hook('InvoicePaid', 1, 'KuberDock_InvoicePaid');

/**
 * Run: This hook runs as an invoice status is changing to Cancelled. This can be from the invoice in the admin area,
 * a mass update action, cancelling an order, a submitted cancellation request or a client disabling the auto
 * renewal of a domain.
 *
 * @param $params
 */
function KuberDock_InvoiceCancelled($params)
{
    $invoiceId = $params['invoiceid'];
    $model = CL_Invoice::model();
    $invoice = $model->loadById($invoiceId);

    if(!$invoice) {
        return;
    }

    try {
        if($invoice->isCustomInvoice()) {
            $model->addCredit($invoice->userid, -$invoice->subtotal, 'Remove funds via custom invoice '.$invoice->id);
        }

        if($invoice->isSetupInvoice()) {
            $model->addCredit($invoice->userid, -$invoice->subtotal, 'Remove funds for setup fee '.$invoice->id);
        }
    } catch(Exception $e) {
        CException::log($e);
    }
}
add_hook('InvoiceCancelled', 1, 'KuberDock_InvoiceCancelled');

/**
 * Run: This hook runs as an invoice status is changing to Unpaid via the Admin area. This can be from a single invoice,
 * or a mass action. The hook runs for each invoice being processed.
 *
 * @param $params
 */
function KuberDock_InvoiceUnpaid($params)
{
    $invoiceId = $params['invoiceid'];
    $model = CL_Invoice::model();
    $invoice = $model->loadById($invoiceId);

    try {
        if($invoice->isCustomInvoice()) {
            $model->addCredit($invoice->userid, -$invoice->subtotal, 'Remove funds via custom invoice '.$invoice->id);
        }

        if($invoice->isSetupInvoice()) {
            $model->addCredit($invoice->userid, -$invoice->subtotal, 'Remove funds for setup fee '.$invoice->id);
        }
    } catch(Exception $e) {
        CException::log($e);
    }
}
add_hook('InvoiceUnpaid', 1, 'KuberDock_InvoiceUnpaid');

/**
 * This hook runs after the Invoice Payment Reminder or Invoice Overdue Notices are sent to the client for an invoice.
 * @param $params
 */
function KuberDock_InvoicePaymentReminder($params)
{
    $invoiceId = $params['invoiceid'];
    $type = $params['type'];
    $model = CL_Invoice::model();
    $invoice = $model->loadById($invoiceId);

    try {
        //
    } catch(Exception $e) {
        CException::log($e);
    }
}
//add_hook('InvoicePaymentReminder', 1, 'KuberDock_InvoicePaymentReminder');

/**
 * Run: When a product is being edited in Setup -> Products/Services -> Products/Services
 *
 * @param $params
 */
function KuberDock_AdminProductConfigFields($params)
{
}
//add_hook('AdminProductConfigFields', 1, 'KuberDock_AdminProductConfigFields');


/**
 * Run: After the module Create function has run successfully.
 * The $_REQUEST array can be accessed in order to save the fields output by the AdminProductConfigFields hook.
 *
 * @param $pid
 */
function KuberDock_AdminProductConfigFieldsSave($pid)
{
    
}
//add_hook('AdminProductConfigFieldsSave', 1, 'KuberDock_AdminProductConfigFieldsSave');

/**
 * Runs when the ChangePackage function is being run, before any command is sent, but after the variables are loaded.
 *
 * @param $params
 */
function KuberDock_PreModuleChangePackage($params)
{
    //
}
//add_hook('PreModuleChangePackage', 1, 'KuberDock_PreModuleChangePackage');

/**
 * Runs after the ChangePackage function has been successfully run
 * @param $params
 */
function KuberDock_AfterModuleChangePackage($params)
{
    //
}
//add_hook('AfterModuleChangePackage', 1, 'KuberDock_AfterModuleChangePackage');


/**
 * Runs Immediately after a new server is added to the database
 * @param array $params
 */
function KuberDock_ServerAdd($params)
{
    $server = KuberDock_Server::model()->loadById($params['serverid']);
    if($server->isKuberDock()) {
        $server->accesshash = '';
        try {
            $server->accesshash = $server->getApi()->getToken();
            $server->save();
        } catch(Exception $e) {
            CException::log($e);
        }
    }
}
add_hook('ServerAdd', 1, 'KuberDock_ServerAdd');

/**
 * Runs Immediately after a server is edited
 * @param array $params
 */
function KuberDock_ServerEdit($params)
{
    $server = KuberDock_Server::model()->loadById($params['serverid']);
    if($server->isKuberDock()) {
        $server->accesshash = '';
        try {
            $server->accesshash = $server->getApi()->getToken();
            $server->save();
        } catch(Exception $e) {
            CException::log($e);
        }
    }
}
add_hook('ServerEdit', 1, 'KuberDock_ServerEdit');


/**
 * Runs When the Delete Client link is clicked on the Client Summary in the Admin area
 * @param $params
 * @return bool
 */
function KuberDock_ClientDelete($params)
{
    $rows = KuberDock_Hosting::model()->getByUser($params['userid']);
    foreach($rows as $row) {
        $service = KuberDock_Hosting::model()->loadByParams($row);
        try {
            $service->getAdminApi()->deleteUserFull($service->username);
        } catch(Exception $e) {
            CException::log($e);
        }
    }
}
add_hook('ClientDelete', 1, 'KuberDock_ClientDelete');


/**
 * Runs when Save Changes is clicked on the service in the Admin area and after the details are saved.
 * @param array $params
 */
function KuberDock_AdminServiceEdit($params)
{
    $nextDueDate = CL_Base::model()->getPost('nextduedate');
    $service = KuberDock_Hosting::model()->loadById($params['serviceid']);
    $service->nextduedate = CL_Tools::getMySQLFormattedDate($nextDueDate);
    $service->save();
}
add_hook('AdminServiceEdit', 1, 'KuberDock_AdminServiceEdit');

/**
 * @param $params
 */
function KuberDock_ClientAreaRegister($params)
{
    // Create app
    $predefinedApp = KuberDock_Addon_PredefinedApp::model()->loadBySessionId('LAST');
    $product = KuberDock_Product::model()->loadById($predefinedApp->product_id);
    $userId = isset($_SESSION['uid']) ? $_SESSION['uid'] : null;
    if($predefinedApp && $userId) {
        try {
            $result = \base\models\CL_Order::model()->createOrder($userId, $product->id);
            \base\models\CL_Order::model()->acceptOrder($result['orderid']);
            $service = \KuberDock_Hosting::model()->loadById($result['productids']);

            // pod
            if($predefinedApp->pod_id) {
                throw new Exception('New user has no pods');
            }

            if($product->isFixedPrice()) {
                $item = $product->addBillableApp($userId, $predefinedApp);
                if(!($pod = $predefinedApp->isPodExists($service->id))) {
                    $pod = $predefinedApp->create($service->id, 'unpaid');
                }
                $predefinedApp->pod_id = $pod['id'];
                $predefinedApp->save();
                $item->pod_id = $pod['id'];
                $item->save();

                if($item->isPayed()) {
                    $product->startPodAndRedirect($item->service_id, $item->pod_id, true);
                } else {
                    $product->jsRedirect('viewinvoice.php?id=' . $item->invoice_id);
                }
            } else {
                $pod = $predefinedApp->create($service->id);
                $predefinedApp->pod_id = $pod['id'];
                $predefinedApp->save();
                $predefinedApp->start($pod['id'], $service->id);
                $product->jsRedirect(sprintf('kdorder.php?a=redirect&sid=%s&podId=%s', $service->id, $predefinedApp->pod_id));
            }
        } catch(Exception $e) {
            CException::log($e);
            CException::displayError($e, true);
        }
    }
}
add_hook('ClientAreaRegister', 1, 'KuberDock_ClientAreaRegister');

/**
 * @param $params
 */
function KuberDock_ClientAdd($params)
{
    // Add service for user created from KD
    $packageId = CL_Base::model()->getPost('package_id');

    if (is_numeric($packageId)) {
        try {
            $data = KuberDock_Addon_Product::model()->loadByAttributes(array(
                'kuber_product_id' => $packageId,
            ));

            if (!$data) {
                throw new Exception('Product not found');
            }

            $data = current($data);
            $result = \base\models\CL_Order::model()->createOrder($params['userid'], $data['product_id']);
            \base\models\CL_Order::model()->acceptOrder($result['orderid'], false);

            // Update service
            $service = KuberDock_Hosting::model()->loadById($result['productids']);
            $service->username = CL_Base::model()->getPost('kduser', '');
            $service->save();

            system('php ' . KUBERDOCK_ROOT_DIR . '/bin/updateUser.php --service_id='. $service->id .
                " > /dev/null 2>/dev/null &");
        } catch (Exception $e) {
            CException::log($e);
        }
    }
}
add_hook('ClientAdd', 1, 'KuberDock_ClientAdd');


/**
 * As the Client Login is being completed, either from the client area, or an admin user utilising "Login as Client"
 * @param $params
 * @throws Exception
 */
function KuberDock_ClientLogin($params)
{
    // Create service if user created from KD
    $user = KuberDock_User::model()->loadById($params['userid']);
    if($user->notes && ($notes = json_decode($user->notes))) {
        $service = KuberDock_Hosting::model()->loadById($notes->KDService);
        $service->username = $notes->KDUser;
        $service->save();
        \base\models\CL_Order::model()->acceptOrder($notes->KDOrder);
        $user->notes = '';
        $user->save();
    }

    if ($predefinedApp = KuberDock_Addon_PredefinedApp::model()->loadBySessionId()) {
        $predefinedApp->user_id = $params['userid'];
        $predefinedApp->save();
    }
}
//add_hook('ClientLogin', 1, 'KuberDock_ClientLogin');

function KuberDock_AfterModuleCreate($params) {
    $userId = $params['params']['userid'];

    $product = \KuberDock_Product::model()->loadById($params['params']['packageid']);
    $predefinedApp = \KuberDock_Addon_PredefinedApp::model()->loadByUserId($userId);
    if ($predefinedApp) {
        $service = \KuberDock_Hosting::model()->loadById($params['params']['serviceid']);

        if ($product->isFixedPrice()) {
            $paid = $product->isSetupPayment() ? true : false;
            $item = $product->addBillableApp($userId, $predefinedApp, $paid);
            if (!($pod = $predefinedApp->isPodExists($service->id))) {
                $pod = $predefinedApp->create($service->id);
            }
            $predefinedApp->pod_id = $pod['id'];
            $predefinedApp->save();
            $item->pod_id = $pod['id'];
            $item->save();
            $product->removeFromCart();

            if ($item->isPayed()) {
                $product->startPodAndRedirect($item->service_id, $item->pod_id, true);
            } else {
                $product->jsRedirect('viewinvoice.php?id=' . $item->invoice_id);
            }
        } else {
            $product->removeFromCart();
            $product->createPodAndRedirect($service->id, true);
        }
    }
}
add_hook('AfterModuleCreate', 1, 'KuberDock_AfterModuleCreate');