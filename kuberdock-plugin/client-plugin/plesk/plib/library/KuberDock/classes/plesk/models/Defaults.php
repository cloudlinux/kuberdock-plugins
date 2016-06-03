<?php

namespace Kuberdock\classes\plesk\models;

use Kuberdock\classes\Tools;
use Kuberdock\classes\components\KuberDock_Api;

class Defaults
{
    private $api;

    public function __construct()
    {
        $kubeCliModel = new \Kuberdock\classes\plesk\models\KubeCli;
        $adminData = $kubeCliModel->read();
        $this->api = KuberDock_Api::create($adminData);
    }

    public function read()
    {
        $packages = $this->api->getPackages();
        $packages = Tools::getKeyAsField($packages, 'id');

        foreach ($packages as $id => &$package) {
            $kubes = $this->api->getPackageKubes($id);
            $package['kubes'] = array_values($kubes);
        }

        return array(
            'packagesKubes' => json_encode(array_values($packages)),
            'defaults' => json_encode($this->getDefaults($packages)),
        );
    }

    public function save($post)
    {
        $this->api->setDefaultPackage((int) $post['packageId']);
        $this->api->setDefaultKube((int) $post['kubeType']);
    }

    private function getDefaults($packagesKubes)
    {
        $defaultPackage = $this->getDefault($packagesKubes);
        $defaultKubeType = $this->getDefault($defaultPackage['kubes']);

        $defaults =  array(
            'packageId' => $defaultPackage['id'],
            'kubeType' =>  $defaultKubeType['id'],
        );

        return $defaults;
    }

    private function getDefault($items)
    {
        foreach ($items as $item) {
            if ($item['is_default']) {
                return $item;
            }
        }
    }
}