<?php


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
     * @var string
     */
    public $error;

    /**
     * View object
     *
     * @var View
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
     * @return View
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