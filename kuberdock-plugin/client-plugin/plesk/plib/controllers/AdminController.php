<?php

class AdminController extends pm_Controller_Action
{
    protected $_accessLevel = 'admin';

    public function init()
    {
        parent::init();

        require_once pm_Context::getPlibDir() . 'library/KuberDock/init.php';

        $this->view->assets = \Kuberdock\classes\Base::model()->getStaticPanel()->getAssets();
        $this->view->pageTitle = 'KuberDock';

        $this->view->tabs = array(
            array(
                'title' => 'Applications',
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
        $this->view->assets->registerScripts(array(
            'script/lib/jquery.min',
            'script/plesk/admin/index',
        ));
        $this->view->assets->registerStyles(array('css/plesk/admin'));
        try {
            $this->view->list = new \Kuberdock\classes\plesk\lists\App($this->view, $this->_request);
        } catch (\Kuberdock\classes\exceptions\CException $e) {
            $this->settingsWrong();
        }
    }

    public function applicationAction()
    {
        $id = (int) $this->_getParam('id');

        $this->view->assets->registerStyles(array(
            'css/plesk/admin',
            'css/bootstrap.min',
            'script/lib/codemirror/codemirror',
        ));

        $this->view->assets->registerScripts(array(
            'script/lib/jquery.min',
            'script/lib/codemirror/codemirror.min',
            'script/lib/codemirror/mode/yaml/yaml',
            'script/lib/jquery.form-validator.min',
            'script/lib/fileupload/js/vendor/jquery.ui.widget',
            'script/lib/bootstrap.min',
            'script/lib/fileupload/js/jquery.iframe-transport',
            'script/lib/fileupload/js/jquery.fileupload',
            'script/plesk/admin/application',
        ));

        $model = new \Kuberdock\classes\plesk\models\App();
        $form = new \Kuberdock\classes\plesk\forms\App;

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $values = $form->getValues();

            try{
                $model->save($values);
                $this->_status->addMessage('info', 'Template "' . $values['name'] . '" was successfully saved.');
                $this->_redirect(pm_Context::getBaseUrl());
            } catch (Exception $e) {
                $this->view->error = $e->getMessage();
            }
        }

        if ($id) {
            $form->populate($model->read($id));
        }

        $form->populate($this->getRequest()->getPost());

        $this->view->form = $form;
    }

    public function extractYamlAction()
    {
        try {
            $fileManager = new \pm_ServerFileManager;
            $file = $_FILES['yaml_file']['tmp_name'];
            $yaml = $fileManager->fileGetContents($file);

            echo json_encode(array(
                'yaml' => $yaml,
                'error' => 0,
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'error' => $e->getMessage(),
            ));
        }

        exit;
    }

    public function validateYamlAction()
    {
        $model = new \Kuberdock\classes\plesk\models\App();
        $template = $_POST['template'];
        $errors = 0;
        try{
            $model->validate($template);
        } catch (\Kuberdock\classes\exceptions\YamlValidationException $e) {
            $errors = $e->getMessage();
        }

        echo json_encode(array('errors' => $errors));
        exit;
    }

    public function defaultsAction()
    {
        $this->view->assets->registerScripts(array(
            'script/lib/jquery.min',
            'script/plesk/admin/defaults',
        ));
        $this->view->assets->registerStyles(array('css/plesk/admin'));

        $model = new \Kuberdock\classes\plesk\models\Defaults;

        if ($this->getRequest()->isPost()) {
            $model->save($this->getRequest()->getPost());
        }

        try{
            $data = $model->read();
        } catch (\Kuberdock\classes\exceptions\CException $e) {
            $this->settingsWrong();
        }

        $this->view->form = new \Kuberdock\classes\plesk\forms\Defaults();
        $this->view->packagesKubes = $data['packagesKubes'];
        $this->view->defaults = $data['defaults'];
    }

    public function settingsAction()
    {
        $this->view->assets->registerStyles(array('css/plesk/admin'));

        $form = new \Kuberdock\classes\plesk\forms\KubeCli;
        $model = new \Kuberdock\classes\plesk\models\KubeCli;

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            try {
                $model->save($form->getValues());
            } catch (\Kuberdock\classes\exceptions\CException $e) {
                echo $e->getMessage();
                exit;
            }
        }

        $form->populate($model->read());

        $this->view->form = $form;
    }

    public function deleteAction()
    {
        if (!$this->getRequest()->isPost()) {
            throw new Exception('post only required');
        }

        $id = (int) $this->getRequest()->getPost('id');
        $name = $this->getRequest()->getPost('name');

        try {
            $model = new \Kuberdock\classes\plesk\models\App();
            $model->delete($id);

            $this->_status->addMessage('info', 'Template ' . $name . ' was successfully deleted.');
        } catch (Exception $e) {
            $this->_status->addMessage('error', $e->getMessage());
        }

        echo json_encode(array(
            'redirect' => pm_Context::getActionUrl('admin', 'index'),
        ));
        die;
    }

    protected function settingsWrong()
    {
        $this->_status->addMessage('error',
            'Cannot connect to KuberDock server, invalid credentials or server url in /root/.kubecli.conf');
        $this->_redirect('admin/settings');
    }
}
