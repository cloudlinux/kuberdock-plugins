<?php

use base\CL_Model;
use base\CL_Tools;
use exceptions\ExistException;
use exceptions\CException;

class KuberDock_Addon_Kube_Template extends CL_Model
{
    /**
     * Can be deleted by user
     */
    const STANDARD_TYPE = 0;

    /**
     * Cannot be deleted by user
     */
    const NON_STANDARD_TYPE = 1;

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'KuberDock_kubes_templates';
    }

    /**
     * @return bool
     * @throws CException
     */
    public function createKube() {
        $api = $this->getApi();
        $kubeName = $this->getKubeName();
        $addonProduct = KuberDock_Addon_Product::model()->loadById($this->product_id);

        if($addonProduct) {
            $this->setAttributes($addonProduct->getAttributes());
        }

        $attributes = array(
            'name' => $kubeName,
            'cpu' => $this->cpu_limit,
            'cpu_units' => 'Cores',
            'disk_space' => $this->hdd_limit,
            'memory' => $this->memory_limit,
            'memory_units' => 'MB',
            'included_traffic' => 0, // AC-3783: (int) $this->traffic_limit
        );

        try {
            $response = $api->createKube($attributes);
            $data = $response->getData();
            $this->kuber_kube_id = $data['id'];
        } catch(ExistException $e) {
            $kube = $api->getKubesByName($kubeName);
            $existingKube = $this->loadByAttributes(array(
                'server_id' => $this->server_id,
                'kuber_kube_id' => $kube['id'],
            ), 'product_id IS NULL AND kuber_product_id IS NULL');

            if($existingKube) {
                throw new CException(sprintf('Kube "%s" already exists', $this->kube_name));
            }

            if(!$kube) {
                throw new CException(sprintf('Kube "%s" not found in KuberDock', $this->kube_name));
            }

            $this->kube_name = $kube['name'];
            $this->kuber_kube_id = $kube['id'];
        }

        return true;
    }

    /**
     *
     */
    public function deleteKube()
    {
        $this->getApi()->deleteKube($this->kuber_kube_id);
    }

    public static function getServerDefault()
    {
        $serverKubes = self::model()->getServerKubes();
        $defaultKube = array_filter($serverKubes, function ($item) {
            return $item['is_default'];
        });

        return current($defaultKube);
    }

    public static function getDefaultTemplate()
    {
        $serverDefault = self::getServerDefault();

        $template = self::model()->loadByAttributes(array(
            'kuber_kube_id' => $serverDefault['id'],
        ));

        return current($template);
    }

    /**
     * @return string
     */
    public function getKubeName()
    {
        return JTransliteration::transliterate($this->kube_name);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getServerKubes()
    {
        $kubes = $this->getApi()->getKubes()->getData();

        return CL_Tools::getKeyAsField($kubes, 'id');
    }

    /**
     *
     */
    public function beforeSave()
    {
        $this->kube_name = JTransliteration::transliterate($this->kube_name);
        $this->cpu_limit = number_format($this->cpu_limit, 2, '.', '');

        return $this->createKube();
    }

    /**
     *
     */
    public function beforeDelete()
    {
        $this->deleteKube();

        return true;
    }

    /**
     * @return bool
     */
    public function isStandart()
    {
        return $this->kube_type == self::STANDARD_TYPE;
    }

    /**
     * @return \api\KuberDock_Api
     * @throws CException
     * @throws Exception
     */
    private function getApi()
    {
        if($this->server_id) {
            $server = KuberDock_Server::model()->loadById($this->server_id);
            if(!$server) {
                throw new CException('Cannot get KuberDock server');
            }
            return $server->getApi();
        }

        return KuberDock_Server::model()->getActive()->getApi();
    }
} 