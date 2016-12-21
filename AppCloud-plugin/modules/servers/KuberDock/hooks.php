<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use models\addon\App;
use models\addon\ItemInvoice;
use models\addon\Resources;
use models\billing\InvoiceItem;
use models\billing\Invoice;
use models\billing\Service;
use components\Tools;
use exceptions\CException;

include_once __DIR__ . DIRECTORY_SEPARATOR . 'init.php';

/**
 * Run After the cron has completed and the cron email has sent, but before the database backups.
 *  The hook can be used to run automatic actions daily along with the WHMCS standard cron actions.
 */
function KuberDock_DailyCronJob() {
    echo "Starting '".KUBERDOCK_MODULE_NAME."' hook\n";

    try {
        $fixedBilling = new \models\addon\billing\Fixed();
        $fixedBilling->processCron();

        $paygBilling = new \models\addon\billing\Payg();
        $paygBilling->processCron();
    } catch (Exception $e) {
        echo 'ERROR: '. $e->getMessage() . "\n";
        CException::log($e);
    }

    echo "KuberDock - Done\n";
}
add_hook('DailyCronJob', 1, 'KuberDock_DailyCronJob');

/**
 * Run: after edit product config options in "Setup" => "Product/Services" => Product/Services
 *
 * @param $params array('pid' => productId)
 */
function KuberDock_ProductEdit($params)
{
    $options = Tools::getPost('packageconfigoption');
    $package = \models\billing\Package::find($params['pid']);

    if (!$package || !$package->isKuberDock() || empty($options)) {
        return;
    }

    $package->edit($options);
}
add_hook('ProductEdit', 1, 'KuberDock_ProductEdit');

/**
 * Run: Immediately before the product is removed from the database
 *
 * @param array $params
 */
function KuberDock_ProductDelete($params)
{
    /* @var \models\billing\Package $package */
    try {
        $package = \models\billing\Package::find($params['pid']);
        if (!$package->isKuberDock()) {
            return;
        }

        $package->relatedKuberDock->delete();
    } catch (Exception $e) {
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
    /* @var \models\billing\Package $package */
    try {
        $service = \models\billing\Service::find($params['serviceid']);
        $package = $service->package;

        if (!$package->isKuberDock()) {
            return;
        }

        $service->getAdminApi()->deleteUser($service->username);
        \models\addon\Trial::where('user_id', $service->userid)->delete();
    } catch (Exception $e) {
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
    $config = \models\billing\Config::get();
    $errors = [];
    $userId = $params['userid'];

    if (isset($_SESSION['cart']) && $userId) {
        foreach ($_SESSION['cart']['products'] as $product) {
            $package = \models\billing\Package::find($product['pid']);
            /* @var \models\billing\Package $package */

            // TOS enabled but not accepted
            if (!$package->isKuberDock() || ((bool) $config->EnableTOSAccept && !isset($_POST['accepttos']))) {
                continue;
            }

            $service = \models\billing\Service::typeKuberDock()
                ->where('userid', $userId)->where('packageid', $package->id)->first();

            try {
                $app = App::getFromSession();

                if ($app && $service) {
                    $invoice = $package->getBilling()->order($app->getResource(), $service);

                    try {
                        \components\BillingApi::model()->applyCredit($invoice);
                    } catch (\exceptions\NotEnoughFundsException $e) {
                        Tools::jsRedirect($invoice->getUrl());
                    }
                }
            } catch (Exception $e) {
                CException::log($e);
                Tools::jsRedirect($app->referer . '&error=' . urlencode($e->getMessage()));
            }

            $errorMessage = 'You can\'t buy more than 1 KuberDock product.';

            if ($service && !in_array($errorMessage, $errors) && !$errors) {
                $errors[] = $errorMessage;
            }

            if ($package->getEnableTrial() && \models\addon\Trial::where('user_id', $userId)->first()) {
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
    $assets = new \components\Assets();
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
    $assets = new \components\Assets();
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
 * Run: As the cart page is being displayed, this hook is run separately for each product added to the cart.
 *
 * @param $params
 * @return array|null
 */
function KuberDock_OrderProductPricingOverride($params)
{
    $ca = new \components\ClientArea();
    return $ca->productPricingOverride($params['pid']);
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
    $ca = new \components\ClientArea();
    $ca->redraw();
}
add_hook('ClientAreaPage', 1, 'KuberDock_ClientAreaPage');

/**
 * Product invoice correction
 * @param $params
 */
function KuberDock_InvoiceCreationPreEmail($params)
{
    if ($params['source'] == 'autogen') {
        $invoice = \models\billing\Invoice::find($params['invoiceid']);
        if ($invoice) {
            (new \models\addon\Item())->invoiceCorrection($invoice);
        }
    }
}
add_hook('InvoiceCreationPreEmail', 1, 'KuberDock_InvoiceCreationPreEmail');

/**
 * @param $params
 */
function KuberDock_InvoiceCreated($params)
{
    (new \models\addon\Item())->handleInvoicing($params['invoiceid']);
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
    // TODO: change
    // Add first deposit
    $invoiceItem = InvoiceItem::where('type', 'Hosting')
        ->where('invoiceid', $params['invoiceid'])
        ->where('description', Invoice::FIRST_DEPOSIT_DESCRIPTION)
        ->first();

    if ($invoiceItem) {
        $invoiceItem->invoice->addFirstDeposit();
    }

    $itemInvoices = ItemInvoice::where('invoice_id', $params['invoiceid'])->get();

    if (!$itemInvoices->count()) {
        return;
    }

    foreach ($itemInvoices as $itemInvoice) {
        try {
            $pod = $itemInvoice->afterPayment();

            Resources::redirectToUnpaidInvoice($itemInvoice);
        } catch (Exception $e) {
            CException::log($e);
        }
    }

    if (!isset($pod) || !$pod) {
        $service = \models\billing\Service::find($itemInvoices->last()->item->service_id);
        Tools::jsRedirect($service->getLoginLink());
    } else {
        $pod->redirect(true);
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
    $invoice = \models\billing\Invoice::find($params['invoiceid']);

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
    $invoice = \models\billing\Invoice::find($params['invoiceid']);

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
    $service = \models\billing\Service::where('orderid', $params['orderid'])->first();

    if (!$service) {
        return;
    }

    if ($service->package && $service->package->isKuberDock() && !$service->package->autosetup) {
        $service->createUser();
    }
}
add_hook('AcceptOrder', 1, 'KuberDock_AcceptOrder');

/**
 * Run: After the checkout button is pressed, once the order has been added to the database.
 */
add_hook('AfterShoppingCartCheckout', 1, function ($params) {
    $app = App::getFromSession();
    $service = Service::whereIn('id', $params['ServiceIDs'])->typeKuberDock()->first();

    if ($app && $service) {
        $app->service_id = $service->id;
        $app->save();
    }
});

/**
 * Runs Immediately after a new server is added to the database
 * @param array $params
 */
function KuberDock_ServerAdd($params)
{
    $server = \models\billing\Server::typeKuberDock()->where('id', $params['serverid'])->first();

    if ($server) {
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
add_hook('ServerAdd', 1, 'KuberDock_ServerAdd');

/**
 * Runs Immediately after a server is edited
 * @param array $params
 */
function KuberDock_ServerEdit($params)
{
    KuberDock_ServerAdd($params);
}
add_hook('ServerEdit', 1, 'KuberDock_ServerEdit');

/**
 * Runs When the Delete Client link is clicked on the Client Summary in the Admin area
 * @param $params
 */
function KuberDock_ClientDelete($params)
{
    $services = \models\billing\Service::typeKuberDock()->where('userid', $params['userid'])->get();

    foreach ($services as $service) {
        try {
            $service->getAdminApi()->deleteUser($service->username, true);
        } catch (Exception $e) {
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
    $nextDueDate = Tools::getPost('nextduedate');
    $service = \models\billing\Service::find($params['serviceid']);

    if ($service->package->isKuberDock() && $nextDueDate) {
        $service->nextduedate = \Carbon\Carbon::createFromFormat(Tools::getDateFormat(), $nextDueDate);
        $service->save();
    }
}
add_hook('AdminServiceEdit', 1, 'KuberDock_AdminServiceEdit');

/**
 * @param $params
 */
function KuberDock_ClientAdd($params)
{
    // Add service for user created from KD
    $packageId = Tools::getPost('package_id');

    if (is_numeric($packageId)) {
        try {
            $packageRelation = \models\addon\PackageRelation::where('kuber_product_id', $packageId)->first();

            if (!$packageRelation) {
                throw new Exception('Product not found');
            }

            $client = \models\billing\Client::find($params['userid']);

            $user = Tools::getPost('kduser');
            $password = Tools::getPost('password');

            $service = new \models\billing\Service();
            $service->userid = $client->id;
            $service->packageid = $packageRelation->product_id;
            $service->server = $packageRelation->package->serverGroup->servers()->first()->id;
            $service->regdate = new DateTime();
            $service->paymentmethod = $client->getGateway();
            $service->billingcycle = 'Free Account';
            $service->domainstatus = 'Active';
            $service->username = $user;
            $service->password = \components\BillingApi::model()->encryptPassword($password);
            $service->save();

            $packageRelation->package->getBilling()->afterModuleCreate($service);

            system('php ' . KUBERDOCK_ROOT_DIR . '/bin/update_user_token.php --service_id='. $service->id .
                " > /dev/null 2>/dev/null &");
        } catch (Exception $e) {
            CException::log($e);
        }
    }
}
add_hook('ClientAdd', 1, 'KuberDock_ClientAdd');

/**
 * Runs when 1st KD product bought, if that was PA - create it
 * @param $params
 */
function KuberDock_AfterModuleCreate($params)
{
    $service = Service::find($params['params']['serviceid']);
    $billing = $service->package->getBilling();

    try {
        Service::typeKuberDock()
            ->where('userid', $service->userid)
            ->where('server', $service->server)
            ->where('id', '!=', $service->id)
            ->delete();

        $billing->afterModuleCreate($service);
    } catch (Exception $e) {
        if ($billing->app) {
            Tools::jsRedirect($billing->app->referer . '&error=' . urlencode($e->getMessage()));
        }

        CException::log($e);
    }
}
add_hook('AfterModuleCreate', 1, 'KuberDock_AfterModuleCreate');


/**
 * ! Actually, user will change package, but KuberDock_ChangePackage not runs if returned "abortcmd" !
 *
 * Runs when the ChangePackage function is being run, before any command is sent, but after the variables are loaded.
 * @param array $params
 * @return array
 */
function KuberDock_PreModuleChangePackage($params)
{
    /* @var \models\billing\Package $newPackage
     * @var \models\billing\Package $oldPackage
     */
    $response = [];

    $packageUpgrade = \models\billing\PackageUpgrade::where('relid', $params['params']['serviceid'])
        ->orderBy('id', 'desc')
        ->first();

    if (!$packageUpgrade) {
        return $response;
    }

    $oldPackage = \models\billing\Package::find($packageUpgrade->originalvalue);
    $newPackage = $packageUpgrade->getNewPackage();

    // User already has trial product
    $trial = \models\addon\Trial::where('user_id', $params['params']['userid'])->first();

    if ($newPackage->getEnableTrial() && $trial) {
        $response[] = ['abortcmd' => true];
    }
    // User want another trial
    if ($oldPackage->getEnableTrial() && $newPackage->getEnableTrial()) {
        $response[] = ['abortcmd' => true];
    }

    return $response;
}
add_hook('PreModuleChangePackage', 1, 'KuberDock_PreModuleChangePackage');