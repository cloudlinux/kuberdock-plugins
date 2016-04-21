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
        $this->view->assets = \Kuberdock\classes\Base::model()->getStaticPanel()->getAssets();
        $this->view->assets->registerScripts(array(
            'script/lib/jquery.min',
            'script/admin/defaults',
        ));
        $this->view->assets->registerStyles(array('css/admin'));

        $model = new \Kuberdock\classes\plesk\models\Defaults;

        if ($this->getRequest()->isPost()) {
            $model->save($this->getRequest()->getPost());
        }

        $data = $model->read();

        $this->view->form = new \Kuberdock\classes\plesk\forms\Defaults();
        $this->view->packagesKubes = $data['packagesKubes'];
        $this->view->defaults = $data['defaults'];
    }

    public function settingsAction()
    {
        $form = new \Kuberdock\classes\plesk\forms\KubeCli;
        $model = new \Kuberdock\classes\plesk\models\KubeCli;

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $model->save($form->getValues());
        }

        $form->populate($model->read());

        $this->view->form = $form;
    }
}
