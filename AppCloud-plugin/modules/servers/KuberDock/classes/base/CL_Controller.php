<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

namespace base;

class CL_Controller {
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
     * @var CL_Assets
     */
    public $assets;

    /**
     * View object
     *
     * @var CL_View
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
        $this->_view = new CL_View($this);
        $this->_view->setLayout($this->layout);
    }

    /**
     * Get View object
     *
     * @return CL_View
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
     * @param string $error
     * @param string $view
     */
    public function renderError($error, $view = 'default')
    {
        $this->_view->setViewDirectory(KUBERDOCK_ROOT_DIR . DS . CL_View::VIEW_DIRECTORY);
        $this->_view->renderPartial('error/' . $view, array(
            'error' => $error,
        ));
    }

    /**
     * @return bool
     */
    public function isAjaxRequest()
    {
        return CL_Tools::getIsAjaxRequest();
    }
}