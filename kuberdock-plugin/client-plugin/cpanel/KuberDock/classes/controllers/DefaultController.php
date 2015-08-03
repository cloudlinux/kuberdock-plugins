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
            $pods = array();

            if(strpos($e->getMessage(), '[]') !== false) //dump fix
                $this->error = '';
            else
                $this->error = $e;
        }

        $this->render('index', array(
            'pods' => $pods,
        ));
    }

    public function podListAction()
    {
        if(!Tools::getIsAjaxRequest()) {
            return;
        }

        try {
            echo json_encode(array(
                'content' => $this->getContainersList(),
            ));
        } catch(CException $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo $e->getJSON();
        }
    }

    public function searchAction()
    {
        $images = array();
        $search = Tools::getParam('search', Tools::getPost('search', ''));
        $page = Tools::getParam('page', Tools::getPost('page', 1));
        $registryUrl = '';

        try {
            $api = WHMCSApi::model();
            $kuberProduct = $api->getUserKuberDockProduct();
            list($username, $password) = $api->getAuthData();
            $command = new KcliCommand($username, $password);
            $images = $command->searchImages($search, $page-1);
            $registryUrl = $command->getRegistryUrl();
        } catch(CException $e) {
            $this->error = $e;
        }

        $values = array(
            'images' => $images,
            'page' => $page,
            'search' => $search,
            'pagination' => new Pagination($page, 0),
            'registryUrl' => $registryUrl,
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

        try {
            $pod = new Pod();
            $pod = $pod->loadByImage($image);
        } catch(CException $e) {
            $this->error = $e;
        }

        if($_POST) {
            $pod->name = Tools::getPost('containerName', str_replace('/', '-', $image)).'-'.rand(1, 100);
            $pod->restartPolicy = 'Always';
            $pod->replicationController = true;
            $pod->packageId = Tools::getPost('product_id');
            $pod->kube_type = Tools::getPost('kuber_kube_id');

            $pod->containers = array(
                'image' => $image,
                'kubes' => Tools::getPost('kube_count'),
                'ports' => Tools::getPost('Ports'),
                'env' => Tools::getPost('Env'),
                'volumeMounts' => Tools::getPost('Volume'),
            );

            try {
                $pod->create();
                $pod->save();

                $pod->command->startContainer($pod->name);

                echo json_encode(array(
                    'message' => $this->renderPartial('success', array('message' => 'Application created'), false),
                    'redirect' => $_SERVER['SCRIPT_URI'],
                ));
            } catch(CException $e) {
                echo $e->getJSON();
            }
            exit();
        }

        $this->render('install', array(
            'image' => $image,
            'pod' => $pod,
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
            $pod->start();

            echo json_encode(array(
                'message' => $this->renderPartial('success', array('message' => 'Application started'), false),
                'content' => $this->getContainersList(),
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
            $pod->stop();

            echo json_encode(array(
                'message' => $this->renderPartial('success', array('message' => 'Application stopped'), false),
                'content' => $this->getContainersList(),
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
                'content' => $this->getContainersList(),
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

    private function getContainersList()
    {
        try {
            $pod = new Pod();
            $pods = $pod->getPods();
        } catch(CException $e) {
            $pods = array();

            if(strpos($e->getMessage(), '[]') !== false) //dump fix
                $this->error = '';
            else
                $this->error = $e;
        }

        return $this->renderPartial('container_content', array(
            'pods' => $pods,
        ), false);
    }
} 