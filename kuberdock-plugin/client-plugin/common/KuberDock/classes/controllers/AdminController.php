<?php

namespace Kuberdock\classes\controllers;

use Kuberdock\classes\KuberDock_Controller;
use Kuberdock\classes\Base;


class AdminController extends KuberDock_Controller
{
    public $panelName;

    public function init()
    {
        $this->assets = Base::model()->getStaticPanel()->getAssets();
        $this->panelName = Base::model()->getPanelType();
        $this->assets->registerScripts(array(
            'script/lib/jquery.min',
            'script/lib/bootstrap.min',
            'script/' . strtolower($this->panelName) . '/admin',
        ));

        $this->assets->registerStyles(array(
            'css/bootstrap.min',
            'css/admin',
            'css/' . strtolower($this->panelName) . '/admin',
        ));
    }

    public function indexAction()
    {
        $kubeCliModel = new \Kuberdock\classes\models\KubeCli($this->panelName);

        if (isset($_POST['tab']) && $_POST['tab']=='kubecli') {
            $kubeCli = array_intersect_key($_POST, array_flip(array(
                'url',
                'user',
                'password',
                'registry',
            )));

            try{
                $kubeCliModel->save($kubeCli);
            } catch (\Exception $e){
                $messages = array($e->getMessage() => 'danger');
            }
        }

        $kubeCli = $kubeCliModel->read();

        if (!$kubeCli['user'] || !$kubeCli['password'] || !$kubeCli['url']) {
            $msg = 'Cannot connect to KuberDock server, invalid credentials or server url in /root/.kubecli.conf';
            $this->render('index', array(
                'kubeCli' => $kubeCli,
                'messages' => array($msg => 'danger'),
                'error' => true,
            ));
            die;
        }

        $appModel = new \Kuberdock\classes\models\App($this->panelName);
        $apps = $appModel->getAll();

        // todo: replace with real data
        $packageKubes = '{}';
        $defaults = '{}';

        $this->render('index', array(
            'kubeCli' => $kubeCli,
            'messages' => isset($messages) ? $messages : array(),
            'defaults' => $defaults,
            'packagesKubes' => $packageKubes,
            'activeTab' => !isset($activeTab) ? 'pre_apps' : $activeTab,
        ));
    }
}
