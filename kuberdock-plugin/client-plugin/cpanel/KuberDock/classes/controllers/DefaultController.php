<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */


class DefaultController extends KuberDock_Controller {
    const DEFAULT_SEARCH_IMAGE = 'nginx';

    public function indexAction()
    {
        try {
            $pod = new Pod();
            $pods = $pod->getPods();
        } catch(CException $e) {
            if(Tools::getIsAjaxRequest()) {
                header('HTTP/1.1 500 Internal Server Error');
                echo $e->getJSON();
            } else {
                throw $e;
            }
        }

        if(Tools::getIsAjaxRequest()) {
            echo json_encode(array(
                'content' => $this->renderPartial('container_content', array(
                    'pods' => $pods,
                ), false),
            ));
        } else {
            $this->render('index', array(
                'pods' => $pods,
            ));
        }
    }

    public function podDetailsAction()
    {
        $podName = Tools::getParam('podName', 'Undefined');
        $postDescription = Tools::getParam('postDescription', '');
        $templateId = Tools::getParam('templateId', null);

        try {
            $pod = new Pod();
            $pod = $pod->loadByName($podName);
        } catch(CException $e) {
            if(Tools::getIsAjaxRequest()) {
                header('HTTP/1.1 500 Internal Server Error');
                echo $e->getJSON();
            } else {
                throw $e;
            }
        }

        if(Tools::getIsAjaxRequest()) {
            echo json_encode(array(
                'content' => $this->renderPartial('pod_details', array(
                    'pod' => $pod,
                    'templateId' => $templateId,
                ), false),
            ));
        } else {
            $this->render('pod_page', array(
                'pod' => $pod,
                'postDescription' => $postDescription,
                'templateId' => $templateId,
            ));
        }
    }

    public function searchAction()
    {
        $search = Tools::getParam('search', Tools::getPost('search', ''));
        $page = Tools::getParam('page', Tools::getPost('page', 1));

        try {
            $pod = new Pod();
            $templates = $pod->command->getYAMLTemplates();
            $images = $pod->searchImages($search, $page);
            $registryUrl = $pod->command->getRegistryUrl();
        } catch(CException $e) {
            $images = $templates = array();
            $registryUrl = '';
            $this->error = $e;
        }

        $values = array(
            'images' => $images,
            'page' => $page,
            'search' => $search,
            'pagination' => new Pagination($page, 0),
            'registryUrl' => $registryUrl,
            'templates' => $templates,
        );

        if($this->isAjaxRequest()) {
            $this->renderPartial('search_content', $values);
        } else {
            $this->render('search', $values);
        }
    }

    public function installAction()
    {
        $image = Tools::getParam('image', Tools::getPost('image'));

        $api = new KuberDock_Api();
        $sysapi = $api->getSysApi('name');
        $maxKubes = $sysapi['max_kubes_per_container']['value'];

        try {
            $pod = new Pod();
            $pod = $pod->loadByImage($image);
        } catch(CException $e) {
            $pod = new stdClass();
            $this->error = $e;
        }

        if($_POST) {
            $pod->name = Tools::getPost('containerName', str_replace('/', '-', $image)).'-'.rand(1, 100);
            $pod->restartPolicy = 'Always';
            $pod->replicationController = true;
            $pod->packageId = $packageId = Tools::getPost('product_id');
            $pod->kube_type = Tools::getPost('kuber_kube_id');

            try {
                $kubeCount = Tools::getPost('kube_count');
                if ($kubeCount > $maxKubes) {
                    throw new CException('Only ' . $maxKubes . ' kubes allowed');
                }

                $pod->containers = array(
                    'image' => $image,
                    'kubes' => $kubeCount,
                    'ports' => Tools::getPost('Ports'),
                    'env' => Tools::getPost('Env'),
                    'volumeMounts' => Tools::getPost('Volume'),
                );

                $pod->create();
                $pod->save();

                $pod = $pod->loadByName($pod->name);

                if(Base::model()->getPanel()->billing->isFixedPrice($packageId)) {
                    $redirect = urlencode($pod->panel->getURL() . '?a=podDetails&podName=' . $pod->name);
                    $link = sprintf('%s/kdorder.php?a=orderPod&pod=%s&user=%s&referer=%s',
                        $pod->panel->billing->getBillingLink(), $pod->asJSON(), json_encode(array('product_id' => $packageId)), $redirect);
                } else {
                    $link = $pod->panel->getURL();
                    $pod->start();
                }

                echo json_encode(array(
                    'message' => $this->renderPartial('success', array('message' => 'Application created'), false),
                    'redirect' => $link,
                ));
            } catch(CException $e) {
                echo $e->getJSON();
            }
            exit();
        }

        $this->render('install', array(
            'image' => $image,
            'pod' => $pod,
            'maxKubes' => $maxKubes,
        ));
    }

    public function startContainerAction()
    {
        if(!Tools::getIsAjaxRequest()) {
            return;
        }

        $container = Tools::getPost('container');

        try {
            $pod = new Pod();
            $pod = $pod->loadByName($container);

            if($pod->isUnPaid()) {
                $productId = $pod->getApi()->getService()['packageid'];
                $redirect = urlencode($pod->getPanel()->getURL() . '?a=podDetails&podName=' . $pod->name);
                $link = sprintf('%s/kdorder.php?a=orderPod&pod=%s&user=%s&referer=%s',
                    $pod->getPanel()->getBillingLink(), $pod->asJSON(),
                    json_encode(array('product_id' => $productId)), $redirect);
                echo json_encode(array('redirect' => $link));
                exit();
            } elseif(in_array($pod->status, array('stopped', 'terminated', 'failed', 'succeeded'))) {
                $pod->start();
                $message = 'Application started';
            } else {
                $message = 'Application is already running';
            }

            echo json_encode(array(
                'message' => $this->renderPartial('success', array('message' => $message), false),
            ));
        } catch(CException $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo $e->getJSON();
        }
    }

    public function stopContainerAction()
    {
        if(!Tools::getIsAjaxRequest()) {
            return;
        }

        $container = Tools::getPost('container');

        try {
            $pod = new Pod();
            $pod = $pod->loadByName($container);

            if (in_array($pod->status, array('running', 'pending'))) {
                $pod->stop();
                $message = 'Application stopped';
            } else {
                $message = 'Application is already stopped';
            }

            echo json_encode(array(
                'message' => $this->renderPartial('success', array('message' => $message), false),
            ));
        } catch(CException $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo $e->getJSON();
        }
    }

    public function deleteContainerAction()
    {
        if(!Tools::getIsAjaxRequest()) {
            return;
        }

        $container = Tools::getPost('container');

        try {
            $pod = new Pod();
            $pod = $pod->loadByName($container);
            $pod->delete();

            echo json_encode(array(
                'message' => $this->renderPartial('success', array('message' => 'Application deleted'), false),
                'redirect' => $_SERVER['SCRIPT_URI'],
            ));
        } catch(CException $e) {
            // TODO: temporary, until it fixed in kcli
            echo json_encode(array(
                'message' => 'Deleted',
                'redirect' => $_SERVER['SCRIPT_URI'],
            ));
        }
    }

    public function restartPodAction()
    {
        if(!Tools::getIsAjaxRequest()) {
            return;
        }

        $podName = Tools::getPost('pod');
        $wipeOut = Tools::getPost('wipeOut', 0);

        try {
            $pod = new Pod();
            $pod = $pod->loadByName($podName);

            Base::model()->getPanel()->getApi()->redeployPod($pod->id, $wipeOut);

            echo json_encode(array(
                'message' => $this->renderPartial('success', array('message' => 'Application restarted'), false),
            ));
        } catch(CException $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo $e->getJSON();
        }
    }

    public function redirectAction()
    {
        $podName = Tools::getPost('name');
        $pod = new Pod();
        $pod = $pod->loadByName($podName);

        echo json_encode(array(
            'redirect'=> $pod->getPodUrl(true),
        ));
    }

    public function getPersistentDrivesAction()
    {
        if(!Tools::getIsAjaxRequest()) {
            return;
        }

        try {
            $pod = new Pod();

            echo json_encode(array(
                'data' => $pod->getPersistentDrives(),
            ));
        } catch(CException $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo $e->getJSON();
        }
    }

    public function streamAction()
    {
        set_time_limit(0);
        header('Content-Type: text/event-stream');

        $config = KcliCommand::getConfig();

        if(!isset($config['url']) || !isset($config['token'])) {
            echo "retry: 10000\n\n";
            exit;
        }

        $url = sprintf('%s/api/stream?token=%s', $config['url'], $config['token']);
        $handle = fopen($url, 'r');

        while($handle) {
            $response = fgets($handle);
            if($response) {
                echo sprintf("%s", $response);
            }
            flush();
            sleep(1);
        }

        echo "retry: 10000\n\n";
        fclose($handle);
    }
}