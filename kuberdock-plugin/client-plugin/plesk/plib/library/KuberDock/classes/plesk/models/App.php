<?php

namespace Kuberdock\classes\plesk\models;

use Kuberdock\classes\components\KuberDock_Api;
use Kuberdock\classes\Tools;

/**
 * Class App
 * @deprecated after admin/password are removed from plesk, use Kuberdock\classes\models\App
 * @package Kuberdock\classes\plesk\models
 */
class App
{
    private $api;

    const PANEL_NAME = 'plesk';

    public function __construct()
    {
        $kubeCliModel = new \Kuberdock\classes\plesk\models\KubeCli;
        $adminData = $kubeCliModel->read();
        $this->api = KuberDock_Api::create($adminData);
    }

    public function read($id)
    {
        return $this->api->getTemplate($id);
    }

    public function validate($template)
    {
        $this->api->validateTemplate($template);
    }

    public function save($post)
    {
        if ($post['id']) {
            $this->api->putTemplate($post['id'], $post['name'], $post['template']);
        } else {
            $this->api->postTemplate($post['name'], self::PANEL_NAME, $post['template']);
        }
    }

    public function delete($id)
    {
        $this->api->deleteTemplate($id);
    }

    public function getAll()
    {
        $templates = $this->api->getTemplates(self::PANEL_NAME);

        $updateActionPath = \pm_Context::getActionUrl('admin', 'application');
        $deleteActionPath = \pm_Context::getActionUrl('admin', 'delete');

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