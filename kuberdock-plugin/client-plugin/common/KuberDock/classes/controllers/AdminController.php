<?php

namespace Kuberdock\classes\controllers;

use Kuberdock\classes\KuberDock_Controller;
use Kuberdock\classes\Base;


class AdminController extends KuberDock_Controller
{
    public $panelName;

    public $homeUrl = '/CMD_PLUGINS_ADMIN/KuberDock';

    public function init()
    {
        $this->assets = Base::model()->getStaticPanel()->getAssets();
        $this->panelName = Base::model()->getPanelType();
        $this->assets->registerScripts(array(
            'jquery.min' => 'script/lib/jquery.min',
            'bootstrap.min' => 'script/lib/bootstrap.min',
        ));

        $this->assets->registerStyles(array(
            'css/bootstrap.min',
            'css/admin',
            'css/' . strtolower($this->panelName) . '/admin',
        ));
    }

    public function indexAction()
    {
        $this->assets->registerScripts(array(
            'index' => 'script/' . strtolower($this->panelName) . '/admin/index',
        ));

        $kubeCliModel = new \Kuberdock\classes\models\KubeCli($this->panelName);
        $defaultsModel = new \Kuberdock\classes\models\Defaults($this->panelName);

        if (isset($_POST['tab']) && $_POST['tab']=='kubecli') {
            $kubeCli = $this->preparePost(array('url', 'user', 'password', 'registry'));

            try{
                $kubeCliModel->save($kubeCli);
            } catch (\Exception $e){
                $messages = array($e->getMessage() => 'danger');
            }
        }

        if (isset($_POST['tab']) && $_POST['tab']=='defaults') {
            $defaultsModel->save($this->preparePost(array('packageId', 'kubeType')));
        }

        $kubeCli = $kubeCliModel->read();
        if (!$kubeCli['token']) {
            $msg = 'Cannot connect to KuberDock server, invalid credentials or server url in ' . $kubeCliModel->getRootPath();
            $this->render('index', array(
                'kubeCli' => $kubeCli,
                'messages' => array($msg => 'danger'),
                'error' => true,
            ));
            die;
        }

        $defaults = $defaultsModel->read();

        $appModel = new \Kuberdock\classes\models\App($this->panelName);
        $apps = $appModel->getAll();

        $this->render('index', array(
            'apps' => $apps,
            'kubeCli' => $kubeCli,
            'messages' => isset($messages) ? $messages : array(),
            'defaults' => $defaults['defaults'],
            'packagesKubes' => $defaults['packagesKubes'],
            'activeTab' => !isset($activeTab) ? 'pre_apps' : $activeTab,
        ));
    }

    public function appAction()
    {
        $this->assets->registerScripts(array(
            'codemirror.min' =>'script/lib/codemirror/codemirror.min',
            'yaml' => 'script/lib/codemirror/mode/yaml/yaml',
            'validator.min' => 'script/lib/jquery.form-validator.min',
            'app' => 'script/' . strtolower($this->panelName) . '/admin/app',
        ));

        $this->assets->registerStyles(array(
            'script/lib/codemirror/codemirror',
        ));

        $appModel = new \Kuberdock\classes\models\App($this->panelName);

        $id = isset($_GET['id'])
            ? (int) $_GET['id']
            : 0;

        $app = $id
            ? $appModel->read($id)
            : array(
                'id' => 0,
                'name' => '',
                'template' => '# Please input your yaml here',
            );

        if ($_POST) {
            $app = $this->preparePost(array('id', 'name', 'template'));

            $validator = new \Kuberdock\classes\Validator(array(
                'name' => array(
                    'name' => 'App name',
                    'rules' => array(
                        'required' => true,
                        'min' => 1,
                        'max' => 30,
                        'alpha' => true,
                    ),
                ),
                'template' => array(
                    'name' => 'YAML',
                    'rules' => array(
                        'required' => true,
                    ),
                ),
            ));

            if ($validator->run($app)) {
                try{
                    $appModel->save($app);
                    $this->setLocalStorage('flash', array('Template was successfully saved'=>'info'));
                    $this->redirect('#pre_apps');
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            } else {
                $errors = $validator->getErrorsAsArray();
            }
        }

        $this->render('app', array(
            'id' => $id,
            'app' => $app,
            'errors' => isset($errors) ? $errors : array(),
        ));
    }

    public function appDeleteAction()
    {
        $id = (int) $_GET['id'];

        try {
            $model = new \Kuberdock\classes\models\App($this->panelName);
            $model->delete($id);
            $this->setLocalStorage('flash', array('Template was successfully deleted'=>'info'));
        } catch (\Exception $e) {
            $this->setLocalStorage('flash', array($e->getMessage() => 'danger'));
        }

        $this->redirect('#pre_apps');
    }

    /**
     * header('Location: ' . $url) doesn't work in directAdmin
     *
     * @param string $url
     */
    private function redirect($url = '')
    {
        echo '<script>window.location.replace("' . $this->homeUrl . $url . '");</script>';
    }

    /**
     * Return only values with allowed keys from $_POST
     *
     * @param $allowed
     * @return mixed
     */
    private function preparePost($allowed)
    {
        return array_intersect_key($_POST, array_flip($allowed));
    }

    /**
     * We can't use sessions in directAdmin
     *
     * @param $key
     * @param $value
     */
    private function setLocalStorage($key, $value)
    {
        echo '<script>if (typeof(Storage) !== "undefined") {
            localStorage.setItem("' . $key . '", JSON.stringify(' . json_encode($value) .'));
        }</script>';
    }

    public function validateYamlAjaxAction()
    {
        $model = new \Kuberdock\classes\models\App($this->panelName);
        $errors = $model->validate($_POST['template']);

        echo json_encode(array('errors' => $errors));
    }
}
