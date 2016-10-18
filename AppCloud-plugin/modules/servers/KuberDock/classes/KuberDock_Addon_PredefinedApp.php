<?php
/**
 * @project whmcs-plugin
 * @author: Ruslan Rakhmanberdiev
 */

use base\CL_Model;
use base\CL_Tools;

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
            ), 'pod_id IS NULL OR pod_id = ""', array(
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
     * @param int $invoiceId
     */
    public function order(KuberDock_Product $product, $service, $userId, $invoiceId = null)
    {
        $app = self::loadBySessionId();
        if (!$app) {
            return;
        }

        $app->user_id = $userId;
        $app->save();

        if (!$service) {
            return;
        }

        if ($product->isFixedPrice()) {
            $app->orderFixed($product, $service, $invoiceId);
        } else {
            $app->orderPAYG($product, $service);
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
            $this->pod_id = $data['id'];
            $this->save();
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
        try {
            $api->startPod($podId);
        } catch (Exception $e) {
            // pass
            \exceptions\CException::log($e);
        }
    }

    /**
     * @param int $serviceId
     * @return bool|array
     * @throws Exception
     */
    public function isPodExists($serviceId)
    {
        $yaml = CL_Tools::parseYaml($this->data);
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
        $pod = $this->getPod();

        if ($pod) {
            $items = $this->getTotalPricePod(CL_Tools::objectToArray($pod));
        } else {
            $items = $this->getTotalPriceYAML(CL_Tools::parseYaml($this->data));
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
            $yaml = CL_Tools::parseYaml($this->data);
            return isset($yaml['metadata']['name']) ? $yaml['metadata']['name'] : 'Undefined';
        }
    }

    /**
     * @return string
     */
    public function getPodId()
    {
        if ($this->pod_id) {
            return $this->pod_id;
        }

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
        $data = CL_Tools::parseYaml($this->data);
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
            $data = CL_Tools::parseYaml($this->data);
            return isset($data['kuberdock']['kube_type']) ? $data['kuberdock']['kube_type'] :
                (isset($data['kuberdock']['appPackage']['kubeType']) ? $data['kuberdock']['appPackage']['kubeType'] : 0);
        }
    }

    /**
     * @return string
     */
    public function getAppPackageName()
    {
        if ($this->getPod())  return '';

        $data = CL_Tools::parseYaml($this->data);
        return isset($data['kuberdock']['appPackage']['name'])
            ? $data['kuberdock']['appPackage']['name']: 'Undefined';
    }

    /**
     * @return string
     */
    public function getAppPackageGoodFor()
    {
        if ($this->getPod())  return '';

        $data = CL_Tools::parseYaml($this->data);
        return isset($data['kuberdock']['appPackage']['goodFor'])
            ? $data['kuberdock']['appPackage']['goodFor']: 'Undefined';
    }

    /**
     *
     */
    public function clear()
    {
        $this->_db->query('DELETE FROM KuberDock_preapps WHERE session_id = :session_id AND pod_id IS NULL', array(
            ':session_id' => \base\CL_Base::model()->getSession(),
        ));
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
     * @return array KuberDock_InvoiceItem[]
     * @throws Exception
     */
    private function getTotalPriceYAML($data)
    {
        $items = array();
        $product = KuberDock_Product::model()->loadById($this->product_id) ;

        $kubeType = isset($data['kuberdock']['kube_type']) ? $data['kuberdock']['kube_type'] :
            (isset($data['kuberdock']['appPackage']['kubeType']) ? $data['kuberdock']['appPackage']['kubeType'] : 0);

        $kubes = CL_Tools::getKeyAsField($product->getKubes(), 'kuber_kube_id');
        $kubePrice = isset($kubes[$kubeType]) ? $kubes[$kubeType]['kube_price'] : 0;
        $hasPublicIP = false;

        if (isset($data['spec']['template']['spec'])) {
            $spec = $data['spec']['template']['spec'];
        } else {
            $spec = $data['spec'];
        }

        $containers = $spec['containers'] ? $spec['containers'] : $spec;
        foreach ($containers as $row) {
            if (isset($row['kubes'])) {
                $items[] = $product->createInvoice('Pod: ' . $row['name'], $kubePrice, 'pod', $row['kubes']);
            }

            if (isset($row['ports']) && !isset($data['kuberdock']['appPackage']['baseDomain'])) {
                foreach ($row['ports'] as $port) {
                    if (isset($port['isPublic']) && $port['isPublic'] && !$hasPublicIP) {
                        $ipPrice = (float) $product->getConfigOption('priceIP');
                        $items[] = $product->createInvoice('Public IP', $ipPrice, 'IP');
                        $hasPublicIP = true;
                    }
                }
            }
        }

        if (isset($spec['volumes'])) {
            foreach ($spec['volumes'] as $row) {
                if (isset($row['persistentDisk']['pdSize'])) {
                    $unit = \components\KuberDock_Units::getPSUnits();
                    $psPrice = (float)$product->getConfigOption('pricePersistentStorage');
                    $title = 'Storage: ' . $row['persistentDisk']['pdName'];
                    $qty = $row['persistentDisk']['pdSize'];
                    $items[] = $product->createInvoice($title, $psPrice, $unit, $qty);
                }
            }
        }

        return $items;
    }

    /**
     * @param array[] $data
     * @return array KuberDock_InvoiceItem[]
     * @throws Exception
     */
    private function getTotalPricePod($data)
    {
        $items = array();
        $product = KuberDock_Product::model()->loadById($this->product_id) ;
        $kubes = CL_Tools::getKeyAsField($product->getKubes(), 'kuber_kube_id');
        $kubeType = isset($data['kube_type']) ? $data['kube_type'] : 0;
        $kubePrice = isset($kubes[$kubeType]) ? $kubes[$kubeType]['kube_price'] : 0;

        $hasPublicIP = false;

        foreach ($data['containers'] as $row) {
            if (isset($row['kubes'])) {
                $description = 'Pod: ' . $data['name'] . ' (' . $row['image'] . ')';
                $items[] = $product->createInvoice($description, $kubePrice, 'pod', $row['kubes']);
            }

            if (isset($row['ports']) && !isset($data['domain'])) {
                foreach ($row['ports'] as $port) {
                    if (isset($port['isPublic']) && $port['isPublic'] && !$hasPublicIP) {
                        $ipPrice = (float) $product->getConfigOption('priceIP');
                        $items[] = $product->createInvoice('Public IP', $ipPrice, 'IP');
                        $hasPublicIP = true;
                    }
                }
            }
        }

        if (isset($data['volumes'])) {
            foreach ($data['volumes'] as $row) {
                if (isset($row['persistentDisk']['pdSize'])) {
                    $psPrice = (float)$product->getConfigOption('pricePersistentStorage');
                    $unit = \components\KuberDock_Units::getPSUnits();
                    $title = 'Storage: ' . $row['persistentDisk']['pdName'];
                    $items[] = $product->createInvoice($title, $psPrice, $unit, $row['persistentDisk']['pdSize']);
                }
            }
        }

        return $items;
    }

    /**
     * Order PA with fixed price billing
     * @param KuberDock_Product $product
     * @param KuberDock_Hosting $service
     * @param int $invoiceId
     * @throws \exceptions\CException
     */
    private function orderFixed(KuberDock_Product $product, $service, $invoiceId = null)
    {
        if ($this->getTotalPrice(true) == 0) {
            throw new \exceptions\CException('You can\'t buy app with 0 price');
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

        $item = $product->addBillableApp($this->user_id, $this, $invoiceId);
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
     * @param KuberDock_Hosting $service
     */
    private function orderPAYG(KuberDock_Product $product, $service)
    {
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
