<?php

namespace Kuberdock\classes\plesk\models;

/**
 * Class KubeCli
 * @deprecated after admin/password are removed from plesk, use Kuberdock\classes\models\KubeCli
 * @package Kuberdock\classes\plesk\models
 */
class KubeCli
{
    const KUBE_CLI_CONF_ROOT_FILE = '/root/.kubecli.conf';
    const KUBE_CLI_CONF_ETC_FILE = '/etc/kubecli.conf';

    const DEFAULT_REGISTRY = 'registry.hub.docker.com';

    public function read()
    {
        $contentRoot = $this->readFile(self::KUBE_CLI_CONF_ROOT_FILE);
        $contentEtc = $this->readFile(self::KUBE_CLI_CONF_ETC_FILE);

        return array(
            'url' => $this->getKey($contentEtc, 'url'),
            'registry' => $this->getKey($contentEtc, 'registry') ?: self::DEFAULT_REGISTRY,
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

        $renderEtc = $view->renderPartial('plesk/template_etc', $data, false);
        $this->saveFile(self::KUBE_CLI_CONF_ETC_FILE, $renderEtc, '644');

        $renderRoot = $view->renderPartial('plesk/template_root', $data, false);
        $this->saveFile(self::KUBE_CLI_CONF_ROOT_FILE, $renderRoot, '600');
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
        $fileManager = new \pm_ServerFileManager;

        try{
            return $fileManager->fileGetContents($path);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function saveFile($path, $content, $mode)
    {
        $fileManager = new \pm_ServerFileManager;

        $fileManager->filePutContents($path, $content);
        $fileManager->chmod($path, $mode);
    }
}