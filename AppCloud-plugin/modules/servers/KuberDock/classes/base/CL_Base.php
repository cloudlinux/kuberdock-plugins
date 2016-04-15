<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base;

use Exception;
use ReflectionMethod;

class CL_Base extends CL_Component {
    /**
     *
     */
    const SESSION_FIELD = '_KD';

    /**
     * @var string
     */
    public $defaultController = 'KuberDock_Default';
    /**
     * @var string
     */
    public $baseUrl;

    /**
     * @var \WHMCS_ClientArea
     */
    private $clientArea;

    /**
     * Getter
     *
     * @param $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        if(method_exists($this, 'get'.ucfirst($name))) {
            return $this->{'get'.ucfirst($name)};
        } else {
            throw new Exception('Undefined property');
        }
    }

    /**
     * Run application engine
     */
    public function run()
    {
        $controller = isset($_GET[CL_Controller::CONTROLLER_PARAM]) ?
            $_GET[CL_Controller::CONTROLLER_PARAM] : $this->defaultController;

        try {
            $className = ucfirst($controller) . 'Controller';
            $namespace = sprintf('controllers\%s', $className);
            $model = new $namespace;
            $action = isset($_GET[CL_Controller::CONTROLLER_ACTION_PARAM]) ?
                $_GET[CL_Controller::CONTROLLER_ACTION_PARAM] : $model->action;
            $model->controller = strtolower($controller);
            $model->action = $action;
            $model->setView();

            $actionMethod = lcfirst($action) . 'Action';

            if(!method_exists($model, $actionMethod)) {
                throw new Exception('Undefined controller action "'.$action.'"');
            }

            $method = new ReflectionMethod($model, $actionMethod);
            $method->invoke($model);
        } catch(Exception $e) {
            if(isset($model)) {
                $model->error = $e->getMessage();
                $model->renderError($e->getMessage());
            } else {
                throw new Exception($e->getMessage());
            }
        }
    }

    /**
     * Get param from $_GET variable
     *
     * @param $key
     * @param null $default
     * @return null
     */
    public function getParam($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    /**
     * Get param from $_POST variable
     *
     * @param $key
     * @param null $default
     * @return null
     */
    public function getPost($key, $default = null)
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    /**
     * @param string $url
     */
    public function redirect($url)
    {
        header('Location: ' . $url);
    }

    /**
     * @param $ca \WHMCS_ClientArea
     */
    public function setClientArea($ca)
    {
        $this->clientArea = $ca;
    }

    /**
     * @return \WHMCS_ClientArea
     */
    public function getClientArea()
    {
        return $this->clientArea;
    }

    /**
     *
     */
    public function setSession()
    {
        $_SESSION[self::SESSION_FIELD] = session_id();
    }

    /**
     * @return string
     */
    public function getSession()
    {
        if(!isset($_SESSION[self::SESSION_FIELD])) {
            $this->setSession();
        }

        return $_SESSION[self::SESSION_FIELD];
    }
}