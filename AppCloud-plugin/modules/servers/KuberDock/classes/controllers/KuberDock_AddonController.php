<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace controllers;

use base\CL_Controller;
use base\CL_Base;
use base\CL_Csrf;
use base\CL_Tools;
use Exception;
use migrations\Migration;

class KuberDock_AddonController extends CL_Controller {
    public $action = 'index';
    /**
     * @var string
     */
    public $layout = 'addon';

    public function init()
    {
        $this->assets = new \KuberDock_Assets();
        $this->assets->registerScriptFiles(array('jquery.min', 'bootstrap.min', 'addon'));
        $this->assets->registerStyleFiles(array('bootstrap.min', 'addon'));
    }

    public function indexAction()
    {
        $this->assets->registerScriptFiles(array('jquery.tablesorter.min'));

        $paginator = $this->getLogsPaginator();
        $logs = \KuberDock_Addon_PriceChange::getLogs($paginator->limit(), $paginator->offset());

        $products = $this->getSortedActiveProducts();
        $kubes = \KuberDock_Addon_Kube::model()->loadByAttributes(array(), 'product_id IS NULL',
            array('order' => 'kuber_kube_id'));
        $productKubes = \KuberDock_Addon_Kube::model()->loadByAttributes(array(), 'product_id IS NOT NULL');
        $brokenPackages = \KuberDock_Addon_Product::model()->getBrokenPackages();

        foreach ($kubes as &$kube) {
            foreach ($products as $product) {
                $productKube = $this->getProductKube($productKubes, $kube['kuber_kube_id'], $product['id']);

                $kube['packages'][] = array(
                    'name' => $product['name'],
                    'payment_type' => $product['payment_type'],
                    'product_id' => $product['id'],
                    'kuber_product_id' => $product['kuber_product_id'],
                    'id' => $productKube ? $productKube['id'] : null,
                    'kube_price' => $productKube ? $productKube['kube_price'] : null,
                );
            }
        }
        unset($kube);

        $this->render('index', array(
            'kubes' => $kubes,
            'products' => $products,
            'brokenPackages' => $brokenPackages,
            'logs' => $logs,
            'paginator' => $paginator,
            'tabs' => $tabs,
            'updateDb' => Migration::check(),
        ));
    }

    private function getProductKube($productKubes, $kube_id, $product_id)
    {
        foreach ($productKubes as $kube) {
            if ($kube['product_id']==$product_id && $kube['kuber_kube_id']==$kube_id) {
                return $kube;
            }
        }

        return null;
    }

    public function migrateAction()
    {
        if(!CL_Tools::getIsAjaxRequest()) {
            exit();
        }

        try{
            Migration::up();
        } catch (Exception $e) {
            echo json_encode(array(
                'message' => $e->getMessage(),
            ));
            exit();
        }

        echo json_encode(array(
            'message' => 'Database successully updated',
        ));
        exit();
    }

    public function addAction()
    {
        $this->assets->registerScriptFiles(array('jquery.form-validator.min'));

        $base = CL_Base::model();
        $kube = \KuberDock_Addon_Kube::model();
        $products = \KuberDock_Product::model()->getActive();
        $servers = \KuberDock_Server::model()->getServers();

        if(!$products) {
            throw new Exception('Products has no relations');
        }

        if($_POST) {
            CL_Csrf::check();
            unset($_POST['csrf_token']);
            unset($_POST['token']);
            $kube->setAttributes($_POST);
            $kube->setAttribute('kube_type', $kube::NON_STANDARD_TYPE);

            try {
                $kube->save();
                $base->redirect($base->baseUrl);
            } catch(Exception $e) {
                $replace = array(
                    'server_id' => 'Server',
                    'name' => 'Kube type name',
                    'cpu' => 'CPU limit',
                    'memory' => 'Memory limit',
                    'disk_space' => 'HDD limit',
                    'traffic_limit' => 'Traffic limit',
                );
                $this->error = str_replace(array_keys($replace), array_values($replace), $e->getMessage());
            }
        }

        $this->render('add', array(
            'products' => $products,
            'kube' => $kube,
            'servers' => $servers,
        ));
    }

    public function deleteAction()
    {
        $id = CL_Base::model()->getParam('id');
        $kube = \KuberDock_Addon_Kube::model()->loadById($id);
        $productKubes = \KuberDock_Addon_Kube::model()->loadByAttributes(array(), 'product_id IS NOT NULL');
        $usedKubes = array_filter($productKubes, function($e) use ($kube) {
            if($e['kuber_kube_id'] == $kube->kuber_kube_id && $e['server_id'] == $kube->server_id) {
                return $e;
            }
        });

        if(!$usedKubes && !$kube->isStandart()) {
            $kube->delete();
            CL_Base::model()->redirect(CL_Base::model()->baseUrl);
        }
    }

    public function kubePriceAction()
    {
        if(!CL_Tools::getIsAjaxRequest() || !$_POST) {
            exit();
        }

        try {
            CL_Csrf::check();

            $kubePrice = CL_Base::model()->getPost('kube_price');
            $id = (int) CL_Base::model()->getPost('id');

            $product_id = (int) CL_Base::model()->getPost('product_id');
            $kuber_kube_id = (int)CL_Base::model()->getPost('kuber_kube_id');
            $currency = \base\models\CL_Currency::model()->getDefaultCurrency();

            $kube = $id
                ? \KuberDock_Addon_Kube::model()->loadById($id)
                : \KuberDock_Addon_Kube::createKubeForPackage($product_id, $kuber_kube_id);

            $old_price = $kube->kube_price;

            if($old_price != $kubePrice) {
                $kube->setAttributes(array(
                    'kube_price' => $kubePrice,
                ));
            }

            $kube->save();

            \KuberDock_Addon_PriceChange::saveLog($kuber_kube_id, $product_id, $old_price, $kubePrice);

            echo json_encode(array('error' => false, 'values' => array(
                'id' => ($kubePrice=='')
                    ? ''
                    : $kube->id,
                'name' => \KuberDock_Product::model()->loadById($product_id)->name,
                'kube_price' => ($kubePrice=='')
                    ? ''
                    : $currency->getFormatted($kubePrice),
            )));

        } catch(Exception $e) {
            echo json_encode(array(
                'error' => true,
                'message' => str_replace("kube_price - field 'kube_price'", 'Value', $e->getMessage()),
            ));
        }

        exit();
    }

    public function isKuberProductAction()
    {
        if(CL_Tools::getIsAjaxRequest()) {
            $productId = CL_Base::model()->getParam('productId');
            $serviceId = CL_Base::model()->getParam('serviceId');

            if($productId && $serviceId) {
                $service = \KuberDock_Hosting::model()->loadById($serviceId);

                echo json_encode(array(
                    'kuberdock' => \KuberDock_Product::model()->loadById($productId)->isKuberProduct(),
                    'nextinvoicedate' => CL_Tools::getFormattedDate($service->nextinvoicedate),
                    'nextduedate' => CL_Tools::getFormattedDate($service->nextduedate),
                ));
            } elseif($productId) {
                $product = \KuberDock_Product::model()->loadById($productId);

                echo json_encode(array(
                    'kuberdock' => $product->isKuberProduct(),
                    'trial' => $product->isTrial(),
                ));
            }
        }

        exit();
    }

    /**
     * @return array
     */
    public function getSortedActiveProducts()
    {
        $products = \KuberDock_Product::model()->getActive();

        foreach ($products as &$product) {
            $product['payment_type'] = \KuberDock_Product::model()->loadByParams($products[$product['id']])->getPaymentType();
        }
        unset($product);

        $payment_types = array_flip(\KuberDock_Product::getPaymentTypes());
        uasort($products, function ($a, $b) use ($payment_types) {
            if ($a['payment_type'] == $b['payment_type']) {
                return ($a['id'] > $b['id']) ? 1 : -1;
            }
            return ($payment_types[$a['payment_type']] < $payment_types[$b['payment_type']]) ? 1 : -1;
        });

        return $products;
    }

    /**
     * @return \KuberDock_Paginator
     */
    public function getLogsPaginator()
    {
        $per_page = 10;
        $count = \KuberDock_Addon_PriceChange::model()->count();

        $page = min(
            ceil($count / $per_page),
            filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
                'options' => array(
                    'default' => 1,
                    'min_range' => 1,
                ),
            ))
        );

        $paginator = \KuberDock_Paginator::create(array(
            'total_count' => $count,
            'requested_page' => $page,
            'per_page' => $per_page,
        ))->append_params(array(
            'module' => 'KuberDock',
        ))->append_anchor('log');

        return $paginator;
    }
} 