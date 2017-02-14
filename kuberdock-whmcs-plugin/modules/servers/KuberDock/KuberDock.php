<?php

use api\Api;
use exceptions\CException;
use components\Tools;
use components\View;
use models\billing\Service;
use models\billing\Server;
use models\billing\Package;

include_once __DIR__ . DIRECTORY_SEPARATOR . 'init.php';

/**
 * @return array
 * @throws Exception
 */
function KuberDock_ConfigOptions() {
    $id = Tools::model()->getParam('id', components\Tools::model()->getPost('id'));

    return Package::find($id)->getConfig();
}

/**
 * @param $params
 * @return string
 */
function KuberDock_CreateAccount($params) {
    $service = Service::find($params['serviceid']);

    try {
        $service->createUser();

        return 'success';
    } catch (Exception $e) {
        CException::log($e);

        return 'ERROR: ' . $e->getMessage();
    }
}

/**
 * @param $params
 * @return string
 */
function KuberDock_TerminateAccount($params) {
    try {
        $service = Service::find($params['serviceid']);
        $service->terminate();

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
        $service = Service::find($params['serviceid']);
        $service->suspend();

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
        $service = Service::find($params['serviceid']);
        $service->unSuspend();

        return 'success';
    } catch(Exception $e) {
        CException::log($e);
        return 'ERROR: ' . $e->getMessage();
    }
}

/**
 * @param array $params
 * @return array
 * @throws LogicException
 */
function KuberDock_AdminServicesTabFields($params) {
    /* @var \models\billing\Service $service
     * @var \models\billing\Package $package
     * */
    $view = new View();
    $service = Service::find($params['serviceid']);
    $package = $service->package;
    $currency = $service->client->currencyModel;

    $regDate = $service->regdate;
    $trialExpired = '';

    if ($service->isTrialExpired()) {
        $trialExpired = $regDate->addDays($package->getTrialTime());
    }

    try {
        if (!$package->relatedKuberDock) {
            throw new LogicException('KuberDock product not found');
        }

        $kubes = $service->getAdminApi()->getPackageKubes($package->relatedKuberDock->kuber_product_id)->getData();
        $pods = $service->getApi()->getPods()->getData();

        if ($pods) {
            $productStatistic = $view->renderPartial('admin/product_statistic', [
                'pods' => $pods,
                'kubes' => $kubes,
            ], false);
        } else {
            $productStatistic = '<div>No pods yet</div>';
        }

        $productInfo = $view->renderPartial('admin/product_info', [
            'currency' => $currency,
            'package' => $package,
            'kubes' => $kubes,
            'trialExpired' => $trialExpired,
        ], false);

    } catch (Exception $e) {
        $productStatistic = sprintf('<div class="error">%s</div>', $e->getMessage());
        $productInfo = '';
    }

    return [
        'Package Kubes' => $productInfo,
        'Pods' => $productStatistic,
    ];
}

/**
 * @param array $params
 * @return string
 * @throws LogicException
 */
function KuberDock_ClientArea($params) {
    /* @var \models\billing\Service $service
     * @var \models\billing\Package $package
     * @var \models\billing\Currency $currency
     * */
    $view = new View();
    $service = Service::find($params['serviceid']);
    $package = $service->package;
    $currency = $service->client->currencyModel;

    $regDate = $service->regdate;
    $trialExpired = '';

    if ($service->isTrialExpired()) {
        $trialExpired = $regDate->addDays($package->getTrialTime());
    }

    try {
        if (!$package->relatedKuberDock) {
            throw new LogicException('KuberDock product not found');
        }
        $pods = $service->getApi()->getPods()->getData();
        $kubes = $service->getAdminApi()->getPackageKubes($package->relatedKuberDock->kuber_product_id)->getData();

        $productStatistic = $view->renderPartial('client/product_statistic', array(
            'pods' => $pods,
            'kubes' => $kubes,
            'service' => $service,
            'package' => $package,
            'currency' => $currency,
        ), false);

        $productInfo = $view->renderPartial('client/product_info', array(
            'currency' => $currency,
            'package' => $package,
            'service' => $service,
            'kubes' => $kubes,
            'trialExpired' => $trialExpired,
        ), false);

        return $productInfo . $productStatistic;
    } catch (Exception $e) {
        return sprintf('<div class="error">%s</div>', $e->getMessage());
    }
}

/**
 * Render button on Setup -> products -> servers page
 * @param array $params
 * @return string
 * @throws CException
 * @throws Exception
 */
function KuberDock_AdminLink($params) {
    $server = Server::typeKuberDock()->find($params['serverid']);

    if (!$server) {
        return '';
    }

    $api = $server->getApi();
    $api->setTimeout(3);

    // Until addon not activated or product with KuberDock server was not edited manually hooks not works
    try {
        $server->accesshash = $api->getToken();
        $server->save();
    } catch (Exception $e) {
        CException::log($e);
    }

    try {
        $token = $api->getJWTToken(array(), true);
        $url = sprintf('%s/?token2=%s', $server->getUrl(), $token);

        return sprintf('<a href="%s" target="_blank" class="btn btn-sm btn-default" >Login to KuberDock</a>', $url);
    } catch (Exception $e) {
        return '';
    }
}

/**
 * @param $params
 * @return string
 */
function KuberDock_LoginLink($params) {
    $service = \models\billing\Service::find($params['serviceid']);

    try {
        return sprintf('<a href="%s" target="_blank">Login to KuberDock</a>', $service->getLoginLink());
    } catch (Exception $e) {
        return '';
    }
}

/**
 * This function is used for upgrading and downgrading of products.
 * @param array $params
 * @return bool
 */
function KuberDock_ChangePackage($params) {
    /* @var \models\billing\PackageUpgrade $packageUpgrade */
    try {
        $packageUpgrade = \models\billing\PackageUpgrade::where('relid', $params['serviceid'])
            ->where('type', 'package')
            ->orderBy('id', 'desc')
            ->first();

        if ($packageUpgrade) {
            $packageUpgrade->upgrade();
        }
    } catch (Exception $e) {
        CException::log($e);
    }
}

/**
 * @param $params
 * @return array
 */
function KuberDock_TestConnection($params) {
    try {
        $protocol = $params['serversecure'] ? Api::PROTOCOL_HTTPS : Api::PROTOCOL_HTTP;
        $url = sprintf('%s://%s', $protocol, $params['serverip']);
        if (empty($params['serverusername']) || empty($params['serverpassword'])) {
            throw new InvalidArgumentException('Username is missing for the selected server. 
                Please save configuration before testing connection.');
        }
        $api = new Api($params['serverusername'], $params['serverpassword'], $url);
        $response = $api->getToken();

        return [
            'success' => true,
        ];
    } catch (Exception $e) {
        return [
            'error' => $e->getMessage(),
        ];
    }
}