<?php

class AdminController extends pm_Controller_Action
{
    
    public function init()
    {
        parent::init();

        $this->view->pageTitle = 'KuberDock Extension';
        $this->view->tabs = array(
            array(
                'title' => 'Existing apps',
                'action' => 'index',
            ),
            array(
                'title' => 'Application defaults',
                'action' => 'defaults',
            ),
            array(
                'title' => 'Edit kubecli.conf',
                'action' => 'settings',
            ),
        );
    }

    public function indexAction()
    {
    }

    public function defaultsAction()
    {
    }

    public function settingsAction()
    {
    }
}
