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
        $bbCode = new BBCode();
        $templateId = Tools::getParam('template', '');
        $new = Tools::getParam('new', '');
        $podName = Tools::getParam('podName', '');
        $postDescription = Tools::getParam('postDescription', '');

        try {
            $app = new PredefinedApp($templateId);
            $parsedTemplate = $app->loadTemplate($podName);
            $variables = $app->getVariables();
            $pods = $app->getExistingPods();
            $podsCount = count($pods);
            $podsCount = $new ? 0 : $podsCount;
            $podsCount = $postDescription || $podName ? 1 : $podsCount;

            if($_POST) {
                try {
                    $app->setPackageId(Tools::getPost('product_id'));
                    $app->createApp($_POST);
                    $app->start();

                    echo json_encode(array(
                        'message' => $this->renderPartial('success', array('message' => 'Application created'), false),
                        'redirect' => sprintf('%s?c=app&a=installPredefined&template=%s&podName=%s&postDescription=%s',
                            $_SERVER['SCRIPT_URI'], $templateId, $app->getPodName(), 1),
                    ));
                } catch (CException $e) {
                    echo $e->getJSON();
                }

                exit();
            }

            switch($podsCount) {
                case 0:
                    $this->render('installPredefined', array(
                        'app' => $app,
                        'variables' => $variables,
                        'podsCount' => $podsCount,
                    ));
                    break;
                case 1:
                    $pod = new Pod();
                    if($podName) {
                        $pod = $pod->loadByName($podName);
                    } else {
                        $pod = $pod->loadByName(current($pods)['name']);
                    }

                    if($postDescription) {
                        $postDescription = isset($parsedTemplate['kuberdock']['postDescription']) ?
                            $parsedTemplate['kuberdock']['postDescription'] : '';
                        $postDescription = $bbCode->toHTML($postDescription);
                    }

                    $domains = array();
                    if(isset($parsedTemplate['kuberdock']['proxy'])) {
                        foreach($parsedTemplate['kuberdock']['proxy'] as $dir => $proxy) {
                            $domains[] = $proxy['domain'] . '/' . $dir;
                        }
                    }

                    if(Tools::getIsAjaxRequest()) {
                        echo json_encode(array(
                            'content' => $this->renderPartial('pod_details', array(
                                'app' => $app,
                                'pod' => $pod,
                                'podsCount' => $podsCount,
                                'domains' => $domains,
                            ), false)
                        ));
                    } else {
                        $this->render('pod_page', array(
                            'app' => $app,
                            'pod' => $pod,
                            'postDescription' => $postDescription,
                            'podsCount' => $podsCount,
                            'domains' => $domains,
                        ));
                    }
                    break;
                default:
                    if(Tools::getIsAjaxRequest()) {
                        echo json_encode(array(
                            'content' => $this->renderPartial('container_content', array(
                                'pods' => $app->getExistingPods(),
                            ), false)
                        ));
                        exit;
                    } else {
                        $this->render('index', array(
                            'app' => $app,
                            'pods' => $app->getExistingPods(),
                        ));
                    }
                    break;
            }
        } catch(CException $e) {
            if(Tools::getIsAjaxRequest()) {
                header('HTTP/1.1 500 Internal Server Error');
                echo $e->getJSON();
            } else {
                throw $e;
            }
        }
    }
}