<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

class AppController extends KuberDock_Controller {

    public function installAction()
    {
        $image = Tools::getParam('image', Tools::getPost('image'));

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

    public function installPredefinedAction()
    {
        $templateId = Tools::getParam('template', '');

        try {
            $app = new PredefinedApp();
            $template = $app->getTemplate($templateId);
        } catch(Exception $e) {
            $this->error = $e;
        }

        $this->render('installPredefined', array(
            'image' => 'Predefined app',
            'app' => $app,
        ));
    }
}