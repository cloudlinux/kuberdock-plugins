<?php

namespace Kuberdock\classes;

class KuberDock_Controller {
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
    public $action;
    /**
     * @var \Exception
     */
    public $error;
    /**
     * @var KuberDock_Assets
     */
    public $assets;

    /**
     * View object
     *
     * @var KuberDock_View
     */
    private $_view;

    /**
     *
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize method
     */
    public function init()
    {

    }

    /**
     * Set view object
     */
    public function setView()
    {
        $this->_view = new KuberDock_View($this);
    }

    /**
     * Get View object
     *
     * @return KuberDock_View
     */
    public function getView()
    {
        return $this->_view;
    }

    /**
     * Render view file with layout
     *
     * @param string $view
     * @param array $values
     * @param bool $output
     * @return string
     */
    public function render($view, $values = array(), $output = true)
    {
        if($this->error) {
            $view = new KuberDock_View();
            return $view->render('errors/default', array(
                'message' => $this->error->getMessage(),
            ));
        }

        return $this->_view->render($view, $values, $output);
    }

    /**
     * Render view file
     *
     * @param string $view
     * @param array $values
     * @param bool $output
     * @return string
     */
    public function renderPartial($view, $values = array(), $output = true)
    {
        return $this->_view->renderPartial($view, $values, $output);
    }

    /**
     * @return bool
     */
    public function isAjaxRequest()
    {
        return Tools::getIsAjaxRequest();
    }
}