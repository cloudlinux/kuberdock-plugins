<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Model;
use base\CL_Tools;
use base\models\CL_Invoice;

/**
 * Class KuberDock_Addon_PredefinedApp
 */
class KuberDock_Addon_PredefinedApp extends CL_Model {
    /**
     *
     */
    const VARIABLE_REGEXP = '/\%(?<variable>\w+)\%/';
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
    const KUBERDOCK_POD_FIELD = 'pod';
    /**
     *
     */
    const KUBERDOCK_USER_FIELD = 'user';
    /**
     *
     */
    const KUBERDOCK_REFERER_FIELD = 'referer';
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
     * @param string $status
     * @return array
     * @throws Exception
     */
    public function create($serviceId, $status = null)
    {
        $api = KuberDock_Hosting::model()->loadById($serviceId)->getApi();
        $response = $api->createPodFromYaml($this->data);

        if($status) {
            $api->updatePod($response->getData()['id'], array(
                'status' => $status,
            ));
        }

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
     * @param string $podId
     * @param int $serviceId
     * @throws Exception
     */
    public function payAndStart($podId, $serviceId)
    {
        $api = KuberDock_Hosting::model()->loadById($serviceId)->getApi();
        $api->updatePod($podId, array(
            'status' => 'stopped',
        ));
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
        $pod= $this->getPod();

        if($pod) {
            return $this->getTotalPricePod($pod);
        } else {
            return $this->getTotalPriceYAML(Spyc::YAMLLoadString($this->data));
        }

    }

    /**
     * @return string
     */
    public function getName()
    {
        if($pod = $this->getPod()) {
            return $pod->name;
        } else {
            $yaml = Spyc::YAMLLoadString($this->data);
            return isset($yaml['metadata']['name']) ? $yaml['metadata']['name'] : 'Undefined';
        }
    }

    /**
     * @return string
     */
    public function getPodId()
    {
        $pod = $this->getPod();

        return $pod ? $pod->id : '';
    }

    /**
     * @return mixed|null
     */
    public function getPod()
    {
        $data = json_decode($this->data);

        if($data) {
            return $data;
        }

        return null;
    }

    /**
     * @param int $serviceId
     * @param array $pod
     * @return string
     */
    public function getPodLink($serviceId, $pod)
    {
        $service = KuberDock_Hosting::model()->loadById($serviceId);
        $variables = $this->getVariables($pod);

        return $service->getLoginByTokenLink(false) . '&postDescription=' . $this->getPostDescription($variables)
            . '&next=#pods/' . $pod['id'];

    }

    /**
     * @return string
     */
    public function getPostDescription()
    {
        $data = Spyc::YAMLLoadString($this->data);
        $postDescription = isset($data['kuberdock'][self::KUBERDOCK_YAML_POST_DESCRIPTION_FIELD]) ?
            $data['kuberdock'][self::KUBERDOCK_YAML_POST_DESCRIPTION_FIELD] : '';

        return $postDescription;
    }

    /**
     * @param array $pod
     * @return array
     */
    private function getVariables($pod)
    {
        $publicIp = isset($pod['public_ip']) ? $pod['public_ip'] : '{Public IP address not set}';
        $variables['%PUBLIC_ADDRESS%'] = $publicIp;

        return $variables;
    }

    /**
     * @param $data
     * @return int
     * @throws Exception
     */
    private function getTotalPriceYAML($data)
    {
        $total = 0;
        $product = KuberDock_Product::model()->loadById($this->product_id) ;
        $kubes = CL_Tools::getKeyAsField($product->getKubes(), 'kuber_kube_id');
        $kubeType = isset($data['kuberdock']['kube_type']) ? $data['kuberdock']['kube_type'] : 0;
        $kubePrice = isset($kubes[$kubeType]) ? $kubes[$kubeType]['kube_price'] : 0;
        $publicIp = 0;

        if(isset($data['spec']['template']['spec'])) {
            $spec = $data['spec']['template']['spec'];
        } elseif(isset($data['spec'])) {
            $spec = $data['spec'];
        }

        $containers = $spec['containers'] ? $spec['containers'] : $spec;
        foreach($containers as $row) {
            if(isset($row['kubes'])) {
                $total += $row['kubes'] * $kubePrice;
            }

            if(isset($row['ports'])) {
                foreach($row['ports'] as $port) {
                    if(isset($port['isPublic']) && $port['isPublic']) {
                        $publicIp = 1;
                    }
                }
            }
        }

        $total += $publicIp * (float) $product->getConfigOption('priceIP');

        if(isset($spec['volumes'])) {
            foreach($spec['volumes'] as $row) {
                if(isset($row['persistentDisk']['pdSize'])) {
                    $total += $row['persistentDisk']['pdSize'] * (float) $product->getConfigOption('pricePersistentStorage');
                }
            }
        }

        return $total;
    }

    /**
     * @param $data
     * @return int
     * @throws Exception
     */
    private function getTotalPricePod($data)
    {
        $total = 0;
        $product = KuberDock_Product::model()->loadById($this->product_id) ;
        $kubes = CL_Tools::getKeyAsField($product->getKubes(), 'kuber_kube_id');
        $kubeType = isset($data->kube_type) ? $data->kube_type : 0;
        $kubePrice = isset($kubes[$kubeType]) ? $kubes[$kubeType]['kube_price'] : 0;
        $publicIp = 0;

        foreach($data->containers as $row) {
            if(isset($row->kubes)) {
                $total += $row->kubes * $kubePrice;
            }

            if(isset($row->ports)) {
                foreach($row->ports as $port) {
                    if(isset($port->isPublic) && $port->isPublic) {
                        $publicIp = 1;
                    }
                }
            }
        }

        $total += $publicIp * (float) $product->getConfigOption('priceIP');

        if(isset($data->volumes)) {
            foreach($data->volumes as $row) {
                if(isset($row->persistentDisk->pdSize)) {
                    $total += $row->persistentDisk->pdSize * (float) $product->getConfigOption('pricePersistentStorage');
                }
            }
        }

        return $total;
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