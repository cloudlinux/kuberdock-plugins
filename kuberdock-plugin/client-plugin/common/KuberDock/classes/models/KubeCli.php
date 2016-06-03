<?php

namespace Kuberdock\classes\models;

class KubeCli
{
    private $fileManager;
    private $rootPath;

    public function __construct($panelName)
    {
        $filemanagerClass = 'Kuberdock\classes\panels\fileManager\\' . $panelName . '_FileManager';
        $this->fileManager = new $filemanagerClass;

        switch ($panelName) {
            case 'DirectAdmin':
                $this->rootPath = '/home/admin/.kubecli.conf';
                break;
            default:
                $this->rootPath = '/root/.kubecli.conf';
        }
    }

    public function read()
    {
        $contentRoot = $this->readFile($this->rootPath);

        return array(
            'url' => $this->getKey($contentRoot, 'url'),
            'registry' => $this->getKey($contentRoot, 'registry'),
            'user' => $this->getKey($contentRoot, 'user'),
            'password' => $this->getKey($contentRoot, 'password'),
            'token' => $this->getKey($contentRoot, 'token'),
        );
    }

    public function save($data)
    {
        $view = new \Kuberdock\classes\KuberDock_View();
        $api = \Kuberdock\classes\components\KuberDock_Api::create($data);
        $api->setToken('');

        $data['token'] = $api->requestToken();

        $renderRoot = $view->renderPartial('admin/template_root', $data, false);
        $this->saveFile($this->rootPath, $renderRoot, 0600);
    }

    public function getRootPath()
    {
        return $this->rootPath;
    }

    private function getKey($content, $key)
    {
        if (!$content) {
            return '';
        }

        preg_match('/' . $key . ' = ([\w\d:\/\.\|]+)/i', $content, $matches);

        if ($matches) {
            return $matches[1];
        }

        return '';
    }

    private function readFile($path)
    {
        set_error_handler(array($this, "warningHandler"), E_WARNING);
        try{
            $content = $this->fileManager->getFileContent($path);
        } catch (\Exception $e) {
            return null;
        }
        restore_error_handler();

        return $content;
    }

    private function saveFile($path, $content, $mode)
    {
        $this->fileManager->putFileContent($path, $content);
        $this->fileManager->chmod($path, $mode);
    }

    public function warningHandler($errno, $errstr)
    {
        // do nothing
    }
}