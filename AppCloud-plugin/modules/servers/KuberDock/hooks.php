<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Base;
use base\CL_Tools;
use base\models\CL_Invoice;
use base\models\CL_User;
use base\models\CL_BillableItems;
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

            KuberDock_Addon_Product::model()->updateKubePricing($params['pid']);

            if($product->isTrial()) {
                $product->createDefaultKubeIfNeeded();
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

    if (isset($_SESSION['cart']) && $userId) {
        foreach ($_SESSION['cart']['products'] as $product) {
            $product = KuberDock_Product::model()->loadById($product['pid']);
            // TOS enabled but not accepted
            if (!$product->isKuberProduct()
                || ((bool) $CONFIG['EnableTOSAccept'] && !isset($_POST['accepttos']))) {
                continue;
            }

            $server = $product->getServer();
            $userProducts = KuberDock_Product::model()->getByUser($userId, $server->id);
            $userProduct = current($userProducts);

            try {
                $service = \KuberDock_Hosting::model()->loadById($userProduct['hosting_id']);
                // Runs only if service already exists
                \KuberDock_Addon_PredefinedApp::model()->order($product, $service, $userId);
            } catch (Exception $e) {
                CException::log($e);
                $errors[] = str_replace('ERROR: ', '', $e->getMessage());
                $product->addToCart();
            }

            $errorMessage = 'You can not buy more than 1 KuberDock product.';
	    if (count($userProducts) && !in_array($errorMessage, $errors) && !$errors) {
                $errors[] = $errorMessage;
            }

            if ($product->getConfigOption('enableTrial') && KuberDock_Addon_Trial::model()->loadById($userId)) {
                $errors[] = 'You are already have trial KuberDock product.';
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
add_hook('AdminAreaHeadOutput', 0, 'KuberDock_AdminAreaHeadOutput');

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
}
add_hook('AfterShoppingCartCheckout', 1, 'KuberDock_AfterShoppingCartCheckout');

/**
 * Run: As the cart page is being displayed, this hook is run separately for each product added to the cart.
 *
 * @param $params
 * @return array|null
 */
function KuberDock_OrderProductPricingOverride($params)
{
    return \components\ClientArea::model()->pricingOverride($params);
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
    return \components\ClientArea::model()->prepare();
}
add_hook('ClientAreaPage', 1, 'KuberDock_ClientAreaPage');

/**
 * Product invoice correction
 * @param $params
 */
function KuberDock_InvoiceCreationPreEmail($params)
{
    if ($params['source'] == 'autogen') {
        KuberDock_Product::model()->productInvoiceCorrection($params['invoiceid']);
    }
}
add_hook('InvoiceCreationPreEmail', 1, 'KuberDock_InvoiceCreationPreEmail');

/**
 * @param $params
 */
function KuberDock_InvoiceCreated($params)
{
    KuberDock_Addon_Items::model()->handleInvoiceCreation($params['invoiceid']);
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

    try {
        $invoice = CL_Invoice::model()->loadById($invoiceId);
        if (!$invoice) {
            return;
        }
        $invoice->addFirstDeposit();

        // Start pod
        if ($item = KuberDock_Addon_Items::model()->loadByInvoice($invoiceId)) {
            $item->status = CL_Invoice::STATUS_PAID;
            $item->save();

            $product = KuberDock_Product::model();
            $product->startPodAndRedirect($item->service_id, $item->pod_id, true);
        }

        // TODO: check is needed. pod edit added
        // Add additional kubes
        if ($invoice->isUpdateKubesInvoice()) {
            if ($data = KuberDock_Hosting::model()->getByUser($invoice->userid)) {
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
                if (isset($params['containers'])) {
                    // upgrade pod
                    $service->getAdminApi()->redeployPod($params['id'], $params);
                } else {
                    // edit pod
                    $pod = $service->getApi()->getPod($params['id']);
                    $service->getAdminApi()->applyEdit($params['id'], $pod['status']);
                }

                // Update app
                $data = KuberDock_Addon_Items::model()->loadByAttributes(array(
                    'billable_item_id' => $billableItem->id
                ));
                if ($addonItem = current($data)) {
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
 * Run: This hook runs as an invoice status is changing to Unpaid via the Admin area. This can be from a single invoice,
 * or a mass action. The hook runs for each invoice being processed.
 *
 * @param $params
 */
function KuberDock_InvoiceUnpaid($params)
{
    $invoice = CL_Invoice::model()->loadById($params['invoiceid']);
    if ($invoice) {
        $invoice->removeFirstDeposit();
    }
}
add_hook('InvoiceUnpaid', 1, 'KuberDock_InvoiceUnpaid');

/**
 * Run: This hook runs as an invoice status is changing to Cancelled. This can be from the invoice in the admin area,
 * a mass update action, cancelling an order, a submitted cancellation request or a client disabling the auto
 * renewal of a domain.
 *
 * @param $params
 */
function KuberDock_InvoiceCancelled($params)
{
    $invoice = CL_Invoice::model()->loadById($params['invoiceid']);
    if ($invoice) {
        $invoice->removeFirstDeposit();
    }
}
add_hook('InvoiceCancelled', 1, 'KuberDock_InvoiceCancelled');

/**
 * Runs when admin manually accept order
 *
 * Used when product settings is "Automatically setup the product when you manually accept a pending order" or
 * "Do not automatically setup this product"
 * @param $params
 */
function KuberDock_AcceptOrder($params)
{
    $data = KuberDock_Hosting::model()->loadByAttributes(array(
        'orderid' => $params['orderid'],
    ));

    if (!$data) {
        return;
    }

    $service = KuberDock_Hosting::model()->loadByParams(current($data));
    $product =  KuberDock_Product::model()->loadById($service->packageid);
    if ($product && $product->isKuberProduct() && !$product->autosetup) {
        $service->createModule();
    }
}
add_hook('AcceptOrder', 1, 'KuberDock_AcceptOrder');

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
    if ($server->isKuberDock()) {
        $server->accesshash = '';
        try {
            $server->accesshash = $server->getApi()->getToken();
            $server->save();
        } catch (Exception $e) {
            CException::log($e);
            $server->save();
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
            $service->getAdminApi()->deleteUser($service->username, true);
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
    $product = KuberDock_Product::model()->loadById($service->packageid);

    if ($product->isKuberProduct()) {
        $service->updateById($service->id, array(
            'nextduedate' => CL_Tools::getMySQLFormattedDate($nextDueDate),
        ));
    }
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

/**
 * Runs when 1st KD product bought, if that was PA - create it
 * @param $params
 */
function KuberDock_AfterModuleCreate($params)
{
    $userId = $params['params']['userid'];
    $product = \KuberDock_Product::model()->loadById($params['params']['packageid']);
    $service = \KuberDock_Hosting::model()->loadById($params['params']['serviceid']);

    try {
        $invoice = CL_Invoice::model()->loadByOrderId($service->orderid);
        \KuberDock_Addon_PredefinedApp::model()->order($product, $service, $userId, $invoice->id);
    } catch (Exception $e) {
        CException::log($e);
    }
}
add_hook('AfterModuleCreate', 1, 'KuberDock_AfterModuleCreate');
