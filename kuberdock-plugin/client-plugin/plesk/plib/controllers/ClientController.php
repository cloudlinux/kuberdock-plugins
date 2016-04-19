<?php


class ClientController extends pm_Controller_Action
{
    public function init()
    {
        parent::init();

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
}
