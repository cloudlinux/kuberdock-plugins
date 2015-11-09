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
     * @var string
     */
    public $defaultController = 'KuberDock_Default';
    /**
     * @var string
     */
    public $baseUrl;

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
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
}