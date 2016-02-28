<?php



class KuberDock_Pod {
    /**
     *
     */
    const UPDATE_KUBES_DESCRIPTION = 'Update kubes';

    /**
     * @var \api\KuberDock_Api
     */
    protected $api;

    /**
     * @var array
     */
    protected $kubes;

    /**
     * @var array
     */
    protected $kube;

    /**
     * @var KuberDock_Product
     */
    protected $product;

    /**
     * @var array
     */
    protected $_values = array();

    /**
     * @param \KuberDock_Hosting $service
     */
    public function __construct(\KuberDock_Hosting $service)
    {
        $this->api = $service->getApi();
        $this->kubes = \KuberDock_Addon_Kube::model()->loadByAttributes(array(
            'product_id' => $service->packageid,
        ));
        $this->product = KuberDock_Product::model()->loadById($service->packageid);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->_values[$name];
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->_values[$name] = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_values[$name]);
    }

    /**
     * @return array
     */
    public function getKube()
    {
        return $this->kube;
    }

    /**
     * @return \KuberDock_Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return number
     */
    public function totalPrice()
    {
        return $this->kubesPrice() + $this->pdPrice() + $this->ipPrice();
    }

    /**
     * @return number
     */
    public function kubesPrice()
    {
        $price = $this->kube['kube_price'];

        return array_sum(array_map(function($c) use ($price) {
            return $c['kubes'] * $price;
        }, $this->_values['containers']));

    }

    /**
     * @return number
     */
    public function pdPrice()
    {
        $price = $this->product->getConfigOption('pricePersistentStorage');

        return array_sum(array_map(function($v) use ($price) {
            if(isset($v['persistentDisk'])) {
                return $v['persistentDisk']['pdSize'] * $price;
            }
        }, $this->_values['volumes']));
    }

    /**
     * @return number
     * @throws Exception
     */
    public function ipPrice()
    {
        $price = $this->product->getConfigOption('priceIP');

        return isset($this->public_ip) ? (float) $price : 0;
    }

    /**
     * @param $name
     * @return array
     */
    public function getContainerByName($name)
    {
        foreach($this->containers as $c) {
            if($c['name'] == $name) return $c;
        }

        return array();
    }

    /**
     * @param $values
     * @return \api\KuberDock_ApiResponse|mixed|void
     * @throws Exception
     */
    public function updateKubes($values)
    {
        $newKubes = 0;
        $params = array();

        foreach($values as $row) {
            $c = $this->getContainerByName($row['name']);
            if($c && $c['kubes'] < $row['kubes']) {
                $params[] = $row;
                $newKubes += $row['kubes'] - $c['kubes'];
            }
        }

        if(!$newKubes) return;

        if($this->product->isFixedPrice()) {
            $data = KuberDock_Addon_Items::model()->loadByAttributes(array('pod_id' => $this->id));
            if($data) {
                $item = KuberDock_Addon_Items::model()->loadByParams(current($data));
                $billableItem = \base\models\CL_BillableItems::model()->loadById($item->billable_item_id);
                $totalPrice = $newKubes * $this->kube['kube_price'] * $billableItem->getProRate();
                $user = KuberDock_User::model()->getCurrent();
                $items[] = array(
                    'description' => self::UPDATE_KUBES_DESCRIPTION . ' (Add ' . $newKubes . ' kubes)',
                    'total' => $totalPrice,
                );
                $invoice = \base\models\CL_Invoice::model()->createInvoice($user->id, $items, $user->getGateway());
                $invoice = \base\models\CL_Invoice::model()->loadById($invoice);
                $invoiceItem = \base\models\CL_InvoiceItems::model()->loadByParams($invoice->invoiceitems);
                $params['id'] = $this->id;
                $invoiceItem->setAttributes(array(
                    'type' => $billableItem::TYPE,
                    'relid' => $billableItem->id,
                    'notes' => json_encode($params),
                ))->save();
                if($invoice->isPayed()) {
                    return $this->api->redeployPod($this->id, $params);
                } else {
                    return $invoice->id;
                }
            }
        } else {
            return $this->api->redeployPod($this->id, $params);
        }
    }

    /**
     * @param string $id
     * @return $this
     */
    public function loadById($id)
    {
        $this->_values = $this->api->getPod($id);
        $this->kube = \base\CL_Tools::getKeyAsField($this->kubes, 'kuber_kube_id')[$this->kube_type];

        return $this;
    }
}