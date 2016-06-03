<?php

namespace Kuberdock\classes\models;

use Kuberdock\classes\components\KuberDock_Api;
use Kuberdock\classes\Tools;

class App
{
    private $api;

    private $panelName;

    public function __construct($panelName)
    {
        $kubeCliModel = new \Kuberdock\classes\models\KubeCli($panelName);
        $adminData = $kubeCliModel->read();
        $this->api = KuberDock_Api::create($adminData);
        $this->panelName = strtolower($panelName);
    }

    public function read($id)
    {
        return $this->api->getTemplate($id);
    }

    public function save($post)
    {
        if ($post['id']) {
            $this->api->putTemplate($post['id'], $post['name'], $post['template']);
        } else {
            $this->api->postTemplate($post['name'], $this->panelName, $post['template']);
        }
    }

    public function delete($id)
    {
        $this->api->deleteTemplate($id);
    }

    public function getAll()
    {
        $templates = $this->api->getTemplates($this->panelName);

        // todo: repair paths
//        $updateActionPath = \pm_Context::getActionUrl('admin', 'application');
//        $deleteActionPath = \pm_Context::getActionUrl('admin', 'delete');
        $updateActionPath = '';
        $deleteActionPath = '';

        $data = array();
        $index = 1;
        foreach ($templates as $template) {
            $updateButton = '<a href="' . $updateActionPath . '?id=' . $template['id'] . '" class="btn">Update</a>';
            $deleteButton = '<a href="' . $deleteActionPath
                . '" data-id="'  . $template['id']
                . '" data-name="'  . $template['name']
                . '" class="btn btn_delete">Delete</a>';

            $data[] = array(
                'id' => $index,
                'name' => $template['name'],
                'actions' => $updateButton . $deleteButton,
            );
            $index++;
        }

        return $data;
    }
}