<?php

use \Kuberdock\classes\api\KuberDock;
use \Kuberdock\classes\api\Response;
use \Kuberdock\classes\exceptions\PaymentRequiredException;
use \Kuberdock\classes\exceptions\ApiException;


class ClientController extends pm_Controller_Action
{
    public function init()
    {
        parent::init();

        $session = new pm_Session();
        $client = $session->getClient();

        if ($client->isAdmin()) {
            $this->_redirect('/admin/index');
        }

        $this->view->pageTitle = 'KuberDock Extension';
        $this->view->tabs = array(
            array(
                'title' => 'Your apps',
                'action' => 'index',
            ),
            array(
                'title' => 'Applications',
                'action' => 'applications',
            ),
        );

        require_once pm_Context::getPlibDir() . 'library/KuberDock/init.php';
    }

    public function indexAction()
    {
        $controller = isset($_GET[\Kuberdock\classes\KuberDock_Controller::CONTROLLER_PARAM])
            ? $_GET[\Kuberdock\classes\KuberDock_Controller::CONTROLLER_PARAM]
            : 'default';

        $action = isset($_GET[\Kuberdock\classes\KuberDock_Controller::CONTROLLER_ACTION_PARAM])
            ? $_GET[\Kuberdock\classes\KuberDock_Controller::CONTROLLER_ACTION_PARAM]
            : 'index';

        try {
            $className = '\Kuberdock\classes\controllers\\' . ucfirst($controller) . 'Controller';
            $model = new $className;
            $model->controller = strtolower($controller);
            $model->action = $action;
            $model->setView();

            $actionMethod = lcfirst($action) . 'Action';

            if(!method_exists($model, $actionMethod)) {
                throw new \Kuberdock\classes\exceptions\CException('Undefined controller action "'.$action.'"');
            }

            $method = new \ReflectionMethod($model, $actionMethod);
            $method->invoke($model);
        } catch(\Kuberdock\classes\exceptions\CException $e) {
            echo $e;
        }
    }

    public function applicationsAction()
    {
    }

    public function apiAction()
    {
        try {
            if (!isset($_REQUEST['request'])) {
                throw new ApiException('Request not found', 404);
            }

            $API = new KuberDock($_REQUEST['request']);
            $API->run();
        } catch (PaymentRequiredException $e) {
            Response::error('Payment required', 402, $e->getRedirect());
        } catch (ApiException $e) {
            Response::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }

        exit(0);
    }
}
