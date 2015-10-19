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
                $pod->start();

                echo json_encode(array(
                    'message' => $this->renderPartial('success', array('message' => 'Application created'), false),
                    'redirect' => sprintf('%s?a=podDetails&podName=%s', $_SERVER['SCRIPT_URI'], $pod->name),
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
            $variables = $app->getVariables($template);
        } catch(Exception $e) {
            $this->error = $e;
        }

        if($_POST) {
            try {
                $app->setPackageId(Tools::getPost('product_id'));
                $pod = $app->createApp($_POST);
                $app->start();

                echo json_encode(array(
                    'message' => $this->renderPartial('success', array('message' => 'Application created'), false),
                    'redirect' => sprintf('%s?a=podDetails&podName=%s&postDescription=%s',
                        $_SERVER['SCRIPT_URI'], $pod['name'], $app->getPostDescription()),
                ));
            } catch (CException $e) {
                echo $e->getJSON();
            }
            exit();
        }

        $this->render('installPredefined', array(
            'app' => $app,
            'variables' => $variables,
        ));
    }
}