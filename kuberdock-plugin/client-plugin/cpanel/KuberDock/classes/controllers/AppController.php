<?php
/**
 * @project cpanel-whmcs
 * @author: Ruslan Rakhmanberdiev
 */

class AppController extends KuberDock_Controller
{
    public function installPredefinedAction()
    {
        $bbCode = new BBCode();
        $templateId = Tools::getParam('template', '');
        $new = Tools::getParam('new', '');
        $podName = Tools::getParam('podName', '');
        $postDescription = Tools::getParam('postDescription', '');
        $plan = Tools::getParam('plan', '');
        $planDetails = Tools::getParam('planDetails', '');

        try {
            $app = new PredefinedApp($templateId);
            $parsedTemplate = $app->getTemplateByPodName($podName);
            $variables = $app->getVariables();
            $pods = $app->getExistingPods();
            $podsCount = count($pods);
            $podsCount = $new || $planDetails ? 0 : $podsCount;
            $podsCount = $postDescription || $podName ? 1 : $podsCount;

            $plans = $app->template->getPlans();
            if(!$planDetails && (($plans && $new) || ($plans && !$podsCount))) {
                $podsCount = -1;
            }

            if($_POST) {
                try {
                    $validator = new Validator(array(
                        'APP_NAME' => array(
                            'name' => 'application name',
                            'rules' => array(
                                'required' => true,
                                'min' => 2,
                                'max' => 64,
                                'alphanum' => true,
                            ),
                        ),
                    ));

                    if (!$validator->run($_POST)) {
                        throw new CException($validator->getErrorsAsString());
                    };

                    $app->setPackageId(Tools::getPost('product_id'));
                    $app->createApp($_POST);

                    $pod = $app->getPod()->loadByName($app->template->getPodName());
                    $link = sprintf('%s?c=app&a=installPredefined&template=%s&podName=%s&postDescription=%s',
                        $pod->panel->getURL(), $templateId, $app->template->getPodName(), 1);

                    if(Base::model()->getPanel()->billing->isFixedPrice($app->getPackageId())) {
                        Base::model()->getPanel()->getApi()->updatePod($pod->id, array(
                            'status' => 'unpaid',
                        ));
                        $response = $pod->order();
                        if($response['status'] == 'Unpaid') {
                            echo json_encode(array('redirect' => $response['redirect']));
                            exit();
                        }
                    } else {
                        $pod->start();
                    }

                    echo json_encode(array(
                        'message' => $this->renderPartial('success', array('message' => 'Application created'), false),
                        'redirect' => $link,
                    ));
                } catch (CException $e) {
                    echo $e->getJSON();
                }

                exit();
            }

            switch($podsCount) {
                case -1:
                    $this->render('selectPlan', array(
                        'app' => $app,
                        'variables' => $variables,
                        'plans' => $plans,
                    ));
                    break;
                case 0:
                    $this->render('installPredefined', array(
                        'app' => $app,
                        'variables' => $variables,
                        'podsCount' => $podsCount,
                        'plan' => $plan,
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
                            $domains[] = $proxy['domain'] . ($dir == Proxy::ROOT_DIR ? '' : '/' . $dir);
                        }
                    }

                    if(Tools::getIsAjaxRequest()) {
                        echo json_encode(array(
                            'content' => $this->renderPartial('pod_details', array(
                                'app' => $app,
                                'pod' => $pod,
                                'podsCount' => count($pods),
                                'domains' => $domains,
                            ), false)
                        ));
                    } else {
                        $this->render('pod_page', array(
                            'app' => $app,
                            'pod' => $pod,
                            'postDescription' => $postDescription,
                            'podsCount' => count($pods),
                            'domains' => $domains,
                        ));
                    }
                    break;
                default:
                    if(Tools::getIsAjaxRequest()) {
                        echo json_encode(array(
                            'content' => $this->renderPartial('container_content', array(
                                'pods' => $pods,
                            ), false)
                        ));
                        exit;
                    } else {
                        $this->render('index', array(
                            'app' => $app,
                            'pods' => $pods,
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