<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_View;
use base\CL_Base;
use base\models\CL_Currency;
use base\models\CL_Client;
use api\KuberDock_Api;
use exceptions\CException;

include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'init.php';

/**
 * @return array
 * @throws Exception
 */
function KuberDock_ConfigOptions() {
    $id = CL_Base::model()->getParam('id');
    $product = KuberDock_Product::model()->loadById($id);

    return $product->getConfig();
}

/**
 * @param $params
 * @return string
 */
function KuberDock_CreateAccount($params) {
    try {
        $product = KuberDock_Product::model()->loadById($params['pid']);
        $client = CL_Client::model()->loadByParams($params['clientsdetails']);
        $client->filterValues();

        $product->setClient($client);
        $product->create($params['serviceid']);

        if($product->getConfigOption('enableTrial')) {
            $trial = KuberDock_Addon_Trial::model();
            if(!$trial->loadById($params['userid'])) {
                $trial->insert(array(
                    'user_id' => $params['userid'],
                    'service_id' => $params['serviceid'],
                ));
            }
        }

        return 'success';
    } catch(Exception $e) {
        $service = KuberDock_Hosting::model()->loadById($params['serviceid']);
        $service->updateById($params['serviceid'], array(
            'domainstatus' => 'Pending',
        ));

        CException::log($e);

        // TODO: Don't work properly for admin, if admin logged as user
        // to distinguish a common user from admin
        /*if (isset($_SESSION['uid'])) {
            // sending user to error page
            CException::displayError($e);
        } else {
            // show error to admin
            return 'ERROR: ' . $e->getMessage();
        }*/

        return 'ERROR: ' . $e->getMessage();
    }
}

/**
 * @param $params
 * @return string
 */
function KuberDock_TerminateAccount($params) {
    try {
        $product = KuberDock_Product::model()->loadById($params['pid']);
        $product->terminate($params['serviceid']);

        return 'success';
    } catch(Exception $e) {
        CException::log($e);
        return 'ERROR: ' . $e->getMessage();
    }
}

/**
 * @param $params
 * @return string
 */
function KuberDock_SuspendAccount($params) {
    try {
        $product = KuberDock_Product::model()->loadById($params['pid']);
        $product->suspend($params['serviceid']);

        return 'success';
    } catch(Exception $e) {
        CException::log($e);
        return 'ERROR: ' . $e->getMessage();
    }
}

/**
 * @param $params
 * @return string
 */
function KuberDock_UnsuspendAccount($params) {
    try {
        $product = KuberDock_Product::model()->loadById($params['pid']);
        $product->unSuspend($params['serviceid']);

        return 'success';
    } catch(Exception $e) {
        CException::log($e);
        return 'ERROR: ' . $e->getMessage();
    }
}

/**
 * @param $params
 * @return array
 * @throws Exception
 */
function KuberDock_AdminServicesTabFields($params) {
    $view = new CL_View();
    $product = KuberDock_Product::model()->loadById($params['pid']);
    $currency = CL_Currency::model()->getDefaultCurrency();
    $service = KuberDock_Hosting::model()->loadById($params['serviceid']);
    $trialTime = (int) $product->getConfigOption('trialTime');
    $enableTrial = $product->getConfigOption('enableTrial');
    $regDate = new DateTime($service->regdate);
    $trialExpired = '';

    if ($enableTrial && $service->isTrialExpired($regDate, $trialTime)) {
        $trialExpired = $regDate->modify('+'.$trialTime.' day')->format('Y-m-d');
    }

    $addonProduct = KuberDock_Addon_Product::model()->loadById($product->pid);

    try {
        $kubes = $service->getAdminApi()->getPackageKubes($addonProduct->kuber_product_id)->getData();
        $kubes = \base\CL_Tools::getKeyAsField($kubes, 'id');
        $pods = $service->getApi()->getPods()->getData();
        $productStatistic = $view->renderPartial('admin/product_statistic', array(
            'pods' => $pods,
            'kubes' => $kubes,
        ), false);

        $productInfo = $view->renderPartial('admin/product_info', array(
            'currency' => $currency,
            'product' => $product,
            'kubes' => $kubes,
            'trialExpired' => $trialExpired,
        ), false);

    } catch(Exception $e) {
        $productStatistic = sprintf('<div class="error">%s</div>', $e->getMessage());
        $productInfo = '';
    }

    return array(
        'Package Kubes' => $productInfo,
        'Pods' => $productStatistic,
    );
}

/**
 * @param $params
 * @return string
 * @throws Exception
 */
function KuberDock_ClientArea($params) {
    $view = new CL_View();
    $product = KuberDock_Product::model()->loadByParams($params);
    $currency = CL_Currency::model()->getDefaultCurrency();
    $service = KuberDock_Hosting::model()->loadById($params['serviceid']);
    $server = KuberDock_Server::model()->loadById($service->server);
    $trialTime = (int) $product->getConfigOption('trialTime');
    $enableTrial = $product->getConfigOption('enableTrial');
    $items = KuberDock_Addon_Items::model()->loadByAttributes(array(
        'user_id' => $params['userid'],
    ));
    $items = \base\CL_Tools::model()->getKeyAsField($items, 'pod_id');
    $regDate = new DateTime($service->regdate);
    $trialExpired = '';

    if($enableTrial && $service->isTrialExpired($regDate, $trialTime)) {
        $trialExpired = $regDate->modify('+'.$trialTime.' day')->format('Y-m-d');
    }

    $addonProduct = KuberDock_Addon_Product::model()->loadById($product->pid);

    try {
        if (!$addonProduct) {
            throw new Exception('KuberDock product not found');
        }
        $pods = $service->getApi()->getPods()->getData();
        $kubes = $service->getAdminApi()->getPackageKubes($addonProduct->kuber_product_id)->getData();
        $kubes = \base\CL_Tools::getKeyAsField($kubes, 'id');
        
        $productStatistic = $view->renderPartial('client/product_statistic', array(
            'pods' => $pods,
            'kubes' => $kubes,
            'service' => $service,
            'items' => $items,
            'product' => $product,
            'currency' => $currency,
        ), false);

        $productInfo = $view->renderPartial('client/product_info', array(
            'currency' => $currency,
            'product' => $product,
            'service' => $service,
            'kubes' => $kubes,
            'server' => $server,
            'trialExpired' => $trialExpired,
        ), false);

        return $productInfo . $productStatistic;
    } catch(Exception $e) {
        return sprintf('<div class="error">%s</div>', $e->getMessage());
    }
}

/**
 * Render button on Setup -> products -> servers page
 * @param $params
 * @return string
 * @throws CException
 * @throws Exception
 */
function KuberDock_AdminLink($params) {
    $server = KuberDock_Server::model()->loadById($params['serverid']);

    if (USE_JWT_TOKENS) {
        $tokenField = 'token2';
        $token = $server->getApi()->getJWTToken(array(), true);
    } else {
        $tokenField = 'token';
        $token = $server->getApi()->getToken();
    }

    $url = sprintf('%s/?%s=%s', $server->getApiServerUrl(), $tokenField, $token);

    return sprintf('<a href="%s" target="_blank" class="btn btn-sm btn-default" >Login to KuberDock</a>', $url);
}

/**
 * @param $params
 * @return string
 */
function KuberDock_LoginLink($params) {
    $service = KuberDock_Hosting::model()->loadById($params['serviceid']);

    return sprintf('<a href="%s" target="_blank">Login to KuberDock</a>', $service->getLoginByTokenLink());
}

/**
 * Run create module command while clicking "Save Changes" in admin area
 * @param $params
 */
/*function KuberDock_AdminServicesTabFieldsSave($params) {
    try {
        $product = KuberDock_Product::model()->loadById($params['pid']);
        $client = CL_Client::model()->loadByParams($params['clientsdetails']);
        $product->setClient($client);
        $product->create($params['serviceid']);
    } catch(Exception $e) {
        CException::log($e);
        // do nothing
    }
}*/

/**
 * This function is used for upgrading and downgrading of products.
 * @param array $params
 * @return bool
 */
function KuberDock_ChangePackage($params) {
    try {
        $upgrade = KuberDock_ProductUpgrade::model()->loadByServiceId($params['serviceid']);
        $upgrade->changePackage();
    } catch(Exception $e) {
        CException::log($e);
        //
    }
}

/**
 * @param $params
 * @return array
 */
function KuberDock_TestConnection($params) {
    try {
        $protocol = $params['serversecure'] ? KuberDock_Api::PROTOCOL_HTTPS : KuberDock_Api::PROTOCOL_HTTP;
        $url = sprintf('%s://%s', $protocol, $params['serverip']);
        if(empty($params['serverusername']) || empty($params['serverpassword'])) {
            throw new Exception('Username is missing for the selected server. Please save configuration before testing connection.');
        }
        $api = new KuberDock_Api($params['serverusername'], $params['serverpassword'], $url);
        $response = $api->getToken();
        return array(
            'success' => true,
        );
    } catch(Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}