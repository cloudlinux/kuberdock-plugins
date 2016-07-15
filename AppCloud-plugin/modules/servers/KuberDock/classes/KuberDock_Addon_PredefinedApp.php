<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Model;
use base\CL_Tools;
use base\models\CL_Invoice;
use \components\KuberDock_InvoiceItem;

/**
 * Class KuberDock_Addon_PredefinedApp
 */
class KuberDock_Addon_PredefinedApp extends CL_Model
{
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
     * @param string $podId | LAST
     * @return $this
     */
    public function loadBySessionId($podId = '')
    {
        if($podId) {
            if($podId == 'LAST') {
                $data = $this->loadByAttributes(array(
                    'session_id' => \base\CL_Base::model()->getSession(),
                ), '', array(
                    'order' => 'id DESC',
                    'limit' => 1,
                ));
            } else {
                $data = $this->loadByAttributes(array(
                    'session_id' => \base\CL_Base::model()->getSession(),
                    'pod_id' => $podId,
                ));
            }
        } else {
            $data = $this->loadByAttributes(array(
                'session_id' => \base\CL_Base::model()->getSession(),
            ), 'pod_id IS NULL', array(
                'order' => 'id DESC',
                'limit' => 1,
            ));
        }

        if(!$data) {
            return false;
        }

        return $this->setAttributes(current($data));
    }

    /**
     * @param string $podId
     * @return $this
     */
    public function loadByPodId($podId)
    {
        $data = $this->loadByAttributes(array(
            'pod_id' => $podId,
        ), '', array(
            'order' => 'id DESC',
            'limit' => 1,
        ));

        if(!$data) {
            return false;
        }

        return $this->setAttributes(current($data));
    }

    /**
     * @param int $userId
     * @return $this|bool
     */
    public function loadByUserId($userId)
    {
        $data = $this->loadByAttributes(array(
            'user_id' => $userId,
        ), 'pod_id IS NULL', array(
            'order' => 'id DESC',
            'limit' => 1,
        ));

        if(!$data) {
            return false;
        }

        return $this->setAttributes(current($data));
    }

    /**
     * Order predefined application.
     * Runs from hooks (KuberDock_AfterModuleCreate & KuberDock_ShoppingCartValidateCheckout)
     * @param KuberDock_Product $product
     * @param KuberDock_Hosting|null $service
     * @param int $userId
     * @param bool $paid
     */
    public function order(KuberDock_Product $product, $service, $userId, $paid = false)
    {
        self::loadBySessionId();
        $this->user_id = $userId;
        $this->save();

        if ($product->isFixedPrice()) {
            $this->orderFixed($product, $service, $paid);
        } else {
            $this->orderPAYG($product, $service);
        }
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
            $adminApi = KuberDock_Hosting::model()->loadById($serviceId)->getAdminApi();

            $data = $response->getData();
            $adminApi->updatePod($data['id'], array(
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
        $service = KuberDock_Hosting::model()->loadById($serviceId);
        $api = $service->getApi();
        $adminApi = $service->getAdminApi();
        $adminApi->updatePod($podId, array(
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
     * @param bool $total
     * @return array
     */
    public function getTotalPrice($total = false)
    {
        $pod= $this->getPod();

        if ($pod) {
            $items = $this->getTotalPricePod($pod);
        } else {
            $items = $this->getTotalPriceYAML(Spyc::YAMLLoadString($this->data));
        }

        if ($total) {
            return array_reduce($items, function ($carry, $item) {
                $carry += $item->getTotal();
                return $carry;
            });
        } else {
            return $items;
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

        return $service->getLoginByTokenLink() . '&postDescription=' . $this->getPostDescription($variables)
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
     * @return mixed
     */
    public function getKubeType()
    {
        if ($pod = $this->getPod()) {
            return $pod->kube_type;
        } else {
            $data = Spyc::YAMLLoadString($this->data);
            return $data['kuberdock']['kube_type'];
        }
    }

    /**
     * @return string
     */
    public function getAppPackageName()
    {
        if ($this->getPod())  return '';

        $data = Spyc::YAMLLoadString($this->data);
        return isset($data['kuberdock']['appPackage']['name'])
            ? $data['kuberdock']['appPackage']['name']: 'Undefined';
    }

    /**
     * @return string
     */
    public function getAppPackageGoodFor()
    {
        if ($this->getPod())  return '';

        $data = Spyc::YAMLLoadString($this->data);
        return isset($data['kuberdock']['appPackage']['goodFor'])
            ? $data['kuberdock']['appPackage']['goodFor']: 'Undefined';
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
        $items = array();
        $product = KuberDock_Product::model()->loadById($this->product_id) ;

        $kubeType = isset($data['kuberdock']['kube_type']) ? $data['kuberdock']['kube_type'] : 0;

        $kubes = CL_Tools::getKeyAsField($product->getKubes(), 'kuber_kube_id');
        $kubePrice = isset($kubes[$kubeType]) ? $kubes[$kubeType]['kube_price'] : 0;

        if(isset($data['spec']['template']['spec'])) {
            $spec = $data['spec']['template']['spec'];
        } else {
            $spec = $data['spec'];
        }

        $containers = $spec['containers'] ? $spec['containers'] : $spec;
        foreach($containers as $row) {
            if(isset($row['kubes'])) {
                $items[] = KuberDock_InvoiceItem::create('Pod: ' . $row['name'], $kubePrice, 'pod', $row['kubes']);
            }

            if(isset($row['ports'])) {
                foreach($row['ports'] as $port) {
                    if(isset($port['isPublic']) && $port['isPublic']) {
                        $ipPrice = (float) $product->getConfigOption('priceIP');
                        $items[] = KuberDock_InvoiceItem::create('IP: ' . $data->public_ip, $ipPrice, 'IP');
                    }
                }
            }
        }

        if(isset($spec['volumes'])) {
            foreach($spec['volumes'] as $row) {
                if(isset($row['persistentDisk']['pdSize'])) {
                    $unit = \components\KuberDock_Units::getPSUnits();
                    $psPrice = (float)$product->getConfigOption('pricePersistentStorage');
                    $title = 'Storage: ' . $row['persistentDisk']['pdName'];
                    $items[] = KuberDock_InvoiceItem::create($title, $psPrice, $unit, $row['persistentDisk']['pdSize']);
                }
            }
        }

        return $items;
    }

    /**
     * @param $data
     * @return int
     * @throws Exception
     */
    private function getTotalPricePod($data)
    {
        $items = array();
        $product = KuberDock_Product::model()->loadById($this->product_id) ;
        $kubes = CL_Tools::getKeyAsField($product->getKubes(), 'kuber_kube_id');
        $kubeType = isset($data->kube_type) ? $data->kube_type : 0;
        $kubePrice = isset($kubes[$kubeType]) ? $kubes[$kubeType]['kube_price'] : 0;

        $ips = array();
        foreach($data->containers as $row) {
            if(isset($row->kubes)) {
                $description = 'Pod: ' . $data->name . ' (' . $row->image . ')';
                $items[] = KuberDock_InvoiceItem::create($description, $kubePrice, 'pod', $row->kubes);
            }

            if(isset($row->ports)) {
                foreach($row->ports as $port) {
                    if(isset($port->isPublic) && $port->isPublic) {
                        $ips[$data->public_ip] = true;
                    }
                }
            }
        }

        $ipPrice = (float) $product->getConfigOption('priceIP');
        foreach ($ips as $ip => $true) {
            $items[] = KuberDock_InvoiceItem::create('IP: ' . $ip, $ipPrice, 'IP');
        }

        if(isset($data->volumes)) {
            foreach($data->volumes as $row) {
                if(isset($row->persistentDisk->pdSize)) {
                    $psPrice = (float)$product->getConfigOption('pricePersistentStorage');
                    $unit = \components\KuberDock_Units::getPSUnits();
                    $title = 'Storage: ' . $row->persistentDisk->pdName;
                    $items[] = KuberDock_InvoiceItem::create($title, $psPrice, $unit, $row->persistentDisk->pdSize);
                }
            }
        }

        return $items;
    }

    /**
     * Order PA with fixed price billing
     * @param KuberDock_Product $product
     * @param KuberDock_Hosting | null $service
     * @param bool $paid
     */
    private function orderFixed(KuberDock_Product $product, $service, $paid)
    {
        if (!$service) {
            if ($product->isSetupPayment()) {
                return;
            }
            $service = $product->orderService($this->user_id);
        }

        // Trying to re-create module
        if ($service->domainstatus == KuberDock_User::STATUS_PENDING) {
            $service->createModule();
        }

        if (!$service->isActive() || !$this->data) {
            return;
        }

        try {
            if ($this->isPodExists($service->id)) {
                throw new Exception("Pod with name '" . $this->getName() . "' already exists");
            }
            $pod = $this->create($service->id, 'unpaid');
        } catch (Exception $e) {
            $product->jsRedirect($this->referer . '&error=' . urlencode($e->getMessage()));
        }

        $item = $product->addBillableApp($this->user_id, $this, $paid);
        $item->pod_id = $pod['id'];
        $item->save();
        $this->referer = null;
        $this->pod_id = $pod['id'];
        $this->save();

        $product->removeFromCart();

        if ($item->isPayed()) {
            $product->startPodAndRedirect($item->service_id, $item->pod_id, true);
        } else {
            $product->jsRedirect('viewinvoice.php?id=' . $item->invoice_id);
        }
    }

    /**
     * Order PA with PAYG billing
     * @param KuberDock_Product $product
     * @param KuberDock_Hosting | null $service
     */
    private function orderPAYG(KuberDock_Product $product, $service)
    {
        if (!$service) {
            $service = $product->orderService($this->user_id);
        }
        // Trying to re-create module
        if ($service->domainstatus == KuberDock_User::STATUS_PENDING) {
            $service->createModule();
        }

        $product->removeFromCart();

        $service = \KuberDock_Hosting::model()->loadById($service->id);

        if (!$service->isActive() || !$this->data) {
            return;
        }

        try {
            if ($this->isPodExists($service->id)) {
                throw new Exception("Pod with name '" . $this->getName() . "' already exists");
            }

            $pod = $this->create($service->id);
            $this->pod_id = $pod['id'];
            $this->referer = null;
            $this->save();
            $product->startPodAndRedirect($service->id, $pod['id'], true);
        } catch (Exception $e) {
            $product->jsRedirect($this->referer . '&error=' . urlencode($e->getMessage()));
        }
    }
} 
