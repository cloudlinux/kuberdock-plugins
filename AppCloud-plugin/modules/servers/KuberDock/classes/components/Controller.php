<?php
/**
 * @project whmcs-plugin
 */

namespace components;

class Controller {
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
     * @var
     */
    public $assets;

    /**
     * @var WHMCS_ClientArea
     */
    public $clientArea;

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
}