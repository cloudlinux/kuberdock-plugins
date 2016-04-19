<?php

class AdminController extends pm_Controller_Action
{
    protected $_accessLevel = 'admin';

    public function init()
    {
        parent::init();

        require_once pm_Context::getPlibDir() . 'library/KuberDock/init.php';

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
        $form = new \Kuberdock\classes\plesk\forms\KubeCli();
        $model = new \Kuberdock\classes\plesk\models\KubeCli();

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $model->save($form->getValues());
        }

        $form->populate($model->read());

        $this->view->form = $form;
    }
}
