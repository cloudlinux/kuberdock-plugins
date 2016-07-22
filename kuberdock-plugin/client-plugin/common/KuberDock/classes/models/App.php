<?php

namespace Kuberdock\classes\models;

use Kuberdock\classes\components\KuberDock_Api;

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

        $data = array();
        $index = 0;
        foreach ($templates as $template) {
            $data[] = array(
                'index' => ++$index,
                'id' => $template['id'],
                'name' => $template['name'],
                'actions' => $this->getActions($template),
            );
        }

        return $data;
    }

    private function getUpdateActionPath($template)
    {
        switch ($this->panelName) {
            case 'plesk':
                return \pm_Context::getActionUrl('admin', 'application') . '?id=' . $template['id'];
            case 'directadmin':
                return '?a=app&id=' . $template['id'];
        }
    }

    private function getDeleteActionPath($template)
    {
        switch ($this->panelName) {
            case 'plesk':
                return \pm_Context::getActionUrl('admin', 'delete') . '?id=' . $template['id'];
            case 'directadmin':
                return '?a=appDelete&id=' . $template['id'];
        }
    }

    private function getActions($template)
    {
        return '
            <a href="' . $this->getUpdateActionPath($template) . '">
                <button type="button" class="btn btn-primary btn-xs" title="Update">
                    <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>Update
                </button>
            </a> <a href="' . $this->getDeleteActionPath($template) . '" data-id="' . $template['id']
                    . '" data-name="' . $template['name'] . '">
                <button type="button" class="btn btn-danger btn-xs" title="Delete">
                    <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>Delete
                </button>
            </a>';
    }
}