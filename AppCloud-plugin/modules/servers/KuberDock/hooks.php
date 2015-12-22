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
        //if($service->userid != 27) continue;

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
    if($params['servertype'] == KUBERDOCK_MODULE_NAME) {
        if(empty($options)) {
            return;
        }

        $product = KuberDock_Product::model()->loadById($params['pid']);

        $i = 1;
        foreach($product->getConfig() as $row) {
            $value = isset($options[$i]) ? $options[$i] : '';
            $product->setConfigOptionByIndex($i, $value);
            $i++;
        }

        if(!$product->getKubes()) {
            $product->hidden = 1;
        }

        $product->save();

        try {
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
        echo $e->getMessage();
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
 */
function KuberDock_ShoppingCartValidateCheckout($params)
{
    $errors = array();
    if(isset($_SESSION['cart']) && $params['userid']) {
        foreach($_SESSION['cart']['products'] as $product) {
            $product = KuberDock_Product::model()->loadById($product['pid']);
            if(!$product->isKuberProduct()) continue;

            try {
                $server = $product->getServer();
                $userProducts = KuberDock_Product::model()->getByUser($params['userid'], $server->id);
            } catch(Exception $e) {
                CException::log($e);
            }

            $errorMessage = 'You can not buy more than 1 KuberDock product.';
            if($userProducts && !in_array($errorMessage, $userProducts)) {
                $errors[] = $errorMessage;
            }

            if($product->getConfigOption('enableTrial') && KuberDock_Addon_Trial::model()->loadById($params['userid'])) {
                $errors[] = 'You are already have trial KuberDock product.';
            } elseif($userProducts) {
                $predefinedApp = KuberDock_Addon_PredefinedApp::model()->loadBySessionId();
                if($predefinedApp) {
                    $userServices = KuberDock_Hosting::model()->getByUser($params['userid']);
                    foreach($userServices as $row) {
                        if($row['domainstatus'] == 'Active' && $row['packageid'] == $predefinedApp->product_id) {
                            try {
                                if(!($pod = $predefinedApp->isPodExists($row['id']))) {
                                    $pod = $predefinedApp->create($row['id']);
                                    $predefinedApp->start($pod['id'], $row['id']);
                                }
                                header('Location: ' . sprintf('cart.php?a=view&sid=%s&podId=%s', $row['id'], $pod['id']));
                            } catch(Exception $e) {
                                CException::displayError($e);
                            }
                        }
                    }
                }
            }
        }
    }

    return $errors;
}
add_hook('ShoppingCartValidateCheckout', 1, 'KuberDock_ShoppingCartValidateCheckout');

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
add_hook('AdminAreaHeadOutput', 1, 'KuberDock_AdminAreaHeadOutput');

/**
 * Run: after an upgrade invoice has been paid, but before the ChangePackage command is sent to the server.
 *
 * @param int $upgradeId
 */
function KuberDock_AfterConfigOptionsUpgrade($upgradeId)
{
    // future
}
add_hook('AfterConfigOptionsUpgrade', 1, 'KuberDock_AfterConfigOptionsUpgrade');

/**
 * Run: As the order is being accepted via the admin area of API, before any updates are completed.
 *
 * @param array $params
 */
function KuberDock_ShoppingCartCheckoutCompletePage($params)
{
}
//add_hook('ShoppingCartCheckoutCompletePage', 1, 'KuberDock_ShoppingCartCheckoutCompletePage');

/**
 * Run: As the cart page is being displayed, this hook is run separately for each product added to the cart.
 *
 * @param $params
 * @return array
 */
function KuberDock_OrderProductPricingOverride($params)
{
    $product = KuberDock_Product::model()->loadById($params['pid']);
    $setup = $product->getConfigOption('firstDeposit') ? $product->getConfigOption('firstDeposit') : 0;

    $productPrice = array(
        'setup' => $setup,
        'recurring' => 0,
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

    // registration fix
    if(!isset($values['uneditablefields'])) {
        $values['uneditablefields'] = array();
    }

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

        // Buy predefined app by link
        $predefinedApp = KuberDock_Addon_PredefinedApp::model();
        if(isset($_GET[$predefinedApp::KUBERDOCK_YAML_FIELD])) {
            $kdProductId = CL_Base::model()->getParam($predefinedApp::KUBERDOCK_PRODUCT_ID_FIELD);
            $yaml = html_entity_decode(urldecode(CL_Base::model()->getParam($predefinedApp::KUBERDOCK_YAML_FIELD)), ENT_QUOTES);
            $referer = CL_Base::model()->getParam($predefinedApp::KUBERDOCK_REFERER_FIELD);
            $parsedYaml = Spyc::YAMLLoadString($yaml);

            try {
                if(isset($parsedYaml['kuberdock']['package_id'])) {
                    $kdProductId = $parsedYaml['kuberdock']['package_id'];
                }

                if(!$referer) {
                    if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
                        $referer = $_SERVER['HTTP_REFERER'];
                    } elseif(isset($parsedYaml['kuberdock']['server'])) {
                        $referer = $parsedYaml['kuberdock']['server'];
                    } elseif($server = KuberDock_Server::model()->getActive()) {
                        $referer = $server->getApiServerUrl();
                    } else {
                        throw new CException('Cannot get KuberDock server url');
                    }
                }

                $kdProduct = KuberDock_Addon_Product::model()->getByKuberId($kdProductId, $referer);
                $product = KuberDock_Product::model()->loadById($kdProduct->product_id);

                $predefinedApp = $predefinedApp->loadBySessionId();
                if(!$predefinedApp) {
                    $predefinedApp = new KuberDock_Addon_PredefinedApp();
                }

                $predefinedApp->setAttributes(array(
                    'session_id' => session_id(),
                    'kuber_product_id' => $kdProductId,
                    'product_id' => $product->id,
                    'data' => $yaml,
                ));

                $predefinedApp->save();
                $product->addToCart();
            } catch(Exception $e) {
                // product not founded
                CException::log($e);
                CException::displayError($e);
            }

            header('Location: cart.php?a=view');
        }

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
                    $price =  $currency->getFullPrice($predefinedApp->getTotalPrice());
                    $product['pricingtext'] = $price . '/' . $p->getReadablePaymentType();
                    $product['pricing']['totaltodayexcltax'] = $price;
                    $product['billingcyclefriendly'] = $p->getReadablePaymentType();
                }
            }
        }

        if(isset($values['productinfo']) && isset($products[$values['productinfo']['pid']])) {
            $product = KuberDock_Product::model()->loadById($values['productinfo']['pid']);
            $kubes = KuberDock_Addon_Kube::model()->loadByAttributes(array(
                'product_id' => $values['productinfo']['pid'],
            ));

            $values['customfields'] = array_merge($values['customfields'], array(
                array(
                    'input' => '',
                    'name' => 'Price for IP',
                    'description' => $currency->getFullPrice($product->getConfigOption('priceIP')) .' per day',
                ),
                array(
                    'input' => '',
                    'name' => 'Price for Persistent Storage',
                    'description' => $product->getReadablePersistentStorage(),
                ),
                array(
                    'input' => '',
                    'name' => 'Price for Additional Traffic',
                    'description' => $product->getReadableOverTraffic(),
                ),
            ));

            $desc = 'Price - %s, CPU - %s, Memory - %s %s, HDD - %s %s, Traffic - %s %s';

            foreach($kubes as $kube) {
                $values['customfields'][] = array(
                    'input' => '',
                    'name' => 'Kube ' . $kube['kube_name'],
                    'description' => vsprintf($desc, array(
                        $currency->getFullPrice($kube['kube_price']) .' \ '. $product->getReadablePaymentType(),
                        $kube['cpu_limit'],
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

        // Complete page
        if(in_array(CL_Base::model()->getParam('a'), array('complete', 'view'))) {
            $serviceId = CL_Base::model()->getParam('sid');
            $podId = CL_Base::model()->getParam('podId');

            if($serviceId && $podId) {
                $service = KuberDock_Hosting::model()->loadById($serviceId);
                $view = new \base\CL_View();
                $predefinedApp = KuberDock_Addon_PredefinedApp::model()->loadBySessionId();
                try {
                    $pod = $service->getApi()->getPod($podId);
                    $view->renderPartial('client/preapp_complete', array(
                        'serverLink' => $service->getServer()->getLoginPageLink(),
                        'token' => $service->getToken(),
                        'podId' => $podId,
                        'postDescription' => htmlentities($predefinedApp->getPostDescription($pod), ENT_QUOTES),
                    ));
                    exit;
                } catch (Exception $e) {
                    CException::log($e);
                    CException::displayError($e);
                }
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
                    $row['pricing']['type'] = 'onetime';
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
            $service = KuberDock_Hosting::model()->loadById($values['id']);

            if(($firstDeposit = $newProduct->getConfigOption('firstDeposit')) && $oldProduct->getConfigOption('enableTrial')
                && $oldProduct->isKuberProduct()) {
                $service->amount = -$firstDeposit;
                $service->save();
                $values['subtotal'] = $values['total'] = $currency->getFullPrice($firstDeposit);
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
 * Run: This hook runs as an invoice status is changing from Unpaid to Paid after all automation associated
 * with the invoice has run.
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
    } catch(Exception $e) {
        CException::log($e);
    }
}
add_hook('InvoicePaid', 1, 'KuberDock_InvoicePaid');

/**
 * Run: This hook runs as an invoice status is changing to Cancelled. This can be from the invoice in the admin area,
 * a mass update action, cancelling an order, a submitted cancellation request or a client disabling the auto
 * renewal of a domain.
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
 * Run: When a product is being edited in Setup -> Products/Services -> Products/Services
 */
function KuberDock_AdminProductConfigFields($params)
{
}
//add_hook('AdminProductConfigFields', 1, 'KuberDock_AdminProductConfigFields');


/**
 * Run: After the module Create function has run successfully.
 * The $_REQUEST array can be accessed in order to save the fields output by the AdminProductConfigFields hook.
 */
function KuberDock_AdminProductConfigFieldsSave($pid)
{
    
}
//add_hook('AdminProductConfigFieldsSave', 1, 'KuberDock_AdminProductConfigFieldsSave');

/**
 * Runs when the ChangePackage function is being run, before any command is sent, but after the variables are loaded.
 * @param $params
 */
function KuberDock_PreModuleChangePackage($params)
{
    //
}
add_hook('PreModuleChangePackage', 1, 'KuberDock_PreModuleChangePackage');

/**
 * Runs after the ChangePackage function has been successfully run
 * @param $params
 */
function KuberDock_AfterModuleChangePackage($params)
{
    //
}
add_hook('AfterModuleChangePackage', 1, 'KuberDock_AfterModuleChangePackage');


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