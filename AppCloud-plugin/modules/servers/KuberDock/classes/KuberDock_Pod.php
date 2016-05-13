<?php

use components\KuberDock_InvoiceItem;

class KuberDock_Pod {
    /**
     *
     */
    const UPDATE_KUBES_DESCRIPTION = 'Update resources';

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
        $this->kubes = \KuberDock_Addon_Kube_Link::loadByProductId($service->packageid);
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
     * @return array
     */
    public function getPod()
    {
        return $this->_values;
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
     * @param array $newPod
     * @param KuberDock_User $user
     * @return \base\models\CL_Invoice
     * @throws Exception
     */
    public function updateKubes($newPod, KuberDock_User $user)
    {
        $newKubes = 0;
        $params = array();
        $attributes['id'] = $this->id;

        foreach($newPod['containers'] as $newContainer) {
            $c = $this->getContainerByName($newContainer['name']);
            if($c) {
                $params[] = array(
                    'name' => $newContainer['name'],
                    'kubes' => $newContainer['kubes'],
                );
                $newKubes += $newContainer['kubes'] - $c['kubes'];
            }
        }

        if($newKubes == 0 || !$this->product->isFixedPrice()) {
            $invoice = new \base\models\CL_Invoice();
            return $invoice->setAttributes(array(
                'invoice_id' => 0,
                'status' => $invoice::STATUS_PAID,
            ));
        }
        $attributes['containers'] = $params;

        $data = KuberDock_Addon_Items::model()->loadByAttributes(array('pod_id' => $this->id));
        if($data) {
            $item = KuberDock_Addon_Items::model()->loadByParams(current($data));
            $billableItem = \base\models\CL_BillableItems::model()->loadById($item->billable_item_id);

            if($newKubes > 0) {
                $items = array();
                $price = $this->kube['kube_price'] * $billableItem->getProRate();
                foreach ($newPod['containers'] as $newContainer) {
                    $c = $this->getContainerByName($newContainer['name']);
                    $delta = $newContainer['kubes'] - $c['kubes'];
                    if ($c && $delta!=0) {
                        $description = self::UPDATE_KUBES_DESCRIPTION
                            . ' (Pod ' . $this->name . ', container ' . $newContainer['name'] . ', '
                            . ($delta > 0 ? 'added ' : 'removed ') . abs($delta) . (abs($delta) == 1 ? ' kube' : ' kubes')
                            . ')';

                        $items[] = KuberDock_InvoiceItem::create($description, $price, 'kube', $delta);
                    }
                }

                $invoice = \base\models\CL_Invoice::model()->createInvoice($user->id, $items, $user->getGateway(), false);
                $invoice = \base\models\CL_Invoice::model()->loadById($invoice);
                $invoiceItem = \base\models\CL_InvoiceItems::model()->loadByParams($invoice->invoiceitems);
                $invoiceItem->setAttributes(array(
                    'type' => $billableItem::TYPE,
                    'relid' => $billableItem->id,
                    'notes' => json_encode($attributes),
                ))->save();

                try {
                    $invoice->applyCredit($invoice->id, $invoice->subtotal);
                } catch (Exception $e) {
                    if ($e->getMessage() == 'Amount exceeds customer credit balance') {
                        return $invoice;
                    } else {
                        throw $e;
                    }
                }

                return \base\models\CL_Invoice::model()->loadById($invoice->id);
            } else {
                $service = KuberDock_Hosting::model()->loadById($item->service_id);
                $service->getAdminApi()->redeployPod($this->id, $attributes);
                $billableItem->amount -= abs($newKubes * $this->kube['kube_price']);
                $billableItem->save();
                // Update app
                $pod = $service->getApi()->getPod($this->id);
                $app = KuberDock_Addon_PredefinedApp::model()->loadById($item->app_id);
                $app->data = json_encode($pod);
                $app->save();

                return \base\models\CL_Invoice::model()->loadById($item->invoice_id);
            }
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

    /**
     * @param array $data
     * @return $this
     */
    public function loadByParams($data)
    {
        $this->_values = $data;
        $this->kube = \base\CL_Tools::getKeyAsField($this->kubes, 'kuber_kube_id')[$this->kube_type];

        return $this;
    }
}