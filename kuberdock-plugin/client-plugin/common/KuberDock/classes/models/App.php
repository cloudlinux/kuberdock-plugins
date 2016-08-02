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
        $this->panelName = $panelName;
    }

    public function read($id)
    {
        return $this->api->getTemplate($id);
    }

    public function validate($template)
    {
        $errors = 0;
        try{
            $this->api->validateTemplate($template);
        } catch (\Kuberdock\classes\exceptions\YamlValidationException $e) {
            $errors = $e->getMessage();
        }

        return $errors;
    }

    public function save($post)
    {
        if ($post['id']) {
            $this->api->putTemplate($post['id'], $post['name'], $post['template']);
        } else {
            $this->api->postTemplate($post['name'], strtolower($this->panelName), $post['template']);
        }
    }

    public function delete($id)
    {
        $this->api->deleteTemplate($id);
    }

    /**
     * List of predefined apps to display in admin panels
     *
     * @return array
     * @throws \Kuberdock\classes\exceptions\CException
     */
    public function getAll()
    {
        $templates = $this->api->getTemplates(strtolower($this->panelName));

        $panel = '\Kuberdock\classes\panels\KuberDock_' . $this->panelName;

        $data = array();
        $index = 0;
        foreach ($templates as $template) {
            $actions = $panel::getAdminUpdateAppButton($template) . ' ' . $panel::getAdminDeleteAppButton($template);
            $data[] = array(
                'index' => ++$index,
                'id' => $template['id'],
                'name' => $template['name'],
                'actions' => $actions,
            );
        }

        return $data;
    }
}