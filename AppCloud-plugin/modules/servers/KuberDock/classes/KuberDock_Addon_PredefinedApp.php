<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

class KuberDock_Addon_PredefinedApp extends CL_Model {
    /**
     *
     */
    const KUBERDOCK_PRODUCT_ID_FIELD = 'pkgid';
    /**
     *
     */
    const KUBERDOCK_YAML_FIELD = 'yaml';
    /**
     *
     */
    const KUBERDOCK_YAML_POST_DESCRIPTION_FIELD = 'postDescription';

    /**
     *
     */
    public function setTableName()
    {
        $this->tableName = 'KuberDock_preapps';
    }

    /**
     * @return $this
     */
    public function loadBySessionId()
    {
        $data = $this->loadByAttributes(array(
            'session_id' => session_id(),
        ));

        if(!$data) {
            return false;
        }

        return $this->setAttributes(current($data));
    }

    /**
     * @param int $serviceId
     * @return array
     * @throws Exception
     */
    public function create($serviceId)
    {
        $api = KuberDock_Hosting::model()->loadById($serviceId)->getApi();
        $response = $api->createPodFromYaml($this->data);

        return $response->getData();
    }

    /**
     * @param string $podId
     * @param int $serviceId
     * @throws Exception
     */
    public function start($podId, $serviceId)
    {
        $api = KuberDock_Hosting::model()->loadById($serviceId)->getApi();
        $api->startPod($podId);
    }

    /**
     * @param int $serviceId
     * @return bool|array
     * @throws Exception
     */
    public function isPodExists($serviceId)
    {
        $yaml = Spyc::YAMLLoadString($this->data);
        $podName = isset($yaml['metadata']['name']) ? $yaml['metadata']['name'] : '';

        $api = KuberDock_Hosting::model()->loadById($serviceId)->getApi();
        $pods = $api->getPods();

        foreach($pods->getData() as $row) {
            if($row['name'] == $podName) {
                return $row;
            }
        }

        return false;
    }

    /**
     * @return float
     */
    public function getTotalPrice()
    {
        $data = Spyc::YAMLLoadString($this->data);
        $total = 0;
        $product = KuberDock_Product::model()->loadById($this->product_id) ;
        $kubes = CL_Tools::getKeyAsField($product->getKubes(), 'kuber_kube_id');
        $kubeType = isset($data['kuberdock']['kube_type']) ? $data['kuberdock']['kube_type'] : 0;
        $kubePrice = isset($kubes[$kubeType]) ? $kubes[$kubeType]['kube_price'] : 0;

        if(isset($data['spec']['template']['spec'])) {
            $spec = $data['spec']['template']['spec'];
        } elseif(isset($data['spec'])) {
            $spec = $data['spec'];
        }

        foreach($spec as $row) {
            if(is_array($row) && $row) {
                $kube = isset($row['kubes']) ? $row['kubes'] : 1;
                $total += $kube * $kubePrice;
            }
        }

        return $total;
    }

    /**
     * @param $serviceId
     * @param $podId
     * @return string
     */
    public function getPodLink($serviceId, $podId)
    {
        $service = KuberDock_Hosting::model()->loadById($serviceId);

        return $service->getLoginByTokenLink() . '&postDescription=' . $this->getPostDescription()
            . '&next=#pods/' . $podId;

    }

    /**
     * @return string
     */
    public function getPostDescription()
    {
        $data = Spyc::YAMLLoadString($this->data);

        return isset($data['kuberdock'][self::KUBERDOCK_YAML_POST_DESCRIPTION_FIELD]) ?
            $data['kuberdock'][self::KUBERDOCK_YAML_POST_DESCRIPTION_FIELD] : '';
    }

    /**
     * Class loader
     *
     * @param string $className
     * @return $this
     */
    public static function model($className = __CLASS__)
    {
        if(isset(self::$_models[$className])) {
            return self::$_models[$className];
        } else {
            self::$_models[$className] = new $className;
            return self::$_models[$className];
        }
    }
} 