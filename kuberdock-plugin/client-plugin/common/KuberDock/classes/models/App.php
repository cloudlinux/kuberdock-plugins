<?php

namespace Kuberdock\classes\models;

use Kuberdock\classes\components\KuberDock_Api;

class App
{
    private $api;

    private $panelName;

    /**
     * @param string $panelName
     * @return $this
     */
    public function setPanel($panelName)
    {
        $kubeCliModel = new \Kuberdock\classes\models\KubeCli($panelName);
        $adminData = $kubeCliModel->read();
        $this->api = KuberDock_Api::create($adminData);
        $this->panelName = $panelName;

        return $this;
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

    public function uninstall($id)
    {
        $this->api->uninstallTemplate($id);
    }

    public function install($id)
    {
        $this->api->installTemplate($id);
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
            $actions = $panel::getAdminInstallAppButton($template) . ' ' .
                $panel::getAdminUpdateAppButton($template) . ' ' .
                $panel::getAdminDeleteAppButton($template);

            $data[] = [
                'index' => ++$index,
                'id' => $template['id'],
                'name' => $template['name'],
                'search_available' => $template['search_available'],
                'actions' => $actions,
            ];
        }

        return $data;
    }
}