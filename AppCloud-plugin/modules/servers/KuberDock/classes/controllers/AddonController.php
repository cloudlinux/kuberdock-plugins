<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace controllers;

use components\Assets;
use components\Controller;
use components\Csrf;
use components\Paginator;
use components\Tools;
use Exception;
use models\addon\KubePrice;
use models\addon\KubePriceChange;
use models\addon\KubeTemplate;
use models\billing\Admin;
use models\billing\Currency;
use models\billing\Package;
use models\billing\Server;
use models\billing\Service;

class AddonController extends Controller {
    /**
     * @var string
     */
    public $action = 'index';
    /**
     * @var string
     */
    public $layout = 'addon';

    public function init()
    {
        $this->assets = new Assets();
        $this->assets->registerScriptFiles(['jquery.min', 'bootstrap.min', 'addon']);
        $this->assets->registerStyleFiles(['bootstrap.min', 'addon']);

        $admin = Admin::getCurrent();
        if ($admin->template == 'blend') {
            $this->assets->registerStyleFiles(['addon_blend']);
        }
    }

    public function indexAction()
    {
        $this->assets->registerScriptFiles(['jquery.tablesorter.min']);

        $paginator = $this->getLogsPaginator();
        $logs = KubePriceChange::offset($paginator->offset())->limit($paginator->limit())->get();
        $package = new Package();

        $tabs = array(
            'kubes' => 'Kube types',
            'log' => 'Changes log',
        );

        $this->render('index', [
            'kubes' => KubeTemplate::with('KubePrice')->orderBy('kube_name')->get(),
            'packages' => $package->getSortedActivePackages(),
            'brokenPackages' => $package->broken()->get(),
            'logs' => $logs,
            'paginator' => $paginator,
            'tabs' => $tabs,
        ]);
    }

    public function addAction()
    {
        $this->assets->registerScriptFiles(array('jquery.form-validator.min'));

        $servers = Server::typeKuberDock()->orderBy('active')->get();
        $kubeTemplate = new KubeTemplate();

        if ($_POST) {
            Csrf::check();
            unset($_POST['csrf_token']);
            unset($_POST['token']);

            $kubeTemplate->setRawAttributes($_POST);
            $kubeTemplate->kube_type = $kubeTemplate::TYPE_NON_STANDARD;

            try {
                $kubeTemplate->save();
                $this->redirect($this->baseUrl);
            } catch (Exception $e) {
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
            'kubeTemplate' => $kubeTemplate,
            'servers' => $servers,
        ));
    }

    public function deleteAction()
    {
        $id = Tools::model()->getPost('id');
        $template =  KubeTemplate::find($id);

        if ($template->isDeletable()) {
            try {
                $template->delete();
                echo json_encode(array(
                    'error' => false,
                    'message' => 'Kube deleted',
                ));
            } catch (Exception $e) {
                echo json_encode(array(
                    'error' => true,
                    'message' => $e->getMessage(),
                ));
            }
        } else {
            echo json_encode(array(
                'error' => true,
                'message' => 'Kube is used by package, can not delete',
            ));
        }

        exit(0);
    }

    public function kubePriceAction()
    {
        if (!Tools::model()->isAjaxRequest() || !$_POST) {
            exit();
        }

        try {
            Csrf::check();

            $kubePrice = Tools::model()->getPost('kube_price', '');
            $id = (int) Tools::model()->getPost('id');
            $productId = (int) Tools::model()->getPost('product_id');
            $templateId = (int) Tools::model()->getPost('template_id');
            $kuberProductId = (int) Tools::model()->getPost('kuber_product_id');
            $currency = Currency::getDefault();

            $kubeTemplate = KubeTemplate::find($templateId);

            if ($kubePrice == '') {
                KubePrice::find($id)->delete();
            } else {
                $kube = $id ? KubePrice::find($id) : new KubePrice();
                $kube->template_id = $templateId;
                $kube->product_id = $productId;
                $kube->kuber_product_id = $kuberProductId;
                $kube->kube_price = $kubePrice;
                $kube->save();
            }

            echo json_encode(array('error' => false, 'values' => array(
                'id' => ($kubePrice == '') ? '' : $kube->id,
                'kube_price' => ($kubePrice == '')
                    ? ''
                    : $currency->getFormatted($kubePrice),
                'deletable' => $kubeTemplate->isDeletable(),
            )));

        } catch (Exception $e) {
            echo json_encode(array(
                'error' => true,
                'message' => str_replace("kube_price - field 'kube_price'", 'Value', $e->getMessage()),
            ));
        }

        exit();
    }

    public function isKuberProductAction()
    {
        if (Tools::isAjaxRequest()) {
            $productId = Tools::model()->getParam('productId');
            $serviceId = Tools::model()->getParam('serviceId');

            if ($productId && $serviceId) {
                $service = Service::find($serviceId);

                echo json_encode(array(
                    'kuberdock' => $service->package->isKuberDock(),
                    'nextinvoicedate' => Tools::getFormattedDate($service->nextinvoicedate),
                    'nextduedate' => Tools::getFormattedDate($service->nextduedate),
                ));
            } elseif ($productId) {
                $package = Package::find($productId);

                echo json_encode(array(
                    'kuberdock' => $package->isKuberDock(),
                    'trial' => $package->getEnableTrial(),
                ));
            }
        }

        exit();
    }

    /**
     * @return Paginator
     */
    public function getLogsPaginator()
    {
        $per_page = 10;
        $count = KubePriceChange::count();

        $page = min(
            ceil($count / $per_page),
            filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
                'options' => array(
                    'default' => 1,
                    'min_range' => 1,
                ),
            ))
        );

        $paginator = Paginator::create(array(
            'total_count' => $count,
            'requested_page' => $page,
            'per_page' => $per_page,
        ))->append_params(array(
            'module' => 'KuberDock',
        ))->append_anchor('log');

        return $paginator;
    }
} 