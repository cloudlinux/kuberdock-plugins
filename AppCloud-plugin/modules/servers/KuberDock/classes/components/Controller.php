<?php
/**
 * @project whmcs-plugin
 */

namespace components;

class Controller extends Component {
    /**
     * Default controller directory
     */
    const CONTROLLER_DIRECTORY  = 'classes';
    /**
     * Controller $_GET param
     */
    const CONTROLLER_PARAM = 'c';
    /**
     * Controller action $_GET param
     */
    const CONTROLLER_ACTION_PARAM = 'a';

    /**
     * @var string
     */
    public $pageTitle;
    /**
     * @var string
     */
    public $controller;
    /**
     * @var string
     */
    public $defaultController = 'Default';
    /**
     * @var string
     */
    public $baseUrl;
    /**
     * @var string
     */
    public $action = 'index';
    /**
     * @var string
     */
    public $layout = 'default';
    /**
     * @var string
     */
    public $error;
    /**
     * @var Assets
     */
    public $assets;
    /**
     * View object
     *
     * @var View
     */
    private $view;

    /**
     * Set view object
     */
    public function setView()
    {
        $this->view = new View($this);
        $this->view->setLayout($this->layout);
    }

    /**
     * Get View object
     *
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Render view file with layout
     *
     * @param string $view
     * @param array $values
     * @param bool $output
     * @return string
     */
    public function render($view, $values = [], $output = true)
    {
        return $this->view->render($view, $values, $output);
    }

    /**
     * Render view file
     *
     * @param string $view
     * @param array $values
     * @param bool $output
     * @return string
     */
    public function renderPartial($view, $values = [], $output = true)
    {
        return $this->view->renderPartial($view, $values, $output);
    }

    /**
     * @param string $error
     * @param string $view
     */
    public function renderError($error, $view = 'default')
    {
        $this->view->setViewDirectory(KUBERDOCK_ROOT_DIR . DS . View::VIEW_DIRECTORY);
        $this->view->renderPartial('error/' . $view, [
            'error' => $error,
        ]);
    }

    /**
     * @param array $attributes
     * @return string
     */
    public function createUrl($attributes = [])
    {
        return $this->baseUrl . (str_contains($this->baseUrl, '?') ? '&' : '?') . http_build_query($attributes);
    }

    /**
     * @param string $url
     */
    public function redirect($url)
    {
        header('Location: ' . $url);
    }

    /**
     * Run application engine
     * @param array $attributes
     * @throws \Exception
     */
    public function run($attributes = [])
    {
        $controller = isset($_GET[self::CONTROLLER_PARAM]) ?
            $_GET[self::CONTROLLER_PARAM] : $this->defaultController;

        try {
            $className = ucfirst($controller) . 'Controller';
            $controllerClass = sprintf('\controllers\%s', $className);
            $model = new $controllerClass;
            $action = isset($_GET[self::CONTROLLER_ACTION_PARAM]) ?
                $_GET[self::CONTROLLER_ACTION_PARAM] : $model->action;
            $model->controller = strtolower($controller);
            $model->action = $action;
            $model->setView();
            $model->setAttributes($attributes);
            $model->init();
            $model->baseUrl = $this->baseUrl;

            $actionMethod = lcfirst($action) . 'Action';

            if (!method_exists($model, $actionMethod)) {
                throw new \Exception('Undefined controller action: ' . $action);
            }

            $method = new \ReflectionMethod($model, $actionMethod);
            $method->invoke($model);
        } catch (\Exception $e) {
            if (isset($model)) {
                $model->error = $e->getMessage();
                $model->renderError($e->getMessage());
            } else {
                throw new \Exception($e->getMessage());
            }
        }
    }
}